<?php
/**
 * Temporary fix: multi-ticket checkout blocked at payment.
 *
 * Buying two or more ticket types for one event fails at checkout. The 3.11.1
 * capacity hardening assumes one ticket type per order, so it misfires on
 * multi-type orders in three places:
 *
 *   1. A false "attendees exceed quantity" error blocks the order at the
 *      pre-payment validation step (sc_et_quantity is still 1 there).
 *   2. The selected tickets (the "cart") are only sent in the AJAX price call,
 *      never with the final checkout form — so the server-side cart/attendee
 *      parity gate sees attendees with no cart and rejects the transaction
 *      ("Unable to process your payment!").
 *   3. When ticket types have different prices, the expected amount is computed
 *      as general-price x qty and never matches the cart total, so the Stripe
 *      intent is rejected as invalid.
 *
 * Restores correct behaviour for multi-ticket events only. Single-ticket
 * checkout and the existing protections are untouched (every hook below
 * early-returns unless the event is a genuine multi-ticket event).
 *
 * Targets Event Ticketing 3.11.1 through 1.5.x (incl. builds with the
 * cart/attendee parity gate). Stop-gap while awaiting the official fix
 * (PR #600); remove after upgrading. Test in Stripe test mode first.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'scs_mtfix_is_multiple_tickets_event' ) ) {
	/**
	 * Resolve an event and report whether it is a multi-ticket event.
	 *
	 * Mirrors core's own detection (the sc_et_is_multiple_tickets filter), so it
	 * returns false — i.e. no-op — whenever the add-on is inactive or the event
	 * has a single ticket type.
	 *
	 * @param int $event_id The event ID.
	 * @return bool
	 */
	function scs_mtfix_is_multiple_tickets_event( $event_id ) {

		$event_id = absint( $event_id );

		if ( empty( $event_id ) || ! function_exists( 'sugar_calendar_get_event' ) ) {
			return false;
		}

		$event = sugar_calendar_get_event( $event_id );

		if ( empty( $event ) ) {
			return false;
		}

		return (bool) apply_filters( 'sc_et_is_multiple_tickets', false, $event, $event->object_id );
	}
}

/**
 * Bug 1 — clear the false "attendees exceed quantity" error for multi-ticket
 * events. Runs at priority 20, after the add-on's own validator (10), so the
 * error exists by the time we remove it. The guard is a single-ticket check
 * (sc_et_quantity defaults to 1) while attendees[] legitimately spans all
 * types; the add-on's per-type parity gate validates the real composition.
 */
add_action(
	'sc_et_checkout_validate_data',
	function ( $checkout ) {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$event_id = ! empty( $_POST['sc_et_event_id'] ) ? absint( $_POST['sc_et_event_id'] ) : 0;

		if ( ! scs_mtfix_is_multiple_tickets_event( $event_id ) ) {
			return;
		}

		$checkout->remove_error( 'attendees_exceed_quantity' );
	},
	20,
	1
);

/**
 * Bug 2 — submit the cart with the final checkout form.
 *
 * The cart lives only in the ticketing app's JS state and is sent in the AJAX
 * price call, but not in the final form POST. The server's cart/attendee parity
 * gate reads $_POST['cart'], so without this the submitted form carries
 * attendees but no cart and the transaction is rejected as a mismatch. We wrap
 * the form's native submit() (the add-on submits via form.submit(), which
 * bypasses jQuery handlers) to mirror the cart into hidden inputs first.
 */
add_action(
	'wp_footer',
	function () {

		if ( is_admin() ) {
			return;
		}
		?>
		<script>
		( function () {
			function patch() {
				var form = document.getElementById( 'sc-event-ticketing-checkout' );

				if ( ! form || form.scsMtfixPatched ) {
					return;
				}

				form.scsMtfixPatched = true;

				var nativeSubmit = form.submit.bind( form );

				form.submit = function () {
					try {
						var app = window.SugarCalendar && window.SugarCalendar.EventTicketingApp;

						if ( app && typeof app.getPreparedCartData === 'function' ) {
							var cart = app.getPreparedCartData() || [];

							// Drop any fields from a previous attempt to avoid duplicates.
							var stale = form.querySelectorAll( 'input.scs-mtfix-cart-field' );
							for ( var i = 0; i < stale.length; i++ ) {
								stale[ i ].parentNode.removeChild( stale[ i ] );
							}

							cart.forEach( function ( item, index ) {
								// Coerce to integers (matching the cart producer); the
								// server re-validates regardless.
								var fields = {
									ticket_type_id: parseInt( item.ticket_type_id, 10 ) || 0,
									qty: parseInt( item.qty, 10 ) || 0
								};

								Object.keys( fields ).forEach( function ( key ) {
									var input = document.createElement( 'input' );
									input.type = 'hidden';
									input.className = 'scs-mtfix-cart-field';
									input.name = 'cart[' + index + '][' + key + ']';
									input.value = fields[ key ];
									form.appendChild( input );
								} );
							} );
						}
					} catch ( e ) {}

					return nativeSubmit();
				};
			}

			if ( document.readyState === 'loading' ) {
				document.addEventListener( 'DOMContentLoaded', patch );
			} else {
				patch();
			}
		} )();
		</script>
		<?php
	},
	99
);

/**
 * Bug 3 (gate) — accept the Stripe intent for multi-ticket events.
 *
 * Core recomputes the expected amount as general-price x qty, which only matches
 * a multi-type cart when every type shares the general price. For mixed prices it
 * mismatches the (correct) intent amount and the order is rejected. We re-affirm
 * every other binding (succeeded / currency / event-id) and accept the intent,
 * trusting its server-set amount. $retrieve is false on a replay, so replays are
 * never accepted here.
 */
add_filter(
	'sc_et_stripe_is_valid_intent',
	function ( $is_valid, $retrieve, $order_data ) {

		if ( $is_valid || empty( $retrieve ) ) {
			return $is_valid;
		}

		$event_id = (int) ( $order_data['event_id'] ?? 0 );

		if ( ! scs_mtfix_is_multiple_tickets_event( $event_id ) ) {
			return $is_valid;
		}

		$expected_currency = strtolower( (string) ( $order_data['currency'] ?? '' ) );

		$ok = isset( $retrieve->status ) && 'succeeded' === $retrieve->status
			&& strtolower( (string) ( $retrieve->currency ?? '' ) ) === $expected_currency
			&& (int) ( $retrieve->metadata->event_id ?? 0 ) === $event_id;

		if ( ! $ok ) {
			return $is_valid;
		}

		// Stash the real charged amount (minor units) so we can record the
		// correct order total below.
		$GLOBALS['scs_mtfix_intent_amount_minor'] = (int) $retrieve->amount;

		return true;
	},
	10,
	3
);

/**
 * Bug 3 (record) — store the real charged total on the order.
 *
 * Core sets the order total from the same wrong general-price x qty recompute.
 * Overwrite it with the actual intent amount captured above so the recorded
 * order matches what the customer was charged.
 */
add_filter(
	'sc_et_checkout_complete_order_data_before_save',
	function ( $order_data, $event ) {

		if ( ! isset( $GLOBALS['scs_mtfix_intent_amount_minor'] ) ) {
			return $order_data;
		}

		$minor = (int) $GLOBALS['scs_mtfix_intent_amount_minor'];
		unset( $GLOBALS['scs_mtfix_intent_amount_minor'] );

		$zero_decimal_fn = '\\Sugar_Calendar\\AddOn\\Ticketing\\Common\\Functions\\is_zero_decimal_currency';
		$is_zero_decimal = function_exists( $zero_decimal_fn ) ? $zero_decimal_fn() : false;

		$amount = $is_zero_decimal ? $minor : ( $minor / 100 );

		$order_data['subtotal'] = $amount;
		$order_data['total']    = $amount;

		return $order_data;
	},
	10,
	2
);

<?php
/**
 * Ticket receipt: show Venue instead of legacy Location.
 *
 * The receipt shortcode uses the old event meta "location". Sugar Calendar
 * now uses Venues (event->venue_id). This snippet replaces the "Location"
 * row with "Venue" and displays the linked venue name (and address when
 * available) on both the order receipt and the single-ticket view.
 *
 * Requires Sugar Calendar with Venues (Pro). If Venues is not available or
 * the event has no venue, the row shows the old location value or empty.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get venue display string for an event (name and optionally formatted address).
 *
 * @param object $event Event from sugar_calendar_get_event().
 * @return string
 */
function sc_snippets_receipt_venue_display( $event ) {
	if ( empty( $event->venue_id ) || ! function_exists( 'sc_get_venue_data' ) ) {
		return '';
	}
	$venue_data = sc_get_venue_data( $event->venue_id );
	if ( empty( $venue_data ) ) {
		return get_the_title( $event->venue_id );
	}
	$name = isset( $venue_data['title'] ) ? $venue_data['title'] : get_the_title( $event->venue_id );
	if ( function_exists( 'sc_format_venue_address' ) ) {
		$address = sc_format_venue_address( $venue_data );
		if ( ! empty( $address ) ) {
			return $name . ' — ' . $address;
		}
	}
	return $name;
}

/**
 * Get event for receipt page (order_id + email) or ticket details page (order_id + ticket_code).
 *
 * @return object|null Event or null.
 */
function sc_snippets_receipt_get_event() {
	$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
	if ( ! $order_id ) {
		return null;
	}
	if ( ! function_exists( 'Sugar_Calendar\AddOn\Ticketing\Common\Functions\get_order' ) ) {
		return null;
	}
	$get_order = 'Sugar_Calendar\AddOn\Ticketing\Common\Functions\get_order';
	$order     = $get_order( $order_id );
	if ( empty( $order ) || empty( $order->event_id ) ) {
		return null;
	}
	if ( ! empty( $_GET['email'] ) ) {
		if ( sanitize_text_field( wp_unslash( $_GET['email'] ) ) !== $order->email ) {
			return null;
		}
	}
	if ( ! empty( $_GET['ticket_code'] ) ) {
		$ticket = \Sugar_Calendar\AddOn\Ticketing\Common\Functions\get_ticket_by_code( sanitize_text_field( wp_unslash( $_GET['ticket_code'] ) ) );
		if ( empty( $ticket ) || (int) $ticket->order_id !== (int) $order_id ) {
			return null;
		}
	}
	return sugar_calendar_get_event( $order->event_id );
}

/**
 * Replace Location row with Venue row in receipt/ticket shortcode output.
 */
add_filter( 'sc_event_tickets_ticket_shortcode_output', function ( $html ) {
	$event = sc_snippets_receipt_get_event();
	if ( empty( $event ) ) {
		return $html;
	}
	$venue_display = sc_snippets_receipt_venue_display( $event );
	if ( '' === $venue_display && ! empty( $event->id ) && function_exists( 'get_event_meta' ) ) {
		$venue_display = get_event_meta( $event->id, 'location', true );
	}
	$venue_display = esc_html( $venue_display );
	$label         = esc_html__( 'Venue', 'sugar-calendar-lite' );

	// Replace the two-row block: Location header row + value row.
	$pattern = '#<tr>\s*<th colspan="3">[^<]*Location[^<]*</th>\s*</tr>\s*<tr>\s*<td colspan="3">[^<]*</td>\s*</tr>#s';
	$replacement = '<tr><th colspan="3">' . $label . '</th></tr><tr><td colspan="3">' . $venue_display . '</td></tr>';
	$html = preg_replace( $pattern, $replacement, $html, 1 );

	return $html;
}, 10, 1 );

<?php
/**
 * Ticket receipt: show the Venue instead of the legacy Location.
 *
 * Core's receipt and ticket views print a "Location" row fed by the old `location`
 * event meta and have no venue awareness at all. This swaps that row for the venue
 * name and address when the event has a venue.
 *
 * Rewritten 2026-09-08. The previous version read $_GET['order_id'] / $_GET['email'],
 * which stopped working in 3.11.1: the canonical receipt URL is now
 * ?order=<uuid>&sce=<secret>, so order_id was never present and the snippet silently
 * no-opped on every real receipt. It now takes the event from core's own filters
 * (`sc_et_receipt_shortcode_event`, `sc_et_ticket_shortcode_event`), which also means
 * no re-validating the link, core has already done that before firing them.
 *
 * Requires Sugar Calendar Pro (Venues). With no venue on the event, core's Location
 * row is left untouched.
 *
 * Remaining limitation: the row is found by matching core's rendered markup, a
 * <th colspan="3"> header followed by a <td colspan="3"> value. If that table is
 * restructured the swap stops applying and the receipt keeps its Location row.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hold the event core is currently rendering a receipt or ticket for.
 *
 * @param object|null $event Event to store, or null to read the stored one.
 * @return object|null
 */
function sc_snippets_receipt_event( $event = null ) {

	static $held = null;

	if ( $event !== null ) {
		$held = $event;
	}

	return $held;
}

add_filter( 'sc_et_receipt_shortcode_event', 'sc_snippets_receipt_hold_event', 10 );
add_filter( 'sc_et_ticket_shortcode_event', 'sc_snippets_receipt_hold_event', 10 );

/**
 * Capture the event without changing it.
 *
 * @param object $event Event object.
 * @return object
 */
function sc_snippets_receipt_hold_event( $event ) {

	sc_snippets_receipt_event( $event );

	return $event;
}

/**
 * Build the venue display string: name, plus the formatted address when there is one.
 *
 * @param object $event Event object.
 * @return string Empty when the event has no venue.
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
			return $name . ', ' . $address;
		}
	}

	return $name;
}

add_filter( 'sc_event_tickets_ticket_shortcode_output', function ( $html ) {

	$event = sc_snippets_receipt_event();

	if ( empty( $event->id ) ) {
		return $html;
	}

	$venue_display = sc_snippets_receipt_venue_display( $event );

	// No venue on this event: leave core's Location row as it is.
	if ( '' === $venue_display ) {
		return $html;
	}

	// Match the label core actually printed, so translated sites work too.
	$location_label = esc_html__( 'Location', 'sugar-calendar-lite' );

	$pattern = '#<tr>\s*<th colspan="3">\s*' . preg_quote( $location_label, '#' )
		. '\s*</th>\s*</tr>\s*<tr>\s*<td colspan="3">.*?</td>\s*</tr>#s';

	$replacement = '<tr><th colspan="3">' . esc_html__( 'Venue', 'sugar-calendar-lite' )
		. '</th></tr><tr><td colspan="3">' . esc_html( $venue_display ) . '</td></tr>';

	return preg_replace( $pattern, $replacement, $html, 1 );
}, 10, 1 );

<?php
/**
 * Add custom content before or after the venue details wrapper.
 *
 * `sc_venue_details_before` fires outside and above the
 * .sc-venue-details container.
 *
 * `sc_venue_details_after` fires below the container and is
 * where related events are rendered by default (at priority 10).
 * Use a lower priority (e.g. 5) to insert content before the
 * related events list, or a higher one (e.g. 15) to go after it.
 *
 * Hooks:  sc_venue_details_before / sc_venue_details_after (actions)
 * File:   sugar-calendar/includes/pro/Features/Venues/FrontEnd/Singular.php
 * Since:  Sugar Calendar 3.5.0
 */
defined( 'ABSPATH' ) || exit;

add_action( 'sc_venue_details_after', 'sc_snippet_venue_after_cta', 5 );

function sc_snippet_venue_after_cta( $venue_data ) {

	// Example: link to Google Maps directions.
	if ( empty( $venue_data['venue_address_1'] ) ) {
		return;
	}

	$address_parts = array_filter( [
		$venue_data['venue_address_1'] ?? '',
		$venue_data['venue_city'] ?? '',
		$venue_data['venue_state'] ?? '',
		$venue_data['venue_postal_code'] ?? '',
	] );

	$maps_url = 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode( implode( ', ', $address_parts ) );

	printf(
		'<p class="sc-venue-directions" style="margin-top:1em;">' .
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>' .
		'</p>',
		esc_url( $maps_url ),
		esc_html__( 'Get Directions', 'sugar-calendar' )
	);
}

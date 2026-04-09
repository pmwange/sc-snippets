<?php
/**
 * Display the venue featured image above the venue details.
 *
 * The venue rendering has no built-in featured image output.
 * This hooks into `sc_venue_details` at priority 5 (the default
 * sc_venue_details() function runs at priority 10) to inject the
 * post thumbnail before the address/map fields.
 *
 * Hook:   sc_venue_details (action)
 * File:   sugar-calendar/includes/pro/Features/Venues/FrontEnd/Singular.php:65
 * Since:  Sugar Calendar 3.5.0
 */
defined( 'ABSPATH' ) || exit;

add_action( 'sc_venue_details', 'sc_snippet_venue_featured_image_first', 5 );

function sc_snippet_venue_featured_image_first( $venue_data ) {

	if ( empty( $venue_data['id'] ) ) {
		return;
	}

	$thumbnail_url = get_the_post_thumbnail_url( $venue_data['id'], 'large' );

	if ( empty( $thumbnail_url ) ) {
		return;
	}

	$title = get_the_title( $venue_data['id'] );

	printf(
		'<div class="sc-venue-details-inner-featured-image" style="margin-bottom:1.5em;">' .
			'<img src="%s" alt="%s" style="max-width:100%%;height:auto;border-radius:6px;" />' .
		'</div>',
		esc_url( $thumbnail_url ),
		esc_attr( $title )
	);
}

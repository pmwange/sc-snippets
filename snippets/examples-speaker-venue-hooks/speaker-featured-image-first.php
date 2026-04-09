<?php
/**
 * Display the speaker featured image above the detail fields.
 *
 * By default the speaker page renders: info fields, then content.
 * This snippet injects the featured image before the info section
 * by hooking into `sc_speaker_details` at priority 5 (the default
 * speaker_details() method runs at priority 10).
 *
 * Hook:   sc_speaker_details (action)
 * File:   sugar-calendar/src/Pro/Features/Speakers/Frontend/Singular.php
 * Since:  Sugar Calendar 3.7.0
 *
 * @see Singular::speaker_details()
 */
defined( 'ABSPATH' ) || exit;

add_action( 'sc_speaker_details', 'sc_snippet_speaker_featured_image_first', 5 );

function sc_snippet_speaker_featured_image_first( $speaker_data ) {

	if ( empty( $speaker_data['featured_image'] ) ) {
		return;
	}

	printf(
		'<div class="sc-speaker-details-inner-featured-image" style="margin-bottom:1.5em;">' .
			'<img src="%s" alt="%s" style="max-width:100%%;height:auto;border-radius:6px;" />' .
		'</div>',
		esc_url( $speaker_data['featured_image'] ),
		esc_attr( $speaker_data['title'] ?? '' )
	);
}

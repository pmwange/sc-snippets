<?php
/**
 * Add custom content before or after the speaker details wrapper.
 *
 * `sc_speaker_details_before` fires outside and above the
 * .sc-speaker-details container. Use it for banners, notices,
 * or breadcrumbs that should sit above the entire speaker block.
 *
 * `sc_speaker_details_after` fires below the container and is
 * where related events are rendered by default.
 *
 * Hooks:  sc_speaker_details_before / sc_speaker_details_after (actions)
 * File:   sugar-calendar/src/Pro/Features/Speakers/Frontend/Singular.php
 * Since:  Sugar Calendar 3.7.0
 */
defined( 'ABSPATH' ) || exit;

add_action( 'sc_speaker_details_before', 'sc_snippet_speaker_before_banner' );

function sc_snippet_speaker_before_banner( $speaker_data ) {

	// Example: show a simple "Meet our speaker" heading.
	printf(
		'<p class="sc-speaker-intro" style="font-style:italic;color:#666;">%s</p>',
		esc_html__( 'Meet our speaker', 'sugar-calendar' )
	);
}

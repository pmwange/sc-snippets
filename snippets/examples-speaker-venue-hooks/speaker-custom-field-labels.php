<?php
/**
 * Customize the speaker detail field labels and meta keys.
 *
 * Use this to rename labels (e.g. "Title" -> "Role") or map
 * fields to different meta keys if you store speaker data differently.
 *
 * Hook:   sc_speaker_details_data_key_pair (filter)
 * File:   sugar-calendar/src/Pro/Features/Speakers/Frontend/Singular.php:208
 * Since:  Sugar Calendar 3.7.0
 */
defined( 'ABSPATH' ) || exit;

add_filter( 'sc_speaker_details_data_key_pair', 'sc_snippet_speaker_custom_labels' );

function sc_snippet_speaker_custom_labels( $data_key_pair ) {

	// Rename "Title" to "Role".
	if ( isset( $data_key_pair['title'] ) ) {
		$data_key_pair['title']['label'] = esc_html__( 'Role', 'sugar-calendar' );
	}

	// Rename "Website" to "Portfolio".
	if ( isset( $data_key_pair['website'] ) ) {
		$data_key_pair['website']['label'] = esc_html__( 'Portfolio', 'sugar-calendar' );
	}

	return $data_key_pair;
}

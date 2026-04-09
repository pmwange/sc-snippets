<?php
/**
 * Reorder the speaker detail fields.
 *
 * The default order is: title, website, email, phone, social_links.
 * This snippet changes it to: title, phone, email, website, social_links.
 *
 * Hook:   sc_speaker_details_data_order (filter)
 * File:   sugar-calendar/src/Pro/Features/Speakers/Frontend/Singular.php:164
 * Since:  Sugar Calendar 3.7.0
 *
 * Available keys: title, website, email, phone, social_links.
 * You can also remove keys to hide fields entirely.
 */
defined( 'ABSPATH' ) || exit;

add_filter( 'sc_speaker_details_data_order', 'sc_snippet_speaker_reorder_details' );

function sc_snippet_speaker_reorder_details( $data_order ) {

	return [
		'title',
		'phone',
		'email',
		'website',
		'social_links',
	];
}

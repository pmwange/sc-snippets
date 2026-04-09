<?php
/**
 * Override the excerpt for Sugar Calendar events.
 *
 * Hooks into get_the_excerpt to modify the output whenever WordPress
 * retrieves an excerpt for a Sugar Calendar event post type.
 */
defined( 'ABSPATH' ) || exit;

add_filter( 'get_the_excerpt', function ( $excerpt, $post ) {

	if ( ! function_exists( 'sugar_calendar_get_event_by_object' ) ) {
		return $excerpt;
	}

	$event = sugar_calendar_get_event_by_object( $post->ID, 'post' );

	if ( ! $event ) {
		return $excerpt;
	}

	// Modify the excerpt here. Examples:
	//
	// 1. Prepend the event start date:
	//    $date    = date_i18n( get_option( 'date_format' ), strtotime( $event->start ) );
	//    $excerpt = $date . ' — ' . $excerpt;
	//
	// 2. Replace the excerpt entirely with a custom string:
	   $excerpt = 'Custom text for: ' . $event->title;
	//
	// 3. Trim to a specific word count:
	//    $excerpt = wp_trim_words( $event->content, 20, '...' );

	return $excerpt;

}, 10, 2 );

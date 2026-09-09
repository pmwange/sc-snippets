<?php
/**
 * Customize the excerpt Sugar Calendar shows for an event.
 *
 * Uses the plugin's own event-excerpt filter (`sugar_calendar_helpers_get_event_excerpt`,
 * since 3.11.0), so it applies wherever Sugar Calendar prints an event description and
 * only ever to events.
 *
 * Was previously hooked to `get_the_excerpt`, which was wrong twice over: core builds event
 * descriptions through Helpers::get_event_excerpt() rather than get_the_excerpt(), so the old
 * version changed nothing that Sugar Calendar renders, and its `if ( ! $event )` guard never
 * fired (an empty Event object is truthy), so any uncommented example also rewrote the
 * excerpt of every blog post on the site.
 *
 * Ships ACTIVE: it prepends the event's start date. Swap the body for what you need.
 */
defined( 'ABSPATH' ) || exit;

add_filter( 'sugar_calendar_helpers_get_event_excerpt', function ( $excerpt, $event_object_id ) {

	$event = sugar_calendar_get_event_by_object(
		$event_object_id,
		'post',
		array( 'object_subtype' => get_post_type( $event_object_id ) )
	);

	// An empty Event object is truthy, so check the id.
	if ( empty( $event->id ) ) {
		return $excerpt;
	}

	// Prepend the start date.
	$date = date_i18n( get_option( 'date_format' ), strtotime( $event->start ) );

	return $date . ': ' . $excerpt;

	// Other things you might do instead of the two lines above:
	//
	// Replace the excerpt entirely:
	//    return 'Join us for ' . $event->title;
	//
	// Trim the event content to a word count:
	//    return wp_trim_words( $event->content, 20, '...' );
}, 10, 2 );

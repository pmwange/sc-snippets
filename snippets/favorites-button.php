<?php
/**
 * Add a favorites button above the content on single posts/events.
 *
 * Outputs the button only if get_favorites_button() exists. On recurring event
 * instance URLs we use the recurring post ID (event->object_id) so the favorite
 * applies to the series. The favorites plugin usually includes the count in the button markup.
 */
defined( 'ABSPATH' ) || exit;
function sc_snippets_get_current_event() {
	$result = array(
		'event'         => null,
		'parent_event'  => null,
		'title'         => '',
		'is_occurrence' => false,
	);

	if ( ! is_singular() || ! function_exists( 'sugar_calendar_get_event_by_object' ) ) {
		return $result;
	}

	$occurrence = false;
	if ( class_exists( 'Sugar_Calendar\Pro\Features\AdvancedRecurring\Helpers' ) ) {
		$occurrence = \Sugar_Calendar\Pro\Features\AdvancedRecurring\Helpers::is_occurrence();
	}

	if ( $occurrence ) {
		// Recurring instance URL (e.g. /events/my-event/2026-02-12/).
		$result['event']         = $occurrence->as_event();
		$result['parent_event']  = $occurrence->get_parent_event_object();
		$result['is_occurrence'] = true;
		$result['title']         = wp_sprintf(
			/* translators: 1: Event title, 2: Event date */
			__( '%1$s - %2$s', 'sugar-snippets' ),
			$result['event']->title,
			function_exists( 'sugar_calendar_format_date_i18n' ) ? sugar_calendar_format_date_i18n( 'F j, Y', $result['event']->start ) : date_i18n( 'F j, Y', strtotime( $result['event']->start ) )
		);
	} else {
		// Normal event or parent recurring event page.
		$post_id = get_the_ID();
		$event   = sugar_calendar_get_event_by_object( $post_id, 'post' );
		if ( $event ) {
			$result['event'] = $event;
			$result['title'] = $event->title;
		}
	}

	return $result;
}
function sc_snippets_get_event_post_id() {
	$data = sc_snippets_get_current_event();

	if ( ! empty( $data['is_occurrence'] ) ) {
		if (
			class_exists( 'Sugar_Calendar\Pro\Features\AdvancedRecurring\Helpers' ) &&
			( $occurrence = \Sugar_Calendar\Pro\Features\AdvancedRecurring\Helpers::is_occurrence() )
		) {
			$parent_post_id = (int) $occurrence->get_parent_post_id();

			if ( $parent_post_id > 0 ) {
				return $parent_post_id;
			}
		}

		if ( ! empty( $data['parent_event'] ) && isset( $data['parent_event']->object_id ) ) {
			return (int) $data['parent_event']->object_id;
		}
	}

	if (
		class_exists( 'Sugar_Calendar\Pro\Features\AdvancedRecurring\Helpers' ) &&
		( $parent_recurring = \Sugar_Calendar\Pro\Features\AdvancedRecurring\Helpers::is_parent_recurring_event() ) &&
		! empty( $parent_recurring['post_object']->ID )
	) {
		return (int) $parent_recurring['post_object']->ID;
	}

	if ( ! empty( $data['event'] ) && isset( $data['event']->object_id ) ) {
		return (int) $data['event']->object_id;
	}

	return is_singular() ? (int) get_the_ID() : 0;
}

add_filter( 'the_content', function ( $content ) {
	if ( ! is_singular() ) {
		return $content;
	}
	if ( ! function_exists( 'get_favorites_button' ) ) {
		return $content;
	}

	$post_id = get_the_ID();
	if ( function_exists( 'sc_snippets_get_event_post_id' ) ) {
		$event_post_id = sc_snippets_get_event_post_id();
		if ( $event_post_id > 0 ) {
			$post_id = $event_post_id;
		}
	}

	$button = get_favorites_button( $post_id );
	return '<p>Current Post ID: ' . $post_id . '</p><div class="favorite-button-container">' . $button . '</div>' . $content;
}, 10, 1 );

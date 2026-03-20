<?php
/**
 * Get the current event object correctly for both standard and recurring instance URLs.
 *
 * On recurring instance URLs (e.g. /events/my-event/2026-02-12/), get_the_ID() and
 * sugar_calendar_get_event_by_object( $id, 'post' ) don't return the occurrence context.
 * This helper uses Sugar Calendar's recurrence API so you get the right event + date.
 *
 * Use sc_snippets_get_current_event() in custom single-sc_event.php or any PHP that
 * needs the "current" event (including recurring instances).
 *
 * @see https://sugarcalendar.com/docs/ (recurring events / customizing templates)
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get the current event (and optional parent/title) for this request.
 *
 * Works on single event pages for both normal events and recurring instance URLs.
 *
 * @return array{ event: object|null, parent_event: object|null, title: string, is_occurrence: bool }
 */
if ( ! function_exists( 'sc_snippets_get_current_event' ) ) :
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
endif;

/**
 * Get the post ID for the current event (recurring or normal).
 *
 * For recurring instance URLs this is the recurring post ID (event->object_id).
 * For normal events this is get_the_ID(). Use this for favorites, permalinks, etc.
 *
 * @return int Post ID, or 0 if not on a single event page.
 */
if ( ! function_exists( 'sc_snippets_get_event_post_id' ) ) :
function sc_snippets_get_event_post_id() {
	$data = sc_snippets_get_current_event();
	if ( ! empty( $data['event'] ) && isset( $data['event']->object_id ) ) {
		return (int) $data['event']->object_id;
	}
	return is_singular() ? (int) get_the_ID() : 0;
}
endif;

/**
 * Shortcode: output the current event title (normal or recurring instance).
 * Use in block templates where you can't run PHP: [sc_event_title]
 */
add_shortcode( 'sc_event_title', function () {
	$data = sc_snippets_get_current_event();
	return $data['title'] ? esc_html( $data['title'] ) : '';
} );

<?php
/**
 * Bare, theme-friendly list of upcoming events.
 *
 * The Events List block bakes in an <h4> title and calendar icons and is hard
 * to restyle. This snippet adds a shortcode that outputs a plain, one-line-per
 * event list — date, time, and title only, no <h4> and no icons — so you can
 * wrap it in your own <ul>/<ol> and style it with your theme.
 *
 * Reads through sugar_calendar_get_events()'s upcoming helper, so single AND
 * recurring events (Pro) are included, in date order.
 *
 * Usage:
 *   <ul>[sc_upcoming_events number="5"]</ul>
 *
 * Attributes:
 *   number    How many events to show. Default 5.
 *   calendars Comma-separated calendar (category) term IDs. Default: all.
 *   order     asc | desc. Default asc (soonest first).
 *   separator Text/character between date, time, title. Default " · ".
 *   venue     yes | no — append the venue name (Pro only). Default no.
 *   wrap      Element per event: li | div | span | p, or "" for plain lines
 *             separated by <br>. Default "li" (wrap in your own <ul>/<ol>).
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get ordered upcoming Event objects (single + recurring, once each).
 *
 * @param int    $number       Max events to return.
 * @param int[]  $calendar_ids Calendar (category) term IDs to filter by.
 * @param string $order        'asc' or 'desc'.
 * @return array Array of Sugar_Calendar\Event objects.
 */
if ( ! function_exists( 'sc_snippets_get_upcoming_events' ) ) :
function sc_snippets_get_upcoming_events( $number = 5, $calendar_ids = array(), $order = 'asc' ) {

	if ( ! class_exists( '\Sugar_Calendar\Helpers' )
		|| ! method_exists( '\Sugar_Calendar\Helpers', 'get_upcoming_events_list_with_recurring' )
	) {
		return array();
	}

	$number = (int) $number;

	if ( $number < 1 ) {
		$number = 5;
	}

	$events = \Sugar_Calendar\Helpers::get_upcoming_events_list_with_recurring(
		array(
			'number'       => $number,
			'calendar_ids' => $calendar_ids,
			'event_order'  => ( strtolower( $order ) === 'desc' ) ? 'desc' : 'asc',
		),
		array()
	);

	// The helper fetches one extra row to detect "has more"; trim to what was asked.
	return array_slice( $events, 0, $number );
}
endif;

/**
 * Shortcode: [sc_upcoming_events number="5"]
 */
add_shortcode( 'sc_upcoming_events', function ( $atts ) {

	$atts = shortcode_atts(
		array(
			'number'    => 5,
			'calendars' => '',
			'order'     => 'asc',
			'separator' => ' · ',
			'venue'     => 'no',
			'wrap'      => 'li',
		),
		$atts,
		'sc_upcoming_events'
	);

	$calendar_ids = array_filter(
		array_map( 'absint', explode( ',', (string) $atts['calendars'] ) )
	);

	$events = sc_snippets_get_upcoming_events( $atts['number'], $calendar_ids, $atts['order'] );

	if ( empty( $events ) ) {
		return '';
	}

	// Display in the site's timezone, using the site's date/time formats.
	$tz          = wp_timezone();
	$date_format = get_option( 'date_format' );
	$time_format = get_option( 'time_format' );
	$separator   = $atts['separator'];

	// Allow only a small set of block/inline elements; empty = <br>-separated.
	$tag = preg_replace( '/[^a-z0-9]/', '', strtolower( (string) $atts['wrap'] ) );

	if ( ! in_array( $tag, array( '', 'li', 'div', 'span', 'p' ), true ) ) {
		$tag = 'li';
	}

	$out = '';

	foreach ( $events as $event ) {

		$parts = array();

		// Date.
		$parts[] = esc_html( $event->start_date( $date_format, $tz ) );

		// Time.
		if ( $event->is_all_day() ) {
			$parts[] = esc_html__( 'All-day', 'sugar-snippets' );
		} else {
			$parts[] = esc_html( $event->start_date( $time_format, $tz ) );
		}

		// Title.
		$parts[] = esc_html( get_the_title( $event->object_id ) );

		// Venue name (Pro only; venue_id is unset on Lite, so this no-ops there).
		if ( strtolower( $atts['venue'] ) === 'yes' && ! empty( $event->venue_id ) ) {
			$venue_name = get_the_title( $event->venue_id );

			if ( $venue_name !== '' ) {
				$parts[] = esc_html( $venue_name );
			}
		}

		$line = implode( $separator, $parts );

		if ( $tag !== '' ) {
			$out .= '<' . $tag . ' class="sc-upcoming-event">' . $line . '</' . $tag . '>';
		} else {
			$out .= $line . '<br />';
		}
	}

	return $out;
} );

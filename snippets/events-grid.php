<?php
/**
 * Custom events grid that includes recurring events.
 *
 * A plain query-loop / Search & Filter query over the `sc_event` post type and
 * post meta never shows recurring events: Pro stores them under the
 * `sc_recurring_event` post type, and event dates live in the `wp_sc_events`
 * table, not in post meta. This grid reads straight from Sugar Calendar's event
 * table (via sugar_calendar_get_events) so single AND recurring events show, in
 * date order. Each recurring event appears once, at its series start date.
 *
 * Usage: [sc_events_grid number="30"]
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get ordered Event objects for the grid (single + recurring, once each).
 *
 * @param int $number Max events to return.
 * @return array Array of Sugar_Calendar\Event objects.
 */
if ( ! function_exists( 'sc_snippets_get_grid_events' ) ) :
function sc_snippets_get_grid_events( $number = 30 ) {

	if ( ! function_exists( 'sugar_calendar_get_events' ) ) {
		return array();
	}

	return sugar_calendar_get_events( array(
		// Include BOTH post types. Pass `object_subtype` as an array (NOT
		// `object_subtype__in`): sugar_calendar_get_events() injects a default
		// `object_subtype => 'sc_event'` whenever that key is absent, which would
		// then AND against an __in clause and silently drop recurring events.
		'object_subtype' => array( 'sc_event', 'sc_recurring_event' ),
		'status'         => 'publish',
		'number'         => (int) $number,
		'orderby'        => 'start',
		'order'          => 'ASC',
	) );
}
endif;

/**
 * Shortcode: [sc_events_grid number="30"]
 */
add_shortcode( 'sc_events_grid', function ( $atts ) {

	$atts = shortcode_atts( array( 'number' => 30 ), $atts, 'sc_events_grid' );

	$events = sc_snippets_get_grid_events( $atts['number'] );

	if ( empty( $events ) ) {
		return '<p>' . esc_html__( 'No events found.', 'sugar-snippets' ) . '</p>';
	}

	// Display in the site's timezone, using the site's date/time formats.
	$tz          = wp_timezone();
	$date_format = get_option( 'date_format' );
	$time_format = get_option( 'time_format' );

	ob_start();
	?>
	<style>
		.sc-snippets-grid { display: grid; grid-template-columns: repeat( auto-fill, minmax( 240px, 1fr ) ); gap: 24px; margin: 24px 0; }
		.sc-snippets-card { border: 1px solid #e2e4e7; border-radius: 8px; padding: 16px; background: #fff; }
		.sc-snippets-card__thumb img { width: 100%; height: auto; border-radius: 6px; margin-bottom: 12px; display: block; }
		.sc-snippets-card__title { margin: 0 0 8px; font-size: 18px; line-height: 1.3; }
		.sc-snippets-card__date { margin: 0; color: #50575e; font-size: 14px; }
		.sc-snippets-card__badge { display: inline-block; margin-top: 10px; padding: 2px 8px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; color: #fff; background: #2271b1; border-radius: 10px; }
	</style>
	<div class="sc-snippets-grid">
		<?php foreach ( $events as $event ) :
			$post_id   = (int) $event->object_id;
			$permalink = get_permalink( $post_id );
			?>
			<article class="sc-snippets-card">

				<?php if ( has_post_thumbnail( $post_id ) ) : ?>
					<a class="sc-snippets-card__thumb" href="<?php echo esc_url( $permalink ); ?>">
						<?php echo get_the_post_thumbnail( $post_id, 'medium' ); ?>
					</a>
				<?php endif; ?>

				<h3 class="sc-snippets-card__title">
					<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $event->title ); ?></a>
				</h3>

				<p class="sc-snippets-card__date">
					<?php
					if ( $event->is_all_day() ) {
						echo esc_html( $event->start_date( $date_format, $tz ) );
						if ( $event->is_multi() ) {
							echo ' &ndash; ' . esc_html( $event->end_date( $date_format, $tz ) );
						}
					} else {
						echo esc_html( $event->start_date( "{$date_format} {$time_format}", $tz ) );
						echo ' &ndash; ' . esc_html( $event->end_date( $time_format, $tz ) );
					}
					?>
				</p>

				<?php if ( ! empty( $event->recurrence ) ) : ?>
					<span class="sc-snippets-card__badge"><?php esc_html_e( 'Recurring', 'sugar-snippets' ); ?></span>
				<?php endif; ?>

			</article>
		<?php endforeach; ?>
	</div>
	<?php
	return ob_get_clean();
} );

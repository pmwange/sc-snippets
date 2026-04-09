<?php
/**
 * Allow tag slugs in the sugarcalendar_events_list shortcode.
 *
 * Sugar Calendar's shortcode only accepts term IDs in the `tags` attribute.
 * This filter intercepts the raw shortcode attributes before the sanitized
 * (now-empty) value is used, resolves any slugs to term IDs, and injects
 * them back so the query works correctly.
 *
 * Usage (slug or ID, single or comma-separated, mixed):
 *   [sugarcalendar_events_list tags="middle-school-2"]
 *   [sugarcalendar_events_list tags="middle-school-2,youth-group"]
 *   [sugarcalendar_events_list tags="42,middle-school-2"]
 */
defined( 'ABSPATH' ) || exit;

add_filter(
	'sugar_calendar_shortcodes_modern_shortcodes_attributes_sugarcalendar-events-list',
	function ( $block_attributes, $shortcode_attributes ) {

		// Only act when the raw shortcode had a `tags` value.
		if ( empty( $shortcode_attributes['tags'] ) ) {
			return $block_attributes;
		}

		$raw    = $shortcode_attributes['tags'];
		$pieces = array_filter( array_map( 'trim', explode( ',', $raw ) ) );
		$ids    = [];

		foreach ( $pieces as $piece ) {
			if ( is_numeric( $piece ) ) {
				// Already an ID — use it directly.
				$ids[] = (int) $piece;
			} else {
				// Treat as a slug and look up the term.
				$term = get_term_by( 'slug', $piece, 'sc_event_tags' );
				if ( $term && ! is_wp_error( $term ) ) {
					$ids[] = $term->term_id;
				}
			}
		}

		if ( ! empty( $ids ) ) {
			$block_attributes['tags'] = $ids;
		}

		return $block_attributes;
	},
	10,
	2
);

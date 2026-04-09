<?php
/**
 * Replace the default venue details renderer with a custom layout.
 *
 * The default sc_venue_details() function has a hardcoded field order
 * (address, city, state, country, postal code, phone, website, map, content).
 * Unlike speakers, there is no filter to reorder fields -- you must
 * remove the default renderer and provide your own.
 *
 * This snippet shows a condensed layout: phone and website first,
 * then a combined address block, then the map.
 *
 * Hook:   sc_venue_details (action) -- remove default at 10, add custom at 10
 * File:   sugar-calendar/includes/pro/Features/Venues/Common/Functions.php:60
 * Since:  Sugar Calendar 3.5.0
 */
defined( 'ABSPATH' ) || exit;

add_action( 'init', 'sc_snippet_venue_swap_renderer' );

function sc_snippet_venue_swap_renderer() {

	// Remove the default venue details renderer.
	remove_action( 'sc_venue_details', 'sc_venue_details', 10 );

	// Add our custom renderer at the same priority.
	add_action( 'sc_venue_details', 'sc_snippet_venue_custom_details', 10 );
}

function sc_snippet_venue_custom_details( $venue_data ) {

	?>
	<div class="sc-venue-details-inner-info">
		<div class="sc-venue-details-inner-info__data">
			<?php
			// Phone first.
			if ( ! empty( $venue_data['venue_phone'] ) ) {
				echo wp_sprintf(
					'<p><b>%1$s:</b> %2$s</p>',
					esc_html__( 'Phone', 'sugar-calendar' ),
					esc_html( $venue_data['venue_phone'] )
				);
			}

			// Website second.
			if ( ! empty( $venue_data['venue_website'] ) ) {
				echo wp_sprintf(
					'<p><b>%1$s:</b> <a href="%2$s">%2$s</a></p>',
					esc_html__( 'Website', 'sugar-calendar' ),
					esc_url( $venue_data['venue_website'] )
				);
			}

			// Combined address block.
			$address_parts = array_filter( [
				$venue_data['venue_address_1'] ?? '',
				$venue_data['venue_address_2'] ?? '',
				$venue_data['venue_city'] ?? '',
				$venue_data['venue_state'] ?? '',
				$venue_data['venue_postal_code'] ?? '',
			] );

			if ( ! empty( $venue_data['venue_country'] ) && function_exists( 'Sugar_Calendar\Pro\Features\Venues\Common\Helpers\get_country_display_name' ) ) {
				$address_parts[] = Sugar_Calendar\Pro\Features\Venues\Common\Helpers\get_country_display_name( $venue_data['venue_country'] );
			}

			if ( ! empty( $address_parts ) ) {
				echo wp_sprintf(
					'<p><b>%1$s:</b> %2$s</p>',
					esc_html__( 'Address', 'sugar-calendar' ),
					esc_html( implode( ', ', $address_parts ) )
				);
			}
			?>
		</div>
	</div>

	<?php if ( ! empty( $venue_data['content'] ) ) : ?>
		<div class="sc-venue-details-inner-content">
			<?php echo wp_kses_post( wpautop( $venue_data['content'] ) ); ?>
		</div>
	<?php endif; ?>
	<?php
}

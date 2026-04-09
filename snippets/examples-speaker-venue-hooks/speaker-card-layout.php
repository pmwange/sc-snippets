<?php
/**
 * Speaker Card Layout — completely replaces the default speaker page
 * with a modern card-style design.
 *
 * Layout:
 *   ┌──────────────────────────────────┐
 *   │         Featured Image           │
 *   │        (hero, 3:2 ratio)         │
 *   ├──────────────────────────────────┤
 *   │  [Title/Role]                    │
 *   │                                  │
 *   │  📧 email  📞 phone  🌐 website │
 *   │  [social icons row]             │
 *   │                                  │
 *   │  ─── About ───                   │
 *   │  Bio content goes here...        │
 *   └──────────────────────────────────┘
 *
 * Hooks used:
 *   - sc_speaker_details        (action, priority 10) — removed & replaced
 *   - sc_speaker_details_before (action, priority 10) — injects inline CSS
 *
 * Filters used:
 *   - sc_speaker_details_social_links — reads available social platforms
 *
 * Files referenced:
 *   sugar-calendar/src/Pro/Features/Speakers/Frontend/Singular.php
 *   sugar-calendar/src/Pro/Features/Speakers/Common/Helpers.php
 *
 * To activate: copy this file into the parent snippets/ folder and
 * enable it under Settings > Sugar Snippets.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Remove the default speaker_details renderer and attach ours.
 *
 * We hook into `init` so the Sugar Calendar Singular class has already
 * registered its hooks() — our remove_action targets priority 10
 * which is the default used by Singular::speaker_details().
 */
add_action( 'init', 'sc_snippet_card_layout_swap_renderer' );

function sc_snippet_card_layout_swap_renderer() {

	// Bail if Sugar Calendar Pro isn't active.
	if ( ! function_exists( 'sugar_calendar' ) || ! class_exists( 'Sugar_Calendar\Plugin' ) ) {
		return;
	}

	// Remove default renderer (Singular::speaker_details at priority 10).
	// We can't reference the exact instance, so use a closure at the same priority.
	add_action( 'sc_speaker_details', 'sc_snippet_card_layout_remove_default', 1 );

	// Render our card layout.
	add_action( 'sc_speaker_details', 'sc_snippet_card_layout_render', 10 );

	// Inject inline styles once, before the details wrapper.
	add_action( 'sc_speaker_details_before', 'sc_snippet_card_layout_styles', 10 );
}

/**
 * Remove the default speaker_details callback.
 *
 * Runs at priority 1 on the same hook, before priority 10 fires.
 * We search for the Singular instance method dynamically.
 */
function sc_snippet_card_layout_remove_default( $speaker_data ) {
	global $wp_filter;

	if ( ! isset( $wp_filter['sc_speaker_details'] ) ) {
		return;
	}

	// Walk priority-10 callbacks and remove any Singular::speaker_details.
	foreach ( $wp_filter['sc_speaker_details']->callbacks as $priority => &$callbacks ) {
		if ( (int) $priority !== 10 ) {
			continue;
		}
		foreach ( $callbacks as $id => $cb ) {
			if (
				is_array( $cb['function'] )
				&& is_object( $cb['function'][0] )
				&& str_ends_with( get_class( $cb['function'][0] ), 'Singular' )
				&& $cb['function'][1] === 'speaker_details'
			) {
				unset( $callbacks[ $id ] );
			}
		}
	}
}

/**
 * Render the card-style speaker layout.
 */
function sc_snippet_card_layout_render( $speaker_data ) {

	$post_id  = $speaker_data['id'] ?? 0;
	$name     = get_the_title( $post_id );
	$image    = $speaker_data['featured_image'] ?? '';
	$role     = $speaker_data['sugarcalendar_speaker_title'] ?? '';
	$website  = $speaker_data['sugarcalendar_speaker_website'] ?? '';
	$email    = $speaker_data['sugarcalendar_speaker_email'] ?? '';
	$phone    = $speaker_data['sugarcalendar_speaker_phone'] ?? '';
	$content  = $speaker_data['content'] ?? '';

	// Gather social links the same way the core plugin does.
	$social_links = apply_filters( 'sc_speaker_details_social_links', [
		[ 'class' => 'sc-icon--linkedin',  'key' => 'sugarcalendar_speaker_linkedin',  'label' => 'LinkedIn' ],
		[ 'class' => 'sc-icon--instagram', 'key' => 'sugarcalendar_speaker_instagram', 'label' => 'Instagram' ],
		[ 'class' => 'sc-icon--facebook',  'key' => 'sugarcalendar_speaker_facebook',  'label' => 'Facebook' ],
		[ 'class' => 'sc-icon--twitter',   'key' => 'sugarcalendar_speaker_twitter',   'label' => 'X / Twitter' ],
		[ 'class' => 'sc-icon--youtube',   'key' => 'sugarcalendar_speaker_youtube',   'label' => 'YouTube' ],
	] );

	$has_contact = $email || $phone || $website;
	$has_socials = false;
	foreach ( $social_links as $link ) {
		if ( ! empty( $speaker_data[ $link['key'] ] ) ) {
			$has_socials = true;
			break;
		}
	}

	?>
	<div class="sc-card">
		<?php if ( $image ) : ?>
			<div class="sc-card__hero">
				<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $name ); ?>" />
			</div>
		<?php endif; ?>

		<div class="sc-card__body">
			<?php if ( $role ) : ?>
				<p class="sc-card__role"><?php echo esc_html( $role ); ?></p>
			<?php endif; ?>

			<?php if ( $has_contact ) : ?>
				<div class="sc-card__contact">
					<?php if ( $email ) : ?>
						<a href="mailto:<?php echo esc_attr( sanitize_email( $email ) ); ?>" class="sc-card__pill">
							<svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor"><path d="M2 4a2 2 0 00-2 2v8a2 2 0 002 2h16a2 2 0 002-2V6a2 2 0 00-2-2H2zm0 2l8 4.5L18 6v8H2V6z"/></svg>
							<?php echo esc_html( $email ); ?>
						</a>
					<?php endif; ?>

					<?php if ( $phone ) : ?>
						<a href="tel:<?php echo esc_attr( $phone ); ?>" class="sc-card__pill">
							<svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/></svg>
							<?php echo esc_html( $phone ); ?>
						</a>
					<?php endif; ?>

					<?php if ( $website ) : ?>
						<a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener noreferrer" class="sc-card__pill">
							<svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor"><path d="M10 0C4.477 0 0 4.477 0 10s4.477 10 10 10 10-4.477 10-10S15.523 0 10 0zm0 2a8 8 0 014.906 1.694C13.58 4.85 11.87 6.5 10 6.5S6.42 4.85 5.094 3.694A8 8 0 0110 2zm0 16a8 8 0 01-6.32-3.1C5.3 13.24 7.52 12 10 12s4.7 1.24 6.32 2.9A8 8 0 0110 18z"/></svg>
							<?php echo esc_html( wp_parse_url( $website, PHP_URL_HOST ) ?: $website ); ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( $has_socials ) : ?>
				<div class="sc-card__socials">
					<?php foreach ( $social_links as $link ) :
						if ( empty( $speaker_data[ $link['key'] ] ) ) {
							continue;
						}
					?>
						<a href="<?php echo esc_url( $speaker_data[ $link['key'] ] ); ?>" target="_blank" rel="noopener noreferrer" class="sc-card__social" title="<?php echo esc_attr( $link['label'] ); ?>">
							<?php echo esc_html( $link['label'] ); ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $content ) : ?>
				<div class="sc-card__divider">
					<span><?php esc_html_e( 'About', 'sugar-calendar' ); ?></span>
				</div>
				<div class="sc-card__bio">
					<?php echo wp_kses_post( wpautop( $content ) ); ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

/**
 * Print inline CSS for the card layout.
 *
 * We inject it once via sc_speaker_details_before so it lives close
 * to the markup and only loads on speaker pages.
 */
function sc_snippet_card_layout_styles( $speaker_data ) {

	static $printed = false;
	if ( $printed ) {
		return;
	}
	$printed = true;

	?>
	<style>
		/* --- Speaker Card Layout (snippet override) --- */

		/* Hide the default inner wrappers that Sugar Calendar adds */
		.sc-speaker-details .sc-speaker-details-inner-info,
		.sc-speaker-details .sc-speaker-details-inner-content {
			display: none !important;
		}

		.sc-card {
			max-width: 640px;
			margin: 0 auto;
			border: 1px solid #e2e4e7;
			border-radius: 12px;
			overflow: hidden;
			background: #fff;
			box-shadow: 0 2px 8px rgba(0, 0, 0, .06);
		}

		/* Hero image */
		.sc-card__hero {
			position: relative;
			aspect-ratio: 3 / 2;
			overflow: hidden;
			background: #f0f0f1;
		}
		.sc-card__hero img {
			width: 100%;
			height: 100%;
			object-fit: cover;
			display: block;
		}

		/* Body */
		.sc-card__body {
			padding: 28px 32px 32px;
		}

		/* Role / title */
		.sc-card__role {
			margin: 0 0 20px;
			font-size: 15px;
			font-weight: 600;
			color: #50575e;
			text-transform: uppercase;
			letter-spacing: .04em;
		}

		/* Contact pills */
		.sc-card__contact {
			display: flex;
			flex-wrap: wrap;
			gap: 10px;
			margin-bottom: 20px;
		}
		.sc-card__pill {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			padding: 6px 14px;
			background: #f6f7f7;
			border: 1px solid #e2e4e7;
			border-radius: 999px;
			font-size: 13px;
			color: #1d2327;
			text-decoration: none;
			transition: background .15s, border-color .15s;
			line-height: 1.4;
		}
		.sc-card__pill:hover {
			background: #e8f0fe;
			border-color: #b0c4de;
			color: #0a4b78;
		}
		.sc-card__pill svg {
			flex-shrink: 0;
			opacity: .6;
		}

		/* Social links */
		.sc-card__socials {
			display: flex;
			flex-wrap: wrap;
			gap: 8px;
			margin-bottom: 20px;
		}
		.sc-card__social {
			display: inline-block;
			padding: 4px 12px;
			border-radius: 999px;
			font-size: 12px;
			font-weight: 500;
			color: #50575e;
			background: #f0f0f1;
			text-decoration: none;
			transition: background .15s, color .15s;
		}
		.sc-card__social:hover {
			background: #1d2327;
			color: #fff;
		}

		/* Divider */
		.sc-card__divider {
			display: flex;
			align-items: center;
			gap: 16px;
			margin: 24px 0 20px;
			color: #8c8f94;
			font-size: 12px;
			font-weight: 600;
			text-transform: uppercase;
			letter-spacing: .06em;
		}
		.sc-card__divider::before,
		.sc-card__divider::after {
			content: "";
			flex: 1;
			height: 1px;
			background: #e2e4e7;
		}

		/* Bio */
		.sc-card__bio {
			font-size: 15px;
			line-height: 1.7;
			color: #3c434a;
		}
		.sc-card__bio p:first-child {
			margin-top: 0;
		}
		.sc-card__bio p:last-child {
			margin-bottom: 0;
		}

		/* Responsive */
		@media (max-width: 480px) {
			.sc-card__body {
				padding: 20px;
			}
			.sc-card__hero {
				aspect-ratio: 16 / 9;
			}
			.sc-card__pill {
				font-size: 12px;
				padding: 5px 10px;
			}
		}
	</style>
	<?php
}

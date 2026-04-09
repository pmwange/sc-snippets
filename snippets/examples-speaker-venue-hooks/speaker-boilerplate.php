<?php
/**
 * Speaker Page Customization — Boilerplate
 *
 * Sugar Calendar Pro does NOT support theme-based template overrides for
 * speaker pages (single-sc_speakers.php won't work). Instead, the plugin
 * renders speaker pages through action hooks. This boilerplate shows every
 * hook and filter available so you can mix and match.
 *
 * ┌─────────────────────────────────────────────────────────────────────┐
 * │  HOW THE SPEAKER PAGE RENDERS                                      │
 * │                                                                     │
 * │  ① sc_speaker_details_before          ← outside the wrapper         │
 * │  ┌─ .sc-speaker-details ────────────────────────────────────────┐   │
 * │  │  ┌─ .sc-speaker-details-inner ─────────────────────────────┐ │   │
 * │  │  │                                                         │ │   │
 * │  │  │  ② sc_speaker_details           ← main content hook     │ │   │
 * │  │  │     └─ default: speaker_details() at priority 10        │ │   │
 * │  │  │        ├─ .sc-speaker-details-inner-info                │ │   │
 * │  │  │        │   └─ loops sc_speaker_details_data_order       │ │   │
 * │  │  │        │       (title → website → email → phone         │ │   │
 * │  │  │        │        → social_links)                         │ │   │
 * │  │  │        └─ .sc-speaker-details-inner-content             │ │   │
 * │  │  │            └─ post content / bio                        │ │   │
 * │  │  └─────────────────────────────────────────────────────────┘ │   │
 * │  └──────────────────────────────────────────────────────────────┘   │
 * │  ③ sc_speaker_details_after           ← related events go here     │
 * └─────────────────────────────────────────────────────────────────────┘
 *
 * AVAILABLE DATA in $speaker_data array:
 *
 *   $speaker_data['id']                              — Post ID
 *   $speaker_data['title']                           — Post title (name)
 *   $speaker_data['content']                         — Post content (bio)
 *   $speaker_data['featured_image']                  — Full-size image URL (if set)
 *   $speaker_data['sugarcalendar_speaker_title']     — Professional title / role
 *   $speaker_data['sugarcalendar_speaker_website']   — Website URL
 *   $speaker_data['sugarcalendar_speaker_email']     — Email address
 *   $speaker_data['sugarcalendar_speaker_phone']     — Phone number
 *   $speaker_data['sugarcalendar_speaker_linkedin']  — LinkedIn URL
 *   $speaker_data['sugarcalendar_speaker_instagram'] — Instagram URL
 *   $speaker_data['sugarcalendar_speaker_facebook']  — Facebook URL
 *   $speaker_data['sugarcalendar_speaker_twitter']   — X / Twitter URL
 *   $speaker_data['sugarcalendar_speaker_youtube']   — YouTube URL
 *
 * SOURCE FILES (for reference):
 *   sugar-calendar/src/Pro/Features/Speakers/Frontend/Singular.php
 *   sugar-calendar/src/Pro/Features/Speakers/Common/Helpers.php
 *
 * USAGE:
 *   Copy this file into wp-content/plugins/sc-snippets/snippets/ and
 *   enable it under Settings → Sugar Snippets. Then uncomment the
 *   sections you need below.
 *
 * @since Sugar Calendar 3.7.0
 */
defined( 'ABSPATH' ) || exit;


/* =========================================================================
 * 1. ADD CONTENT BEFORE THE SPEAKER BLOCK
 *
 * Hook:     sc_speaker_details_before (action)
 * Fires:    Outside and above the .sc-speaker-details wrapper.
 * Use for:  Banners, breadcrumbs, notices.
 * ====================================================================== */

// Uncomment to enable:
// add_action( 'sc_speaker_details_before', 'my_speaker_before', 10 );

function my_speaker_before( $speaker_data ) {
	// Example: a subtle intro line.
	printf(
		'<p style="color:#666;font-style:italic;">%s</p>',
		esc_html__( 'Meet our speaker', 'sugar-calendar' )
	);
}


/* =========================================================================
 * 2. SHOW THE FEATURED IMAGE ABOVE THE DETAIL FIELDS
 *
 * Hook:     sc_speaker_details (action) at priority < 10
 * Why < 10: The default speaker_details() renders at priority 10.
 *           Hooking at 5 places our image before it.
 * ====================================================================== */

// Uncomment to enable:
// add_action( 'sc_speaker_details', 'my_speaker_image_above', 5 );

function my_speaker_image_above( $speaker_data ) {
	if ( empty( $speaker_data['featured_image'] ) ) {
		return;
	}

	printf(
		'<div style="margin-bottom:1.5em;">' .
			'<img src="%s" alt="%s" style="max-width:100%%;height:auto;border-radius:8px;" />' .
		'</div>',
		esc_url( $speaker_data['featured_image'] ),
		esc_attr( $speaker_data['title'] ?? '' )
	);
}


/* =========================================================================
 * 3. REORDER THE DETAIL FIELDS
 *
 * Filter:   sc_speaker_details_data_order
 * Default:  [ 'title', 'website', 'email', 'phone', 'social_links' ]
 *
 * You can reorder, remove, or add custom keys.
 * Removing a key hides that field entirely.
 * ====================================================================== */

// Uncomment to enable:
// add_filter( 'sc_speaker_details_data_order', 'my_speaker_field_order' );

function my_speaker_field_order( $order ) {
	return [
		'title',         // keep
		'phone',         // moved up
		'email',         // moved up
		'website',       // moved down
		'social_links',  // keep last
	];
}


/* =========================================================================
 * 4. RENAME FIELD LABELS
 *
 * Filter:   sc_speaker_details_data_key_pair
 * Default labels: Title, Website, Email, Phone.
 * Each entry maps a data key to [ 'label' => '...', 'key' => 'meta_key' ].
 * ====================================================================== */

// Uncomment to enable:
// add_filter( 'sc_speaker_details_data_key_pair', 'my_speaker_labels' );

function my_speaker_labels( $pairs ) {
	// Rename "Title" → "Role".
	if ( isset( $pairs['title'] ) ) {
		$pairs['title']['label'] = __( 'Role', 'sugar-calendar' );
	}

	// Rename "Website" → "Portfolio".
	if ( isset( $pairs['website'] ) ) {
		$pairs['website']['label'] = __( 'Portfolio', 'sugar-calendar' );
	}

	return $pairs;
}


/* =========================================================================
 * 5. CUSTOMIZE SOCIAL LINKS
 *
 * Filter:   sc_speaker_details_social_links
 * Each entry: [ 'icon' => '<svg...>', 'class' => 'sc-icon--name', 'key' => 'meta_key' ]
 *
 * You can add new platforms or remove existing ones.
 * ====================================================================== */

// Uncomment to enable:
// add_filter( 'sc_speaker_details_social_links', 'my_speaker_socials' );

function my_speaker_socials( $links ) {
	// Example: add TikTok.
	$links[] = [
		'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M448 209.9a210.1 210.1 0 01-122.8-39.3v178.8A162.6 162.6 0 11185 188.3v89.9a74.6 74.6 0 1052.2 71.2V0h88a121 121 0 00122.8 121.3z"/></svg>',
		'class' => 'sc-icon--tiktok',
		'key'   => 'sugarcalendar_speaker_tiktok', // Requires this meta to be saved on the speaker.
	];

	// Example: remove Facebook.
	$links = array_filter( $links, function ( $link ) {
		return ( $link['class'] ?? '' ) !== 'sc-icon--facebook';
	} );

	return $links;
}


/* =========================================================================
 * 6. ADD CONTENT AFTER THE SPEAKER BLOCK
 *
 * Hook:     sc_speaker_details_after (action)
 * Fires:    Below the .sc-speaker-details wrapper.
 * Note:     Related events render here at the default priority (10).
 *           Use priority < 10 to insert above them, > 10 to go below.
 * ====================================================================== */

// Uncomment to enable:
// add_action( 'sc_speaker_details_after', 'my_speaker_after', 5 );

function my_speaker_after( $speaker_data ) {
	// Example: a call-to-action.
	printf(
		'<div style="margin:2em 0;padding:16px 20px;background:#f0f6ff;border-left:4px solid #2271b1;border-radius:4px;">%s</div>',
		esc_html__( 'Interested in booking this speaker? Contact us!', 'sugar-calendar' )
	);
}


/* =========================================================================
 * 7. FULLY REPLACE THE SPEAKER LAYOUT
 *
 * If reordering fields isn't enough, you can remove the default renderer
 * and build your own from scratch using $speaker_data.
 *
 * Steps:
 *   a) Remove the default speaker_details() (priority 10)
 *   b) Add your own renderer at priority 10
 * ====================================================================== */

// Uncomment to enable:
// add_action( 'init', 'my_speaker_replace_layout' );

function my_speaker_replace_layout() {

	// Remove default at priority 10 (searches dynamically for the instance).
	add_action( 'sc_speaker_details', function () {
		global $wp_filter;
		if ( ! isset( $wp_filter['sc_speaker_details'] ) ) {
			return;
		}
		foreach ( $wp_filter['sc_speaker_details']->callbacks[10] ?? [] as $id => $cb ) {
			if (
				is_array( $cb['function'] )
				&& is_object( $cb['function'][0] )
				&& $cb['function'][1] === 'speaker_details'
			) {
				remove_action( 'sc_speaker_details', $cb['function'], 10 );
			}
		}
	}, 1 );

	// Add custom renderer.
	add_action( 'sc_speaker_details', 'my_speaker_custom_render', 10 );
}

function my_speaker_custom_render( $speaker_data ) {
	$image   = $speaker_data['featured_image'] ?? '';
	$role    = $speaker_data['sugarcalendar_speaker_title'] ?? '';
	$email   = $speaker_data['sugarcalendar_speaker_email'] ?? '';
	$phone   = $speaker_data['sugarcalendar_speaker_phone'] ?? '';
	$website = $speaker_data['sugarcalendar_speaker_website'] ?? '';
	$content = $speaker_data['content'] ?? '';

	// Build your layout however you like:
	?>
	<div class="my-speaker-layout">
		<?php if ( $image ) : ?>
			<img src="<?php echo esc_url( $image ); ?>" alt="" style="max-width:200px;border-radius:50%;" />
		<?php endif; ?>

		<?php if ( $role ) : ?>
			<p><strong><?php echo esc_html( $role ); ?></strong></p>
		<?php endif; ?>

		<ul>
			<?php if ( $email ) : ?>
				<li><?php esc_html_e( 'Email:', 'sugar-calendar' ); ?> <a href="mailto:<?php echo esc_attr( sanitize_email( $email ) ); ?>"><?php echo esc_html( $email ); ?></a></li>
			<?php endif; ?>
			<?php if ( $phone ) : ?>
				<li><?php esc_html_e( 'Phone:', 'sugar-calendar' ); ?> <a href="tel:<?php echo esc_attr( $phone ); ?>"><?php echo esc_html( $phone ); ?></a></li>
			<?php endif; ?>
			<?php if ( $website ) : ?>
				<li><?php esc_html_e( 'Website:', 'sugar-calendar' ); ?> <a href="<?php echo esc_url( $website ); ?>" target="_blank"><?php echo esc_html( $website ); ?></a></li>
			<?php endif; ?>
		</ul>

		<?php if ( $content ) : ?>
			<div><?php echo wp_kses_post( wpautop( $content ) ); ?></div>
		<?php endif; ?>
	</div>
	<?php
}

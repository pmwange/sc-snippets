<?php
/**
 * Plugin Name:       Sugar Snippets
 * Plugin URI:        https://github.com/pmwange/sc-snippets
 * Description:       Add custom PHP snippets (like functions.php). Enable or disable each snippet from the settings page.
 * Author:            Custom
 * Version:           1.1.0
 * Text Domain:       sugar-snippets
 * Requires PHP:      7.4
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'SUGAR_SNIPPETS_VERSION' ) ) {
	define( 'SUGAR_SNIPPETS_VERSION', '1.1.0' );
}
if ( ! defined( 'SUGAR_SNIPPETS_FILE' ) ) {
	define( 'SUGAR_SNIPPETS_FILE', __FILE__ );
}
if ( ! defined( 'SUGAR_SNIPPETS_DIR' ) ) {
	define( 'SUGAR_SNIPPETS_DIR', plugin_dir_path( __FILE__ ) );
}

/**
 * Get list of available snippet keys and file paths.
 *
 * @return array<string, string> Map of snippet_key => absolute file path.
 */
function sugar_snippets_get_available() {
	$dir   = SUGAR_SNIPPETS_DIR;
	$list  = array();

	$main_file = $dir . 'snippets.php';
	if ( is_readable( $main_file ) ) {
		$list['main'] = $main_file;
	}

	$snippets_folder = $dir . 'snippets';
	if ( is_dir( $snippets_folder ) ) {
		$files = glob( $snippets_folder . '/*.php' );
		if ( is_array( $files ) ) {
			foreach ( $files as $file ) {
				if ( is_readable( $file ) ) {
					$key = basename( $file, '.php' );
					$list[ $key ] = $file;
				}
			}
		}
	}

	return $list;
}

/**
 * Get enabled snippet keys from options. Empty array means "all enabled" for backwards compatibility.
 *
 * @return array<string>
 */
function sugar_snippets_get_enabled() {
	$enabled = get_option( 'sugar_snippets_enabled', array() );
	if ( ! is_array( $enabled ) ) {
		return array();
	}
	return $enabled;
}

/**
 * Load only enabled snippets.
 */
add_action( 'plugins_loaded', function () {
	$available = sugar_snippets_get_available();
	$enabled   = sugar_snippets_get_enabled();

	// If no preference saved yet, enable all (backwards compatible).
	if ( empty( $enabled ) ) {
		$enabled = array_keys( $available );
	}

	foreach ( $enabled as $key ) {
		if ( isset( $available[ $key ] ) && is_readable( $available[ $key ] ) ) {
			require_once $available[ $key ];
		}
	}
}, 1 );

// Admin: settings page.
if ( is_admin() ) {
	require_once SUGAR_SNIPPETS_DIR . 'includes/class-sugar-snippets-admin.php';
	Sugar_Snippets_Admin::init();
}

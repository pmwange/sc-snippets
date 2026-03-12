<?php
/**
 * Plugin Name:       Custom Snippets
 * Plugin URI:        https://github.com/sc-test
 * Description:       Add custom PHP snippets (like functions.php). When active, all snippets are loaded and run.
 * Author:            Custom
 * Version:           1.0.0
 * Text Domain:       sc-snippets
 * Requires PHP:      7.4
 */

defined( 'ABSPATH' ) || exit;

/**
 * Load all snippets when the plugin is active.
 * 1. Loads snippets.php (main file, like theme functions.php)
 * 2. Loads every .php file inside the snippets/ folder
 */
add_action( 'plugins_loaded', function () {
	$dir = plugin_dir_path( __FILE__ );

	// Main snippets file (like functions.php)
	$snippets_file = $dir . 'snippets.php';
	if ( is_readable( $snippets_file ) ) {
		require_once $snippets_file;
	}

	// Load each .php file in the snippets/ folder
	$snippets_folder = $dir . 'snippets';
	if ( is_dir( $snippets_folder ) ) {
		$files = glob( $snippets_folder . '/*.php' );
		if ( is_array( $files ) ) {
			foreach ( $files as $file ) {
				if ( is_readable( $file ) ) {
					require_once $file;
				}
			}
		}
	}
}, 1 );

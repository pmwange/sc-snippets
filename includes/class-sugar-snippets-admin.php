<?php
/**
 * Sugar Snippets — admin settings page.
 *
 * @package Sugar_Snippets
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Sugar_Snippets_Admin
 */
class Sugar_Snippets_Admin {

	/**
	 * Option key for enabled snippet keys.
	 *
	 * @var string
	 */
	const OPTION_ENABLED = 'sugar_snippets_enabled';

	/**
	 * Hook into WordPress.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'save_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
	}

	/**
	 * Add Settings submenu page.
	 */
	public static function add_menu() {
		add_options_page(
			__( 'Sugar Snippets', 'sugar-snippets' ),
			__( 'Sugar Snippets', 'sugar-snippets' ),
			'manage_options',
			'sugar-snippets',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Enqueue admin styles.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public static function enqueue_styles( $hook_suffix ) {
		if ( $hook_suffix !== 'settings_page_sugar-snippets' ) {
			return;
		}
		wp_add_inline_style( 'wp-admin', self::get_inline_css() );
	}

	/**
	 * Inline CSS for the snippets list.
	 *
	 * @return string
	 */
	private static function get_inline_css() {
		return '
			.sugar-snippets-list { max-width: 640px; margin: 1em 0; }
			.sugar-snippets-list .snippet-row { padding: 10px 12px; margin: 0 0 2px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; }
			.sugar-snippets-list .snippet-row label { display: flex; align-items: center; gap: 10px; cursor: pointer; }
			.sugar-snippets-list .snippet-row .snippet-key { font-family: monospace; color: #1d2327; }
			.sugar-snippets-list .snippet-row .snippet-file { font-size: 12px; color: #646970; }
			.sugar-snippets-saved { margin: 15px 0 0; }
		';
	}

	/**
	 * Save enabled snippets from POST.
	 */
	public static function save_settings() {
		if ( ! isset( $_POST['sugar_snippets_nonce'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sugar_snippets_nonce'] ) ), 'sugar_snippets_save' ) ) {
			return;
		}

		$available = sugar_snippets_get_available();
		$posted    = isset( $_POST['sugar_snippets_enabled'] ) && is_array( $_POST['sugar_snippets_enabled'] )
			? array_map( 'sanitize_text_field', wp_unslash( $_POST['sugar_snippets_enabled'] ) )
			: array();
		$enabled   = array_intersect( array_keys( $available ), $posted );
		update_option( self::OPTION_ENABLED, array_values( $enabled ) );

		wp_safe_redirect( add_query_arg( 'sugar_snippets_saved', '1', self::get_settings_url() ) );
		exit;
	}

	/**
	 * URL of the settings page.
	 *
	 * @return string
	 */
	private static function get_settings_url() {
		return admin_url( 'options-general.php?page=sugar-snippets' );
	}

	/**
	 * Render the settings page.
	 */
	public static function render_page() {
		$available = sugar_snippets_get_available();
		$enabled   = get_option( self::OPTION_ENABLED, array() );
		if ( ! is_array( $enabled ) ) {
			$enabled = array();
		}
		// First time: no option saved => enable all.
		$is_first_run = get_option( 'sugar_snippets_enabled' ) === false;
		if ( $is_first_run && ! empty( $available ) ) {
			$enabled = array_keys( $available );
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Sugar Snippets', 'sugar-snippets' ); ?></h1>
			<p><?php esc_html_e( 'Choose which snippets are active. Only enabled snippets are loaded on the site.', 'sugar-snippets' ); ?></p>

			<?php if ( isset( $_GET['sugar_snippets_saved'] ) ) : ?>
				<div class="notice notice-success is-dismissible sugar-snippets-saved">
					<p><?php esc_html_e( 'Settings saved.', 'sugar-snippets' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="">
				<?php wp_nonce_field( 'sugar_snippets_save', 'sugar_snippets_nonce' ); ?>
				<div class="sugar-snippets-list">
					<?php foreach ( $available as $key => $path ) : ?>
						<?php
						$label = $key === 'main'
							? __( 'Main snippets (snippets.php)', 'sugar-snippets' )
							: $key;
						$file_display = $key === 'main' ? 'snippets.php' : 'snippets/' . $key . '.php';
						$checked = in_array( $key, $enabled, true );
						?>
						<div class="snippet-row">
							<label>
								<input type="checkbox" name="sugar_snippets_enabled[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $checked ); ?> />
								<span class="snippet-key"><?php echo esc_html( $label ); ?></span>
								<span class="snippet-file"><?php echo esc_html( $file_display ); ?></span>
							</label>
						</div>
					<?php endforeach; ?>
				</div>
				<?php if ( ! empty( $available ) ) : ?>
					<p class="submit">
						<?php submit_button( __( 'Save snippet selection', 'sugar-snippets' ), 'primary', 'submit', false ); ?>
					</p>
				<?php else : ?>
					<p><?php esc_html_e( 'No snippet files found. Add snippets.php or .php files in the snippets/ folder.', 'sugar-snippets' ); ?></p>
				<?php endif; ?>
			</form>
		</div>
		<?php
	}
}

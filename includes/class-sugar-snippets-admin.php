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
			.sugar-snippets-list { max-width: 720px; margin: 1em 0; }

			/* Folder group */
			.ss-folder { margin: 0 0 12px; border: 1px solid #c3c4c7; border-radius: 6px; background: #fff; overflow: hidden; }
			.ss-folder-header { display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: #f6f7f7; border-bottom: 1px solid #c3c4c7; cursor: pointer; user-select: none; }
			.ss-folder-header:hover { background: #eff0f1; }
			.ss-folder-toggle { width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; transition: transform .15s ease; flex-shrink: 0; }
			.ss-folder-toggle svg { fill: #50575e; }
			.ss-folder.is-collapsed .ss-folder-toggle { transform: rotate(-90deg); }
			.ss-folder.is-collapsed .ss-folder-body { display: none; }
			.ss-folder-check { flex-shrink: 0; }
			.ss-folder-name { font-weight: 600; color: #1d2327; flex: 1; }
			.ss-folder-count { font-size: 12px; color: #646970; white-space: nowrap; }

			/* Snippet row */
			.ss-folder-body { padding: 0; }
			.ss-snippet-row { border-top: 1px solid #e0e0e0; }
			.ss-snippet-row:first-child { border-top: none; }
			.ss-snippet { display: flex; align-items: center; gap: 10px; padding: 9px 14px 9px 44px; }
			.ss-snippet label { display: flex; align-items: center; gap: 10px; cursor: pointer; flex: 1; min-width: 0; }
			.ss-snippet .ss-name { font-family: monospace; color: #1d2327; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
			.ss-snippet .ss-file { font-size: 12px; color: #646970; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

			/* Preview toggle button */
			.ss-preview-btn { background: none; border: 1px solid #c3c4c7; border-radius: 4px; padding: 2px 6px; cursor: pointer; color: #50575e; line-height: 1; flex-shrink: 0; display: flex; align-items: center; gap: 4px; font-size: 12px; }
			.ss-preview-btn:hover { background: #f0f0f1; color: #1d2327; border-color: #8c8f94; }
			.ss-preview-btn svg { width: 14px; height: 14px; fill: currentColor; }

			/* Code preview panel */
			.ss-preview { display: none; position: relative; background: #f6f7f7; border-top: 1px solid #e0e0e0; padding: 0; margin: 0; }
			.ss-preview.is-open { display: block; }
			.ss-preview pre { margin: 0; padding: 12px 14px 12px 44px; font-size: 12px; line-height: 1.6; overflow-x: auto; white-space: pre; color: #1d2327; max-height: 400px; }
			.ss-preview-copy { position: absolute; top: 8px; right: 12px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 4px 8px; cursor: pointer; color: #50575e; font-size: 11px; display: flex; align-items: center; gap: 4px; line-height: 1; z-index: 1; }
			.ss-preview-copy:hover { background: #f0f0f1; color: #1d2327; border-color: #8c8f94; }
			.ss-preview-copy svg { width: 14px; height: 14px; fill: currentColor; }
			.ss-preview-copy.is-copied { color: #00a32a; border-color: #00a32a; }

			/* Top-level (no folder) group */
			.ss-folder--root > .ss-folder-header .ss-folder-name::before { content: none; }

			/* Success notice */
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

		// Only allow keys that actually exist on disk.
		$enabled = array_values( array_intersect( array_keys( $available ), $posted ) );
		update_option( self::OPTION_ENABLED, $enabled );

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
	 * Group snippet keys by folder.
	 *
	 * Returns an ordered array of groups:
	 *   [ 'folder' => 'snippets/', 'snippets' => [ key => path, ... ] ]
	 *
	 * Top-level snippets (including "main") go into a virtual "snippets/" root group.
	 *
	 * @param array<string, string> $available Available snippets.
	 * @return array
	 */
	private static function group_by_folder( $available ) {
		$groups = array();

		foreach ( $available as $key => $path ) {
			$slash = strrpos( $key, '/' );
			if ( $slash !== false ) {
				$folder = substr( $key, 0, $slash );
			} else {
				$folder = '';
			}

			if ( ! isset( $groups[ $folder ] ) ) {
				$groups[ $folder ] = array();
			}
			$groups[ $folder ][ $key ] = $path;
		}

		// Sort: root first, then alphabetical subfolders.
		uksort( $groups, function ( $a, $b ) {
			if ( $a === '' ) return -1;
			if ( $b === '' ) return 1;
			return strcasecmp( $a, $b );
		} );

		return $groups;
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

		// First time: no option saved => enable only top-level snippets.
		$is_first_run = get_option( self::OPTION_ENABLED ) === false;
		if ( $is_first_run && ! empty( $available ) ) {
			$enabled = array_filter( array_keys( $available ), function ( $key ) {
				return ! sugar_snippets_is_subfolder_snippet( $key );
			} );
		}

		$groups = self::group_by_folder( $available );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Sugar Snippets', 'sugar-snippets' ); ?></h1>
			<p><?php esc_html_e( 'Choose which snippets are active. Only enabled snippets are loaded on the site.', 'sugar-snippets' ); ?></p>

			<?php if ( isset( $_GET['sugar_snippets_saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible sugar-snippets-saved">
					<p><?php esc_html_e( 'Settings saved.', 'sugar-snippets' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="">
				<?php wp_nonce_field( 'sugar_snippets_save', 'sugar_snippets_nonce' ); ?>

				<?php if ( ! empty( $available ) ) : ?>
					<div class="sugar-snippets-list">
						<?php foreach ( $groups as $folder => $snippets ) :
							$folder_id    = $folder === '' ? 'root' : sanitize_title( $folder );
							$folder_label = $folder === '' ? 'snippets/' : 'snippets/' . $folder . '/';
							$active_count = count( array_filter( array_keys( $snippets ), function ( $k ) use ( $enabled ) {
								return in_array( $k, $enabled, true );
							} ) );
							$total_count  = count( $snippets );
							$all_checked  = $active_count === $total_count;
							$some_checked = $active_count > 0 && ! $all_checked;
							$is_root      = $folder === '';
						?>
							<div class="ss-folder <?php echo $is_root ? 'ss-folder--root' : ''; ?>" data-folder="<?php echo esc_attr( $folder_id ); ?>">
								<div class="ss-folder-header" role="button" tabindex="0" aria-expanded="true">
									<span class="ss-folder-toggle">
										<svg width="16" height="16" viewBox="0 0 20 20"><path d="M5 6l5 5 5-5 2 1-7 7-7-7z"/></svg>
									</span>
									<input
										type="checkbox"
										class="ss-folder-check"
										data-folder-toggle="<?php echo esc_attr( $folder_id ); ?>"
										<?php checked( $all_checked ); ?>
										<?php if ( $some_checked ) : ?>data-indeterminate="1"<?php endif; ?>
									/>
									<span class="ss-folder-name"><?php echo esc_html( $folder_label ); ?></span>
									<span class="ss-folder-count">
										<?php
										printf(
											/* translators: %1$d: active count, %2$d: total count */
											esc_html__( '%1$d of %2$d active', 'sugar-snippets' ),
											$active_count,
											$total_count
										);
										?>
									</span>
								</div>
								<div class="ss-folder-body">
									<?php foreach ( $snippets as $key => $path ) :
										$slash    = strrpos( $key, '/' );
										$basename = $slash !== false ? substr( $key, $slash + 1 ) : $key;
										$label    = $key === 'main'
											? __( 'Main snippets (snippets.php)', 'sugar-snippets' )
											: $basename;
										$file_display = $key === 'main' ? 'snippets.php' : 'snippets/' . $key . '.php';
										$checked      = in_array( $key, $enabled, true );
										$snippet_id   = 'ss-preview-' . sanitize_title( $key );
										// Read file contents for preview.
										$file_contents = is_readable( $path ) ? file_get_contents( $path ) : '';
									?>
										<div class="ss-snippet-row">
											<div class="ss-snippet">
												<label>
													<input
														type="checkbox"
														name="sugar_snippets_enabled[]"
														value="<?php echo esc_attr( $key ); ?>"
														class="ss-snippet-check"
														data-folder="<?php echo esc_attr( $folder_id ); ?>"
														<?php checked( $checked ); ?>
													/>
													<span class="ss-name"><?php echo esc_html( $label ); ?></span>
													<span class="ss-file"><?php echo esc_html( $file_display ); ?></span>
												</label>
												<button type="button" class="ss-preview-btn" data-preview="<?php echo esc_attr( $snippet_id ); ?>" title="<?php esc_attr_e( 'Preview code', 'sugar-snippets' ); ?>">
													<svg viewBox="0 0 20 20"><path d="M9 2h2v2H9V2zm0 14h2v2H9v-2zm-4.707.707l1.414-1.414L7.12 16.707l-1.414 1.414-1.414-1.414zM14.293 3.293l1.414 1.414L14.293 6.12l-1.414-1.414 1.414-1.414zM3.293 5.707L4.707 4.293 6.12 5.707 4.707 7.12 3.293 5.707zM15.707 14.293l1.414 1.414-1.414 1.414-1.414-1.414 1.414-1.414zM2 9h2v2H2V9zm14 0h2v2h-2V9zM10 6a4 4 0 100 8 4 4 0 000-8zm0 2a2 2 0 110 4 2 2 0 010-4z"/></svg>
													<span><?php esc_html_e( 'View', 'sugar-snippets' ); ?></span>
												</button>
											</div>
											<div class="ss-preview" id="<?php echo esc_attr( $snippet_id ); ?>">
												<button type="button" class="ss-preview-copy" title="<?php esc_attr_e( 'Copy to clipboard', 'sugar-snippets' ); ?>">
													<svg viewBox="0 0 20 20"><path d="M6 2h10a2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2V4a2 2 0 012-2zm0 2v10h10V4H6zM2 8v10a2 2 0 002 2h10v-2H4V8H2z"/></svg>
													<span><?php esc_html_e( 'Copy', 'sugar-snippets' ); ?></span>
												</button>
												<pre><code><?php echo esc_html( $file_contents ); ?></code></pre>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>

					<p class="submit">
						<?php submit_button( __( 'Save snippet selection', 'sugar-snippets' ), 'primary', 'submit', false ); ?>
					</p>
				<?php else : ?>
					<p><?php esc_html_e( 'No snippet files found. Add snippets.php or .php files in the snippets/ folder.', 'sugar-snippets' ); ?></p>
				<?php endif; ?>
			</form>
		</div>

		<script>
		(function() {
			'use strict';

			// --- Collapse / Expand ---
			document.querySelectorAll('.ss-folder-header').forEach(function(header) {
				header.addEventListener('click', function(e) {
					// Don't toggle when clicking the checkbox itself.
					if (e.target.closest('.ss-folder-check')) return;
					header.closest('.ss-folder').classList.toggle('is-collapsed');
				});
				header.addEventListener('keydown', function(e) {
					if (e.key === 'Enter' || e.key === ' ') {
						e.preventDefault();
						header.click();
					}
				});
			});

			// --- Indeterminate state on load ---
			document.querySelectorAll('[data-indeterminate="1"]').forEach(function(cb) {
				cb.indeterminate = true;
			});

			// --- Folder checkbox → toggle all children ---
			document.querySelectorAll('.ss-folder-check').forEach(function(folderCb) {
				folderCb.addEventListener('change', function() {
					var folderId = this.getAttribute('data-folder-toggle');
					var checked  = this.checked;
					document.querySelectorAll('.ss-snippet-check[data-folder="' + folderId + '"]').forEach(function(cb) {
						cb.checked = checked;
					});
					this.indeterminate = false;
					updateCount(folderId);
				});
			});

			// --- Child checkbox → update folder checkbox state ---
			document.querySelectorAll('.ss-snippet-check').forEach(function(cb) {
				cb.addEventListener('change', function() {
					var folderId = this.getAttribute('data-folder');
					syncFolderCheckbox(folderId);
					updateCount(folderId);
				});
			});

			function syncFolderCheckbox(folderId) {
				var children = document.querySelectorAll('.ss-snippet-check[data-folder="' + folderId + '"]');
				var folderCb = document.querySelector('[data-folder-toggle="' + folderId + '"]');
				if (!folderCb || !children.length) return;

				var total   = children.length;
				var checked = 0;
				children.forEach(function(c) { if (c.checked) checked++; });

				folderCb.checked       = checked === total;
				folderCb.indeterminate = checked > 0 && checked < total;
			}

			function updateCount(folderId) {
				var folder = document.querySelector('[data-folder="' + folderId + '"]');
				if (!folder) return;

				var children = folder.querySelectorAll('.ss-snippet-check');
				var active   = 0;
				children.forEach(function(c) { if (c.checked) active++; });

				var countEl = folder.querySelector('.ss-folder-count');
				if (countEl) {
					countEl.textContent = active + ' of ' + children.length + ' active';
				}
			}

			// --- Preview toggle ---
			document.querySelectorAll('.ss-preview-btn').forEach(function(btn) {
				btn.addEventListener('click', function(e) {
					e.preventDefault();
					var targetId = this.getAttribute('data-preview');
					var panel    = document.getElementById(targetId);
					if (!panel) return;
					var isOpen = panel.classList.toggle('is-open');
					this.setAttribute('aria-expanded', isOpen);
				});
			});

			// --- Copy to clipboard ---
			document.querySelectorAll('.ss-preview-copy').forEach(function(btn) {
				btn.addEventListener('click', function(e) {
					e.preventDefault();
					var panel = this.closest('.ss-preview');
					var code  = panel ? panel.querySelector('code') : null;
					if (!code) return;

					var text = code.textContent;
					navigator.clipboard.writeText(text).then(function() {
						var label = btn.querySelector('span');
						var orig  = label.textContent;
						btn.classList.add('is-copied');
						label.textContent = '<?php echo esc_js( __( 'Copied!', 'sugar-snippets' ) ); ?>';
						setTimeout(function() {
							btn.classList.remove('is-copied');
							label.textContent = orig;
						}, 1500);
					});
				});
			});
		})();
		</script>
		<?php
	}
}

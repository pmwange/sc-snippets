<?php
/**
 * Add your own fields to the event editor and show them on the event page.
 *
 * Sugar Calendar has no custom-fields UI. This adds one: edit the array in
 * sc_snippets_custom_event_fields() and each entry becomes a field in a new
 * "Details" tab on the event editor, a value stored against the event, and a
 * row on the single event page.
 *
 * Read a value anywhere in your own code with:
 *   get_event_meta( $event->id, 'your_key', true );
 *
 * Pick keys that Sugar Calendar does not already use. Taken today: audience,
 * capacity, language, location, color, and with the Zoom integration
 * online_provider plus anything starting with meeting_.
 */

defined( 'ABSPATH' ) || exit;

/**
 * The fields to add.
 *
 * Key is the meta key. `type` is text, url or textarea; anything else is
 * treated as text.
 *
 * @return array
 */
if ( ! function_exists( 'sc_snippets_custom_event_fields' ) ) :
function sc_snippets_custom_event_fields() {

	return array(
		'extra_info' => array(
			'label' => __( 'Extra Info', 'sugar-snippets' ),
			'type'  => 'text',
		),
	);
}
endif;

/**
 * Sanitize callback for a field type.
 *
 * @param string $type Field type.
 *
 * @return string Callable name.
 */
if ( ! function_exists( 'sc_snippets_custom_event_field_sanitizer' ) ) :
function sc_snippets_custom_event_field_sanitizer( $type = 'text' ) {

	$map = array(
		'url'      => 'esc_url_raw',
		'textarea' => 'sanitize_textarea_field',
	);

	return isset( $map[ $type ] ) ? $map[ $type ] : 'sanitize_text_field';
}
endif;

/**
 * Add the fields to Sugar Calendar's event meta schema.
 *
 * @param array $schema Existing schema.
 *
 * @return array
 */
if ( ! function_exists( 'sc_snippets_custom_event_fields_schema' ) ) :
function sc_snippets_custom_event_fields_schema( $schema = array() ) {

	foreach ( sc_snippets_custom_event_fields() as $key => $field ) {

		$type = isset( $field['type'] ) ? $field['type'] : 'text';

		$schema[ $key ] = array(
			'type'              => 'string',
			'description'       => '',
			'single'            => true,
			'sanitize_callback' => sc_snippets_custom_event_field_sanitizer( $type ),
			'auth_callback'     => null,
			'show_in_rest'      => false,
		);
	}

	return $schema;
}
endif;

add_filter( 'sugar_calendar_meta_data', 'sc_snippets_custom_event_fields_schema' );

// Sugar Calendar turns that schema into register_meta() calls on init:10, and an
// unregistered key is discarded without warning when the event saves. If this
// snippet loaded after init:10 had already run, register the keys here instead.
// Registering twice is harmless, so no guard is needed for the early case.
if ( did_action( 'init' ) ) {
	foreach ( sc_snippets_custom_event_fields_schema() as $sc_snippets_key => $sc_snippets_args ) {
		register_meta( 'sc_event', $sc_snippets_key, $sc_snippets_args );
	}
	unset( $sc_snippets_key, $sc_snippets_args );
}

/**
 * Add the "Details" tab to the event editor.
 *
 * Order 60 puts it after Location (50).
 *
 * @param \Sugar_Calendar\Admin\Events\Metaboxes\Event $metabox Metabox instance.
 */
if ( ! function_exists( 'sc_snippets_custom_event_fields_section' ) ) :
function sc_snippets_custom_event_fields_section( $metabox ) {

	if ( ! is_object( $metabox ) || ! method_exists( $metabox, 'add_section' ) ) {
		return;
	}

	$metabox->add_section(
		array(
			'id'       => 'sc-snippets-details',
			'label'    => __( 'Details', 'sugar-snippets' ),
			'icon'     => 'edit',
			'order'    => 60,
			'callback' => 'sc_snippets_custom_event_fields_render',
		)
	);
}
endif;

add_action( 'sugar_calendar_admin_meta_box_setup_sections', 'sc_snippets_custom_event_fields_section' );

/**
 * Render the fields inside the Details tab.
 *
 * @param \Sugar_Calendar\Event $event Event being edited. Empty object on add-new.
 */
if ( ! function_exists( 'sc_snippets_custom_event_fields_render' ) ) :
function sc_snippets_custom_event_fields_render( $event = null ) {

	$event_id = ( is_object( $event ) && ! empty( $event->id ) ) ? $event->id : 0;

	foreach ( sc_snippets_custom_event_fields() as $key => $field ) {

		$value = $event_id ? get_event_meta( $event_id, $key, true ) : '';
		$type  = isset( $field['type'] ) ? $field['type'] : 'text';
		$label = isset( $field['label'] ) ? $field['label'] : $key;
		?>
		<div class="sugar-calendar-metabox__field-row">
			<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
			<div class="sugar-calendar-metabox__field">
				<?php if ( $type === 'textarea' ) : ?>
					<textarea name="<?php echo esc_attr( $key ); ?>"
							  id="<?php echo esc_attr( $key ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
				<?php else : ?>
					<input type="<?php echo $type === 'url' ? 'url' : 'text'; ?>"
						   name="<?php echo esc_attr( $key ); ?>"
						   id="<?php echo esc_attr( $key ); ?>"
						   value="<?php echo esc_attr( $value ); ?>" />
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
endif;

/**
 * Add the posted values to the event save payload.
 *
 * Two things this deliberately does not do, both matching core's own
 * add_location_to_save():
 *
 * - No nonce or capability check. Metaboxes::can_save_meta_box() verifies the
 *   nonce and edit_post before this filter is ever reached.
 * - No wp_unslash() and no sanitizing. update_metadata() unslashes, then runs
 *   the sanitize_callback registered above. Doing either here would sanitize a
 *   still-slashed value, or unslash it twice.
 *
 * An empty value deletes the stored meta, so clearing a field works.
 *
 * @param array $data Event data being saved.
 *
 * @return array
 */
if ( ! function_exists( 'sc_snippets_custom_event_fields_save' ) ) :
function sc_snippets_custom_event_fields_save( $data ) {

	foreach ( sc_snippets_custom_event_fields() as $key => $field ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		$data[ $key ] = isset( $_POST[ $key ] ) ? $_POST[ $key ] : '';
	}

	return $data;
}
endif;

add_filter( 'sugar_calendar_event_to_save', 'sc_snippets_custom_event_fields_save' );

/**
 * Print the stored values on the single event page.
 *
 * Priority 60 puts these below core's rows (date 20, time 30, location 40,
 * online meeting 42, calendars 50).
 *
 * @param \Sugar_Calendar\Event $event The event being displayed.
 */
if ( ! function_exists( 'sc_snippets_custom_event_fields_display' ) ) :
function sc_snippets_custom_event_fields_display( $event ) {

	if ( ! is_object( $event ) || empty( $event->id ) ) {
		return;
	}

	foreach ( sc_snippets_custom_event_fields() as $key => $field ) {

		$value = get_event_meta( $event->id, $key, true );

		if ( $value === '' || $value === null ) {
			continue;
		}

		$type  = isset( $field['type'] ) ? $field['type'] : 'text';
		$label = isset( $field['label'] ) ? $field['label'] : $key;
		?>
		<div class="sc-frontend-single-event__details-row sc-frontend-single-event__details__<?php echo esc_attr( sanitize_html_class( $key ) ); ?>">
			<div class="sc-frontend-single-event__details__label">
				<?php echo esc_html( $label ); ?>:
			</div>
			<div class="sc-frontend-single-event__details__val">
				<?php if ( $type === 'url' ) : ?>
					<a href="<?php echo esc_url( $value ); ?>" rel="nofollow noopener"><?php echo esc_html( $value ); ?></a>
				<?php else : ?>
					<?php echo nl2br( esc_html( $value ) ); ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
endif;

add_action( 'sugar_calendar_frontend_event_details', 'sc_snippets_custom_event_fields_display', 60 );

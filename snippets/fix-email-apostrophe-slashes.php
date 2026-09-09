<?php
/**
 * Fix backslashes before apostrophes in the Order, Ticket and admin sale email
 * subject and message settings.
 *
 * When you type an apostrophe (') in Sugar Calendar's Order or Ticket email
 * settings, the value can be stored with a backslash (\'). This snippet strips
 * those slashes when the settings are read, so the sent emails and the admin
 * form show the correct character.
 *
 * Uses the Ticketing setting filter so both subject and message are fixed
 * for Order Receipt and Ticket Receipt emails. Because it filters on read, it also
 * corrects values that were already saved with slashes, with no need to re-enter them.
 *
 * OBSOLETE as of core commit e23947ef (2026-03-20, "Apostrophe character has slash in
 * Order and Ticket emails, fix #482"). Area::handle_post() now calls wp_unslash() on
 * $_POST before firing sugar_calendar_admin_area_handle_post, so nothing downstream
 * stores the slash. Verified on the stack 2026-09-08: a slashed $_POST value is stored
 * clean.
 *
 * Still useful for exactly one thing: repairing values that a pre-3.x build already saved
 * with slashes, since it filters on read. One-time repair, not a library snippet.
 *
 * The remaining unslash gap is handle_post_ajax(), which reads $_POST['options'] raw.
 * Its only caller is the sandbox/live toggle, which writes a boolean, so no text field
 * goes through it.
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'sc_et_get_setting', function ( $value, $key, $default, $options ) {
	$email_setting_keys = array(
		'receipt_subject',
		'receipt_message',
		'ticket_subject',
		'ticket_message',
		'ticket_sale_admin_subject',
		'ticket_sale_admin_message',
	);
	if ( in_array( $key, $email_setting_keys, true ) && is_string( $value ) ) {
		return stripslashes( $value );
	}
	return $value;
}, 10, 4 );

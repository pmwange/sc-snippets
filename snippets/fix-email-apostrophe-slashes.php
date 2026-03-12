<?php
/**
 * Fix backslashes before apostrophes in Order and Ticket email subject/message.
 *
 * When you type an apostrophe (') in Sugar Calendar's Order or Ticket email
 * settings, the value can be stored with a backslash (\'). This snippet strips
 * those slashes when the settings are read, so the sent emails and the admin
 * form show the correct character.
 *
 * Uses the Ticketing setting filter so both subject and message are fixed
 * for Order Receipt and Ticket Receipt emails.
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'sc_et_get_setting', function ( $value, $key, $default, $options ) {
	$email_setting_keys = array( 'receipt_subject', 'receipt_message', 'ticket_subject', 'ticket_message' );
	if ( in_array( $key, $email_setting_keys, true ) && is_string( $value ) ) {
		return stripslashes( $value );
	}
	return $value;
}, 10, 4 );

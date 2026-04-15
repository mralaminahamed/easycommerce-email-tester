<?php
/**
 * Uninstall EasyCommerce Email Tester.
 *
 * Removes options and, when configured, the email log table.
 *
 * @since 1.0.0
 * @package EC_Email_Tester
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$settings = get_option( 'ec_email_tester_settings', [] );

if ( ! empty( $settings['logger_delete_on_uninstall'] ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ec-email-tester-logger.php';
	EC_Email_Tester_Logger::drop_table();
}

delete_option( 'ec_email_tester_settings' );

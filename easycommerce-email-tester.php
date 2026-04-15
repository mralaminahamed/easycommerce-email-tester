<?php
/**
 * EasyCommerce Email Tester
 *
 * A developer tool for testing, previewing, and debugging all EasyCommerce
 * email notifications without needing real triggers or live SMTP.
 *
 * @link              https://easycommerce.dev
 * @since             1.0.0
 * @package           EC_Email_Tester
 *
 * @wordpress-plugin
 * Plugin Name:       EasyCommerce Email Tester
 * Plugin URI:        https://easycommerce.dev
 * Description:       A developer tool to test, preview, and debug all EasyCommerce email notifications. Supports dry-run preview, override recipients, and unresolved placeholder detection.
 * Version:           1.0.0
 * Author:            Al Amin Ahamed
 * Author URI:        https://alaminahamed.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       easycommerce-email-tester
 * Domain Path:       /languages
 * Requires at least: 6.5
 * Tested up to:      6.9
 * Requires PHP:      8.0
 * Requires Plugins:  easycommerce
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

define( 'EC_EMAIL_TESTER_VERSION', '1.0.0' );
define( 'EC_EMAIL_TESTER_PATH', plugin_dir_path( __FILE__ ) );
define( 'EC_EMAIL_TESTER_URL', plugin_dir_url( __FILE__ ) );
define( 'EC_EMAIL_TESTER_FILE', __FILE__ );
define( 'EC_EMAIL_TESTER_MIN_PHP_VERSION', '8.0' );

require_once EC_EMAIL_TESTER_PATH . 'includes/class-ec-email-tester-sender.php';
require_once EC_EMAIL_TESTER_PATH . 'includes/class-ec-email-tester-logger.php';
require_once EC_EMAIL_TESTER_PATH . 'includes/api/class-ec-email-tester-api.php';
require_once EC_EMAIL_TESTER_PATH . 'includes/admin/class-ec-email-tester-admin.php';
require_once EC_EMAIL_TESTER_PATH . 'includes/admin/class-ec-email-tester-log-list.php';
require_once EC_EMAIL_TESTER_PATH . 'includes/class-ec-email-tester.php';

register_activation_hook( __FILE__, 'ec_email_tester_activate' );
register_deactivation_hook( __FILE__, 'ec_email_tester_deactivate' );

/**
 * Plugin activation: create the email log table and schedule cron cleanup.
 *
 * @since 1.0.0
 */
function ec_email_tester_activate(): void {
	EC_Email_Tester_Logger::create_table();
	EC_Email_Tester_Logger::schedule_cleanup();
}

/**
 * Plugin deactivation: unschedule the cron cleanup job.
 *
 * @since 1.0.0
 */
function ec_email_tester_deactivate(): void {
	EC_Email_Tester_Logger::unschedule_cleanup();
}

/**
 * Initialise the plugin after all plugins have loaded.
 *
 * @since 1.0.0
 */
function ec_email_tester_init(): void {
	if ( version_compare( PHP_VERSION, EC_EMAIL_TESTER_MIN_PHP_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'ec_email_tester_php_notice' );
		return;
	}

	if ( ! defined( 'EASYCOMMERCE_VERSION' ) ) {
		add_action( 'admin_notices', 'ec_email_tester_dependency_notice' );
		return;
	}

	load_plugin_textdomain( 'easycommerce-email-tester', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	EC_Email_Tester::instance();
}
add_action( 'plugins_loaded', 'ec_email_tester_init' );

/**
 * Admin notice: PHP version requirement not met.
 *
 * @since 1.0.0
 */
function ec_email_tester_php_notice(): void {
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		wp_kses_post(
			sprintf(
				/* translators: 1: Required PHP version 2: Current PHP version */
				__( '<strong>EasyCommerce Email Tester</strong> requires PHP %1$s or higher. Your server is running PHP %2$s.', 'easycommerce-email-tester' ),
				EC_EMAIL_TESTER_MIN_PHP_VERSION,
				PHP_VERSION
			)
		)
	);
}

/**
 * Admin notice: EasyCommerce is not active.
 *
 * @since 1.0.0
 */
function ec_email_tester_dependency_notice(): void {
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		wp_kses_post(
			__( '<strong>EasyCommerce Email Tester</strong> requires the EasyCommerce plugin to be installed and active.', 'easycommerce-email-tester' )
		)
	);
}

/**
 * Global accessor for the plugin singleton.
 *
 * @since 1.0.0
 *
 * @return EC_Email_Tester
 */
function ec_email_tester(): EC_Email_Tester {
	return EC_Email_Tester::instance();
}

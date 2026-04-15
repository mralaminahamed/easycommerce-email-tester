<?php
/**
 * Core singleton for EasyCommerce Email Tester.
 *
 * @since   1.0.0
 * @package EC_Email_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class EC_Email_Tester
 *
 * Bootstraps the plugin: instantiates all subsystems and wires them together.
 */
class EC_Email_Tester {

	/**
	 * Singleton instance.
	 *
	 * @since 1.0.0
	 * @var EC_Email_Tester|null
	 */
	private static ?EC_Email_Tester $instance = null;

	/**
	 * Plugin version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public string $version = EC_EMAIL_TESTER_VERSION;

	/**
	 * Private constructor — use instance().
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Return (or create) the singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Prevent cloning.
	 *
	 * @since 1.0.0
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup(): void {}

	/**
	 * Initialise all plugin subsystems.
	 *
	 * @since 1.0.0
	 */
	private function init(): void {
		new EC_Email_Tester_API();
		new EC_Email_Tester_Logger();
		$this->init_admin();
		$this->register_hooks();
	}

	/**
	 * Instantiate the admin page.
	 *
	 * @since 1.0.0
	 */
	private function init_admin(): void {
		new EC_Email_Tester_Admin();
	}

	/**
	 * Register miscellaneous plugin hooks.
	 *
	 * @since 1.0.0
	 */
	private function register_hooks(): void {
		add_filter(
			'plugin_action_links_' . plugin_basename( EC_EMAIL_TESTER_FILE ),
			[ $this, 'add_action_links' ]
		);
	}

	/**
	 * Add "Tester" action link on the plugins list screen.
	 *
	 * @since 1.0.0
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public function add_action_links( array $links ): array {
		$tester_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=easycommerce-email-tester' ) ),
			esc_html__( 'Open Tester', 'easycommerce-email-tester' )
		);

		array_unshift( $links, $tester_link );

		return $links;
	}
}

<?php
/**
 * Admin controller for EasyCommerce Email Tester.
 *
 * Registers a top-level "Email Tester" admin menu with three sub-pages:
 *   - Dashboard  (?page=easycommerce-email-tester)
 *   - Testing    (?page=easycommerce-email-tester-testing)
 *   - Settings   (?page=easycommerce-email-tester-settings)
 *
 * Handles menu registration, asset enqueuing, form processing, settings
 * persistence, and template dispatch. All HTML markup lives in templates/admin/.
 *
 * @since   1.0.0
 * @package EC_Email_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class EC_Email_Tester_Admin
 */
class EC_Email_Tester_Admin {

	/**
	 * Option key used to persist plugin settings.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const SETTINGS_OPTION = 'ec_email_tester_settings';

	/**
	 * Admin page hook suffixes returned by add_menu_page() / add_submenu_page().
	 *
	 * @since 1.0.0
	 * @var string[]
	 */
	private array $page_hooks = [];

	/**
	 * Constructor — register WordPress hooks.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'dequeue_foreign_assets' ], PHP_INT_MAX );
		add_filter( 'admin_body_class', [ $this, 'filter_admin_body_class' ], PHP_INT_MAX );
	}

	// -------------------------------------------------------------------------
	// Menu & assets
	// -------------------------------------------------------------------------

	/**
	 * Register the top-level "Email Tester" menu and its three sub-pages.
	 *
	 * @since 1.0.0
	 */
	public function register_menu(): void {
		// Top-level menu — callback renders the Dashboard.
		$hook = add_menu_page(
			__( 'Email Tester', 'easycommerce-email-tester' ),
			__( 'Email Tester', 'easycommerce-email-tester' ),
			'manage_options',
			'easycommerce-email-tester',
			[ $this, 'render_dashboard' ],
			'dashicons-email-alt2',
			56
		);

		if ( $hook ) {
			$this->page_hooks[] = $hook;
		}

		// Rename the auto-generated first submenu item from "Email Tester" → "Dashboard".
		// add_submenu_page() returns false here (duplicate slug) — no hook to store.
		add_submenu_page(
			'easycommerce-email-tester',
			__( 'Dashboard — Email Tester', 'easycommerce-email-tester' ),
			__( 'Dashboard', 'easycommerce-email-tester' ),
			'manage_options',
			'easycommerce-email-tester',
			[ $this, 'render_dashboard' ]
		);

		// Testing sub-page.
		$hook = add_submenu_page(
			'easycommerce-email-tester',
			__( 'Testing — Email Tester', 'easycommerce-email-tester' ),
			__( 'Testing', 'easycommerce-email-tester' ),
			'manage_options',
			'easycommerce-email-tester-testing',
			[ $this, 'render_testing' ]
		);

		if ( $hook ) {
			$this->page_hooks[] = $hook;
		}

		// Logs sub-page — guard against missing table on first install.
		$log_count  = EC_Email_Tester_Logger::table_exists() ? EC_Email_Tester_Logger::count_logs() : 0;
		$logs_label = $log_count > 0
			? sprintf( 'Logs <span class="awaiting-mod">%d</span>', $log_count )
			: 'Logs';

		$hook = add_submenu_page(
			'easycommerce-email-tester',
			__( 'Logs — Email Tester', 'easycommerce-email-tester' ),
			$logs_label,
			'manage_options',
			'easycommerce-email-tester-logs',
			[ $this, 'render_logs' ]
		);

		if ( $hook ) {
			$this->page_hooks[] = $hook;
		}

		// Settings sub-page.
		$hook = add_submenu_page(
			'easycommerce-email-tester',
			__( 'Settings — Email Tester', 'easycommerce-email-tester' ),
			__( 'Settings', 'easycommerce-email-tester' ),
			'manage_options',
			'easycommerce-email-tester-settings',
			[ $this, 'render_settings' ]
		);

		if ( $hook ) {
			$this->page_hooks[] = $hook;
		}
	}

	/**
	 * Enqueue CSS and JS only on the plugin's own admin pages.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( ! in_array( $hook, $this->page_hooks, true ) ) {
			return;
		}

		wp_enqueue_style(
			'ec-email-tester-select2',
			EC_EMAIL_TESTER_URL . 'assets/vendor/select2.min.css',
			[],
			'4.1.0'
		);

		wp_enqueue_style(
			'ec-email-tester-admin',
			EC_EMAIL_TESTER_URL . 'assets/css/admin.css',
			[ 'ec-email-tester-select2' ],
			EC_EMAIL_TESTER_VERSION
		);

		wp_enqueue_script(
			'ec-email-tester-select2',
			EC_EMAIL_TESTER_URL . 'assets/vendor/select2.min.js',
			[ 'jquery' ],
			'4.1.0',
			true
		);

		wp_enqueue_script(
			'ec-email-tester-admin',
			EC_EMAIL_TESTER_URL . 'assets/js/admin.js',
			[ 'jquery', 'ec-email-tester-select2' ],
			EC_EMAIL_TESTER_VERSION,
			true
		);

		wp_localize_script(
			'ec-email-tester-admin',
			'ectAdmin',
			[
				'restUrl' => esc_url_raw( rest_url( EC_Email_Tester_API::NAMESPACE . '/' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			]
		);
	}

	/**
	 * Dequeue styles and scripts registered by other plugins or the active theme
	 * on our own admin pages.
	 *
	 * Assets whose src URL resolves inside wp-includes/ or wp-admin/ (WordPress
	 * core) and assets with our own handle prefix are preserved. Everything else
	 * is dequeued so third-party plugins and themes cannot interfere with the
	 * plugin UI.
	 *
	 * @since 1.0.0
	 * @hooked admin_enqueue_scripts (PHP_INT_MAX)
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public function dequeue_foreign_assets( string $hook ): void {
		if ( ! in_array( $hook, $this->page_hooks, true ) ) {
			return;
		}

		// Base URLs for the two locations we want to strip from.
		$plugins_url = trailingslashit( plugins_url() );        // .../wp-content/plugins/
		$themes_url  = trailingslashit( get_theme_root_uri() ); // .../wp-content/themes/
		$our_url     = EC_EMAIL_TESTER_URL;                     // .../wp-content/plugins/easycommerce-email-tester/

		/**
		 * Returns true if the asset originates from another plugin or a theme.
		 *
		 * Assets with no src (pseudo-handles / inline-only) and assets from our
		 * own plugin are never considered foreign.
		 *
		 * @param string $src Fully-resolved source URL.
		 */
		$is_foreign = static function ( string $src ) use ( $plugins_url, $themes_url, $our_url ): bool {
			// No src → not foreign (pseudo-handle or inline-only).
			if ( '' === $src ) {
				return false;
			}
			// Our own plugin assets → not foreign.
			if ( str_starts_with( $src, $our_url ) ) {
				return false;
			}
			// Another plugin or any theme → foreign.
			return str_starts_with( $src, $plugins_url ) || str_starts_with( $src, $themes_url );
		};

		$styles  = wp_styles();
		$scripts = wp_scripts();

		foreach ( $styles->queue as $handle ) {
			$dep = $styles->registered[ $handle ] ?? null;
			if ( $dep && $is_foreign( (string) ( $dep->src ?? '' ) ) ) {
				wp_dequeue_style( $handle );
			}
		}

		foreach ( $scripts->queue as $handle ) {
			$dep = $scripts->registered[ $handle ] ?? null;
			if ( $dep && $is_foreign( (string) ( $dep->src ?? '' ) ) ) {
				wp_dequeue_script( $handle );
			}
		}
	}

	/**
	 * Filter admin body classes on plugin pages.
	 *
	 * Removes the 'easycommerce' and 'folded' classes (added by EasyCommerce
	 * and WordPress respectively) and adds 'ect-page' so plugin CSS can scope
	 * styles without interference from the host plugin's stylesheet.
	 *
	 * @since 1.0.0
	 * @hooked admin_body_class (PHP_INT_MAX)
	 *
	 * @param string $classes Space-separated list of current admin body classes.
	 * @return string Modified class list.
	 */
	public function filter_admin_body_class( string $classes ): string {
		$screen = get_current_screen();

		if ( null === $screen || ! in_array( $screen->id, $this->page_hooks, true ) ) {
			return $classes;
		}

		$class_list = array_filter(
			explode( ' ', $classes ),
			fn( string $body_class ) => ! in_array( $body_class, [ 'easycommerce', 'folded' ], true )
		);

		$class_list[] = 'ect-page';

		return implode( ' ', $class_list );
	}

	// -------------------------------------------------------------------------
	// Page renderers
	// -------------------------------------------------------------------------

	/**
	 * Render the Dashboard page.
	 *
	 * @since 1.0.0
	 */
	public function render_dashboard(): void {
		$this->render_page_nav();
		$this->load_template(
			'admin/dashboard.php',
			[
				'email_type_count' => count( EC_Email_Tester_Sender::$email_types ),
				'testing_url'      => admin_url( 'admin.php?page=easycommerce-email-tester-testing' ),
				'settings_url'     => admin_url( 'admin.php?page=easycommerce-email-tester-settings' ),
				'settings'         => $this->get_settings(),
			]
		);
	}

	/**
	 * Process the send form (if submitted) then render the Testing page.
	 *
	 * @since 1.0.0
	 */
	public function render_testing(): void {
		$result   = null;
		$settings = $this->get_settings();

		if ( $this->is_send_form_submitted() ) {
			$result = $this->handle_send_submission();
		}

		// When the form has not been submitted yet, pre-populate fields from
		// the saved settings defaults.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above via wp_verify_nonce()
		$form_submitted = isset( $_POST['ec_email_tester_nonce'] );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above via wp_verify_nonce()
		$order_id_value = absint( $_POST['order_id'] ?? 0 );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above via wp_verify_nonce()
		$user_id_value = absint( $_POST['user_id'] ?? 0 );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above via wp_verify_nonce()
		$cart_hash_value = sanitize_text_field( wp_unslash( $_POST['cart_hash'] ?? '' ) );

		$this->render_page_nav();
		$this->load_template(
			'admin/testing.php',
			[
				'email_types'      => EC_Email_Tester_Sender::$email_types,
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above via wp_verify_nonce()
				'selected_type'    => $result['email_type'] ?? sanitize_text_field( wp_unslash( $_POST['email_type'] ?? $settings['default_email_type'] ) ),
				'order_id_value'   => $order_id_value,
				'order_id_option'  => $order_id_value > 0 ? EC_Email_Tester_API::get_order_option( $order_id_value ) : null,
				'user_id_value'    => $user_id_value,
				'user_id_option'   => $user_id_value > 0 ? EC_Email_Tester_API::get_user_option( $user_id_value ) : null,
				'cart_hash_value'  => $cart_hash_value,
				'cart_hash_option' => '' !== $cart_hash_value ? EC_Email_Tester_API::get_cart_option( $cart_hash_value ) : null,
				'override_value'   => $form_submitted
					// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above via wp_verify_nonce()
					? sanitize_email( wp_unslash( $_POST['override_email'] ?? '' ) )
					: $settings['default_override_email'],
				'dry_run_checked'  => $form_submitted
					// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above via wp_verify_nonce()
					? ! empty( $_POST['dry_run'] )
					: $settings['default_dry_run'],
				'result'           => $result,
			]
		);
	}

	/**
	 * Save settings (if submitted) then render the Settings page.
	 *
	 * @since 1.0.0
	 */
	public function render_settings(): void {
		$saved = false;

		if ( $this->is_settings_form_submitted() ) {
			$this->handle_settings_submission();
			$saved = true;
		}

		$this->render_page_nav();
		$this->load_template(
			'admin/settings.php',
			[
				'settings'    => $this->get_settings(),
				'email_types' => EC_Email_Tester_Sender::$email_types,
				'saved'       => $saved,
			]
		);
	}

	/**
	 * Handle single-row log actions (view, delete) and bulk actions, then render the logs page.
	 *
	 * @since 1.0.0
	 */
	public function render_logs(): void {
		$notice      = null;
		$notice_type = 'success';

		// --- Single-row action (view / delete via GET) ---
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce is verified below via wp_verify_nonce()
		$log_action = sanitize_text_field( wp_unslash( $_GET['log_action'] ?? '' ) );
		$log_id     = absint( $_GET['log_id'] ?? 0 );

		if ( 'view' === $log_action && $log_id > 0 ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- sanitize_text_field handles slashing
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'ec_email_tester_log_view_' . $log_id ) ) {
				wp_die( esc_html__( 'Security check failed.', 'easycommerce-email-tester' ) );
			}

			$log = EC_Email_Tester_Logger::get_log( $log_id );

			if ( ! $log ) {
				wp_die( esc_html__( 'Log entry not found.', 'easycommerce-email-tester' ) );
			}

			$back_url = admin_url( 'admin.php?page=easycommerce-email-tester-logs' );
			$this->render_page_nav();
			$this->load_template( 'admin/log-view.php', compact( 'log', 'back_url' ) );
			return;
		}

		if ( 'delete' === $log_action && $log_id > 0 ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'ec_email_tester_log_delete_' . $log_id ) ) {
				wp_die( esc_html__( 'Security check failed.', 'easycommerce-email-tester' ) );
			}

			EC_Email_Tester_Logger::delete_log( $log_id );
			$notice = __( 'Log entry deleted.', 'easycommerce-email-tester' );

			wp_safe_redirect(
				add_query_arg(
					[
						'page'       => 'easycommerce-email-tester-logs',
						'ect_notice' => 'deleted',
					],
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		// --- Clear-all form (POST) ---
		if ( isset( $_POST['ec_log_action'] ) && 'clear_all' === $_POST['ec_log_action'] ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ec_clear_logs_nonce'] ?? '' ) ), 'ec_email_tester_clear_logs' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'easycommerce-email-tester' ) );
			}

			EC_Email_Tester_Logger::truncate();
			wp_safe_redirect(
				add_query_arg(
					[
						'page'       => 'easycommerce-email-tester-logs',
						'ect_notice' => 'cleared',
					],
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		// --- Bulk delete (list table form — method="get", so read from $_REQUEST) ---
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce checked below via check_admin_referer()
		$bulk_action = sanitize_text_field( wp_unslash( $_REQUEST['action'] ?? '' ) );
		if ( '' === $bulk_action || '-1' === $bulk_action ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce checked below via check_admin_referer()
			$bulk_action = sanitize_text_field( wp_unslash( $_REQUEST['action2'] ?? '' ) );
		}

		if ( 'delete' === $bulk_action && ! empty( $_REQUEST['log_ids'] ) ) {
			check_admin_referer( 'bulk-logs' );
			$ids = array_map( 'absint', (array) $_REQUEST['log_ids'] );
			EC_Email_Tester_Logger::delete_logs( $ids );
			wp_safe_redirect(
				add_query_arg(
					[
						'page'       => 'easycommerce-email-tester-logs',
						'ect_notice' => 'bulk_deleted',
					],
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		// --- Redirect notice (after GET redirect) ---
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display of redirect notice, no state change
		$redirect_notice = sanitize_text_field( wp_unslash( $_GET['ect_notice'] ?? '' ) );
		$notice_map      = [
			'deleted'      => __( 'Log entry deleted.', 'easycommerce-email-tester' ),
			'cleared'      => __( 'All log entries cleared.', 'easycommerce-email-tester' ),
			'bulk_deleted' => __( 'Selected log entries deleted.', 'easycommerce-email-tester' ),
		];

		if ( isset( $notice_map[ $redirect_notice ] ) ) {
			$notice = $notice_map[ $redirect_notice ];
		}

		// --- Build and render the list table ---
		$list_table = new EC_Email_Tester_Log_List();
		$list_table->prepare_items();

		$this->render_page_nav();
		$this->load_template( 'admin/logs.php', compact( 'list_table', 'notice', 'notice_type' ) );
	}

	// -------------------------------------------------------------------------
	// Send form handling
	// -------------------------------------------------------------------------

	/**
	 * Whether the send form has been submitted with a valid nonce.
	 *
	 * @since 1.0.0
	 */
	private function is_send_form_submitted(): bool {
		if ( ! isset( $_POST['ec_email_tester_nonce'] ) ) {
			return false;
		}

		return (bool) wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_POST['ec_email_tester_nonce'] ) ),
			'ec_email_tester_send'
		);
	}

	/**
	 * Read POST data, run the sender, and return the result array.
	 *
	 * @since 1.0.0
	 */
	private function handle_send_submission(): array {
		$sender = new EC_Email_Tester_Sender(
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in is_send_form_submitted()
			sanitize_email( wp_unslash( $_POST['override_email'] ?? '' ) ),
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in is_send_form_submitted()
			! empty( $_POST['dry_run'] )
		);

		// Signal the logger that the following wp_mail() calls are test sends.
		do_action( 'ec_email_tester_before_test_send' );

		$result = $sender->send(
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in is_send_form_submitted()
			sanitize_text_field( wp_unslash( $_POST['email_type'] ?? '' ) ),
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in is_send_form_submitted()
			absint( $_POST['order_id'] ?? 0 ),
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in is_send_form_submitted()
			absint( $_POST['user_id'] ?? 0 ),
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in is_send_form_submitted()
			sanitize_text_field( wp_unslash( $_POST['cart_hash'] ?? '' ) )
		);

		do_action( 'ec_email_tester_after_test_send' );

		return $result;
	}

	// -------------------------------------------------------------------------
	// Settings form handling
	// -------------------------------------------------------------------------

	/**
	 * Whether the settings form has been submitted with a valid nonce.
	 *
	 * @since 1.0.0
	 */
	private function is_settings_form_submitted(): bool {
		if ( ! isset( $_POST['ec_email_tester_settings_nonce'] ) ) {
			return false;
		}

		return (bool) wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_POST['ec_email_tester_settings_nonce'] ) ),
			'ec_email_tester_settings_save'
		);
	}

	/**
	 * Persist submitted settings to the database.
	 *
	 * @since 1.0.0
	 */
	private function handle_settings_submission(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in is_settings_form_submitted()
		$submitted_type   = sanitize_text_field( wp_unslash( $_POST['default_email_type'] ?? '' ) );
		$valid_email_type = array_key_exists( $submitted_type, EC_Email_Tester_Sender::$email_types )
			? $submitted_type
			: 'order_pending';

		update_option(
			self::SETTINGS_OPTION,
			[
				// Testing defaults.
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in is_settings_form_submitted()
				'default_override_email'         => sanitize_email( wp_unslash( $_POST['default_override_email'] ?? '' ) ),
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in is_settings_form_submitted()
				'default_dry_run'                => ! empty( $_POST['default_dry_run'] ),
				'default_email_type'             => $valid_email_type,
				// Logger settings.
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in is_settings_form_submitted()
				'logger_enabled'                 => ! empty( $_POST['logger_enabled'] ),
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in is_settings_form_submitted()
				'logger_log_test_emails'         => ! empty( $_POST['logger_log_test_emails'] ),
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in is_settings_form_submitted()
				'logger_retention_count_enabled' => ! empty( $_POST['logger_retention_count_enabled'] ),
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in is_settings_form_submitted()
				'logger_retention_count'         => max( 1, absint( $_POST['logger_retention_count'] ?? 500 ) ),
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in is_settings_form_submitted()
				'logger_retention_days_enabled'  => ! empty( $_POST['logger_retention_days_enabled'] ),
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in is_settings_form_submitted()
				'logger_retention_days'          => max( 1, absint( $_POST['logger_retention_days'] ?? 30 ) ),
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in is_settings_form_submitted()
				'logger_delete_on_uninstall'     => ! empty( $_POST['logger_delete_on_uninstall'] ),
			]
		);
	}

	/**
	 * Return the saved settings merged with defaults.
	 *
	 * @since 1.0.0
	 *
	 * @return array{
	 *     default_override_email: string,
	 *     default_dry_run: bool,
	 *     default_email_type: string,
	 *     logger_enabled: bool,
	 *     logger_log_test_emails: bool,
	 *     logger_retention_count_enabled: bool,
	 *     logger_retention_count: int,
	 *     logger_retention_days_enabled: bool,
	 *     logger_retention_days: int,
	 *     logger_delete_on_uninstall: bool,
	 * }
	 */
	private function get_settings(): array {
		$defaults = [
			'default_override_email'         => '',
			'default_dry_run'                => false,
			'default_email_type'             => 'order_pending',
			'logger_enabled'                 => true,
			'logger_log_test_emails'         => true,
			'logger_retention_count_enabled' => true,
			'logger_retention_count'         => 500,
			'logger_retention_days_enabled'  => false,
			'logger_retention_days'          => 30,
			'logger_delete_on_uninstall'     => false,
		];

		$saved = get_option( self::SETTINGS_OPTION, [] );

		return wp_parse_args( is_array( $saved ) ? $saved : [], $defaults );
	}

	// -------------------------------------------------------------------------
	// Template loader
	// -------------------------------------------------------------------------

	/**
	 * Load a template file from the templates/ directory.
	 *
	 * All entries in $args are extracted into the template's local scope.
	 * Using EXTR_SKIP so we never overwrite variables already in scope.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $template Relative path inside templates/ (e.g. 'admin/testing.php').
	 * @param array<string, mixed> $args     Variables to expose inside the template.
	 */
	private function load_template( string $template, array $args = [] ): void {
		$path = EC_EMAIL_TESTER_PATH . 'templates/' . $template;

		if ( ! file_exists( $path ) ) {
			return;
		}

		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $args, EXTR_SKIP );

		include $path;
	}

	// -------------------------------------------------------------------------
	// Page nav
	// -------------------------------------------------------------------------

	/**
	 * Render the shared horizontal tab bar shown above every page.
	 *
	 * @since 1.0.0
	 */
	public function render_page_nav(): void {
		$current = sanitize_text_field( wp_unslash( $_GET['page'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$nav_items = [
			'easycommerce-email-tester'          => [
				'label' => __( 'Dashboard', 'easycommerce-email-tester' ),
				'icon'  => 'mail',
			],
			'easycommerce-email-tester-testing'  => [
				'label' => __( 'Testing', 'easycommerce-email-tester' ),
				'icon'  => 'send',
			],
			'easycommerce-email-tester-logs'     => [
				'label' => __( 'Logs', 'easycommerce-email-tester' ),
				'icon'  => 'list',
			],
			'easycommerce-email-tester-settings' => [
				'label' => __( 'Settings', 'easycommerce-email-tester' ),
				'icon'  => 'settings',
			],
		];

		echo '<nav class="ect-page-nav" aria-label="' . esc_attr__( 'Email Tester Navigation', 'easycommerce-email-tester' ) . '">';
		foreach ( $nav_items as $slug => $item ) {
			$is_active = ( $current === $slug );
			printf(
				'<a href="%s" class="ect-page-nav__item%s">%s<span>%s</span></a>',
				esc_url( admin_url( 'admin.php?page=' . $slug ) ),
				$is_active ? ' is-active' : '',
				EC_Email_Tester_Icons::get( $item['icon'] ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html( $item['label'] )
			);
		}
		echo '</nav>';
	}

	// -------------------------------------------------------------------------
	// Static helper (callable from templates)
	// -------------------------------------------------------------------------

	/**
	 * Wrap a resolved email body in a minimal HTML document for iframe rendering.
	 *
	 * Declared public static so templates can call EC_Email_Tester_Admin::wrap_preview_html().
	 *
	 * @since 1.0.0
	 *
	 * @param string $body Resolved email body HTML.
	 * @return string Full HTML document string safe to use as an iframe srcdoc value.
	 */
	public static function wrap_preview_html( string $body ): string {
		$styles = 'body{font-family:sans-serif;font-size:14px;color:#333;padding:16px;margin:0;}'
			. 'a{color:#0073aa;}'
			. 'ul,ol{padding-left:20px;}'
			. 'table{border-collapse:collapse;width:100%;}'
			. 'td,th{padding:6px 10px;border:1px solid #ddd;}';

		return '<!DOCTYPE html><html><head><meta charset="UTF-8">'
			. '<style>' . $styles . '</style>'
			. '</head><body>'
			. wp_kses_post( wpautop( $body ) )
			. '</body></html>';
	}
}

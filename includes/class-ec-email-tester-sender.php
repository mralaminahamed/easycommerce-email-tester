<?php
/**
 * Email trigger and capture logic for EasyCommerce Email Tester.
 *
 * @since   1.0.0
 * @package EC_Email_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class EC_Email_Tester_Sender
 *
 * Fires the appropriate EasyCommerce trigger hook for a given email type,
 * captures all resulting easycommerce_email action calls before shoot() runs,
 * resolves placeholders for preview, and optionally blocks the actual wp_mail()
 * dispatch (dry-run mode).
 */
class EC_Email_Tester_Sender {

	/**
	 * Override recipient email address.
	 * When non-empty every outgoing wp_mail() call is redirected here.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $override_email;

	/**
	 * Whether to block the actual wp_mail() dispatch.
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	private bool $dry_run;

	/**
	 * Emails captured from the easycommerce_email action during the trigger.
	 *
	 * Each entry: [ recipient, subject_raw, subject, body_raw, body_resolved, placeholders, unresolved ]
	 *
	 * @since 1.0.0
	 * @var array<int, array<string, mixed>>
	 */
	private array $captured = [];

	/**
	 * Closures registered during the trigger, kept so they can be removed cleanly.
	 *
	 * @since 1.0.0
	 * @var array<string, callable>
	 */
	private array $listeners = [];

	/**
	 * Map email type keys to human-readable labels.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	public static array $email_types = [
		'order_pending'    => 'Order: Pending Payment',
		'order_processing' => 'Order: Processing',
		'order_completed'  => 'Order: Completed',
		'order_on_hold'    => 'Order: On Hold',
		'order_cancelled'  => 'Order: Cancelled',
		'order_refunded'   => 'Order: Refunded',
		'new_account'      => 'New Account',
		'abandoned_cart'   => 'Abandoned Cart Reminder',
	];

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $override_email Optional. Redirect all outgoing mail to this address.
	 * @param bool   $dry_run        Optional. When true, block actual wp_mail() dispatch.
	 */
	public function __construct( string $override_email = '', bool $dry_run = false ) {
		$this->override_email = $override_email;
		$this->dry_run        = $dry_run;
	}

	/**
	 * Fire the trigger for the given email type and return a result array.
	 *
	 * @since 1.0.0
	 *
	 * @param string $email_type  One of the keys in self::$email_types.
	 * @param int    $order_id    Required for order_* types.
	 * @param int    $user_id     Required for new_account type.
	 * @param string $cart_hash   Required for abandoned_cart type.
	 * @return array {
	 *     @type string|null                  $error     Validation/trigger error message, or null on success.
	 *     @type array<int, array>            $captured  Captured email details.
	 *     @type string                       $email_type
	 *     @type bool                         $dry_run
	 *     @type bool                         $mail_sent Result of apply_filters('easycommerce_mail_sent', false).
	 * }
	 */
	public function send(
		string $email_type,
		int $order_id = 0,
		int $user_id  = 0,
		string $cart_hash = ''
	): array {
		$error = $this->validate( $email_type, $order_id, $user_id, $cart_hash );

		if ( null !== $error ) {
			return $this->result( $email_type, $error, false );
		}

		$this->setup_listeners();

		$trigger_error = $this->fire_trigger( $email_type, $order_id, $user_id, $cart_hash );

		$mail_sent = apply_filters( 'easycommerce_mail_sent', false );

		$this->teardown_listeners();

		return $this->result( $email_type, $trigger_error, (bool) $mail_sent );
	}

	// -------------------------------------------------------------------------
	// Validation
	// -------------------------------------------------------------------------

	/**
	 * Validate inputs before firing.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null Error message, or null if valid.
	 */
	private function validate( string $email_type, int $order_id, int $user_id, string $cart_hash ): ?string {
		if ( ! array_key_exists( $email_type, self::$email_types ) ) {
			return __( 'Unknown email type selected.', 'easycommerce-email-tester' );
		}

		if ( str_starts_with( $email_type, 'order_' ) && $order_id < 1 ) {
			return __( 'An Order ID is required for order email types.', 'easycommerce-email-tester' );
		}

		if ( 'new_account' === $email_type && $user_id < 1 ) {
			return __( 'A User ID is required for the new account email type.', 'easycommerce-email-tester' );
		}

		if ( 'abandoned_cart' === $email_type && '' === $cart_hash ) {
			return __( 'A cart hash is required for the abandoned cart email type.', 'easycommerce-email-tester' );
		}

		if ( ! empty( $this->override_email ) && ! is_email( $this->override_email ) ) {
			return sprintf(
				/* translators: %s: invalid email address */
				__( 'Override recipient "%s" is not a valid email address.', 'easycommerce-email-tester' ),
				esc_html( $this->override_email )
			);
		}

		return null;
	}

	// -------------------------------------------------------------------------
	// Listener setup / teardown
	// -------------------------------------------------------------------------

	/**
	 * Register all transient hooks needed for capture and interception.
	 *
	 * @since 1.0.0
	 */
	private function setup_listeners(): void {
		// Capture every easycommerce_email call before shoot() runs (priority < 10).
		$capture = function ( $recipient, $subject, $body, $placeholders ): void {
			$this->capture_email( $recipient, $subject, $body, $placeholders );
		};

		add_action( 'easycommerce_email', $capture, 5, 4 );
		$this->listeners['capture'] = $capture;

		// Dry-run: short-circuit wp_mail() so nothing actually goes out.
		if ( $this->dry_run ) {
			$pre = '__return_true';
			add_filter( 'pre_wp_mail', $pre );
			$this->listeners['pre_wp_mail'] = $pre;
		}

		// Override recipient: rewrite "to" inside wp_mail() args.
		if ( ! empty( $this->override_email ) ) {
			$override_email  = $this->override_email;
			$override_filter = static function ( array $args ) use ( $override_email ): array {
				$args['to'] = $override_email;
				return $args;
			};

			add_filter( 'wp_mail', $override_filter, 1 );
			$this->listeners['override'] = $override_filter;
		}
	}

	/**
	 * Remove all transient hooks registered by setup_listeners().
	 *
	 * @since 1.0.0
	 */
	private function teardown_listeners(): void {
		if ( isset( $this->listeners['capture'] ) ) {
			remove_action( 'easycommerce_email', $this->listeners['capture'], 5 );
		}

		if ( isset( $this->listeners['pre_wp_mail'] ) ) {
			remove_filter( 'pre_wp_mail', $this->listeners['pre_wp_mail'] );
		}

		if ( isset( $this->listeners['override'] ) ) {
			remove_filter( 'wp_mail', $this->listeners['override'], 1 );
		}

		$this->listeners = [];
	}

	// -------------------------------------------------------------------------
	// Capture
	// -------------------------------------------------------------------------

	/**
	 * Called at priority 5 on easycommerce_email, before shoot() at priority 10.
	 *
	 * @since 1.0.0
	 *
	 * @param string $recipient    Intended recipient email.
	 * @param string $subject      Raw subject (may contain ##placeholder## tokens).
	 * @param string $body         Raw body template.
	 * @param array  $placeholders Associative array of ##token## => value.
	 */
	private function capture_email(
		string $recipient,
		string $subject,
		string $body,
		array $placeholders
	): void {
		$resolved_subject = $this->apply_all_placeholders( $subject, $placeholders );
		$resolved_body    = $this->apply_all_placeholders( $body, $placeholders );
		$unresolved       = $this->find_unresolved( $resolved_body . ' ' . $resolved_subject );

		$this->captured[] = [
			'recipient'        => $recipient,
			'subject_raw'      => $subject,
			'subject'          => $resolved_subject,
			'body_raw'         => $body,
			'body_resolved'    => $resolved_body,
			'placeholders'     => $placeholders,
			'unresolved'       => $unresolved,
		];
	}

	// -------------------------------------------------------------------------
	// Trigger dispatch
	// -------------------------------------------------------------------------

	/**
	 * Fire the WordPress action that EasyCommerce's email controller listens on.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null Error message if the trigger itself fails, or null on success.
	 */
	private function fire_trigger(
		string $email_type,
		int $order_id,
		int $user_id,
		string $cart_hash
	): ?string {
		if ( str_starts_with( $email_type, 'order_' ) ) {
			return $this->trigger_order_email( $email_type, $order_id );
		}

		if ( 'new_account' === $email_type ) {
			return $this->trigger_new_account_email( $user_id );
		}

		if ( 'abandoned_cart' === $email_type ) {
			do_action( 'easycommerce_send_abandoned_reminder', $cart_hash );
			return null;
		}

		return __( 'Unhandled email type.', 'easycommerce-email-tester' );
	}

	/**
	 * Fire easycommerce_order_email for an order status type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $email_type  e.g. 'order_pending'
	 * @param int    $order_id
	 * @return string|null
	 */
	private function trigger_order_email( string $email_type, int $order_id ): ?string {
		// Map 'order_pending' → 'pending', 'order_on_hold' → 'on_hold', etc.
		$event = substr( $email_type, strlen( 'order_' ) );

		$order = new \EasyCommerce\Models\Order( $order_id );

		if ( ! $order->exists() ) {
			return sprintf(
				/* translators: %d: order ID */
				__( 'Order #%d does not exist.', 'easycommerce-email-tester' ),
				$order_id
			);
		}

		do_action( 'easycommerce_order_email', $event, $order_id );

		return null;
	}

	/**
	 * Fire easycommerce_user_created for a customer user.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id WordPress user ID.
	 * @return string|null
	 */
	private function trigger_new_account_email( int $user_id ): ?string {
		$wp_user = get_userdata( $user_id );

		if ( false === $wp_user ) {
			return sprintf(
				/* translators: %d: user ID */
				__( 'User #%d does not exist.', 'easycommerce-email-tester' ),
				$user_id
			);
		}

		$customer = new \EasyCommerce\Models\Customer( $user_id );

		// Mirrors the dispatch in EasyCommerce\Abstracts\User::create().
		do_action( 'easycommerce_user_created', $user_id, $customer );

		return null;
	}

	// -------------------------------------------------------------------------
	// Placeholder resolution helpers
	// -------------------------------------------------------------------------

	/**
	 * Apply both the custom placeholders and EasyCommerce's built-in defaults.
	 *
	 * This mirrors what EasyCommerce\Helpers\Email::set_placeholders() +
	 * apply_placeholders() do during a real send.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content
	 * @param array  $custom_placeholders
	 * @return string
	 */
	private function apply_all_placeholders( string $content, array $custom_placeholders ): string {
		$defaults = [
			'##site_name##'      => get_bloginfo( 'name' ),
			'##shop_name##'      => \EasyCommerce\Helpers\Utility::get_option( 'general', 'business', 'store_name' ),
			'##year##'           => date_i18n( 'Y' ),
			'##shop_page##'      => easycommerce_shop_page( true ),
			'##checkout_page##'  => easycommerce_checkout_page( true ),
			'##dashboard_page##' => easycommerce_dashboard_page( true ),
		];

		$all = array_merge( $defaults, $custom_placeholders );

		return str_replace( array_keys( $all ), array_values( $all ), $content );
	}

	/**
	 * Find any ##token## patterns that were not replaced.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content Already-resolved content.
	 * @return string[] Array of unresolved tokens (e.g. ['##unknown##']).
	 */
	private function find_unresolved( string $content ): array {
		preg_match_all( '/##\w+##/', $content, $matches );

		return array_unique( $matches[0] );
	}

	// -------------------------------------------------------------------------
	// Result builder
	// -------------------------------------------------------------------------

	/**
	 * Build the standardised result array returned by send().
	 *
	 * @since 1.0.0
	 *
	 * @param string      $email_type
	 * @param string|null $error
	 * @param bool        $mail_sent
	 * @return array
	 */
	private function result( string $email_type, ?string $error, bool $mail_sent ): array {
		return [
			'email_type' => $email_type,
			'error'      => $error,
			'captured'   => $this->captured,
			'dry_run'    => $this->dry_run,
			'mail_sent'  => $mail_sent,
		];
	}
}

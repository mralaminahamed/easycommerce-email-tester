<?php
/**
 * REST API for EasyCommerce Email Tester.
 *
 * Registers three read-only endpoints used by the Select2 fields on the
 * Testing page to search for orders, users, and abandoned carts.
 *
 * Namespace : ec-email-tester/v1
 * Endpoints :
 *   GET /orders          ?search=&per_page=
 *   GET /users           ?search=&per_page=
 *   GET /abandoned-carts ?search=&per_page=
 *
 * All endpoints require the current user to have manage_options capability.
 * Each returns a flat JSON array of { id, text } objects — the format Select2
 * AJAX expects out of the box.
 *
 * @since   1.0.0
 * @package EC_Email_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class EC_Email_Tester_API
 */
class EC_Email_Tester_API {

	/**
	 * REST namespace for all plugin routes.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const NAMESPACE = 'ec-email-tester/v1';

	/**
	 * Constructor — register REST routes on rest_api_init.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	// -------------------------------------------------------------------------
	// Route registration
	// -------------------------------------------------------------------------

	/**
	 * Register all REST routes.
	 *
	 * @since 1.0.0
	 */
	public function register_routes(): void {
		$shared_args = [
			'search'   => [
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'per_page' => [
				'type'    => 'integer',
				'default' => 20,
				'minimum' => 1,
				'maximum' => 100,
			],
		];

		register_rest_route(
			self::NAMESPACE,
			'/orders',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_orders' ],
				'permission_callback' => [ $this, 'check_permission' ],
				'args'                => $shared_args,
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/users',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_users' ],
				'permission_callback' => [ $this, 'check_permission' ],
				'args'                => $shared_args,
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/abandoned-carts',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_abandoned_carts' ],
				'permission_callback' => [ $this, 'check_permission' ],
				'args'                => $shared_args,
			]
		);
	}

	// -------------------------------------------------------------------------
	// Permission
	// -------------------------------------------------------------------------

	/**
	 * Only administrators can query these endpoints.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function check_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	// -------------------------------------------------------------------------
	// Endpoint callbacks
	// -------------------------------------------------------------------------

	/**
	 * GET /orders
	 *
	 * Returns the most-recent orders, optionally filtered by a search term
	 * that matches the order ID, customer display name, or customer e-mail.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function get_orders( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$search   = (string) $request->get_param( 'search' );
		$per_page = absint( $request->get_param( 'per_page' ) ) ?: 20;

		if ( '' !== $search ) {
			$like    = '%' . $wpdb->esc_like( $search ) . '%';
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT o.id, o.status, u.display_name, u.user_email
					 FROM {$wpdb->prefix}ec_orders o
					 LEFT JOIN {$wpdb->users} u ON o.customer_id = u.ID
					 WHERE CAST(o.id AS CHAR) LIKE %s
					    OR u.user_email LIKE %s
					    OR u.display_name LIKE %s
					 ORDER BY o.id DESC
					 LIMIT %d",
					$like,
					$like,
					$like,
					$per_page
				)
			);
		} else {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT o.id, o.status, u.display_name, u.user_email
					 FROM {$wpdb->prefix}ec_orders o
					 LEFT JOIN {$wpdb->users} u ON o.customer_id = u.ID
					 ORDER BY o.id DESC
					 LIMIT %d",
					$per_page
				)
			);
		}

		return new WP_REST_Response(
			array_map( [ static::class, 'format_order' ], $results ?: [] ),
			200
		);
	}

	/**
	 * GET /users
	 *
	 * Returns WordPress users, optionally filtered by a search term that
	 * matches the login, display name, or e-mail address.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function get_users( WP_REST_Request $request ): WP_REST_Response {
		$search   = (string) $request->get_param( 'search' );
		$per_page = absint( $request->get_param( 'per_page' ) ) ?: 20;

		$args = [
			'number'  => $per_page,
			'orderby' => 'display_name',
			'order'   => 'ASC',
		];

		if ( '' !== $search ) {
			$args['search']         = '*' . $search . '*';
			$args['search_columns'] = [ 'user_login', 'user_email', 'display_name' ];
		}

		$query = new WP_User_Query( $args );

		return new WP_REST_Response(
			array_map( [ static::class, 'format_user' ], $query->get_results() ),
			200
		);
	}

	/**
	 * GET /abandoned-carts
	 *
	 * Returns abandoned carts, optionally filtered by a search term that
	 * matches the cart hash, customer name, or customer e-mail.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function get_abandoned_carts( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$search   = (string) $request->get_param( 'search' );
		$per_page = absint( $request->get_param( 'per_page' ) ) ?: 20;

		if ( '' !== $search ) {
			$like    = '%' . $wpdb->esc_like( $search ) . '%';
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT hash, customer_name, customer_email
					 FROM {$wpdb->prefix}ec_cart_sessions
					 WHERE status = 'abandoned'
					   AND ( hash LIKE %s
					      OR customer_email LIKE %s
					      OR customer_name LIKE %s )
					 ORDER BY updated_at DESC
					 LIMIT %d",
					$like,
					$like,
					$like,
					$per_page
				)
			);
		} else {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT hash, customer_name, customer_email
					 FROM {$wpdb->prefix}ec_cart_sessions
					 WHERE status = 'abandoned'
					 ORDER BY updated_at DESC
					 LIMIT %d",
					$per_page
				)
			);
		}

		return new WP_REST_Response(
			array_map( [ static::class, 'format_cart' ], $results ?: [] ),
			200
		);
	}

	// -------------------------------------------------------------------------
	// Static formatters  (also used by the admin to build pre-selected options)
	// -------------------------------------------------------------------------

	/**
	 * Format a raw order DB row into a Select2 { id, text } pair.
	 *
	 * @since 1.0.0
	 *
	 * @param object $row DB row with id, status, display_name, user_email.
	 * @return array{id: int, text: string}
	 */
	public static function format_order( object $row ): array {
		$name  = isset( $row->display_name ) && '' !== $row->display_name
			? $row->display_name
			: __( 'Guest', 'easycommerce-email-tester' );
		$email = isset( $row->user_email ) && '' !== $row->user_email
			? $row->user_email
			: '—';

		return [
			'id'   => (int) $row->id,
			/* translators: 1: order ID  2: customer name  3: customer email  4: status */
			'text' => sprintf( '#%1$d · %2$s · %3$s · %4$s', $row->id, $name, $email, ucfirst( $row->status ) ),
		];
	}

	/**
	 * Format a WP_User object into a Select2 { id, text } pair.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_User $user WordPress user object.
	 * @return array{id: int, text: string}
	 */
	public static function format_user( WP_User $user ): array {
		return [
			'id'   => $user->ID,
			/* translators: 1: display name  2: user ID  3: email */
			'text' => sprintf( '%1$s (#%2$d) — %3$s', $user->display_name, $user->ID, $user->user_email ),
		];
	}

	/**
	 * Format a raw abandoned-cart DB row into a Select2 { id, text } pair.
	 *
	 * @since 1.0.0
	 *
	 * @param object $row DB row with hash, customer_name, customer_email.
	 * @return array{id: string, text: string}
	 */
	public static function format_cart( object $row ): array {
		$name  = isset( $row->customer_name ) && '' !== $row->customer_name
			? $row->customer_name
			: __( 'Guest', 'easycommerce-email-tester' );
		$email = isset( $row->customer_email ) && '' !== $row->customer_email
			? $row->customer_email
			: '—';
		$hash  = strlen( $row->hash ) > 16
			? substr( $row->hash, 0, 16 ) . '…'
			: $row->hash;

		return [
			'id'   => $row->hash,
			/* translators: 1: cart hash (truncated)  2: customer name  3: customer email */
			'text' => sprintf( '%1$s · %2$s · %3$s', $hash, $name, $email ),
		];
	}

	// -------------------------------------------------------------------------
	// Single-record helpers  (used to pre-populate selects after form submit)
	// -------------------------------------------------------------------------

	/**
	 * Return the Select2 option for a single order by ID, or null if not found.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Order ID.
	 * @return array{id: int, text: string}|null
	 */
	public static function get_order_option( int $id ): ?array {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT o.id, o.status, u.display_name, u.user_email
				 FROM {$wpdb->prefix}ec_orders o
				 LEFT JOIN {$wpdb->users} u ON o.customer_id = u.ID
				 WHERE o.id = %d
				 LIMIT 1",
				$id
			)
		);

		return $row ? static::format_order( $row ) : null;
	}

	/**
	 * Return the Select2 option for a single user by ID, or null if not found.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id WordPress user ID.
	 * @return array{id: int, text: string}|null
	 */
	public static function get_user_option( int $id ): ?array {
		$user = get_userdata( $id );

		return $user ? static::format_user( $user ) : null;
	}

	/**
	 * Return the Select2 option for a single abandoned cart by hash, or null if not found.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hash Cart hash.
	 * @return array{id: string, text: string}|null
	 */
	public static function get_cart_option( string $hash ): ?array {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT hash, customer_name, customer_email
				 FROM {$wpdb->prefix}ec_cart_sessions
				 WHERE hash = %s
				 LIMIT 1",
				$hash
			)
		);

		return $row ? static::format_cart( $row ) : null;
	}
}

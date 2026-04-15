<?php
/**
 * WP_List_Table implementation for the Email Logs admin page.
 *
 * @since   1.0.0
 * @package EC_Email_Tester
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class EC_Email_Tester_Log_List
 */
class EC_Email_Tester_Log_List extends WP_List_Table {

	/**
	 * Active filter values read from the request.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $filters = [];

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => 'log',
				'plural'   => 'logs',
				'ajax'     => false,
			]
		);

		$this->filters = [
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only list table filter params, no state change
			'status' => sanitize_text_field( wp_unslash( $_GET['log_status'] ?? '' ) ),
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only list table filter params, no state change
			'source' => sanitize_text_field( wp_unslash( $_GET['log_source'] ?? '' ) ),
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only list table filter params, no state change
			'search' => sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ),
		];
	}

	// -------------------------------------------------------------------------
	// Column definitions
	// -------------------------------------------------------------------------

	/**
	 * Define the columns for the list table.
	 *
	 * @inheritDoc
	 */
	public function get_columns(): array {
		return [
			'cb'        => '<input type="checkbox" />',
			'id'        => __( '#', 'easycommerce-email-tester' ),
			'timestamp' => __( 'Date / Time', 'easycommerce-email-tester' ),
			'to_email'  => __( 'To', 'easycommerce-email-tester' ),
			'subject'   => __( 'Subject', 'easycommerce-email-tester' ),
			'status'    => __( 'Status', 'easycommerce-email-tester' ),
			'source'    => __( 'Source', 'easycommerce-email-tester' ),
		];
	}

	/**
	 * Define sortable columns for the list table.
	 *
	 * @inheritDoc
	 */
	protected function get_sortable_columns(): array {
		return [
			'id'        => [ 'id', true ],
			'timestamp' => [ 'timestamp', false ],
			'status'    => [ 'status', false ],
			'source'    => [ 'source', false ],
		];
	}

	/**
	 * Define bulk actions available for the list table.
	 *
	 * @inheritDoc
	 */
	protected function get_bulk_actions(): array {
		return [
			'delete' => __( 'Delete', 'easycommerce-email-tester' ),
		];
	}

	// -------------------------------------------------------------------------
	// Column renderers
	// -------------------------------------------------------------------------

	/**
	 * Checkbox column.
	 *
	 * @param object $item Log row.
	 * @return string
	 */
	protected function column_cb( $item ): string {
		return sprintf( '<input type="checkbox" name="log_ids[]" value="%d" />', (int) $item->id );
	}

	/**
	 * ID column with row actions.
	 *
	 * @param object $item Log row.
	 * @return string
	 */
	protected function column_id( $item ): string {
		$view_url   = $this->row_action_url( 'view', (int) $item->id );
		$delete_url = $this->row_action_url( 'delete', (int) $item->id );

		$row_actions = [
			'view'   => sprintf( '<a href="%s">%s</a>', esc_url( $view_url ), esc_html__( 'View', 'easycommerce-email-tester' ) ),
			'delete' => sprintf(
				'<a href="%s" class="ect-log-delete" onclick="return confirm(\'%s\')">%s</a>',
				esc_url( $delete_url ),
				esc_js( __( 'Delete this log entry?', 'easycommerce-email-tester' ) ),
				esc_html__( 'Delete', 'easycommerce-email-tester' )
			),
		];

		return sprintf(
			'<a href="%s"><strong>#%d</strong></a>%s',
			esc_url( $view_url ),
			(int) $item->id,
			$this->row_actions( $row_actions )
		);
	}

	/**
	 * Timestamp column — shows local time formatted by WP settings.
	 *
	 * @param object $item Log row.
	 * @return string
	 */
	protected function column_timestamp( $item ): string {
		$utc   = new \DateTimeImmutable( $item->timestamp, new \DateTimeZone( 'UTC' ) );
		$local = $utc->setTimezone( wp_timezone() );
		$fmt   = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

		return esc_html( $local->format( $fmt ) );
	}

	/**
	 * To column — truncated with full address in title attribute.
	 *
	 * @param object $item Log row.
	 * @return string
	 */
	protected function column_to_email( $item ): string {
		$to = (string) $item->to_email;

		if ( mb_strlen( $to ) <= 45 ) {
			return esc_html( $to );
		}

		return sprintf(
			'<span title="%s">%s&hellip;</span>',
			esc_attr( $to ),
			esc_html( mb_substr( $to, 0, 42 ) )
		);
	}

	/**
	 * Subject column — truncated.
	 *
	 * @param object $item Log row.
	 * @return string
	 */
	protected function column_subject( $item ): string {
		$subject = (string) $item->subject;

		if ( mb_strlen( $subject ) <= 60 ) {
			return esc_html( $subject );
		}

		return sprintf(
			'<span title="%s">%s&hellip;</span>',
			esc_attr( $subject ),
			esc_html( mb_substr( $subject, 0, 57 ) )
		);
	}

	/**
	 * Status column — badge.
	 *
	 * @param object $item Log row.
	 * @return string
	 */
	protected function column_status( $item ): string {
		if ( 1 === (int) $item->status ) {
			return '<span class="ect-badge ect-badge--sent">' . esc_html__( 'Sent', 'easycommerce-email-tester' ) . '</span>';
		}

		$title = ! empty( $item->error )
			? ' title="' . esc_attr( $item->error ) . '"'
			: '';

		return '<span class="ect-badge ect-badge--failed"' . $title . '>' . esc_html__( 'Failed', 'easycommerce-email-tester' ) . '</span>';
	}

	/**
	 * Source column — badge.
	 *
	 * @param object $item Log row.
	 * @return string
	 */
	protected function column_source( $item ): string {
		if ( 'test' === $item->source ) {
			return '<span class="ect-badge ect-badge--source-test">' . esc_html__( 'Test', 'easycommerce-email-tester' ) . '</span>';
		}

		return '<span class="ect-badge ect-badge--source-live">' . esc_html__( 'Live', 'easycommerce-email-tester' ) . '</span>';
	}

	/**
	 * Display text when no items are found in the list table.
	 *
	 * @inheritDoc
	 */
	public function no_items(): void {
		esc_html_e( 'No email logs found. Enable logging in Settings to start capturing outgoing emails.', 'easycommerce-email-tester' );
	}

	/**
	 * Fallback column renderer.
	 *
	 * @param object $item        Log row.
	 * @param string $column_name Column key.
	 * @return string
	 */
	protected function column_default( $item, $column_name ): string {
		return esc_html( (string) ( $item->$column_name ?? '—' ) );
	}

	// -------------------------------------------------------------------------
	// Filter views
	// -------------------------------------------------------------------------

	/**
	 * Get the list of views available on this list table (filter links).
	 *
	 * @inheritDoc
	 */
	protected function get_views(): array {
		$base = remove_query_arg( [ 'log_status', 'log_source', 'paged' ] );

		$total  = EC_Email_Tester_Logger::count_logs();
		$sent   = EC_Email_Tester_Logger::count_logs( [ 'status' => '1' ] );
		$failed = EC_Email_Tester_Logger::count_logs( [ 'status' => '0' ] );
		$live   = EC_Email_Tester_Logger::count_logs( [ 'source' => 'live' ] );
		$test   = EC_Email_Tester_Logger::count_logs( [ 'source' => 'test' ] );

		$current_status = $this->filters['status'];
		$current_source = $this->filters['source'];

		$views = [
			'all'    => $this->view_link(
				$base,
				__( 'All', 'easycommerce-email-tester' ),
				$total,
				( '' === $current_status && '' === $current_source )
			),
			'sent'   => $this->view_link(
				add_query_arg( 'log_status', '1', $base ),
				__( 'Sent', 'easycommerce-email-tester' ),
				$sent,
				'1' === $current_status
			),
			'failed' => $this->view_link(
				add_query_arg( 'log_status', '0', $base ),
				__( 'Failed', 'easycommerce-email-tester' ),
				$failed,
				'0' === $current_status
			),
			'live'   => $this->view_link(
				add_query_arg( 'log_source', 'live', $base ),
				__( 'Live', 'easycommerce-email-tester' ),
				$live,
				'live' === $current_source
			),
			'test'   => $this->view_link(
				add_query_arg( 'log_source', 'test', $base ),
				__( 'Test', 'easycommerce-email-tester' ),
				$test,
				'test' === $current_source
			),
		];

		return array_filter( $views );
	}

	// -------------------------------------------------------------------------
	// prepare_items
	// -------------------------------------------------------------------------

	/**
	 * Prepare items for display in the list table.
	 *
	 * @inheritDoc
	 */
	public function prepare_items(): void {
		$per_page = $this->get_items_per_page( 'ec_email_tester_logs_per_page', 25 );
		$page     = $this->get_pagenum();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only list table filter params, no state change
		$orderby = sanitize_text_field( wp_unslash( $_GET['orderby'] ?? 'id' ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only list table filter params, no state change
		$order = sanitize_text_field( wp_unslash( $_GET['order'] ?? 'desc' ) );

		$query_args = array_merge(
			$this->filters,
			[
				'page'     => $page,
				'per_page' => $per_page,
				'orderby'  => $orderby,
				'order'    => $order,
			]
		);

		$this->items = EC_Email_Tester_Logger::get_logs( $query_args );

		$total = EC_Email_Tester_Logger::count_logs( $this->filters );

		$this->set_pagination_args(
			[
				'total_items' => $total,
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $total / $per_page ),
			]
		);

		$this->_column_headers = [
			$this->get_columns(),
			[],
			$this->get_sortable_columns(),
		];
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Build a view-filter link with count badge.
	 *
	 * @param string $url     The URL for the filter link.
	 * @param string $label   The human-readable label for the filter.
	 * @param int    $count   Number of items matching this filter.
	 * @param bool   $current Whether this filter is currently active.
	 * @return string
	 */
	private function view_link( string $url, string $label, int $count, bool $current ): string {
		$class = $current ? ' class="current"' : '';

		return sprintf(
			'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
			esc_url( $url ),
			$class,
			esc_html( $label ),
			$count
		);
	}

	/**
	 * Build a single-row action URL with nonce.
	 *
	 * @param string $action Row action: 'view' or 'delete'.
	 * @param int    $id     Log entry ID.
	 * @return string
	 */
	private function row_action_url( string $action, int $id ): string {
		return wp_nonce_url(
			add_query_arg(
				[
					'page'       => 'easycommerce-email-tester-logs',
					'log_action' => $action,
					'log_id'     => $id,
				],
				admin_url( 'admin.php' )
			),
			'ec_email_tester_log_' . $action . '_' . $id
		);
	}
}

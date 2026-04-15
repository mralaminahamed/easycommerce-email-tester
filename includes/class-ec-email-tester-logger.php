<?php
/**
 * Email Logger for EasyCommerce Email Tester.
 *
 * Intercepts every wp_mail() call site-wide and persists the result to a
 * custom database table.  Provides static CRUD helpers used by the log-list
 * admin page and the WP-Cron retention cleanup job.
 *
 * @since   1.0.0
 * @package EC_Email_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class EC_Email_Tester_Logger
 */
class EC_Email_Tester_Logger {

	/**
	 * Database table name WITHOUT the wpdb prefix.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const TABLE = 'ec_email_tester_logs';

	/**
	 * WP-Cron hook name for the scheduled retention cleanup.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const CRON_HOOK = 'ec_email_tester_log_cleanup';

	/**
	 * Settings option key (shared with EC_Email_Tester_Admin).
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const SETTINGS_OPTION = 'ec_email_tester_settings';

	/**
	 * Mail args captured from the wp_mail filter, pending the actual send result.
	 * Cleared after each send attempt (success or failure).
	 *
	 * @since 1.0.0
	 * @var array|null
	 */
	private ?array $pending_log = null;

	/**
	 * Whether the current wp_mail() call originated from the Testing page.
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	private bool $is_test_send = false;

	// -------------------------------------------------------------------------
	// Boot
	// -------------------------------------------------------------------------

	/**
	 * Constructor — registers hooks when logging is enabled in settings.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Always register the cron callback so the scheduled event fires.
		add_action( self::CRON_HOOK, [ self::class, 'purge_old_logs' ] );

		if ( ! self::is_enabled() ) {
			return;
		}

		// Capture mail args in the filter — side-effect-free, no DB write here.
		// This lets SMTP plugins (WP Mail SMTP, FluentSMTP, etc.) configure
		// PHPMailer freely via phpmailer_init without interference.
		add_filter( 'wp_mail', [ $this, 'capture_mail' ], PHP_INT_MAX );

		// Write to the DB only after the send attempt completes (WP 5.9+).
		add_action( 'wp_mail_succeeded', [ $this, 'capture_success' ] );
		add_action( 'wp_mail_failed',    [ $this, 'capture_failure' ] );

		// Allow the Testing page to tag its sends as source='test'.
		add_action( 'ec_email_tester_before_test_send', [ $this, 'mark_as_test' ] );
		add_action( 'ec_email_tester_after_test_send',  [ $this, 'unmark_as_test' ] );
	}

	// -------------------------------------------------------------------------
	// wp_mail hooks
	// -------------------------------------------------------------------------

	/**
	 * wp_mail filter callback — capture args for later, return unchanged.
	 *
	 * This callback is intentionally side-effect-free: it stores the args in
	 * $this->pending_log and immediately returns them.  No DB writes happen here
	 * so external SMTP plugins (WP Mail SMTP, FluentSMTP, Postmark, etc.) can
	 * reconfigure PHPMailer via phpmailer_init without any interference.
	 *
	 * Dry-run test emails short-circuit via pre_wp_mail before PHPMailer runs,
	 * so wp_mail_succeeded never fires for them — they are never logged.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args wp_mail() arguments.
	 * @return array Unchanged.
	 */
	public function capture_mail( array $args ): array {
		$settings = self::get_settings();

		// Honour the "also log test emails" preference.
		if ( $this->is_test_send && ! $settings['logger_log_test_emails'] ) {
			$this->pending_log = null;
			return $args;
		}

		// Just stash; the actual DB insert happens in capture_success / capture_failure.
		$this->pending_log = $args;

		return $args;
	}

	/**
	 * wp_mail_succeeded action — insert a "sent" log row.
	 *
	 * Fires after PHPMailer successfully dispatches the message (WP 5.9+).
	 * Using the pending args captured in capture_mail so we have the full
	 * message body even when an SMTP plugin altered the transport.
	 *
	 * @since 1.0.0
	 *
	 * @param array $mail_data Mail data array passed by core (to/subject/message/headers/attachments).
	 */
	public function capture_success( array $mail_data ): void {
		if ( null === $this->pending_log ) {
			return;
		}

		$this->insert_log( $this->pending_log, 1, '' );
		$this->pending_log = null;
	}

	/**
	 * wp_mail_failed action — insert a "failed" log row.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Error $error PHPMailer error.
	 */
	public function capture_failure( \WP_Error $error ): void {
		$args = $this->pending_log;
		$this->pending_log = null;

		if ( null === $args ) {
			return;
		}

		$this->insert_log( $args, 0, $error->get_error_message() );
	}

	/**
	 * Write one row to the log table.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $args   wp_mail()-style args array.
	 * @param int    $status 1 = sent, 0 = failed.
	 * @param string $error  Error message (empty on success).
	 */
	private function insert_log( array $args, int $status, string $error ): void {
		global $wpdb;

		$to          = is_array( $args['to'] ) ? implode( ', ', $args['to'] ) : (string) $args['to'];
		$headers     = is_array( $args['headers'] ) ? implode( "\n", $args['headers'] ) : (string) $args['headers'];
		$attachments = is_array( $args['attachments'] ) ? implode( ', ', $args['attachments'] ) : (string) $args['attachments'];

		$wpdb->insert(
			self::table(),
			[
				'timestamp'   => current_time( 'mysql', true ),
				'to_email'    => mb_substr( $to, 0, 500 ),
				'subject'     => mb_substr( (string) ( $args['subject'] ?? '' ), 0, 500 ),
				'message'     => (string) ( $args['message'] ?? '' ),
				'headers'     => $headers,
				'attachments' => mb_substr( $attachments, 0, 1000 ),
				'status'      => $status,
				'error'       => mb_substr( $error, 0, 500 ),
				'source'      => $this->is_test_send ? 'test' : 'live',
			],
			[ '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' ]
		);
	}

	/**
	 * Set the is_test_send flag — called before the Testing form sender runs.
	 *
	 * @since 1.0.0
	 */
	public function mark_as_test(): void {
		$this->is_test_send = true;
	}

	/**
	 * Clear the is_test_send flag — called after the Testing form sender runs.
	 *
	 * @since 1.0.0
	 */
	public function unmark_as_test(): void {
		$this->is_test_send = false;
	}

	// -------------------------------------------------------------------------
	// Database — table lifecycle
	// -------------------------------------------------------------------------

	/**
	 * Full table name including the wpdb prefix.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * Create (or silently upgrade) the logs table.
	 * Called on plugin activation via register_activation_hook.
	 *
	 * @since 1.0.0
	 */
	public static function create_table(): void {
		global $wpdb;

		$charset = $wpdb->get_charset_collate();
		$table   = self::table();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			timestamp datetime NOT NULL,
			to_email varchar(500) NOT NULL DEFAULT '',
			subject varchar(500) NOT NULL DEFAULT '',
			message longtext NOT NULL,
			headers text NOT NULL,
			attachments text NOT NULL,
			status tinyint(1) NOT NULL DEFAULT 1,
			error varchar(500) NOT NULL DEFAULT '',
			source varchar(20) NOT NULL DEFAULT 'live',
			PRIMARY KEY  (id),
			KEY timestamp (timestamp),
			KEY status (status),
			KEY source (source)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Drop the logs table — called from uninstall.php when enabled in settings.
	 *
	 * @since 1.0.0
	 */
	public static function drop_table(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( 'DROP TABLE IF EXISTS ' . self::table() );
	}

	/**
	 * Schedule the hourly retention cleanup event.
	 * Called on plugin activation.
	 *
	 * @since 1.0.0
	 */
	public static function schedule_cleanup(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'hourly', self::CRON_HOOK );
		}
	}

	/**
	 * Unschedule the retention cleanup event.
	 * Called on plugin deactivation.
	 *
	 * @since 1.0.0
	 */
	public static function unschedule_cleanup(): void {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	// -------------------------------------------------------------------------
	// Database — CRUD
	// -------------------------------------------------------------------------

	/**
	 * Return a paginated list of log rows.
	 *
	 * @since 1.0.0
	 *
	 * @param array{
	 *     page?:     int,
	 *     per_page?: int,
	 *     status?:   string,
	 *     source?:   string,
	 *     search?:   string,
	 *     orderby?:  string,
	 *     order?:    string,
	 * } $args
	 * @return array<int, object>
	 */
	public static function get_logs( array $args = [] ): array {
		global $wpdb;

		$page     = max( 1, (int) ( $args['page'] ?? 1 ) );
		$per_page = max( 1, (int) ( $args['per_page'] ?? 25 ) );
		$offset   = ( $page - 1 ) * $per_page;
		$orderby  = in_array( $args['orderby'] ?? '', [ 'id', 'timestamp', 'status', 'source' ], true )
			? $args['orderby']
			: 'id';
		$order    = 'ASC' === strtoupper( $args['order'] ?? '' ) ? 'ASC' : 'DESC';

		[ $where_sql, $params ] = self::build_where( $args );

		$params[] = $per_page;
		$params[] = $offset;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM " . self::table() . " {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
				...$params
			)
		);
		// phpcs:enable
	}

	/**
	 * Count log rows matching the given filters.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Same filter keys as get_logs() (page/per_page/orderby/order ignored).
	 * @return int
	 */
	public static function count_logs( array $args = [] ): int {
		global $wpdb;

		[ $where_sql, $params ] = self::build_where( $args );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		if ( $params ) {
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM " . self::table() . " {$where_sql}",
					...$params
				)
			);
		} else {
			$count = $wpdb->get_var( "SELECT COUNT(*) FROM " . self::table() );
		}
		// phpcs:enable

		return (int) $count;
	}

	/**
	 * Retrieve a single log entry by ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id
	 * @return object|null
	 */
	public static function get_log( int $id ): ?object {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) ) ?: null;
	}

	/**
	 * Delete a single log entry.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id
	 * @return bool
	 */
	public static function delete_log( int $id ): bool {
		global $wpdb;
		return (bool) $wpdb->delete( self::table(), [ 'id' => $id ], [ '%d' ] );
	}

	/**
	 * Bulk-delete log entries by their IDs.
	 *
	 * @since 1.0.0
	 *
	 * @param int[] $ids
	 */
	public static function delete_logs( array $ids ): void {
		if ( empty( $ids ) ) {
			return;
		}

		global $wpdb;
		$ids          = array_map( 'intval', $ids );
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . self::table() . " WHERE id IN ({$placeholders})", ...$ids ) );
	}

	/**
	 * Truncate the logs table (removes all rows, resets AUTO_INCREMENT).
	 *
	 * @since 1.0.0
	 */
	public static function truncate(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( 'TRUNCATE TABLE ' . self::table() );
	}

	// -------------------------------------------------------------------------
	// WP-Cron retention cleanup
	// -------------------------------------------------------------------------

	/**
	 * Delete log rows that exceed the configured count or age limits.
	 * Runs hourly via WP-Cron.
	 *
	 * @since 1.0.0
	 */
	public static function purge_old_logs(): void {
		$settings = self::get_settings();

		global $wpdb;

		// Retain only the newest N logs.
		if ( $settings['logger_retention_count_enabled'] ) {
			$keep = max( 1, (int) $settings['logger_retention_count'] );
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM " . self::table() . "
					WHERE id NOT IN (
						SELECT id FROM (
							SELECT id FROM " . self::table() . " ORDER BY id DESC LIMIT %d
						) AS t
					)",
					$keep
				)
			);
			// phpcs:enable
		}

		// Delete rows older than N days.
		if ( $settings['logger_retention_days_enabled'] ) {
			$days = max( 1, (int) $settings['logger_retention_days'] );
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM " . self::table() . " WHERE timestamp < DATE_SUB( UTC_TIMESTAMP(), INTERVAL %d DAY )",
					$days
				)
			);
			// phpcs:enable
		}
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Whether email logging is enabled in settings.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return (bool) self::get_settings()['logger_enabled'];
	}

	/**
	 * Return logger-related settings merged with defaults.
	 *
	 * @since 1.0.0
	 *
	 * @return array{
	 *     logger_enabled: bool,
	 *     logger_log_test_emails: bool,
	 *     logger_retention_count_enabled: bool,
	 *     logger_retention_count: int,
	 *     logger_retention_days_enabled: bool,
	 *     logger_retention_days: int,
	 *     logger_delete_on_uninstall: bool,
	 * }
	 */
	public static function get_settings(): array {
		static $cache = null;

		if ( null !== $cache ) {
			return $cache;
		}

		$saved = get_option( self::SETTINGS_OPTION, [] );
		$saved = is_array( $saved ) ? $saved : [];

		$cache = wp_parse_args( $saved, [
			'logger_enabled'                 => true,
			'logger_log_test_emails'         => true,
			'logger_retention_count_enabled' => true,
			'logger_retention_count'         => 500,
			'logger_retention_days_enabled'  => false,
			'logger_retention_days'          => 30,
			'logger_delete_on_uninstall'     => false,
		] );

		return $cache;
	}

	/**
	 * Build a WHERE clause + params array from filter args.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args
	 * @return array{ 0: string, 1: array }  [ where_sql, params ]
	 */
	private static function build_where( array $args ): array {
		global $wpdb;

		$conditions = [];
		$params     = [];

		if ( isset( $args['status'] ) && in_array( (string) $args['status'], [ '0', '1' ], true ) ) {
			$conditions[] = 'status = %d';
			$params[]     = (int) $args['status'];
		}

		if ( ! empty( $args['source'] ) && in_array( $args['source'], [ 'live', 'test' ], true ) ) {
			$conditions[] = 'source = %s';
			$params[]     = $args['source'];
		}

		if ( ! empty( $args['search'] ) ) {
			$like         = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$conditions[] = '( to_email LIKE %s OR subject LIKE %s )';
			$params[]     = $like;
			$params[]     = $like;
		}

		$where_sql = $conditions ? 'WHERE ' . implode( ' AND ', $conditions ) : '';

		return [ $where_sql, $params ];
	}
}

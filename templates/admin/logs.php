<?php
/**
 * Template: Logs list page.
 *
 * Available variables (set by EC_Email_Tester_Admin::render_logs()):
 *
 * @var EC_Email_Tester_Log_List $list_table  Prepared list table instance.
 * @var string|null              $notice      Success/info notice message, or null.
 * @var string                   $notice_type 'success' | 'error'
 *
 * @since   1.0.0
 * @package EC_Email_Tester
 */

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$has_logs = EC_Email_Tester_Logger::table_exists() && EC_Email_Tester_Logger::count_logs() > 0;
?>
<div class="wrap ect-wrap">

	<!-- ---- Page header ---- -->
	<div class="ect-page-header">
		<div class="ect-page-header__body">
			<h1><?php esc_html_e( 'Logs', 'easycommerce-email-tester' ); ?></h1>
			<p class="ect-page-header__desc">
				<?php esc_html_e( 'Every email dispatched through wp_mail() is captured here. Test emails sent via the Testing page are tagged separately.', 'easycommerce-email-tester' ); ?>
			</p>
		</div>
		<?php if ( $has_logs ) : ?>
			<div class="ect-page-header__actions">
				<form method="post" action="" class="ect-form-inline">
					<?php wp_nonce_field( 'ec_email_tester_clear_logs', 'ec_clear_logs_nonce' ); ?>
					<input type="hidden" name="ec_log_action" value="clear_all" />
					<button
						type="submit"
						class="button ect-btn-danger"
						onclick="return confirm('<?php echo esc_js( __( 'Delete all log entries? This cannot be undone.', 'easycommerce-email-tester' ) ); ?>')"
					>
						<?php esc_html_e( 'Clear All', 'easycommerce-email-tester' ); ?>
					</button>
				</form>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( $notice ) : ?>
		<div class="ect-saved-notice <?php echo 'error' === $notice_type ? 'ect-saved-notice--error' : ''; ?>">
			<?php EC_Email_Tester_Icons::render( 'error' === $notice_type ? 'triangle-alert' : 'circle-check' ); ?>
			<?php echo esc_html( $notice ); ?>
		</div>
	<?php endif; ?>

	<!-- ---- Log list card ---- -->
	<div class="ect-card ect-logs-card">

		<h2 class="ect-card-title">
			<?php EC_Email_Tester_Icons::render( 'list' ); ?>
			<?php esc_html_e( 'Log Entries', 'easycommerce-email-tester' ); ?>
		</h2>

		<form id="ect-log-list-form" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
			<input type="hidden" name="page" value="easycommerce-email-tester-logs" />

			<div class="ect-logs-controls">
				<?php $list_table->search_box( __( 'Search logs', 'easycommerce-email-tester' ), 'ect-log-search' ); ?>
				<?php $list_table->views(); ?>
			</div>

			<?php $list_table->display(); ?>
		</form>

	</div><!-- .ect-logs-card -->

</div><!-- .ect-wrap -->

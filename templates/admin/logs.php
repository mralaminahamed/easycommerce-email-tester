<?php
/**
 * Template: Email Logs list page.
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
?>
<div class="wrap ect-wrap">

	<!-- ---- Page header ---- -->
	<div class="ect-page-header">
		<div class="ect-page-header__body">
			<h1><?php esc_html_e( 'Email Logs', 'easycommerce-email-tester' ); ?></h1>
			<p class="ect-page-header__desc">
				<?php esc_html_e( 'Every email dispatched through wp_mail() is captured here. Test emails sent via the Testing page are tagged separately.', 'easycommerce-email-tester' ); ?>
			</p>
		</div>
		<div class="ect-page-header__actions">
			<?php if ( EC_Email_Tester_Logger::count_logs() > 0 ) : ?>
				<form method="post" action="" style="display:inline;">
					<?php wp_nonce_field( 'ec_email_tester_clear_logs', 'ec_clear_logs_nonce' ); ?>
					<input type="hidden" name="ec_log_action" value="clear_all" />
					<button
						type="submit"
						class="button ect-btn-danger"
						onclick="return confirm('<?php echo esc_js( __( 'Delete all log entries? This cannot be undone.', 'easycommerce-email-tester' ) ); ?>')"
					>
						<span class="dashicons dashicons-trash"></span>
						<?php esc_html_e( 'Clear All Logs', 'easycommerce-email-tester' ); ?>
					</button>
				</form>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( $notice ) : ?>
		<div class="ect-saved-notice <?php echo 'error' === $notice_type ? 'ect-saved-notice--error' : ''; ?>">
			<span class="dashicons <?php echo 'error' === $notice_type ? 'dashicons-warning' : 'dashicons-yes-alt'; ?>"></span>
			<?php echo esc_html( $notice ); ?>
		</div>
	<?php endif; ?>

	<form id="ect-log-list-form" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
		<input type="hidden" name="page" value="easycommerce-email-tester-logs" />

		<?php
		$list_table->search_box( __( 'Search logs', 'easycommerce-email-tester' ), 'ect-log-search' );
		$list_table->views();
		$list_table->display();
		?>
	</form>

</div><!-- .ect-wrap -->

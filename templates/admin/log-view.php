<?php
/**
 * Template: Single log entry view.
 *
 * Available variables (set by EC_Email_Tester_Admin::render_logs()):
 *
 * @var object $log      Log row from the database.
 * @var string $back_url URL to return to the logs list.
 *
 * @since   1.0.0
 * @package EC_Email_Tester
 */

defined( 'ABSPATH' ) || exit;

$utc        = new DateTimeImmutable( $log->timestamp, new DateTimeZone( 'UTC' ) );
$local      = $utc->setTimezone( wp_timezone() );
$date_fmt   = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
$is_sent    = (int) $log->status === 1;
$is_test    = $log->source === 'test';
$preview    = EC_Email_Tester_Admin::wrap_preview_html( $log->message );

$source_class = $is_test ? 'ect-badge--source-test' : 'ect-badge--source-live';
$source_label = $is_test ? __( 'Test', 'easycommerce-email-tester' ) : __( 'Live', 'easycommerce-email-tester' );
?>
<div class="wrap ect-wrap">

	<!-- ---- Page header ---- -->
	<div class="ect-page-header">
		<div class="ect-page-header__body">
			<a href="<?php echo esc_url( $back_url ); ?>" class="ect-back-link">
				<?php EC_Email_Tester_Icons::render( 'arrow-left' ); ?>
				<?php esc_html_e( 'Logs', 'easycommerce-email-tester' ); ?>
			</a>
			<h1>
				<?php
				printf(
					/* translators: %d: log entry ID */
					esc_html__( 'Log #%d', 'easycommerce-email-tester' ),
					(int) $log->id
				);
				?>
			</h1>
		</div>
		<div class="ect-page-header__actions">
			<?php
			$delete_url = wp_nonce_url(
				add_query_arg(
					[ 'page' => 'easycommerce-email-tester-logs', 'log_action' => 'delete', 'log_id' => $log->id ],
					admin_url( 'admin.php' )
				),
				'ec_email_tester_log_delete_' . $log->id
			);
			?>
			<a
				href="<?php echo esc_url( $delete_url ); ?>"
				class="button ect-btn-danger"
				onclick="return confirm('<?php echo esc_js( __( 'Delete this log entry?', 'easycommerce-email-tester' ) ); ?>')"
			>
				<?php esc_html_e( 'Delete', 'easycommerce-email-tester' ); ?>
			</a>
		</div>
	</div>

	<div class="ect-log-view">

		<!-- Meta card -->
		<div class="ect-card ect-log-meta-card">
			<h2 class="ect-card-title"><?php esc_html_e( 'Details', 'easycommerce-email-tester' ); ?></h2>

			<table class="ect-meta-table">
				<tbody>
					<tr>
						<th><?php esc_html_e( 'Date', 'easycommerce-email-tester' ); ?></th>
						<td><?php echo esc_html( $local->format( $date_fmt ) ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'To', 'easycommerce-email-tester' ); ?></th>
						<td><?php echo esc_html( $log->to_email ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Subject', 'easycommerce-email-tester' ); ?></th>
						<td><?php echo esc_html( $log->subject ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Status', 'easycommerce-email-tester' ); ?></th>
						<td>
							<?php if ( $is_sent ) : ?>
								<span class="ect-badge ect-badge--sent"><?php esc_html_e( 'Sent', 'easycommerce-email-tester' ); ?></span>
							<?php else : ?>
								<span class="ect-badge ect-badge--failed"><?php esc_html_e( 'Failed', 'easycommerce-email-tester' ); ?></span>
								<?php if ( ! empty( $log->error ) ) : ?>
									<span class="ect-log-error"><?php echo esc_html( $log->error ); ?></span>
								<?php endif; ?>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Source', 'easycommerce-email-tester' ); ?></th>
						<td>
							<span class="ect-badge <?php echo esc_attr( $source_class ); ?>"><?php echo esc_html( $source_label ); ?></span>
						</td>
					</tr>
					<?php if ( ! empty( $log->attachments ) ) : ?>
						<tr>
							<th><?php esc_html_e( 'Attachments', 'easycommerce-email-tester' ); ?></th>
							<td><?php echo esc_html( $log->attachments ); ?></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>

			<!-- Headers (collapsible) -->
			<?php if ( ! empty( $log->headers ) ) : ?>
				<div class="ect-source-wrap" style="margin-top: 0; border-top: none;">
					<button type="button" class="button ect-toggle-source" aria-expanded="false" data-target="ect-log-headers"
						data-label-show="<?php esc_attr_e( 'Show Headers', 'easycommerce-email-tester' ); ?>"
						data-label-hide="<?php esc_attr_e( 'Hide Headers', 'easycommerce-email-tester' ); ?>">
						<?php esc_html_e( 'Show Headers', 'easycommerce-email-tester' ); ?>
					</button>
					<div class="ect-source-block" id="ect-log-headers" hidden>
						<textarea class="ect-source-textarea" readonly style="min-height: 80px;"><?php echo esc_textarea( $log->headers ); ?></textarea>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<!-- Email body preview card -->
		<div class="ect-card ect-log-body-card">
			<h2 class="ect-card-title"><?php esc_html_e( 'Email Preview', 'easycommerce-email-tester' ); ?></h2>

			<div class="ect-preview-wrap" style="border-top: none;">
				<iframe
					class="ect-preview-iframe"
					srcdoc="<?php echo esc_attr( $preview ); ?>"
					sandbox="allow-same-origin"
					loading="lazy"
					title="<?php esc_attr_e( 'Email preview', 'easycommerce-email-tester' ); ?>"
				></iframe>
			</div>

			<div class="ect-source-wrap">
				<button type="button" class="button ect-toggle-source" aria-expanded="false" data-target="ect-log-body-source"
					data-label-show="<?php esc_attr_e( 'Show HTML source', 'easycommerce-email-tester' ); ?>"
					data-label-hide="<?php esc_attr_e( 'Hide HTML source', 'easycommerce-email-tester' ); ?>">
					<?php esc_html_e( 'Show HTML source', 'easycommerce-email-tester' ); ?>
				</button>
				<div class="ect-source-block" id="ect-log-body-source" hidden>
					<textarea class="ect-source-textarea" readonly><?php echo esc_textarea( $log->message ); ?></textarea>
				</div>
			</div>
		</div>

	</div><!-- .ect-log-view -->

</div><!-- .ect-wrap -->

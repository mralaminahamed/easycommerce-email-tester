<?php
/**
 * Template: Result panel — send status banner, tab nav, and per-email tab panels.
 *
 * Variables available from the parent include scope (set by EC_Email_Tester_Admin::render_testing()):
 *
 * @var array $result {
 *     @type string|null          $error      Validation / trigger error message, or null.
 *     @type array<int, array>    $captured   Captured email entries.
 *     @type string               $email_type Email type key.
 *     @type bool                 $dry_run    Whether this was a dry run.
 *     @type bool                 $mail_sent  Whether wp_mail() reported success.
 * }
 *
 * @since   1.0.0
 * @package EC_Email_Tester
 */

defined( 'ABSPATH' ) || exit;

// Resolve send badge once — shared across all captured emails.
if ( $result['dry_run'] ) {
	$send_badge_class = 'ect-badge--dryrun';
	$send_label       = __( 'Dry Run', 'easycommerce-email-tester' );
	$send_status_text = __( 'wp_mail() was blocked — no mail left the server.', 'easycommerce-email-tester' );
} elseif ( $result['mail_sent'] ) {
	$send_badge_class = 'ect-badge--sent';
	$send_label       = __( 'Sent', 'easycommerce-email-tester' );
	$send_status_text = __( 'EasyCommerce reported the email as sent.', 'easycommerce-email-tester' );
} else {
	$send_badge_class = 'ect-badge--failed';
	$send_label       = __( 'Failed', 'easycommerce-email-tester' );
	$send_status_text = __( 'wp_mail() returned false — check your SMTP configuration.', 'easycommerce-email-tester' );
}
?>

<?php if ( ! empty( $result['error'] ) ) : ?>
	<div class="ect-notice ect-notice--error">
		<span class="ect-notice-icon">&#10007;</span>
		<?php echo esc_html( $result['error'] ); ?>
	</div>
<?php elseif ( empty( $result['captured'] ) ) : ?>
	<div class="ect-notice ect-notice--warning">
		<span class="ect-notice-icon">&#9888;</span>
		<?php esc_html_e( 'No emails were captured. The trigger fired but EasyCommerce did not call the easycommerce_email action — check that the order/user/cart exists and the email type is enabled.', 'easycommerce-email-tester' ); ?>
	</div>
<?php else : ?>

	<!-- Send status banner -->
	<div class="ect-send-status">
		<span class="ect-badge <?php echo esc_attr( $send_badge_class ); ?>"><?php echo esc_html( $send_label ); ?></span>
		<?php echo esc_html( $send_status_text ); ?>
	</div>

	<!-- Tab navigation -->
	<div class="ect-tabs-nav" role="tablist" aria-label="<?php esc_attr_e( 'Captured emails', 'easycommerce-email-tester' ); ?>">
		<?php foreach ( $result['captured'] as $i => $tab_email ) :
			$tab_has_unresolved = ! empty( $tab_email['unresolved'] );
		?>
			<button
				type="button"
				role="tab"
				class="ect-tab-btn"
				aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>"
				aria-controls="ect-tab-<?php echo esc_attr( (string) $i ); ?>"
				id="ect-tab-trigger-<?php echo esc_attr( (string) $i ); ?>"
			>
				<?php
				printf(
					/* translators: %d: email entry number */
					esc_html__( 'Email #%d', 'easycommerce-email-tester' ),
					$i + 1
				);
				?>
				<span class="ect-badge <?php echo esc_attr( $send_badge_class ); ?>"><?php echo esc_html( $send_label ); ?></span>
				<?php if ( $tab_has_unresolved ) : ?>
					<span class="ect-badge ect-badge--warning">
						<?php
						printf(
							/* translators: %d: number of unresolved placeholders */
							esc_html__( '%d unresolved', 'easycommerce-email-tester' ),
							count( $tab_email['unresolved'] )
						);
						?>
					</span>
				<?php endif; ?>
			</button>
		<?php endforeach; ?>
	</div>

	<!-- Tab panels -->
	<?php foreach ( $result['captured'] as $index => $email ) : ?>
		<div
			class="ect-tab-panel"
			id="ect-tab-<?php echo esc_attr( (string) $index ); ?>"
			role="tabpanel"
			aria-labelledby="ect-tab-trigger-<?php echo esc_attr( (string) $index ); ?>"
			<?php if ( $index > 0 ) : ?>hidden<?php endif; ?>
		>
			<?php include EC_EMAIL_TESTER_PATH . 'templates/admin/email-entry.php'; ?>
		</div>
	<?php endforeach; ?>

<?php endif; ?>

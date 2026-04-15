<?php
/**
 * Template: Single captured email entry.
 *
 * Variables available from the parent include scope:
 *
 * @var array $result  Full result array from EC_Email_Tester_Sender::send().
 * @var array $email   Single captured email entry {
 *     @type string   $recipient      Intended recipient address.
 *     @type string   $subject_raw    Subject before placeholder resolution.
 *     @type string   $subject        Resolved subject.
 *     @type string   $body_raw       Body before placeholder resolution.
 *     @type string   $body_resolved  Body after placeholder resolution.
 *     @type array    $placeholders   Map of ##token## => value passed by EasyCommerce.
 *     @type string[] $unresolved     Tokens that remained unreplaced after resolution.
 * }
 * @var int   $index   Zero-based index of this email within $result['captured'].
 *
 * @since   1.0.0
 * @package EC_Email_Tester
 */

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$preview_html   = EC_Email_Tester_Admin::wrap_preview_html( $email['body_resolved'] );
$has_unresolved = ! empty( $email['unresolved'] );
?>

<div class="ect-email-entry" id="ect-email-entry-<?php echo esc_attr( (string) $index ); ?>">

	<!-- Meta table -->
	<table class="ect-meta-table">
		<tbody>
			<tr>
				<th><?php esc_html_e( 'To', 'easycommerce-email-tester' ); ?></th>
				<td><?php echo esc_html( $email['recipient'] ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Subject', 'easycommerce-email-tester' ); ?></th>
				<td><?php echo esc_html( $email['subject'] ); ?></td>
			</tr>
		</tbody>
	</table>

	<!-- Unresolved placeholder warning -->
	<?php if ( $has_unresolved ) : ?>
		<div class="ect-notice ect-notice--warning">
			<strong><?php esc_html_e( 'Unresolved placeholders detected:', 'easycommerce-email-tester' ); ?></strong>
			<ul class="ect-unresolved-list">
				<?php foreach ( $email['unresolved'] as $token ) : ?>
					<li><code><?php echo esc_html( $token ); ?></code></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<!-- Email body preview (iframe) -->
	<div class="ect-preview-wrap">
		<h4 class="ect-section-title"><?php esc_html_e( 'Preview', 'easycommerce-email-tester' ); ?></h4>
		<iframe
			class="ect-preview-iframe"
			srcdoc="<?php echo esc_attr( $preview_html ); ?>"
			sandbox="allow-same-origin"
			loading="lazy"
			title="<?php esc_attr_e( 'Email preview', 'easycommerce-email-tester' ); ?>"
		></iframe>
	</div>

	<!-- Source toggle -->
	<div class="ect-source-wrap">
		<button type="button" class="button ect-toggle-source" aria-expanded="false"
			data-target="ect-source-<?php echo esc_attr( (string) $index ); ?>"
			data-label-show="<?php esc_attr_e( 'Show HTML source', 'easycommerce-email-tester' ); ?>"
			data-label-hide="<?php esc_attr_e( 'Hide HTML source', 'easycommerce-email-tester' ); ?>">
			<?php esc_html_e( 'Show HTML source', 'easycommerce-email-tester' ); ?>
		</button>
		<div class="ect-source-block" id="ect-source-<?php echo esc_attr( (string) $index ); ?>" hidden>
			<textarea class="ect-source-textarea" readonly><?php echo esc_textarea( $email['body_resolved'] ); ?></textarea>
		</div>
	</div>

	<!-- Placeholder details table -->
	<?php if ( ! empty( $email['placeholders'] ) ) : ?>
		<div class="ect-placeholders-wrap">
			<?php
				/* translators: %d: number of placeholders */
				$ph_show_label = sprintf( __( 'Show placeholders (%d)', 'easycommerce-email-tester' ), count( $email['placeholders'] ) );
				/* translators: %d: number of placeholders */
				$ph_hide_label = sprintf( __( 'Hide placeholders (%d)', 'easycommerce-email-tester' ), count( $email['placeholders'] ) );
			?>
			<button type="button" class="button ect-toggle-placeholders" aria-expanded="false"
				data-target="ect-placeholders-<?php echo esc_attr( (string) $index ); ?>"
				data-label-show="<?php echo esc_attr( $ph_show_label ); ?>"
				data-label-hide="<?php echo esc_attr( $ph_hide_label ); ?>">
				<?php echo esc_html( $ph_show_label ); ?>
			</button>
			<div class="ect-placeholders-block" id="ect-placeholders-<?php echo esc_attr( (string) $index ); ?>" hidden>
				<table class="ect-placeholders-table widefat">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Token', 'easycommerce-email-tester' ); ?></th>
							<th><?php esc_html_e( 'Resolved value', 'easycommerce-email-tester' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $email['placeholders'] as $token => $value ) : ?>
							<tr>
								<td><code><?php echo esc_html( $token ); ?></code></td>
								<td><?php echo esc_html( (string) $value ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	<?php endif; ?>

</div><!-- .ect-email-entry -->

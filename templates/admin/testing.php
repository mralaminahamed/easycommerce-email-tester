<?php
/**
 * Template: Testing page — send form panel + results panel.
 *
 * Available variables (set by EC_Email_Tester_Admin::render_testing()):
 *
 * @var array<string, string> $email_types     Map of type-key => human label.
 * @var string                $selected_type   Currently selected email type key.
 * @var int                   $order_id_value   Order ID from the last POST (or 0).
 * @var array|null            $order_id_option  { id, text } for the pre-selected order, or null.
 * @var int                   $user_id_value    User ID from the last POST (or 0).
 * @var array|null            $user_id_option   { id, text } for the pre-selected user, or null.
 * @var string                $cart_hash_value  Cart hash from the last POST.
 * @var array|null            $cart_hash_option { id, text } for the pre-selected cart, or null.
 * @var string                $override_value  Override recipient from the last POST.
 * @var bool                  $dry_run_checked Whether the dry-run checkbox was checked.
 * @var array|null            $result          Sender result array, or null if not submitted.
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
			<h1><?php esc_html_e( 'Testing', 'easycommerce-email-tester' ); ?></h1>
			<p class="ect-page-header__desc">
				<?php esc_html_e( 'Trigger an email notification and inspect the resolved output.', 'easycommerce-email-tester' ); ?>
			</p>
		</div>
	</div>

	<div class="ect-layout">

		<!-- ---- Form panel ---- -->
		<div class="ect-card ect-form-card">
			<h2 class="ect-card-title"><?php esc_html_e( 'Trigger an Email', 'easycommerce-email-tester' ); ?></h2>

			<form method="post" action="" id="ect-form">
				<?php wp_nonce_field( 'ec_email_tester_send', 'ec_email_tester_nonce' ); ?>

				<!-- Email type -->
				<div class="ect-field">
					<label for="ect-email-type"><?php esc_html_e( 'Email Type', 'easycommerce-email-tester' ); ?></label>
					<select id="ect-email-type" name="email_type">
						<?php foreach ( $email_types as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $selected_type, $key ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<!-- Order ID (shown for order_* types) -->
				<div class="ect-field ect-id-field ect-field-order" id="ect-order-id-field">
					<label for="ect-order-id"><?php esc_html_e( 'Order', 'easycommerce-email-tester' ); ?></label>
					<select
						id="ect-order-id"
						name="order_id"
						class="ect-select2"
						data-endpoint="orders"
						data-placeholder="<?php esc_attr_e( 'Search by order ID, name or email…', 'easycommerce-email-tester' ); ?>"
					>
						<?php if ( $order_id_option ) : ?>
							<option value="<?php echo esc_attr( (string) $order_id_option['id'] ); ?>" selected>
								<?php echo esc_html( $order_id_option['text'] ); ?>
							</option>
						<?php endif; ?>
					</select>
					<p class="ect-hint">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=easycommerce#/orders' ) ); ?>" target="_blank">
							<?php esc_html_e( 'Browse orders ↗', 'easycommerce-email-tester' ); ?>
						</a>
					</p>
				</div>

				<!-- User (shown for new_account) -->
				<div class="ect-field ect-id-field ect-field-new-account" id="ect-user-id-field">
					<label for="ect-user-id"><?php esc_html_e( 'User', 'easycommerce-email-tester' ); ?></label>
					<select
						id="ect-user-id"
						name="user_id"
						class="ect-select2"
						data-endpoint="users"
						data-placeholder="<?php esc_attr_e( 'Search by name or email…', 'easycommerce-email-tester' ); ?>"
					>
						<?php if ( $user_id_option ) : ?>
							<option value="<?php echo esc_attr( (string) $user_id_option['id'] ); ?>" selected>
								<?php echo esc_html( $user_id_option['text'] ); ?>
							</option>
						<?php endif; ?>
					</select>
					<p class="ect-hint">
						<a href="<?php echo esc_url( admin_url( 'users.php' ) ); ?>" target="_blank">
							<?php esc_html_e( 'Browse users ↗', 'easycommerce-email-tester' ); ?>
						</a>
					</p>
				</div>

				<!-- Abandoned cart (shown for abandoned_cart) -->
				<div class="ect-field ect-id-field ect-field-abandoned-cart" id="ect-cart-hash-field">
					<label for="ect-cart-hash"><?php esc_html_e( 'Abandoned Cart', 'easycommerce-email-tester' ); ?></label>
					<select
						id="ect-cart-hash"
						name="cart_hash"
						class="ect-select2"
						data-endpoint="abandoned-carts"
						data-placeholder="<?php esc_attr_e( 'Search by hash, name or email…', 'easycommerce-email-tester' ); ?>"
					>
						<?php if ( $cart_hash_option ) : ?>
							<option value="<?php echo esc_attr( $cart_hash_option['id'] ); ?>" selected>
								<?php echo esc_html( $cart_hash_option['text'] ); ?>
							</option>
						<?php endif; ?>
					</select>
					<p class="ect-hint">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=easycommerce#/abandoned-carts' ) ); ?>" target="_blank">
							<?php esc_html_e( 'Browse abandoned carts ↗', 'easycommerce-email-tester' ); ?>
						</a>
					</p>
				</div>

				<!-- Divider -->
				<div class="ect-field-divider"></div>

				<!-- Override recipient -->
				<div class="ect-field ect-field-override">
					<label for="ect-override-email">
						<?php esc_html_e( 'Override Recipient', 'easycommerce-email-tester' ); ?>
						<span class="ect-optional"><?php esc_html_e( 'optional', 'easycommerce-email-tester' ); ?></span>
					</label>
					<input
						type="email"
						id="ect-override-email"
						name="override_email"
						value="<?php echo esc_attr( $override_value ); ?>"
						placeholder="<?php esc_attr_e( 'dev@example.com', 'easycommerce-email-tester' ); ?>"
					/>
					<p class="ect-hint">
						<?php esc_html_e( 'Redirect all outgoing mail to this address instead of the natural recipient.', 'easycommerce-email-tester' ); ?>
					</p>
				</div>

				<!-- Dry run toggle -->
				<div class="ect-field ect-field-dryrun">
					<label class="ect-toggle-label">
						<input
							type="checkbox"
							name="dry_run"
							value="1"
							id="ect-dry-run"
							<?php checked( $dry_run_checked ); ?>
						/>
						<span class="ect-toggle-text">
							<?php esc_html_e( 'Dry run — preview only, do not send', 'easycommerce-email-tester' ); ?>
						</span>
					</label>
					<p class="ect-hint">
						<?php esc_html_e( 'wp_mail() is blocked — placeholders resolve but nothing leaves the server.', 'easycommerce-email-tester' ); ?>
					</p>
				</div>

				<div class="ect-actions">
					<button type="submit" class="button button-primary">
						<?php esc_html_e( 'Send Test Email', 'easycommerce-email-tester' ); ?>
					</button>
				</div>

			</form>
		</div><!-- .ect-form-card -->

		<!-- ---- Results panel ---- -->
		<?php if ( null !== $result ) : ?>
			<div class="ect-card ect-results-card">
				<h2 class="ect-card-title"><?php esc_html_e( 'Result', 'easycommerce-email-tester' ); ?></h2>
				<?php include EC_EMAIL_TESTER_PATH . 'templates/admin/result.php'; ?>
			</div>
		<?php else : ?>
			<div class="ect-results-empty">
				<?php EC_Email_Tester_Icons::render( 'send' ); ?>
				<p><?php esc_html_e( 'Results will appear here after you trigger an email.', 'easycommerce-email-tester' ); ?></p>
			</div>
		<?php endif; ?>

	</div><!-- .ect-layout -->

</div><!-- .ect-wrap -->

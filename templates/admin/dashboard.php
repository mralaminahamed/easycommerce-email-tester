<?php
/**
 * Template: Dashboard page.
 *
 * Available variables (set by EC_Email_Tester_Admin::render_dashboard()):
 *
 * @var int    $email_type_count Number of supported email types.
 * @var string $testing_url      Admin URL for the Testing sub-page.
 * @var string $settings_url     Admin URL for the Settings sub-page.
 * @var array  $settings {
 *     @type string $default_override_email Saved default override address (may be empty).
 *     @type bool   $default_dry_run        Saved default dry-run state.
 * }
 *
 * @since   1.0.0
 * @package EC_Email_Tester
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap ect-wrap">

	<!-- ============================================================
	     Hero banner
	     ============================================================ -->
	<div class="ect-hero">
		<div class="ect-hero__icon">
			<?php EC_Email_Tester_Icons::render( 'send' ); ?>
		</div>
		<div class="ect-hero__body">
			<h1 class="ect-hero__title"><?php esc_html_e( 'EasyCommerce Email Tester', 'easycommerce-email-tester' ); ?></h1>
			<p class="ect-hero__desc">
				<?php esc_html_e( 'Trigger any EasyCommerce email notification, preview the fully-resolved template, and catch unresolved placeholders — without waiting for a real order or customer event.', 'easycommerce-email-tester' ); ?>
			</p>
		</div>
		<div class="ect-hero__action">
			<a href="<?php echo esc_url( $testing_url ); ?>" class="ect-hero-btn">
				<?php esc_html_e( 'Send Test Email', 'easycommerce-email-tester' ); ?>
			</a>
		</div>
	</div>

	<!-- ============================================================
	     Status cards
	     ============================================================ -->
	<div class="ect-stats">

		<div class="ect-stat-card ect-stat-card--blue">
			<div class="ect-stat-card__top">
				<div class="ect-stat-card__icon">
					<?php EC_Email_Tester_Icons::render( 'send' ); ?>
				</div>
			</div>
			<span class="ect-stat-card__value"><?php echo esc_html( (string) $email_type_count ); ?></span>
			<span class="ect-stat-card__label"><?php esc_html_e( 'Email types supported', 'easycommerce-email-tester' ); ?></span>
		</div>

		<div class="ect-stat-card <?php echo ! empty( $settings['default_override_email'] ) ? 'ect-stat-card--green' : 'ect-stat-card--neutral'; ?>">
			<div class="ect-stat-card__top">
				<div class="ect-stat-card__icon">
					<?php EC_Email_Tester_Icons::render( 'mail' ); ?>
				</div>
			</div>
			<span class="ect-stat-card__value">
				<?php echo ! empty( $settings['default_override_email'] )
					? esc_html( $settings['default_override_email'] )
					: esc_html__( '—', 'easycommerce-email-tester' ); ?>
			</span>
			<span class="ect-stat-card__label"><?php esc_html_e( 'Override recipient', 'easycommerce-email-tester' ); ?></span>
			<?php if ( empty( $settings['default_override_email'] ) ) : ?>
				<span class="ect-stat-card__sub"><?php esc_html_e( 'Not configured', 'easycommerce-email-tester' ); ?></span>
			<?php endif; ?>
		</div>

		<div class="ect-stat-card <?php echo $settings['default_dry_run'] ? 'ect-stat-card--orange' : 'ect-stat-card--green'; ?>">
			<div class="ect-stat-card__top">
				<div class="ect-stat-card__icon">
					<?php EC_Email_Tester_Icons::render( $settings['default_dry_run'] ? 'eye' : 'circle-check' ); ?>
				</div>
			</div>
			<span class="ect-stat-card__value">
				<?php echo $settings['default_dry_run']
					? esc_html__( 'Dry run', 'easycommerce-email-tester' )
					: esc_html__( 'Live send', 'easycommerce-email-tester' ); ?>
			</span>
			<span class="ect-stat-card__label"><?php esc_html_e( 'Default send mode', 'easycommerce-email-tester' ); ?></span>
			<span class="ect-stat-card__sub">
				<?php echo $settings['default_dry_run']
					? esc_html__( 'wp_mail() blocked by default', 'easycommerce-email-tester' )
					: esc_html__( 'Emails actually sent by default', 'easycommerce-email-tester' ); ?>
			</span>
		</div>

	</div><!-- .ect-stats -->

	<!-- ============================================================
	     Feature cards
	     ============================================================ -->
	<div class="ect-features">

		<div class="ect-feature-card">
			<div class="ect-feature-card__icon">
				<?php EC_Email_Tester_Icons::render( 'mail' ); ?>
			</div>
			<div class="ect-feature-card__body">
				<h3><?php esc_html_e( 'Testing', 'easycommerce-email-tester' ); ?></h3>
				<p><?php esc_html_e( 'Fire any email type against a real order, user, or abandoned cart. Preview the rendered body, inspect resolved placeholders, and spot broken tokens instantly.', 'easycommerce-email-tester' ); ?></p>
				<a href="<?php echo esc_url( $testing_url ); ?>" class="ect-feature-card__link">
					<?php esc_html_e( 'Go to Testing →', 'easycommerce-email-tester' ); ?>
				</a>
			</div>
		</div>

		<div class="ect-feature-card">
			<div class="ect-feature-card__icon">
				<?php EC_Email_Tester_Icons::render( 'settings' ); ?>
			</div>
			<div class="ect-feature-card__body">
				<h3><?php esc_html_e( 'Settings', 'easycommerce-email-tester' ); ?></h3>
				<p><?php esc_html_e( 'Set a default override recipient and default dry-run state so every test session starts with your preferred configuration automatically pre-filled.', 'easycommerce-email-tester' ); ?></p>
				<a href="<?php echo esc_url( $settings_url ); ?>" class="ect-feature-card__link">
					<?php esc_html_e( 'Go to Settings →', 'easycommerce-email-tester' ); ?>
				</a>
			</div>
		</div>

	</div><!-- .ect-features -->

</div><!-- .ect-wrap -->

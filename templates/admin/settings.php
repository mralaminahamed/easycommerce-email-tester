<?php
/**
 * Template: Settings page.
 *
 * Available variables (set by EC_Email_Tester_Admin::render_settings()):
 *
 * @var array $settings {
 *     @type string $default_override_email         Saved default override recipient address.
 *     @type bool   $default_dry_run                Saved default dry-run state.
 *     @type string $default_email_type             Saved default email type key.
 *     @type bool   $logger_enabled                 Whether email logging is enabled.
 *     @type bool   $logger_log_test_emails         Whether to also log test sends.
 *     @type bool   $logger_retention_count_enabled Whether to limit by count.
 *     @type int    $logger_retention_count         Max number of logs to keep.
 *     @type bool   $logger_retention_days_enabled  Whether to limit by age.
 *     @type int    $logger_retention_days          Max age in days.
 *     @type bool   $logger_delete_on_uninstall     Whether to drop the table on uninstall.
 * }
 * @var array<string, string> $email_types Map of type-key => human label (from EC_Email_Tester_Sender).
 * @var bool                  $saved       Whether the form was just saved successfully.
 *
 * @since   1.0.0
 * @package EC_Email_Tester
 */

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
?>
<div class="wrap ect-wrap">

	<!-- ---- Page header ---- -->
	<div class="ect-page-header">
		<div class="ect-page-header__body">
			<h1><?php esc_html_e( 'Settings', 'easycommerce-email-tester' ); ?></h1>
			<p class="ect-page-header__desc">
				<?php esc_html_e( 'Configure default values that pre-populate the Testing form on every page load.', 'easycommerce-email-tester' ); ?>
			</p>
		</div>
	</div>

	<?php if ( $saved ) : ?>
		<div class="ect-saved-notice">
			<?php EC_Email_Tester_Icons::render( 'circle-check' ); ?>
			<?php esc_html_e( 'Settings saved successfully.', 'easycommerce-email-tester' ); ?>
		</div>
	<?php endif; ?>

	<form method="post" action="" id="ect-settings-form">
		<?php wp_nonce_field( 'ec_email_tester_settings_save', 'ec_email_tester_settings_nonce' ); ?>

		<div class="ect-settings-layout">

			<!-- ---- Testing defaults ---- -->
			<div class="ect-card">

				<h2 class="ect-card-title"><?php esc_html_e( 'Testing Defaults', 'easycommerce-email-tester' ); ?></h2>

				<!-- Default email type -->
				<div class="ect-field">
					<label for="ect-default-email-type">
						<?php esc_html_e( 'Default Email Type', 'easycommerce-email-tester' ); ?>
					</label>
					<select id="ect-default-email-type" name="default_email_type">
						<?php foreach ( $email_types as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $settings['default_email_type'], $key ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="ect-hint">
						<?php esc_html_e( 'Email type that is pre-selected on the Testing form when no previous session value is present.', 'easycommerce-email-tester' ); ?>
					</p>
				</div>

				<!-- Default override recipient -->
				<div class="ect-field">
					<label for="ect-default-override-email">
						<?php esc_html_e( 'Default Override Recipient', 'easycommerce-email-tester' ); ?>
						<span class="ect-optional"><?php esc_html_e( 'optional', 'easycommerce-email-tester' ); ?></span>
					</label>
					<input
						type="email"
						id="ect-default-override-email"
						name="default_override_email"
						value="<?php echo esc_attr( $settings['default_override_email'] ); ?>"
						placeholder="<?php esc_attr_e( 'dev@example.com', 'easycommerce-email-tester' ); ?>"
					/>
					<p class="ect-hint">
						<?php esc_html_e( 'When set, all test emails are redirected to this address instead of the natural recipient. Useful on staging to avoid accidentally emailing real customers.', 'easycommerce-email-tester' ); ?>
					</p>
				</div>

				<!-- Default dry run -->
				<div class="ect-field ect-field-dryrun">
					<label class="ect-toggle-label">
						<input
							type="checkbox"
							name="default_dry_run"
							value="1"
							id="ect-default-dry-run"
							<?php checked( $settings['default_dry_run'] ); ?>
						/>
						<span class="ect-toggle-text">
							<?php esc_html_e( 'Enable dry run by default', 'easycommerce-email-tester' ); ?>
						</span>
					</label>
					<p class="ect-hint">
						<?php esc_html_e( 'When checked, the Testing form loads with dry-run enabled so emails are previewed without being sent unless you explicitly uncheck it.', 'easycommerce-email-tester' ); ?>
					</p>
				</div>

			</div><!-- .ect-card -->

			<!-- ---- Email Logger ---- -->
			<div class="ect-card">

				<h2 class="ect-card-title">
					<?php EC_Email_Tester_Icons::render( 'list' ); ?>
					<?php esc_html_e( 'Email Logger', 'easycommerce-email-tester' ); ?>
				</h2>

				<!-- Enable logging -->
				<div class="ect-field ect-field-toggle-parent">
					<label class="ect-toggle-label">
						<input
							type="checkbox"
							name="logger_enabled"
							value="1"
							id="ect-logger-enabled"
							<?php checked( $settings['logger_enabled'] ); ?>
						/>
						<span class="ect-toggle-text">
							<?php esc_html_e( 'Enable email logging', 'easycommerce-email-tester' ); ?>
						</span>
					</label>
					<p class="ect-hint">
						<?php esc_html_e( 'Capture every outgoing wp_mail() call and store it in the log table. Logs are accessible from the Logs page in this plugin\'s menu.', 'easycommerce-email-tester' ); ?>
					</p>
				</div>

				<!-- Child fields — visually dimmed when logging is disabled -->
				<div class="ect-logger-dependents">

					<!-- Log test emails -->
					<div class="ect-field">
						<label class="ect-toggle-label">
							<input
								type="checkbox"
								name="logger_log_test_emails"
								value="1"
								id="ect-logger-log-test"
								<?php checked( $settings['logger_log_test_emails'] ); ?>
							/>
							<span class="ect-toggle-text">
								<?php esc_html_e( 'Also log test emails', 'easycommerce-email-tester' ); ?>
							</span>
						</label>
						<p class="ect-hint">
							<?php esc_html_e( 'When checked, emails triggered from the Testing page (non-dry-run) are recorded with source "Test". Uncheck to log only real application emails.', 'easycommerce-email-tester' ); ?>
						</p>
					</div>

					<div class="ect-field-divider"></div>

					<h3 class="ect-settings-section-title"><?php esc_html_e( 'Log Retention', 'easycommerce-email-tester' ); ?></h3>

					<!-- Retention by count -->
					<div class="ect-field">
						<label class="ect-toggle-label">
							<input
								type="checkbox"
								name="logger_retention_count_enabled"
								value="1"
								id="ect-logger-retention-count-enabled"
								<?php checked( $settings['logger_retention_count_enabled'] ); ?>
							/>
							<span class="ect-toggle-text">
								<?php esc_html_e( 'Limit by count', 'easycommerce-email-tester' ); ?>
							</span>
						</label>
						<div class="ect-retention-row">
							<label for="ect-logger-retention-count" class="screen-reader-text">
								<?php esc_html_e( 'Number of logs to keep', 'easycommerce-email-tester' ); ?>
							</label>
							<?php esc_html_e( 'Keep the newest', 'easycommerce-email-tester' ); ?>
							<input
								type="number"
								id="ect-logger-retention-count"
								name="logger_retention_count"
								value="<?php echo esc_attr( (string) $settings['logger_retention_count'] ); ?>"
								min="1"
								step="1"
								class="small-text"
							/>
							<?php esc_html_e( 'log entries (oldest are deleted automatically).', 'easycommerce-email-tester' ); ?>
						</div>
					</div>

					<!-- Retention by age -->
					<div class="ect-field">
						<label class="ect-toggle-label">
							<input
								type="checkbox"
								name="logger_retention_days_enabled"
								value="1"
								id="ect-logger-retention-days-enabled"
								<?php checked( $settings['logger_retention_days_enabled'] ); ?>
							/>
							<span class="ect-toggle-text">
								<?php esc_html_e( 'Limit by age', 'easycommerce-email-tester' ); ?>
							</span>
						</label>
						<div class="ect-retention-row">
							<label for="ect-logger-retention-days" class="screen-reader-text">
								<?php esc_html_e( 'Days to keep logs', 'easycommerce-email-tester' ); ?>
							</label>
							<?php esc_html_e( 'Delete logs older than', 'easycommerce-email-tester' ); ?>
							<input
								type="number"
								id="ect-logger-retention-days"
								name="logger_retention_days"
								value="<?php echo esc_attr( (string) $settings['logger_retention_days'] ); ?>"
								min="1"
								step="1"
								class="small-text"
							/>
							<?php esc_html_e( 'days.', 'easycommerce-email-tester' ); ?>
						</div>
					</div>

					<div class="ect-field-divider"></div>

					<!-- Delete on uninstall -->
					<div class="ect-field">
						<label class="ect-toggle-label">
							<input
								type="checkbox"
								name="logger_delete_on_uninstall"
								value="1"
								id="ect-logger-delete-uninstall"
								<?php checked( $settings['logger_delete_on_uninstall'] ); ?>
							/>
							<span class="ect-toggle-text">
								<?php esc_html_e( 'Delete all logs when the plugin is uninstalled', 'easycommerce-email-tester' ); ?>
							</span>
						</label>
						<p class="ect-hint">
							<?php esc_html_e( 'When enabled, the email log table is dropped on plugin removal. Leave unchecked to preserve logs across reinstalls.', 'easycommerce-email-tester' ); ?>
						</p>
					</div>

				</div><!-- .ect-logger-dependents -->

			</div><!-- .ect-card (logger) -->

		</div><!-- .ect-settings-layout -->

		<!-- Save button — applies to the entire form, not just the logger section -->
		<div class="ect-actions">
			<button type="submit" class="button button-primary">
				<?php esc_html_e( 'Save Settings', 'easycommerce-email-tester' ); ?>
			</button>
		</div>

	</form>

</div><!-- .ect-wrap -->

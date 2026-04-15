=== EasyCommerce Email Tester ===
Contributors:      mralaminahamed
Tags:              easycommerce, email, testing, debug, developer
Requires at least: 6.5
Tested up to:      6.9
Requires PHP:      8.0
Stable tag:        1.0.0
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

A developer tool to test, preview, and debug all EasyCommerce email notifications without needing real triggers or live SMTP.

== Description ==

EasyCommerce Email Tester lets you trigger any EasyCommerce email notification on demand, preview the fully resolved template, and catch unresolved placeholders — all without waiting for a real order, customer event, or live SMTP connection.

= Key Features =

* **Trigger any email** — Fire any EasyCommerce notification (new order, processing, completed, refund, new account, abandoned cart, and more) against a real order, user, or cart.
* **Dry-run preview** — Block `wp_mail()` entirely and preview the rendered body without sending anything.
* **Override recipient** — Redirect all test mail to a single safe address so staging emails never reach real customers.
* **Placeholder inspector** — See every token that was resolved and instantly spot any that remained unresolved.
* **HTML source view** — Toggle the raw HTML source of any captured email.
* **Email logger** — Capture every outgoing `wp_mail()` call (live and test) in a searchable log table with sent/failed status, source, headers, and full body preview.
* **Log retention** — Automatically prune logs by count or age using WP-Cron.
* **Foreign asset isolation** — Strips third-party plugin and theme scripts/styles from the plugin's admin pages to prevent conflicts.

= Requirements =

* WordPress 6.5 or higher
* PHP 8.0 or higher
* EasyCommerce plugin (active)

= Usage =

1. Navigate to **Email Tester → Testing** in the WordPress admin.
2. Select an email type and a matching order, user, or abandoned cart.
3. Optionally set an override recipient or enable dry-run mode.
4. Click **Send Test Email** and inspect the result panel.

To review captured log entries, go to **Email Tester → Logs**.

Configure default form values and logging behaviour at **Email Tester → Settings**.

== Installation ==

1. Upload the `easycommerce-email-tester` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Ensure EasyCommerce is installed and active.
4. Navigate to **Email Tester** in the admin sidebar.

== Frequently Asked Questions ==

= Does this plugin send real emails? =

Only if you explicitly leave dry-run mode off. With dry-run enabled, `wp_mail()` is blocked and no mail leaves the server. You can also set an override recipient to redirect all sends to a single safe address.

= Will it interfere with my SMTP plugin? =

No. The logger hooks only into `wp_mail_succeeded` and `wp_mail_failed` — actions that fire after your SMTP plugin has already sent (or attempted to send) the email. It does not modify the `wp_mail` filter in any way that would affect delivery.

= Where are the log entries stored? =

In a custom database table (`{prefix}ec_email_tester_logs`) created on plugin activation. The table is only dropped on uninstall if you enable **Delete all logs when the plugin is uninstalled** in Settings.

= Can I limit how many log entries are kept? =

Yes. Under **Email Tester → Settings → Log Retention**, you can limit by count (keep the newest N entries) and/or by age (delete entries older than N days). Cleanup runs automatically via WP-Cron.

== Screenshots ==

1. Dashboard — overview of supported email types and current configuration.
2. Testing — send form with email type selector, order/user/cart search, and override options.
3. Result panel — send status badge, resolved email preview, placeholder table, and HTML source.
4. Logs — searchable log list with sent/failed status and source badges.
5. Log detail — full email metadata, headers, body preview, and delete action.
6. Settings — testing defaults and email logger configuration.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.

# Changelog

All notable changes to EasyCommerce Email Tester are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] — 2026-04-15

### Added
- **Testing page** — trigger any EasyCommerce email notification on demand against a real order, user, or abandoned cart.
- **Dry-run mode** — block `wp_mail()` entirely and preview the fully resolved email body without sending anything.
- **Override recipient** — redirect all test mail to a single safe address so staging emails never reach real customers.
- **Placeholder inspector** — display every token that was resolved and instantly spot any that remained unresolved.
- **HTML source view** — toggle the raw HTML source of any captured email.
- **Email logger** — capture every outgoing `wp_mail()` call (live and test) in a searchable log table with sent/failed status, source, headers, and full body preview.
- **Log retention** — automatically prune logs by count or age using WP-Cron.
- **Settings page** — configure default form values (email type, override recipient, dry-run) and all logging behaviour.
- **Dashboard** — overview of supported email types and current plugin configuration.
- **Foreign asset isolation** — strip third-party plugin and theme scripts/styles from the plugin's admin pages to prevent conflicts.
- Requires WordPress 6.5+, PHP 8.0+, and an active EasyCommerce installation.

[Unreleased]: https://github.com/mralaminahamed/easycommerce-email-tester/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/mralaminahamed/easycommerce-email-tester/releases/tag/v1.0.0

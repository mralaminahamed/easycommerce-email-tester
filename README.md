# EasyCommerce Email Tester

[![WordPress Plugin](https://img.shields.io/badge/WordPress-Plugin-blue.svg)](https://wordpress.org/)
[![License](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](http://www.gnu.org/licenses/gpl-2.0.txt)
[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-8892BF.svg)](https://php.net/)
[![Version](https://img.shields.io/badge/Version-1.0.0-green.svg)]()

A developer tool to test, preview, and debug all EasyCommerce email notifications without needing real triggers or live SMTP.

## What is EasyCommerce Email Tester?

**EasyCommerce Email Tester** lets you trigger any EasyCommerce email notification on demand, preview the fully resolved template, and catch unresolved placeholders — all without waiting for a real order, customer event, or live SMTP connection. Whether you're:

- **Developing** new email templates and need instant feedback
- **Debugging** placeholder resolution issues
- **Testing** email rendering across different scenarios
- **Staging** without risking emails reaching real customers

## Get Started in Minutes

1. **Install & Activate**: Upload the plugin and activate it from the Plugins screen
2. **Navigate**: Go to WordPress Admin → **Email Tester**
3. **Select**: Choose an email type and a matching order, user, or abandoned cart
4. **Send**: Click **Send Test Email** and inspect the result panel

## Key Features

- **Trigger any email** — Fire any EasyCommerce notification (new order, processing, completed, refund, new account, abandoned cart, and more) against a real order, user, or cart.
- **Dry-run preview** — Block `wp_mail()` entirely and preview the rendered body without sending anything.
- **Override recipient** — Redirect all test mail to a single safe address so staging emails never reach real customers.
- **Placeholder inspector** — See every token that was resolved and instantly spot any that remained unresolved.
- **HTML source view** — Toggle the raw HTML source of any captured email.
- **Email logger** — Capture every outgoing `wp_mail()` call (live and test) in a searchable log table with sent/failed status, source, headers, and full body preview.
- **Log retention** — Automatically prune logs by count or age using WP-Cron.
- **Foreign asset isolation** — Strips third-party plugin and theme scripts/styles from the plugin's admin pages to prevent conflicts.

## Requirements

- **WordPress**: 6.5+
- **PHP**: 8.0+
- **EasyCommerce Plugin**: Required (active)
- **Composer**: For PHP dependencies

## Quick Commands

```bash
# Install dependencies
composer install

# Code quality
composer phpcs          # PHP CodeSniffer (WordPress standards)
composer phpstan        # PHP Static Analysis
composer test           # PHPUnit tests

# Generate translation template
composer makepot

# Build release ZIP
composer release
```

## Screenshots

### Testing Page
Send form with email type selector, order/user/cart search, and override options.

### Result Panel
Send status badge, resolved email preview, placeholder table, and HTML source view.

### Logs
Searchable log list with sent/failed status and source badges.

### Settings
Testing defaults and email logger configuration.

## Documentation

| Document | Description |
|---|---|
| [readme.txt](readme.txt) | WordPress.org plugin readme |
| [CHANGELOG.md](CHANGELOG.md) | Version history and release notes |
| [SECURITY.md](SECURITY.md) | Security policy and vulnerability reporting |

## License

GPL v2 or later — see [LICENSE](LICENSE) file.

## Author

**Al Amin Ahamed**

- Website: [alaminahamed.com](https://alaminahamed.com)
- GitHub: [@mralaminahamed](https://github.com/mralaminahamed)
- Email: me@alaminahamed.com

## Support

[GitHub Issues](https://github.com/mralaminahamed/easycommerce-email-tester/issues) | [Changelog](CHANGELOG.md)

---

**EasyCommerce Email Tester v1.0.0**

_Test, preview, and debug EasyCommerce email notifications without real triggers or live SMTP._

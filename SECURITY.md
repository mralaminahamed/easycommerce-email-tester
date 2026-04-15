# Security Policy

## Supported Versions

We actively maintain and provide security updates for the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 1.0.x   | :white_check_mark: |

## Reporting a Vulnerability

We take security vulnerabilities seriously. If you discover a security vulnerability in EasyCommerce Email Tester, please report it responsibly.

### How to Report

**Please do NOT report security vulnerabilities through public GitHub issues.**

Instead, please report security vulnerabilities by emailing:

- **Email**: me@alaminahamed.com
- **Subject**: [SECURITY] EasyCommerce Email Tester - Brief Description

### What to Include

When reporting a vulnerability, please include as much information as possible:

1. **Type of vulnerability** (e.g., XSS, SQL injection, privilege escalation, etc.)
2. **Location** of the affected source code (file path and line number if possible)
3. **Step-by-step instructions** to reproduce the issue
4. **Proof of concept** or exploit code (if applicable)
5. **Impact** assessment of the vulnerability
6. **Suggested fix** (if you have one)
7. **WordPress version** where the issue was discovered
8. **Plugin version** affected

### Response Timeline

We aim to respond to security reports according to the following timeline:

- **Initial Response**: Within 48 hours
- **Assessment**: Within 7 days
- **Fix Development**: Within 30 days (depending on complexity)
- **Release**: Coordinated with reporter

### Disclosure Policy

We follow responsible disclosure practices:

1. **Private reporting** — Issues are reported privately first
2. **Assessment and fix** — We assess and develop fixes internally
3. **Coordinated disclosure** — We coordinate the public disclosure with the reporter
4. **Public disclosure** — After fixes are released and users have time to update

## Security Measures

### Input Validation & Sanitization

- All user inputs are sanitized using appropriate WordPress functions (`sanitize_text_field`, `absint`, `sanitize_email`, etc.)
- Nonce verification on all form submissions and AJAX requests
- Strict capability checks (`manage_options`) for all admin functions
- All output is escaped using `esc_html`, `esc_attr`, `esc_url`, `esc_textarea`

### Access Control

- All plugin pages require `manage_options` capability
- Log deletion protected by per-entry nonces
- Settings save protected by nonce verification

### Database Security

- Exclusive use of WordPress database API (`$wpdb`)
- Prepared statements for all parameterised queries
- No direct SQL query construction from user input

### Email Logger Security

- Logs stored in a dedicated custom table with no user-controlled schema
- Log entries can only be deleted by administrators with valid nonces
- Sensitive email content displayed only within the WordPress admin

## Vulnerability Scope

### High Priority

- **Cross-Site Scripting (XSS)** — Stored or reflected XSS in log entries or result panels
- **SQL Injection** — Any form of SQL injection via log or search inputs
- **Privilege Escalation** — Accessing plugin features without `manage_options`
- **CSRF** — State-changing actions without nonce protection

### Out of Scope

- Issues in third-party dependencies (report to the respective maintainers)
- WordPress core vulnerabilities (report to the WordPress security team)
- EasyCommerce plugin vulnerabilities (report to the EasyCommerce team)
- Theoretical vulnerabilities without practical impact
- Issues requiring admin access to exploit (unless privilege escalation)

## Contact Information

- **Primary Email**: me@alaminahamed.com
- **GitHub**: [@mralaminahamed](https://github.com/mralaminahamed)
- **Response Time**: Within 48 hours

---

**Thank you for helping keep EasyCommerce Email Tester and its users secure!**

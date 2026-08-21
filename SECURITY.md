# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 3.3.x   | :white_check_mark: |
| < 3.3   | :x:                |

## Reporting a Vulnerability

We take the security of Npcink Site Toolbox seriously. If you discover a security vulnerability, please follow these steps:

1. **Do NOT** open a public issue for sensitive security reports
2. Send details to the repository maintainer via private message
3. Include:
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if any)

## Security Measures

This plugin implements the following security measures:

- **SQL Injection Prevention**: All database queries use `$wpdb->prepare()`
- **CSRF Protection**: AJAX endpoints use `check_ajax_referer` nonce verification
- **XSS Prevention**: All output is escaped using `esc_html()`, `esc_url()`, `esc_attr()`
- **Authorization**: Sensitive operations check `current_user_can('manage_options')`
- **Input Sanitization**: User input is sanitized using `sanitize_text_field()`, `wp_unslash()`

## Security Audit History

- **2024-06**: Initial security hardening - fixed 13 categories of vulnerabilities
- **2024-12**: Code quality review - fixed XSS in go/index.php, added error handling to wx_xcx_link

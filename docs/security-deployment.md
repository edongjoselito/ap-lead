# Signup design and request protection

Deploy these files together, preserving the production database connection settings:

- The root `.htaccess`, every folder `.htaccess` listed below, and `robots.txt`
- `application/controllers/Pages.php`
- `application/config/request_guard.php`
- `application/libraries/Request_guard.php`
- `application/libraries/Signup_captcha.php`
- `application/views/pages/school_signup.php`
- `assets/css/school-signup.css`

The signup page uses the landing page's navy (`#103f6e`), gold (`#f0aa20`),
light blue surfaces, type styles, and existing `assets/r11-logo.jpg` seal.

## Production configuration

Keep the matching **v2 Checkbox** key pair in production `recaptcha_settings`,
row `id=1`, with `expected_hostname=ap.depedmis.com`. Local database settings
are not copied by deploying PHP files. The secret stays server-side.

PHP must be able to create/write `application/cache/request-guard/`. Counters
are shared across sessions and PHP workers using file locks. They contain hashed
identifiers, not raw client IPs. A storage error returns HTTP 503 rather than
silently disabling protection. Use ownership/permissions appropriate to the PHP
worker; do not make the cache world-writable. Set `APLEAD_GUARD_DIRECTORY` to use
a private directory outside the web root instead. Expired files are cleaned up
opportunistically after 24 hours, with bounded work per request.

Thresholds are configurable in `application/config/request_guard.php`:

| Endpoint | Requests per client IP |
| --- | --- |
| Landing, signup, sign-in, and password-reset page views | 120 per minute |
| Signup submissions | 20 per 15 minutes |
| Email availability | 60 per 5 minutes |
| District lookup | 120 per 5 minutes |
| Login submissions | 30 per 5 minutes, plus existing per-account restrictions |
| Password reset submissions | 5 per 15 minutes |

Requests beyond a limit receive HTTP 429 and `Retry-After`. Signup errors retain
school details; users must re-enter their password and complete a new CAPTCHA.
Consider shared school networks when changing thresholds. Counters require a
shared filesystem if the app runs on multiple servers; otherwise use a shared
store or an edge rate limiter for a fleet-wide limit.

If production runs behind a reverse proxy/CDN, configure only its trusted IP
addresses/CIDRs in CodeIgniter's `proxy_ips` setting, or arrange trusted client-IP
restoration in the web server. Untrusted forwarded headers cannot bypass limits.
Without correct proxy configuration all visitors may share the proxy's limit.

Apache rules reject unsupported methods, known scanner signatures, common probe
paths, executable uploads (including double extensions), and private/backup files.
Public form bodies are limited to 32 KiB; regular authenticated file uploads retain
their existing limits. ACME certificate validation paths remain available.

`robots.txt` keeps compliant crawlers on the public landing page and assets.
Account/internal pages also send `X-Robots-Tag: noindex, nofollow`. These are
crawler instructions, not access controls. Bots can spoof User-Agent values;
the server request limits, CSRF, authorization, and reCAPTCHA remain the controls.
High-volume attacks still need hosting/CDN-level filtering before traffic reaches PHP.

## Folder access rules

Include hidden files when uploading. These folder rules work independently of
the root rewrite rules and are inherited by their subdirectories:

| Folder `.htaccess` | HTTP access |
| --- | --- |
| `application/`, `system/`, `vendor/`, `database/`, `docs/`, `tests/` | Deny all direct requests |
| `application/config/`, `application/cache/`, `application/logs/` | Additional independent deny-all rules |
| `resources/For the Development/`, `resources/updates/` | Deny internal documents |
| `assets/` | Serve static assets; deny scripts, dotfiles, backups, dependency manifests, and source maps |
| `resources/` | Serve public manuals; deny scripts, dotfiles, credentials, and backups |
| `uploads/`, `uploads/division_logos/` | Serve raster images only; deny scripts, double extensions, HTML, and SVG |

Directory listings, CGI execution, and server-side includes are disabled in
public file folders. The root rules also deny Markdown/reStructuredText documents
such as `SECURITY_DEPLOYMENT.md`. PHP includes and CLI dependency/test tools still
work because these are HTTP access restrictions, not filesystem restrictions.
Apache must honor `.htaccess` for the deployed directories (`AllowOverride`);
verify the expected HTTP statuses after uploading hidden files.

## Deployment checks

Check that homepage, signup, sign-in, district selection, and static assets load;
complete one live signup CAPTCHA and sign-in. Verify `.env`, backup files,
`uploads/probe.php.jpg`, and `/wp-login.php` return 403, and unsupported methods
return 405. Verify HTTPS and Google reCAPTCHA connections are available. If the
host rejects an Apache directive, consult its error log rather than removing
all protection rules. Keep local development on its existing localhost URL.

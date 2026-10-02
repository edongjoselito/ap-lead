# AP-LEAD production security requirements

The application now defaults to the `production` environment. A production
deployment must use HTTPS and provide secrets through the web-server or service
environment; do not put them in the repository or a web-accessible `.env` file.

Required environment variables:

- `APP_BASE_URL` — canonical HTTPS URL ending in `/`
- `APP_ENCRYPTION_KEY` — at least 32 cryptographically random bytes
- `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD`, `DB_DATABASE`
- `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_PORT`, `MAIL_CRYPTO`

For local HTTP-only development, set `CI_ENV=development`. Never use that value
on an internet-facing server.

School signup uses a local PNG CAPTCHA and does not require Google reCAPTCHA
keys or network access. Enable PHP GD and allow the web-server user to create a
private application-specific directory under PHP's temporary directory for signup
rate limiting. Challenges expire after five
minutes and are single-use; each IP may submit ten attempts per fifteen-minute
window (100 attempts for direct loopback requests in development mode). Storage
failures block signup with a separate availability message. The local file rate limiter is intended for a single application server;
use shared rate-limit storage when scaling to multiple servers. Email verification
still requires the configured SMTP service.

Deployment checklist:

1. Rotate the database and SMTP credentials that were previously committed.
2. Rotate every test or shared account whose password is known, reused, or
   matches its username. Issue a unique password of at least 12 characters to
   each person and do not share privileged accounts between staff.
3. Remove repository metadata, SQL exports, backups, and migration utilities
   from the deployed document root. The Apache rules block them as defense in
   depth, but they should not be deployed.
4. Configure the virtual host to redirect all HTTP traffic to HTTPS and enable
   HSTS only after the final hostname and all subdomains support HTTPS.
5. Use a dedicated database account. After deployment migrations complete,
   restrict it to only the data permissions the running application needs.
6. Disable directory listing, PHP execution in `uploads/`, verbose PHP errors,
   and server version banners at the virtual-host/PHP configuration level too.
7. Store sessions and login throttling in a shared protected backend when more
   than one application server is used.
8. Send application, authentication, and audit logs to a protected centralized
   log service with alerting, retention, and restricted administrator access.
9. Back up the database encrypted, test restoration regularly, and keep backups
   outside the web root.
10. Require MFA for privileged accounts at the identity-provider or reverse-
   proxy layer until native MFA is implemented.
11. Run an authenticated penetration test and access-control matrix test in a
    staging clone before government production accreditation.

Certificate QR codes issued before the signed-link hardening must be regenerated;
the old unsigned verifier is intentionally disabled.

The current Content Security Policy permits inline scripts/styles for legacy
views. Refactor those assets to nonce- or hash-based loading before removing the
`unsafe-inline` compatibility allowance.

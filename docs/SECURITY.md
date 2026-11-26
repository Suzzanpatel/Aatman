# Security

This page lists the security controls in Aatman and the vulnerabilities that were found and fixed in the November 2026 overhaul. Each fix has an automated check in `tests/e2e_smoke.py`.

## ⚠️ Action required: rotate the leaked Gemini API key

The original `chatbot.php` contained a **hard-coded Google Gemini API key**, and the repository is public. Removing it from the code does **not** remove it from git history. The key must be **revoked in Google AI Studio** and replaced with a new one, stored only in `.env`.

The old `Aatman_final.sql` also contained what looked like real names, phone numbers and a home address. That file has been removed and replaced with fictitious seed data, but it is still in git history. If that data is real, consider rewriting history (`git filter-repo`) or making the old commits private.

## Vulnerabilities fixed

| # | Severity | Issue (before) | Fix (now) |
|---|---|---|---|
| 1 | **Critical** | **Hard-coded Gemini API key** in `chatbot.php`, publicly visible | Key read from the `GEMINI_API_KEY` env var; `.env` is git-ignored; key sent in a header, not the URL |
| 2 | **Critical** | **Admin bypass**: `admin.php` ran `$_SESSION['admin_logged_in'] = true` *before* checking it, so every logged-in user could open the admin panel and see every user's personal data | `require_role('admin')` checks `users.role` from the DB |
| 3 | **Critical** | **Privilege escalation at sign-up**: the `role` field was inserted as sent, so posting `role=admin` created an admin | Role allow-list (`ngo, donor, need, volunteer`); admins only via the `scripts/create_admin.php` CLI |
| 4 | **High** | **Unauthenticated campaign status changes**: `update_campaign_status.php` had no session or role check at all | `campaign_status.php`: admin for any campaign, NGO only for its own (ownership in the `WHERE` clause) |
| 5 | **High** | **IDOR on onboarding pages**: `*_details.php?user_id=N` let *anyone* write profile data for *any* account | User id comes from the server-side session set at sign-up; already-onboarded accounts are redirected away |
| 6 | **High** | **No CSRF protection** on any form; delete/complete actions were plain GET links (`?delete_id=`) | Synchronizer token on every POST (checked centrally in `bootstrap.php`); state changes are POST-only; logout is POST |
| 7 | **High** | **Missing role checks**: `volunteer_dashboard.php` and `campaign.php` only checked "logged in", so any role could create campaigns as an NGO | `require_role()` on every dashboard and endpoint |
| 8 | **Medium** | **Deactivated accounts could still log in**, and live sessions survived deactivation | Login rejects `inactive`; `current_user()` re-checks status on every request and ends the session |
| 9 | **Medium** | **Session fixation**: session id was not regenerated on login | `session_regenerate_id(true)` on login, sign-up and logout; `use_strict_mode`; `HttpOnly`, `SameSite=Lax` and (on HTTPS) `Secure` cookie |
| 10 | **Medium** | **Unlimited password guessing** | `login_attempts` table: 5 failures per email or 20 per IP within 15 minutes triggers a lockout |
| 11 | **Medium** | **User enumeration**: "No user found with that email" vs "Invalid password" | One generic message, plus a dummy `password_verify` so response timing matches |
| 12 | **Medium** | **XSS in the chatbot**: replies (and user input) were inserted with `innerHTML` | `textContent` only |
| 13 | **Medium** | **Open chatbot proxy**: `Access-Control-Allow-Origin: *`, no auth, no limits, raw upstream errors (`debug_raw`) returned to the browser | Same-origin only, CSRF token, 20 messages per 10 minutes per session, 500-character cap, errors logged server-side |
| 14 | **Medium** | **Error/information disclosure**: `display_errors=1` and raw DB errors (`$e->getMessage()`, `$conn->error`) shown to users | Errors hidden unless `APP_ENV=development`; logged with `error_log`; users see generic messages |
| 15 | **Medium** | **Client-controlled fields**: request `status` came from a hidden form field; enums and quantity were not validated | Server sets `status='pending'`, `user_id`, `user_role`; every enum is allow-listed; numbers, phones and dates are validated |
| 16 | Low | Unescaped output (`dashboard.php` echoed `$_SESSION['name']` raw; admin `$conn->error` raw) | All output goes through `e()` (`htmlspecialchars`, `ENT_QUOTES`, UTF-8) |
| 17 | Low | DB connection as `root` with an empty password, hard-coded in `config.php` | Credentials from `.env`; `create_database.sql` makes a least-privilege user |
| 18 | Low | No password policy; login `trim()`med passwords but sign-up didn't, so passwords with spaces could never log in | Minimum 8 characters, confirmation field, no trimming on either side; `password_needs_rehash` upgrade |
| 19 | Low | No security headers; source folders and the SQL dump were web-accessible | `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`; `public/` web root plus `.htaccess` deny rules |
| 20 | Low | Real-looking personal data in the committed SQL dump | Replaced with fictitious demo data |

## Controls at a glance

- **Authentication:** bcrypt via `password_hash(PASSWORD_DEFAULT)`, timing-safe login, lockout, rehash on login
- **Sessions:** custom name, strict mode, cookie-only, `HttpOnly`, `SameSite=Lax`, `Secure` on HTTPS, id rotated on every privilege change
- **Authorisation:** `require_role()` reads the role from the DB on every request; ownership enforced in SQL; admin-only user management; admins can't lock out admins
- **CSRF:** a 256-bit per-session token, compared with `hash_equals`; all POSTs are checked centrally, including the JSON API (`X-CSRF-Token`)
- **Injection:** 100% prepared statements (`db_exec`). The only interpolated identifiers are hard-coded table-name constants.
- **XSS:** `e()` on all output; `textContent` in JS; no user data in inline scripts
- **Secrets:** `.env` (git-ignored) or real environment variables; nothing secret in the repo
- **Errors:** logged, never shown in production
- **Database:** least-privilege user, FK/CHECK/ENUM constraints, utf8mb4

## Recommended next steps

- Serve over **HTTPS** only and add `Strict-Transport-Security`.
- Add a **Content-Security-Policy** once the Google Translate and CDN origins are pinned.
- Add **email verification** and a **password-reset** flow (both need an SMTP service).
- Add an **admin approval** step for NGO registration numbers before NGOs become visible.
- Store chatbot rate-limit counters in the DB or Redis if you run several PHP servers.

## Reporting a vulnerability

Please open a private security advisory on GitHub (Security → Advisories) rather than a public issue.

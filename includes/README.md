# `includes/`: application core (not web-accessible)

Shared PHP loaded by the pages in `public/`. A `.htaccess` here denies direct web access, in case the document root is the repository root.

| File | Responsibility |
|---|---|
| `bootstrap.php` | **Included first by every page.** Loads everything below, starts a hardened session, sends security headers, rejects POSTs without a valid CSRF token |
| `config.php` | Loads `.env`, defines constants (DB, Gemini, security limits) |
| `db.php` | `db()` connection; `db_exec()`, `db_one()`, `db_all()`: prepared statements only |
| `auth.php` | `current_user()`, `login_user()`, `logout_user()`, `require_login()`, `require_role()`, `require_onboarding()`, role → dashboard map |
| `csrf.php` | `csrf_token()`, `csrf_field()`, `csrf_enforce()` |
| `helpers.php` | `e()` escaping, `url()`, `redirect()`, flash messages, input/validation helpers, domain enums (roles, categories, statuses…) |
| `repository.php` | Shared queries and writes: pending requests, campaigns, request creation, profile updates, self-deactivation |
| `views.php` | Shared HTML: requests table, campaigns table, "my requests" table, request form, status badges |
| `partials/head.php` | `<head>` + CSS (`$page_title`, `$page_css`) |
| `partials/navbar.php` | Top bar: user name and role, logout, language selector, flash message |
| `partials/foot.php` | AI assistant widget, page scripts (`$page_js`), Google Translate loader |

## Conventions

- **Never trust the client for identity or role.** Use `$user = require_role(...)`, which reads the DB on every request.
- **Ownership goes in SQL:** `WHERE id = ? AND user_id = ?`.
- **Validate with allow-lists:** `post_enum('category', CATEGORIES)`.
- **Escape on output:** `<?= e($value) ?>`.
- **Errors:** `report_error($e, 'Friendly message')` logs the details and returns a safe message.

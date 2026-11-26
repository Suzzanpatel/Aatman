# Changelog

## 2.0.0: Security & structure overhaul (2026-11-26)

### Security
All 20 issues are described in [SECURITY.md](SECURITY.md#vulnerabilities-fixed). In short: removed the hard-coded API key, closed the admin bypass, blocked self-assigned admin role, added authentication to campaign status changes, fixed the onboarding IDOR, added CSRF everywhere, enforced role checks on every page, made deactivation actually block access, rotated session ids, added login throttling, stopped user enumeration, fixed chatbot XSS, locked down the chatbot endpoint, hid internal errors, and validated all input server-side.

### Functional bugs fixed

| Where | Bug | Fix |
|---|---|---|
| Admin dashboard | `script.js` crashed on load (looked for chat elements that don't exist), so **tabs never worked** | Split into `dashboard.js` (tabs) and `chat-widget.js` (chat), both null-safe |
| Admin dashboard | All four buttons had `class="active"` and the Donors and NGOs sections were both visible at once | Proper single-active tab state with `#hash` deep links |
| Landing page | **Chat button did nothing**: the open/close code was in `script.js`, which the landing page didn't load | Widget toggle is in `chat-widget.js`, loaded everywhere the widget appears |
| NGO dashboard | **Campaign status dropdown did nothing**: `onchange="this.form.submit()"` doesn't send the hidden submit button's name, so the handler never ran | Dropdown posts to `campaign_status.php` with its own fields |
| NGO dashboard | Profile update wrote to `ngo_details` but the table was `NGO_details` (fails on Linux) | Table renamed consistently; migration provided |
| NGO dashboard | Deactivation set `status = 'Inactive'` (wrong case for the enum) and didn't block login | Shared `deactivate_self()`; login checks status |
| Donor dashboard | Campaign list joined `users ON c.id = u.id` (campaign id = user id), so **NGO names were wrong or missing** and disabled campaigns showed | Joins `ngo_id`; only active campaigns; shows organisation name |
| Donor dashboard | State couldn't be edited; unclosed `<div class="container">` | State field added; markup fixed |
| Volunteer dashboard | Form read from an **undefined `$volunteer` variable**, so it was never pre-filled (and threw PHP warnings) | Reads the fetched profile |
| Volunteer dashboard | Status option `inactive` isn't in the enum (`active/disabled`), so saving failed | Uses `active/disabled` ("Available / Not available") |
| Volunteer dashboard | "Other" category free text was ignored; "Contact person" field was mislabelled | Free text saved; fields renamed to emergency contact; alt phone added |
| Needy / NGO | New requests took `status` from a hidden input (anyone could post "completed" requests) | Status forced to `pending` server-side |
| NGO "My requests" | Complete/Delete buttons were commented out (TODO) | Implemented via `request_action.php` (shared with people in need) |
| Sign-up flow | After onboarding, users were sent to a dashboard while not logged in, so they bounced to login | Onboarding logs the new user in and opens their dashboard |
| Sign-up | No validation; a duplicate email showed a JS `alert()` | Server-side validation with inline errors; confirm-password field |
| Login | `trim()` on the password at login but not at sign-up | No trimming on either side |
| Campaign form | No date validation (end before start allowed) | Validated in PHP and by a DB `CHECK` |
| Shared navbar | `navbar.php` output a complete second `<!DOCTYPE html><html><body>` inside each page | It's now a partial; the page shell lives in `partials/head.php` / `foot.php` |
| Layout | Content column shrank to its content (narrow campaign form; wide tables overflowed the page) | Flex-item width fix; tables scroll inside their card |
| `dashboard.php` | Generic page that echoed the name unescaped and was never linked | `dashboard/index.php` redirects to the user's own dashboard |
| HTML | `colspan'10'` typos, duplicate `</tbody>`, duplicate `id="open-login"` | Fixed |
| Landing | "Donate Now" pointed to `#` | Goes to sign-up (or to your dashboard if logged in) |

### Added
- Admin **Overview** tab with platform counts, and **activate/deactivate** for any non-admin account
- `scripts/create_admin.php` to create or promote admins safely
- Chat assistant on dashboards (not only the landing page), aware of the user's role and replying in the user's language
- Flash messages after every action; Cancel button when editing a profile; status badges
- `tests/e2e_smoke.py` with 60+ functional and security checks
- GitHub Actions CI (PHP lint, secret scan, end-to-end tests on MariaDB)
- GitHub community files: `CONTRIBUTING.md`, `SECURITY.md`, issue forms, pull-request template
- A `README.md` in every top-level folder, `docs/USER_GUIDE.md`, and screenshots in `docs/images/`

### Restructured

| Before (flat, 25 files in root) | After |
|---|---|
| `landing.php` | `public/index.php` |
| `login.php`, `signup.php`, `logout.php` | `public/` |
| `*_dashboard.php`, `admin.php` | `public/dashboard/{ngo,donor,volunteer,needy,admin}.php` |
| `campaign.php`, `update_campaign_status.php` | `public/dashboard/campaign_create.php`, `campaign_status.php` |
| `*_details.php` | `public/onboarding/{ngo,donor,volunteer,needy}.php` |
| `chatbot.php` | `public/api/chatbot.php` |
| `style.css`, `forms.css`, `landing.css` | `public/assets/css/` (+ `chat.css`; ~600 lines of duplicated inline `<style>` removed) |
| `script.js`, `chatbot.js` | `public/assets/js/dashboard.js`, `chat-widget.js`, `translate.js` |
| `config.php`, `navbar.php` | `includes/` (+ `bootstrap`, `auth`, `csrf`, `db`, `helpers`, `repository`, `views`, `partials/`) |
| `Aatman_final.sql` | `database/schema.sql`, `seed.sql`, `create_database.sql`, `migrations/` |
| – | `docs/`, `README.md`, `.env.example`, `.gitignore`, `.htaccess` rules |

## 1.0.0 (2025-10-27)
Initial upload.

# Architecture

Aatman is a server-rendered PHP application with no framework. It is organised into small layers so that each concern lives in exactly one place.

```
Browser ──HTTP──▶ public/<page>.php
                     │ require includes/bootstrap.php
                     │   ├─ config.php      .env → constants
                     │   ├─ helpers.php     e(), url(), redirect(), flash(), validation, enums
                     │   ├─ db.php          db(), db_exec(), db_one(), db_all()  (prepared statements only)
                     │   ├─ csrf.php        token + csrf_enforce() on every POST
                     │   ├─ auth.php        current_user(), require_role(), login_user()
                     │   ├─ repository.php  shared queries / writes
                     │   └─ views.php       shared HTML fragments
                     │   + hardened session, security headers
                     │
                     ├─ 1. guard     $user = require_role('ngo');
                     ├─ 2. actions   if (is_post() && isset($_POST['…'])) { validate → write → flash → redirect }
                     ├─ 3. data      SELECTs for the page
                     └─ 4. render    partials/head → navbar → HTML → partials/foot
```

## Request lifecycle

1. **Bootstrap**: every page starts with `require_once __DIR__ . '/../includes/bootstrap.php'`. It loads the configuration, starts a hardened session, sends the security headers, and **rejects any POST without a valid CSRF token** before page code runs.
2. **Guard**: `require_role(...)` re-reads the user from the database. If they are not logged in, they are redirected to the login page. If they have the wrong role they get a 403. If they have been deactivated, their session ends.
3. **Action (POST)**: input goes through allow-lists (`post_enum`) and validators (`valid_phone`, `valid_date`, …). The page writes with prepared statements (in a transaction when two tables change), sets a flash message, and redirects (Post/Redirect/Get) to the right tab using `#section`.
4. **Render**: every dynamic value goes through `e()` (HTML escaping). `dashboard.js` handles the tabs and inline editing. `chat-widget.js` handles the assistant.

## Role-based access control

The session stores only `user_id`. The **role is never trusted from the session or the request**. It is read from `users.role` on every protected request.

| Page / endpoint | need | ngo | donor | volunteer | admin | anonymous |
|---|:-:|:-:|:-:|:-:|:-:|:-:|
| `index.php`, `login.php`, `signup.php` | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |
| `onboarding/<role>.php` | own role, only right after sign-up or with details still missing | | | | | ✖ |
| `dashboard/needy.php` | ✔ | 403 | 403 | 403 | 403 | → login |
| `dashboard/ngo.php`, `campaign_create.php` | 403 | ✔ | 403 | 403 | 403 | → login |
| `dashboard/donor.php` | 403 | 403 | ✔ | 403 | 403 | → login |
| `dashboard/volunteer.php` | 403 | 403 | 403 | ✔ | 403 | → login |
| `dashboard/admin.php` | 403 | 403 | 403 | 403 | ✔ | → login |
| `dashboard/request_action.php` | own requests | own requests | 403 | 403 | 403 | → login |
| `dashboard/campaign_status.php` | 403 | own campaigns | 403 | 403 | any campaign | → login |
| `api/chatbot.php` | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ (rate-limited) |

**Ownership** is enforced in SQL, not only in the UI. For example: `UPDATE campaigns … WHERE id = ? AND ngo_id = ?` and `DELETE FROM requests WHERE id = ? AND user_id = ?`. Changing an id in the browser cannot reach someone else's data.

**Admin accounts** can only be created with `scripts/create_admin.php`. The sign-up form only accepts `ngo | donor | need | volunteer`. Admins cannot deactivate themselves or other admins from the UI.

## Sign-up flow

```
signup.php ──insert users──▶ session.onboarding_user_id = id
    └─▶ onboarding/<role>.php ──insert <role>_details──▶ login_user() ──▶ dashboard/<role>.php
```

The onboarding pages take the user id from the **server-side session**. The old version read it from `?user_id=`, which let anyone write profile data for any account.

## Main features → code

| Feature | Where |
|---|---|
| Help requests (create / list / complete / delete) | `repository.php::create_request_from_post`, `pending_requests`, `views.php::render_request_form`, `render_my_requests_table`, `dashboard/request_action.php` |
| Campaigns | `dashboard/campaign_create.php` (iframe inside the NGO dashboard), `repository.php::campaigns`, `views.php::render_campaigns_table`, `dashboard/campaign_status.php` |
| Profiles | Each dashboard's `update_profile` handler, `repository.php::update_user_core`, `validate_user_core` |
| Self-deactivation | `repository.php::deactivate_self` |
| Admin user management | `dashboard/admin.php` (`set_user_status`) |
| AI assistant | `public/api/chatbot.php` ← `assets/js/chat-widget.js` (widget markup in `partials/foot.php`) |
| Multilingual UI | Google Translate widget: `#google_translate_element` in the navbar / landing header, initialised by `assets/js/translate.js` |

## AI assistant

`chat-widget.js` POSTs `{message}` as JSON with an `X-CSRF-Token` header. `chatbot.php` then:

1. validates the method, length (≤ 500 characters) and the per-session rate limit (20 messages per 10 minutes);
2. builds a **system instruction** describing Aatman and the user's role. The user's text is sent as a separate `contents` part instead of being pasted into the prompt;
3. calls Gemini `generateContent` with the key in the `x-goog-api-key` header, with a 20-second timeout;
4. returns `{reply}`. Upstream errors are logged server-side and never sent to the browser.

The widget renders replies with `textContent`, so neither the user nor the model can inject HTML.

## Front-end

- `assets/css/dashboard.css`: dashboard layout and shared components (alerts, buttons, badges, tables)
- `assets/css/forms.css`: login, sign-up, onboarding and campaign forms
- `assets/css/landing.css`: landing page
- `assets/css/chat.css`: AI assistant widget
- `assets/js/dashboard.js`: tabs (with `#hash` deep links), Edit/Save/Cancel profile toggle, auto-submit selects, confirm dialogs
- `assets/js/chat-widget.js`, `assets/js/translate.js`

No inline event handlers are used for the core interactions, which keeps the door open for a strict Content-Security-Policy later.

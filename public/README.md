# `public/`: web root

This is the **only** folder a browser can reach. In production, point Apache or Nginx's document root here (see [docs/SETUP.md](../docs/SETUP.md)).

| Path | What it is | Who can access it |
|---|---|---|
| `index.php` | Landing page with the AI assistant and language selector | Everyone |
| `login.php` / `signup.php` / `logout.php` | Authentication (logout is POST + CSRF) | Everyone |
| `onboarding/` | Role-specific profile step right after sign-up | The just-created account only |
| `dashboard/` | One dashboard per role, plus action endpoints | Logged-in users, by role |
| `api/chatbot.php` | JSON endpoint for the AI assistant | Same-origin pages, CSRF + rate-limited |
| `assets/css`, `assets/js` | Stylesheets and scripts | Static |

Every PHP file here starts with:

```php
require_once __DIR__ . '/../includes/bootstrap.php';   // adjust ../ for depth
$user = require_role('ngo');                            // the RBAC guard
```

### `dashboard/`

| File | Role(s) | Purpose |
|---|---|---|
| `admin.php` | admin | Platform stats, user lists, activate/deactivate users, all requests and campaigns |
| `ngo.php` | ngo | Profile, needy requests, campaigns, volunteers, requests to donors |
| `donor.php` | donor | Profile, open requests, active campaigns |
| `volunteer.php` | volunteer | Profile/availability, campaigns, open requests |
| `needy.php` | need | Profile, create and track help requests, campaigns |
| `campaign_create.php` | ngo | Campaign form (embedded in the NGO dashboard) |
| `campaign_status.php` | admin, ngo (own campaigns) | POST endpoint: change a campaign's status |
| `request_action.php` | need, ngo (own requests) | POST endpoint: complete or delete a request |
| `index.php` | any | Redirects to the user's own dashboard |

### `assets/js/`

| File | Purpose |
|---|---|
| `dashboard.js` | Tabs (with `#hash` deep links), Edit/Save/Cancel on profiles, auto-submit selects, confirm dialogs |
| `chat-widget.js` | AI assistant widget (renders with `textContent`, so it is XSS-safe) |
| `translate.js` | Google Translate initialisation (multilingual UI) |

# `tests/`

`e2e_smoke.py` runs 60+ end-to-end checks against a running instance with a freshly seeded database. It covers:

- every role's login and dashboard rendering without PHP warnings
- profile updates, request creation, completion and deletion, campaign creation and status changes, admin user management
- **security regressions:** admin bypass, `role=admin` sign-up, IDOR on requests, onboarding and campaigns, missing CSRF, stored XSS, brute-force lockout, user enumeration, session rotation, deactivated-user sessions, chatbot hardening, security headers

```bash
mysql -u root aatman < database/schema.sql && mysql -u root aatman < database/seed.sql
php -S 127.0.0.1:8080 -t public &
pip install requests
python3 tests/e2e_smoke.py
```

| Variable | Default |
|---|---|
| `AATMAN_BASE_URL` | `http://127.0.0.1:8080/` |
| `AATMAN_MYSQL` | `mysql -u root aatman` (used to check DB state) |

The tests change data, so re-seed before every run. CI does this automatically (`.github/workflows/ci.yml`).

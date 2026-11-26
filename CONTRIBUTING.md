# Contributing to Aatman

Thanks for helping! Aatman connects people in need with NGOs, donors and volunteers, so reliability and privacy matter.

## Development setup

Follow [docs/SETUP.md](docs/SETUP.md). The quickest route is:

```bash
cp .env.example .env            # APP_ENV=development
mysql -u root -p < database/create_database.sql
mysql -u root -p aatman < database/schema.sql && mysql -u root -p aatman < database/seed.sql
php -S 127.0.0.1:8080 -t public
```

## Workflow

1. Create a branch from `main`: `feature/<name>` or `fix/<name>`.
2. Make your change and follow the conventions below.
3. Run the checks:
   ```bash
   find . -name '*.php' -not -path './.git/*' -exec php -l {} \; | grep -v 'No syntax errors'
   python3 tests/e2e_smoke.py       # against a freshly seeded DB
   ```
4. Open a pull request. The template has a checklist, and CI runs the same checks automatically.

## Code conventions

- **New page?** Put it in `public/` and start with `require_once …/includes/bootstrap.php` and `require_role(...)`.
- **Database:** use `db_exec` / `db_one` / `db_all` with `?` placeholders. Never concatenate user input into SQL.
- **Ownership:** scope writes with `WHERE … AND user_id = ?` (or `ngo_id`).
- **Forms:** POST only, include `<?= csrf_field() ?>`, validate with `post_enum` / `post_int` / `valid_phone`, then redirect with `flash()`.
- **Output:** always `<?= e($value) ?>`. In JS use `textContent`, never `innerHTML`, for data.
- **Schema changes:** add a migration in `database/migrations/`, update `schema.sql`, and update the enums in `includes/helpers.php`.
- **Shared logic** goes in `includes/repository.php` (queries) or `includes/views.php` (HTML), not copy-pasted between dashboards.
- **Secrets** go in `.env` only. Never commit keys, passwords or real personal data (seed data must be fictitious).

## Commit messages

Use the imperative mood with a short summary line, for example: `Fix volunteer status enum`, `Add campaign search`.

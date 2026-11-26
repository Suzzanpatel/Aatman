## What does this change?

<!-- A short summary, plus the issue it closes (e.g. "Closes #12") -->

## Type
- [ ] Bug fix
- [ ] New feature
- [ ] Security fix
- [ ] Docs / refactor

## Checklist
- [ ] New pages call `require_role()` and POST forms include `csrf_field()`
- [ ] SQL uses placeholders; writes are scoped to the owner
- [ ] Output is escaped with `e()`
- [ ] Schema changes have a migration and `schema.sql` is updated
- [ ] `php -l` passes and `tests/e2e_smoke.py` passes
- [ ] Docs updated (README / docs/ / CHANGELOG) if behaviour changed

## Screenshots (UI changes)

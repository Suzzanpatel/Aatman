# `database/`

| File | When to use it |
|---|---|
| `create_database.sql` | Once, as root: creates the `aatman` database and a least-privilege `aatman_app` user. **Change the password inside first.** |
| `schema.sql` | Creates all tables. **Drops existing tables**, so use it only for fresh installs and tests. |
| `seed.sql` | Optional fictitious demo data (password `Aatman@123` for every account). **Development only.** |
| `migrations/001_upgrade_from_legacy_dump.sql` | Upgrades a database created from the old `Aatman_final.sql` **without losing data** |

```bash
mysql -u root -p < database/create_database.sql
mysql -u root -p aatman < database/schema.sql
mysql -u root -p aatman < database/seed.sql   # optional
```

Schema design, ER diagram and table reference: [docs/DATABASE.md](../docs/DATABASE.md).

## Adding a migration

1. Create `migrations/NNN_short_description.sql` (next number, zero-padded).
2. Put the same change in `schema.sql`, so fresh installs match migrated ones.
3. If you added an ENUM value, update the matching constant in `includes/helpers.php`.
4. Document it in [docs/CHANGELOG.md](../docs/CHANGELOG.md).

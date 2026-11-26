# Setup & Deployment

## Requirements

- PHP **8.1 or newer** with the `mysqli`, `curl` and `mbstring` extensions (all are enabled in XAMPP by default)
- MySQL **8.0.16+** or MariaDB **10.4+** (needed for `CHECK` constraints)
- Optional: a Google Gemini API key for the AI assistant

## 1. Create the database

```bash
# Open database/create_database.sql and change 'change-me-to-a-strong-password' first.
mysql -u root -p < database/create_database.sql      # DB `aatman` + user `aatman_app`
mysql -u root -p aatman < database/schema.sql          # tables
mysql -u root -p aatman < database/seed.sql            # OPTIONAL demo data (dev only)
```

With **phpMyAdmin** (XAMPP), create a database called `aatman` (collation `utf8mb4_unicode_ci`). Then use **Import** to load `schema.sql`, and `seed.sql` if you want demo data. You can keep using `root` locally, but production should use a dedicated user with only `SELECT, INSERT, UPDATE, DELETE` rights.

## 2. Configure

```bash
cp .env.example .env
```

| Variable | Meaning | Default |
|---|---|---|
| `APP_ENV` | `development` shows PHP errors; anything else hides them and only logs them | `production` |
| `DB_HOST`, `DB_PORT` | Database server | `localhost`, `3306` |
| `DB_NAME` | Database name | `aatman` |
| `DB_USER`, `DB_PASS` | Database credentials | `aatman_app`, empty |
| `GEMINI_API_KEY` | Key from <https://aistudio.google.com/app/apikey>. Leave empty to disable the chatbot | empty |
| `GEMINI_MODEL` | Gemini model id | `gemini-2.5-flash` |

Real environment variables (for example set in the Apache vhost or your host's panel) override `.env`. **Never commit `.env`**; it is already in `.gitignore`.

## 3. Run

### PHP built-in server (fastest for development)

```bash
php -S 127.0.0.1:8080 -t public
```

### XAMPP / WAMP (repository inside `htdocs`)

1. Copy the project to `htdocs/Aatman`.
2. Visit `http://localhost/Aatman/`. The root `index.php` forwards you to `public/`.
3. The `.htaccess` files block web access to `includes/`, `database/`, `docs/`, `scripts/` and `.env`. Apache needs `AllowOverride All` for them to apply (XAMPP's default).

### Apache / Nginx (recommended for production)

Point the document root at **`public/`**. Then nothing outside it can ever be served.

```apache
<VirtualHost *:443>
    ServerName aatman.example.org
    DocumentRoot /var/www/aatman/public
    <Directory /var/www/aatman/public>
        AllowOverride All
        Require all granted
    </Directory>
    SetEnv APP_ENV production
    # SSL directives …
</VirtualHost>
```

```nginx
server {
    server_name aatman.example.org;
    root /var/www/aatman/public;
    index index.php;
    location ~ /\. { deny all; }
    location ~ \.php$ { include fastcgi_params; fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; fastcgi_pass unix:/run/php/php8.3-fpm.sock; }
}
```

Serve the site over **HTTPS**. The session cookie is automatically marked `Secure` when the request is HTTPS.

## 4. Create an administrator

Admins cannot sign up through the website. Create one from the command line:

```bash
php scripts/create_admin.php "Your Name" you@example.org
# prompts for the password; or set AATMAN_ADMIN_PASSWORD for scripted setups
```

If the email already exists, that account is promoted to admin.

## Upgrading a database built from the old `Aatman_final.sql`

The old dump used a database called `aatman_final1` and a table called `NGO_details`. Upgrade it in place without losing data:

```bash
mysqldump -u root -p aatman_final1 > backup_before_001.sql
mysql -u root -p aatman_final1 < database/migrations/001_upgrade_from_legacy_dump.sql
```

Then set `DB_NAME=aatman_final1` in `.env`. There is no admin in the old data, so run `scripts/create_admin.php` afterwards.

## Troubleshooting

| Symptom | Fix |
|---|---|
| "The service is temporarily unavailable" | Database connection failed. Check the `DB_*` values in `.env`. Set `APP_ENV=development` to see the exact error. |
| "Your session has expired or the form is invalid" | The CSRF token was missing or stale (for example, the page was open across a login). Refresh and resubmit. |
| Chatbot says "not configured" | Set `GEMINI_API_KEY` in `.env`. |
| Chatbot says "unavailable right now" | Check the PHP error log for the `[Aatman chatbot]` line. It usually means a wrong key or model name, or no outbound HTTPS from the server. |
| "Too many failed login attempts" | Wait 15 minutes, or run `DELETE FROM login_attempts WHERE email = '…';`. |
| Table `ngo_details` doesn't exist | You are on the old schema. Run the migration above. |

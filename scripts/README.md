# `scripts/`: command-line tools

These run from a terminal only and are never served over the web.

| Script | Usage |
|---|---|
| `create_admin.php` | `php scripts/create_admin.php "Full Name" admin@example.org`: creates an administrator, or promotes an existing account, and prompts for the password. Admins **cannot** sign up through the website. Set `AATMAN_ADMIN_PASSWORD` to run it without a prompt (CI or automation). |

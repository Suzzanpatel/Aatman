<?php
/**
 * Authentication and role-based access control (RBAC).
 *
 * The session only stores the user id. On every protected request the user
 * row is re-read from the database, so a role change or deactivation by an
 * admin takes effect immediately, and a session cannot claim a role it was
 * never granted.
 */

declare(strict_types=1);

const ROLE_HOME = [
    'admin'     => 'dashboard/admin.php',
    'ngo'       => 'dashboard/ngo.php',
    'donor'     => 'dashboard/donor.php',
    'volunteer' => 'dashboard/volunteer.php',
    'need'      => 'dashboard/needy.php',
];

const ROLE_ONBOARDING = [
    'ngo'       => 'onboarding/ngo.php',
    'donor'     => 'onboarding/donor.php',
    'volunteer' => 'onboarding/volunteer.php',
    'need'      => 'onboarding/needy.php',
];

/** The logged-in, active user (fresh from the DB), or null. */
function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $user = null;
    $id = $_SESSION['user_id'] ?? null;
    if (is_int($id)) {
        $row = db_one('SELECT id, name, email, phone, role, status FROM users WHERE id = ?', [$id]);
        if ($row && $row['status'] === 'active') {
            $user = $row;
        } else {
            logout_user();          // deleted or deactivated since login
        }
    }
    return $user;
}

function login_user(array $user): void
{
    session_regenerate_id(true);    // prevent session fixation
    unset($_SESSION['onboarding_user_id']);
    $_SESSION['user_id']   = (int) $user['id'];
    $_SESSION['logged_at'] = time();
    csrf_token();                   // new token for the new session
}

/**
 * Wipe all session data and move to a brand-new session id (the old one is
 * deleted server-side). Keeping a fresh, empty session lets us still show a
 * flash message such as "You have been logged out".
 */
function logout_user(): void
{
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

function home_for(string $role): string
{
    return ROLE_HOME[$role] ?? 'index.php';
}

/** Require any logged-in user. */
function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash('Please log in to continue.', 'error');
        redirect('login.php');
    }
    return $user;
}

/**
 * Require one of the given roles. Users with another role get a 403
 * instead of being silently shown someone else's dashboard.
 */
function require_role(string ...$roles): array
{
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit('403 Forbidden: your account (' . e(ROLE_LABELS[$user['role']] ?? $user['role'])
            . ') cannot access this page. <a href="' . e(url(home_for($user['role']))) . '">Go to your dashboard</a>');
    }
    return $user;
}

/**
 * Onboarding pages (profile details after sign-up) may be used by:
 *   - the account that was *just* created in this browser session, or
 *   - a logged-in user of that role who has not filled in details yet.
 * The user id always comes from the server-side session, never from the URL.
 */
function require_onboarding(string $role, string $detailsTable): array
{
    $id = $_SESSION['onboarding_user_id'] ?? ($_SESSION['user_id'] ?? null);
    $user = is_int($id) ? db_one('SELECT id, name, email, phone, role, status FROM users WHERE id = ?', [$id]) : null;

    if (!$user || $user['role'] !== $role || $user['status'] !== 'active') {
        flash('Please sign up or log in first.', 'error');
        redirect('login.php');
    }
    // Table name is a hard-coded constant from the caller, never user input.
    if (db_one("SELECT id FROM `$detailsTable` WHERE id = ?", [(int) $user['id']])) {
        login_user($user);
        redirect(home_for($role));       // already onboarded
    }
    return $user;
}

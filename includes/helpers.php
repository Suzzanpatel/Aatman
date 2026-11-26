<?php
/**
 * Small, dependency-free helpers shared by every page.
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Domain constants (must match the ENUMs in database/schema.sql)
// ---------------------------------------------------------------------------
const ROLES             = ['ngo', 'donor', 'need', 'volunteer', 'admin'];
const SELF_SIGNUP_ROLES = ['ngo', 'donor', 'need', 'volunteer'];   // admin is never self-assignable
const ROLE_LABELS       = ['ngo' => 'NGO', 'donor' => 'Donor', 'need' => 'Person in Need',
                           'volunteer' => 'Volunteer', 'admin' => 'Administrator'];
const CATEGORIES        = ['Education', 'Health', 'Environment', 'Women', 'Other'];
const DONATION_TYPES    = ['Money', 'Goods', 'Services', 'Other'];
const REQUEST_TYPES     = ['Food', 'Shelter', 'Clothes', 'Medicine', 'Education', 'Other'];
const PRIORITIES        = ['Low', 'Medium', 'High'];
const CAMPAIGN_STATUSES = ['active', 'completed', 'disabled'];
const USER_STATUSES     = ['active', 'inactive'];

/** HTML-escape for output. */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Build a link relative to the `public/` directory that works no matter
 * which sub-folder the current script lives in or where the app is mounted.
 */
function url(string $path = ''): string
{
    static $prefix = null;
    if ($prefix === null) {
        $prefix = '';
        $script = realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: '';
        $public = realpath(PUBLIC_PATH) ?: PUBLIC_PATH;
        if ($script !== '' && str_starts_with($script, $public)) {
            $rel = trim(str_replace('\\', '/', substr(dirname($script), strlen($public))), '/');
            $prefix = $rel === '' ? '' : str_repeat('../', substr_count($rel, '/') + 1);
        }
    }
    $result = $prefix . ltrim($path, '/');
    return $result === '' ? './' : $result;
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

/** Store a one-time message to show after a redirect. */
function flash(string $message, string $type = 'info'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function take_flash(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Trimmed POST string, truncated to $max characters. */
function post_str(string $key, int $max = 255): string
{
    $v = $_POST[$key] ?? '';
    return is_string($v) ? mb_substr(trim($v), 0, $max) : '';
}

/** POST value restricted to an allow-list; returns $default if not allowed. */
function post_enum(string $key, array $allowed, ?string $default = null): ?string
{
    $v = $_POST[$key] ?? null;
    return (is_string($v) && in_array($v, $allowed, true)) ? $v : $default;
}

function post_int(string $key): ?int
{
    $v = filter_var($_POST[$key] ?? null, FILTER_VALIDATE_INT);
    return $v === false ? null : $v;
}

function valid_phone(string $phone, bool $required = false): bool
{
    if ($phone === '') {
        return !$required;
    }
    return (bool) preg_match('/^\+?[0-9 \-]{7,20}$/', $phone);
}

function valid_date(string $date): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d !== false && $d->format('Y-m-d') === $date;
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/** Log the real exception, show a safe message. */
function report_error(Throwable $e, string $userMessage = 'Something went wrong. Please try again.'): string
{
    error_log('[Aatman] ' . $e::class . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    return APP_DEBUG ? $userMessage . ' (' . $e->getMessage() . ')' : $userMessage;
}

/** True if a mysqli exception is a duplicate-key violation. */
function is_duplicate_key(Throwable $e): bool
{
    return $e instanceof mysqli_sql_exception && $e->getCode() === 1062;
}

/** Render <option> tags for a list of values. */
function options(array $values, ?string $selected = null, array $labels = []): string
{
    $html = '';
    foreach ($values as $v) {
        $sel = ($selected !== null && $selected === $v) ? ' selected' : '';
        $html .= '<option value="' . e($v) . '"' . $sel . '>' . e($labels[$v] ?? $v) . '</option>';
    }
    return $html;
}

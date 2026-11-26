<?php
/**
 * CSRF protection (synchronizer token pattern).
 *
 * Every state-changing request must be a POST carrying the session token,
 * either as a `csrf_token` form field or an `X-CSRF-Token` header (AJAX).
 */

declare(strict_types=1);

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    $sent = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($sent) && $sent !== '' && hash_equals(csrf_token(), $sent);
}

/** Abort any POST that lacks a valid token. Called from bootstrap.php. */
function csrf_enforce(): void
{
    if (is_post() && !csrf_valid()) {
        http_response_code(419);
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
            || str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            header('Content-Type: application/json');
            exit(json_encode(['error' => 'Session expired. Please refresh the page.']));
        }
        exit('Your session has expired or the form is invalid. Please go back, refresh the page and try again.');
    }
}

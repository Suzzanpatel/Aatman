<?php
/**
 * Owner actions on a help request: mark completed / delete.
 * POST only, CSRF-checked by bootstrap, and the WHERE clause always includes
 * the logged-in user's id so nobody can modify another user's request (IDOR).
 */
require_once __DIR__ . '/../../includes/bootstrap.php';

$user = require_role('need', 'ngo');
$back = home_for($user['role']) . '#my-requests';

if (!is_post()) {
    redirect($back);
}

$requestId = post_int('request_id');
$action    = post_enum('action', ['complete', 'delete']);

if ($requestId === null || $action === null) {
    flash('Invalid request.', 'error');
    redirect($back);
}

$stmt = $action === 'complete'
    ? db_exec("UPDATE requests SET status = 'completed' WHERE id = ? AND user_id = ? AND status = 'pending'", [$requestId, (int) $user['id']])
    : db_exec('DELETE FROM requests WHERE id = ? AND user_id = ?', [$requestId, (int) $user['id']]);

if ($stmt->affected_rows === 1) {
    flash($action === 'complete' ? 'Request marked as completed.' : 'Request deleted.');
} else {
    flash('That request was not found.', 'error');
}
redirect($back);

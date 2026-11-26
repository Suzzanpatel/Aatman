<?php
/**
 * Change a campaign's status.
 *   - admin: any campaign
 *   - ngo:   only campaigns it owns (ownership enforced in the SQL WHERE clause)
 * Previously this endpoint had no authentication at all.
 */
require_once __DIR__ . '/../../includes/bootstrap.php';

$user = require_role('admin', 'ngo');
$back = home_for($user['role']) . '#completed-drives';

if (!is_post()) {
    redirect($back);
}

$campaignId = post_int('campaign_id');
$status     = post_enum('status', CAMPAIGN_STATUSES);

if ($campaignId === null || $status === null) {
    flash('Invalid campaign status.', 'error');
    redirect($back);
}

if ($user['role'] === 'admin') {
    $exists = db_one('SELECT id FROM campaigns WHERE id = ?', [$campaignId]);
    db_exec('UPDATE campaigns SET status = ? WHERE id = ?', [$status, $campaignId]);
} else {
    $exists = db_one('SELECT id FROM campaigns WHERE id = ? AND ngo_id = ?', [$campaignId, (int) $user['id']]);
    db_exec('UPDATE campaigns SET status = ? WHERE id = ? AND ngo_id = ?', [$status, $campaignId, (int) $user['id']]);
}

$exists ? flash('Campaign status updated to “' . ucfirst($status) . '”.') : flash('Campaign not found.', 'error');
redirect($back);

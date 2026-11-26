<?php
/**
 * Reusable HTML fragments. Every value is passed through e() before output.
 */

declare(strict_types=1);

function badge(?string $status): string
{
    $s = (string) $status;
    return '<span class="badge badge-' . e(strtolower($s)) . '">' . e(ucfirst($s)) . '</span>';
}

/** Table of pending help requests (read-only view for NGOs, donors, volunteers, admin). */
function render_requests_table(array $rows): void
{ ?>
    <div class="table-container">
        <table class="dashboard-table">
            <thead>
            <tr>
                <th>Title</th><th>Description</th><th>Location</th><th>Contact Person</th><th>Contact Details</th>
                <th>Request Type</th><th>Quantity</th><th>Priority</th><th>Posted By</th><th>Date</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="10" class="empty-row">No pending requests found.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= e($r['title']) ?></td>
                    <td><?= e($r['description']) ?></td>
                    <td><?= e($r['location']) ?></td>
                    <td><?= e($r['contact_person_name']) ?></td>
                    <td><?= e($r['contact_person_phone']) ?></td>
                    <td><?= e($r['request_type']) ?></td>
                    <td><?= e($r['quantity'] . ' ' . $r['unit']) ?></td>
                    <td><?= e($r['priority']) ?></td>
                    <td><?= e(ROLE_LABELS[$r['role']] ?? $r['role']) ?></td>
                    <td><?= e(substr((string) $r['created_at'], 0, 10)) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php }

/**
 * Table of campaigns. If $statusAction is given (a public/ path), the status
 * column becomes a CSRF-protected dropdown that posts to that endpoint.
 */
function render_campaigns_table(array $rows, ?string $statusAction = null, bool $showNgo = true): void
{ ?>
    <div class="table-container">
        <table class="dashboard-table">
            <thead>
            <tr>
                <th>Campaign Name</th><th>Description</th><th>Category</th><th>Start Date</th><th>End Date</th>
                <?php if ($showNgo): ?><th>NGO</th><?php endif; ?>
                <th>Contact Person</th><th>Contact Number</th><th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="9" class="empty-row">No campaigns found.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $c): ?>
                <tr>
                    <td><?= e($c['title']) ?></td>
                    <td><?= e($c['description']) ?></td>
                    <td><?= e($c['category']) ?></td>
                    <td><?= e($c['start_date']) ?></td>
                    <td><?= e($c['end_date']) ?></td>
                    <?php if ($showNgo): ?><td><?= e($c['ngo_name'] ?? 'Unknown NGO') ?></td><?php endif; ?>
                    <td><?= e($c['contact_person_name'] ?? 'N/A') ?></td>
                    <td><?= e($c['contact_person_phone'] ?? 'N/A') ?></td>
                    <td>
                        <?php if ($statusAction): ?>
                            <form method="POST" action="<?= e(url($statusAction)) ?>" class="inline-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="campaign_id" value="<?= (int) $c['id'] ?>">
                                <select name="status" class="status-select" data-autosubmit aria-label="Campaign status">
                                    <?= options(CAMPAIGN_STATUSES, $c['status'], array_combine(CAMPAIGN_STATUSES, array_map('ucfirst', CAMPAIGN_STATUSES))) ?>
                                </select>
                                <noscript><button type="submit" class="btn btn-small">Save</button></noscript>
                            </form>
                        <?php else: ?>
                            <?= badge($c['status']) ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php }

/** "My requests" table with owner actions (complete / delete) posted to request_action.php. */
function render_my_requests_table(array $rows): void
{ ?>
    <div class="table-container">
        <table class="dashboard-table">
            <thead>
            <tr><th>Title</th><th>Type</th><th>Quantity</th><th>Location</th><th>Priority</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="7" class="empty-row">You have not made any requests yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $req): ?>
                <tr>
                    <td><?= e($req['title']) ?></td>
                    <td><?= e($req['request_type']) ?></td>
                    <td><?= e($req['quantity'] . ' ' . $req['unit']) ?></td>
                    <td><?= e($req['location']) ?></td>
                    <td><?= e($req['priority']) ?></td>
                    <td><?= badge($req['status']) ?></td>
                    <td>
                        <?php if ($req['status'] === 'pending'): ?>
                            <form method="POST" action="<?= e(url('dashboard/request_action.php')) ?>" class="inline-form"
                                  data-confirm="Mark this request as completed?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="request_id" value="<?= (int) $req['id'] ?>">
                                <button type="submit" name="action" value="complete" class="btn btn-small">Mark as Completed</button>
                            </form>
                        <?php endif; ?>
                        <form method="POST" action="<?= e(url('dashboard/request_action.php')) ?>" class="inline-form"
                              data-confirm="Delete this request? This cannot be undone.">
                            <?= csrf_field() ?>
                            <input type="hidden" name="request_id" value="<?= (int) $req['id'] ?>">
                            <button type="submit" name="action" value="delete" class="btn-danger btn-small">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php }

/** The "make a request" form used by people in need and NGOs. */
function render_request_form(string $action, array $user, string $intro): void
{ ?>
    <p><?= e($intro) ?></p>
    <form action="<?= e(url($action)) ?>" method="post" class="request-form">
        <?= csrf_field() ?>
        <input type="text" name="title" placeholder="Title of your request" required maxlength="255">
        <textarea name="description" placeholder="Description of your need" rows="5" required maxlength="2000"></textarea>
        <select name="request_type" required>
            <option value="">Select Request Type</option>
            <?= options(REQUEST_TYPES) ?>
        </select>
        <input type="number" name="quantity" placeholder="Quantity" required min="1" max="1000000" step="1">
        <input type="text" name="unit" placeholder="Unit (e.g. kg, packets, people)" required maxlength="50">
        <input type="text" name="location" placeholder="Your Location" required maxlength="255">
        <label for="priority">Priority</label>
        <select name="priority" id="priority" required>
            <?= options(PRIORITIES, 'Medium') ?>
        </select>
        <input type="text" name="contact_person_name" placeholder="Contact Person Name" required maxlength="100" value="<?= e($user['name']) ?>">
        <input type="tel" name="contact_person_phone" placeholder="Contact Person Phone" required maxlength="20" value="<?= e($user['phone'] ?? '') ?>">
        <button type="submit" name="create_request" value="1">Submit Request</button>
    </form>
<?php }

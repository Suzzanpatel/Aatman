<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

// Previously this page *set* $_SESSION['admin_logged_in'] = true before checking
// it, so every logged-in user was an admin. Access now requires role = admin.
$user = require_role('admin');

// --- Action: activate / deactivate a user account --------------------------------
if (is_post() && isset($_POST['set_user_status'])) {
    $targetId = post_int('user_id');
    $status   = post_enum('status', USER_STATUSES);
    $section  = post_enum('section', ['donors', 'ngos', 'volunteers', 'needy'], 'donors');

    if ($targetId === null || $status === null) {
        flash('Invalid request.', 'error');
    } elseif ($targetId === (int) $user['id']) {
        flash('You cannot change the status of your own account.', 'error');
    } else {
        // Admin accounts cannot be toggled from the UI (prevents locking out other admins).
        $stmt = db_exec("UPDATE users SET status = ? WHERE id = ? AND role <> 'admin'", [$status, $targetId]);
        $stmt->affected_rows === 1
            ? flash('User #' . $targetId . ' is now ' . $status . '.')
            : flash('No change made (user not found, is an admin, or already ' . $status . ').', 'error');
    }
    redirect('dashboard/admin.php#' . $section);
}

// --- Data ------------------------------------------------------------------------
$stats = db_one(
    "SELECT
        (SELECT COUNT(*) FROM users WHERE role = 'donor')                AS donors,
        (SELECT COUNT(*) FROM users WHERE role = 'ngo')                  AS ngos,
        (SELECT COUNT(*) FROM users WHERE role = 'volunteer')            AS volunteers,
        (SELECT COUNT(*) FROM users WHERE role = 'need')                 AS needy,
        (SELECT COUNT(*) FROM requests WHERE status = 'pending')         AS pending_requests,
        (SELECT COUNT(*) FROM campaigns WHERE status = 'active')         AS active_campaigns"
);

$donors = db_all(
    "SELECT u.id, u.name, u.email, u.phone, u.status, u.created_at,
            d.address, d.city, d.state, d.donation_type, d.preferred_category
     FROM users u LEFT JOIN donor_details d ON d.id = u.id
     WHERE u.role = 'donor' ORDER BY u.created_at DESC"
);
$ngos = db_all(
    "SELECT u.id, u.name, u.email, u.phone, u.status, u.created_at,
            n.organisation_name, n.registration_number, n.address, n.city, n.category, n.description
     FROM users u LEFT JOIN ngo_details n ON n.id = u.id
     WHERE u.role = 'ngo' ORDER BY u.created_at DESC"
);
$volunteers = db_all(
    "SELECT u.id, u.name, u.email, u.phone, u.status, u.created_at,
            v.alt_phone, v.emergency_contact_name, v.emergency_contact_phone, v.city, v.state,
            v.skills, v.availability, v.preferred_category
     FROM users u LEFT JOIN volunteer_details v ON v.id = u.id
     WHERE u.role = 'volunteer' ORDER BY u.created_at DESC"
);
$needy = db_all(
    "SELECT u.id, u.name, u.email, u.phone, u.status, u.created_at,
            p.contact_person_name, p.contact_person_phone, p.alt_phone, p.address, p.city, p.state
     FROM users u LEFT JOIN people_in_need_details p ON p.id = u.id
     WHERE u.role = 'need' ORDER BY u.created_at DESC"
);
$requests      = pending_requests();
$all_campaigns = campaigns(null);

/** Activate/deactivate button for a user row. */
function status_toggle(array $row, string $section): string
{
    $next = $row['status'] === 'active' ? 'inactive' : 'active';
    $label = $next === 'inactive' ? 'Deactivate' : 'Activate';
    $class = $next === 'inactive' ? 'btn-danger' : 'btn';
    return '<form method="POST" action="' . e(url('dashboard/admin.php')) . '" class="inline-form"'
        . ' data-confirm="' . e($label . ' ' . $row['name'] . '?') . '">'
        . csrf_field()
        . '<input type="hidden" name="user_id" value="' . (int) $row['id'] . '">'
        . '<input type="hidden" name="status" value="' . e($next) . '">'
        . '<input type="hidden" name="section" value="' . e($section) . '">'
        . '<button type="submit" name="set_user_status" value="1" class="' . $class . ' btn-small">' . e($label) . '</button>'
        . '</form>';
}

$page_title = 'Admin Dashboard';
require INCLUDES_PATH . '/partials/head.php';
require INCLUDES_PATH . '/partials/navbar.php';
?>
<main class="container">
    <div class="dashboard-nav">
        <button class="active" data-section="overview">Overview</button>
        <button data-section="donors">Donors</button>
        <button data-section="ngos">NGOs</button>
        <button data-section="volunteers">Volunteers</button>
        <button data-section="needy">People in Need</button>
        <button data-section="requests">Pending Requests</button>
        <button data-section="completed-drives">Campaigns</button>
    </div>

    <div class="container">
        <section id="overview" class="dashboard-section">
            <h2>Platform Overview</h2>
            <div class="profile-card">
                <?php foreach (['donors' => 'Donors', 'ngos' => 'NGOs', 'volunteers' => 'Volunteers', 'needy' => 'People in Need',
                                'pending_requests' => 'Pending Requests', 'active_campaigns' => 'Active Campaigns'] as $k => $label): ?>
                    <div class="profile-info"><h3><?= e($label) ?></h3><p style="font-size:2rem;font-weight:600"><?= (int) $stats[$k] ?></p></div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="donors" class="dashboard-section hidden-section">
            <h2>Donors</h2>
            <div class="table-container"><table class="dashboard-table">
                <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Donation Type</th><th>Preferred Category</th>
                    <th>City</th><th>State</th><th>Joined</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php if (!$donors): ?><tr><td colspan="11" class="empty-row">No donors found.</td></tr><?php endif; ?>
                <?php foreach ($donors as $r): ?>
                    <tr><td><?= (int) $r['id'] ?></td><td><?= e($r['name']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['phone'] ?: 'N/A') ?></td>
                        <td><?= e($r['donation_type'] ?: 'N/A') ?></td><td><?= e($r['preferred_category'] ?: 'N/A') ?></td>
                        <td><?= e($r['city'] ?: 'N/A') ?></td><td><?= e($r['state'] ?: 'N/A') ?></td><td><?= e(substr($r['created_at'], 0, 10)) ?></td>
                        <td><?= badge($r['status']) ?></td><td><?= status_toggle($r, 'donors') ?></td></tr>
                <?php endforeach; ?>
                </tbody></table></div>
        </section>

        <section id="ngos" class="dashboard-section hidden-section">
            <h2>Registered NGOs</h2>
            <div class="table-container"><table class="dashboard-table">
                <thead><tr><th>ID</th><th>Organisation</th><th>Reg. No.</th><th>Contact</th><th>Email</th><th>Phone</th><th>City</th>
                    <th>Category</th><th>Description</th><th>Joined</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php if (!$ngos): ?><tr><td colspan="12" class="empty-row">No NGOs found.</td></tr><?php endif; ?>
                <?php foreach ($ngos as $r): ?>
                    <tr><td><?= (int) $r['id'] ?></td><td><?= e($r['organisation_name'] ?: 'Details pending') ?></td><td><?= e($r['registration_number'] ?: 'N/A') ?></td>
                        <td><?= e($r['name']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['phone'] ?: 'N/A') ?></td><td><?= e($r['city'] ?: 'N/A') ?></td>
                        <td><?= e($r['category'] ?: 'N/A') ?></td><td><?= e($r['description'] ?: '') ?></td><td><?= e(substr($r['created_at'], 0, 10)) ?></td>
                        <td><?= badge($r['status']) ?></td><td><?= status_toggle($r, 'ngos') ?></td></tr>
                <?php endforeach; ?>
                </tbody></table></div>
        </section>

        <section id="volunteers" class="dashboard-section hidden-section">
            <h2>Volunteers</h2>
            <div class="table-container"><table class="dashboard-table">
                <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Alt Phone</th><th>Emergency Contact</th>
                    <th>City</th><th>Skills</th><th>Availability</th><th>Category</th><th>Joined</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php if (!$volunteers): ?><tr><td colspan="13" class="empty-row">No volunteers found.</td></tr><?php endif; ?>
                <?php foreach ($volunteers as $r): ?>
                    <tr><td><?= (int) $r['id'] ?></td><td><?= e($r['name']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['phone'] ?: 'N/A') ?></td>
                        <td><?= e($r['alt_phone'] ?: 'N/A') ?></td><td><?= e(trim(($r['emergency_contact_name'] ?? '') . ' ' . ($r['emergency_contact_phone'] ?? '')) ?: 'N/A') ?></td>
                        <td><?= e($r['city'] ?: 'N/A') ?></td><td><?= e($r['skills'] ?: 'N/A') ?></td><td><?= e($r['availability'] ?: 'N/A') ?></td>
                        <td><?= e($r['preferred_category'] ?: 'Any') ?></td><td><?= e(substr($r['created_at'], 0, 10)) ?></td>
                        <td><?= badge($r['status']) ?></td><td><?= status_toggle($r, 'volunteers') ?></td></tr>
                <?php endforeach; ?>
                </tbody></table></div>
        </section>

        <section id="needy" class="dashboard-section hidden-section">
            <h2>People in Need</h2>
            <div class="table-container"><table class="dashboard-table">
                <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Alt Phone</th><th>Contact Person</th>
                    <th>Address</th><th>City</th><th>State</th><th>Joined</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php if (!$needy): ?><tr><td colspan="12" class="empty-row">No people in need found.</td></tr><?php endif; ?>
                <?php foreach ($needy as $r): ?>
                    <tr><td><?= (int) $r['id'] ?></td><td><?= e($r['name']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['phone'] ?: 'N/A') ?></td>
                        <td><?= e($r['alt_phone'] ?: 'N/A') ?></td><td><?= e(trim(($r['contact_person_name'] ?? '') . ' ' . ($r['contact_person_phone'] ?? '')) ?: 'N/A') ?></td>
                        <td><?= e($r['address'] ?: 'N/A') ?></td><td><?= e($r['city'] ?: 'N/A') ?></td><td><?= e($r['state'] ?: 'N/A') ?></td>
                        <td><?= e(substr($r['created_at'], 0, 10)) ?></td><td><?= badge($r['status']) ?></td><td><?= status_toggle($r, 'needy') ?></td></tr>
                <?php endforeach; ?>
                </tbody></table></div>
        </section>

        <section id="requests" class="dashboard-section hidden-section">
            <h2><i class="fas fa-hands-helping"></i> Pending Requests</h2>
            <?php render_requests_table($requests); ?>
        </section>

        <section id="completed-drives" class="dashboard-section hidden-section">
            <h2><i class="fas fa-clipboard-list"></i> All Campaigns</h2>
            <p>Change a campaign's status to complete or disable it.</p>
            <?php render_campaigns_table($all_campaigns, 'dashboard/campaign_status.php'); ?>
        </section>
    </div>
</main>
<?php
$page_js = ['assets/js/dashboard.js'];
$with_chat = false;
require INCLUDES_PATH . '/partials/foot.php';

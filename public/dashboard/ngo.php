<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$user = require_role('ngo');
$uid  = (int) $user['id'];

// --- Actions -------------------------------------------------------------------
if (is_post() && isset($_POST['update_profile'])) {
    $name    = post_str('user_name', 100);
    $email   = post_str('email', 89);
    $phone   = post_str('phone', 20);
    $org     = post_str('organisation_name');
    $address = post_str('address');
    $city    = post_str('city', 100);
    $state   = post_str('state', 100);
    $desc    = post_str('description', 2000);
    $cat     = post_enum('category', CATEGORIES);

    $errors = validate_user_core($name, $email, $phone);
    if ($org === '')   { $errors[] = 'Organisation name is required.'; }
    if ($cat === null) { $errors[] = 'Please choose a valid category.'; }

    if ($errors) {
        flash(implode(' ', $errors), 'error');
    } else {
        $conn = db();
        $conn->begin_transaction();
        try {
            update_user_core($uid, $name, $email, $phone);
            // Registration number is fixed at onboarding and is not editable here.
            db_exec('UPDATE ngo_details SET organisation_name = ?, address = ?, city = ?, state = ?, description = ?, category = ?
                     WHERE id = ?', [$org, $address, $city, $state, $desc, $cat, $uid]);
            $conn->commit();
            flash('Profile updated successfully!');
        } catch (Throwable $e) {
            $conn->rollback();
            flash(is_duplicate_key($e) ? 'That email is already used by another account.'
                : report_error($e, 'Error updating profile.'), 'error');
        }
    }
    redirect('dashboard/ngo.php#profile');
}

if (is_post() && isset($_POST['deactivate'])) {
    deactivate_self($user, 'ngo_details');
}

if (is_post() && isset($_POST['create_request'])) {
    try {
        $errors = create_request_from_post($user);
        $errors ? flash(implode(' ', $errors), 'error') : flash('Your request has been submitted successfully!');
    } catch (Throwable $e) {
        flash(report_error($e, 'Error submitting request.'), 'error');
    }
    redirect('dashboard/ngo.php#' . (empty($errors) ? 'my-requests' : 'donor-requests'));
}

// --- Data ----------------------------------------------------------------------
$profile = db_one(
    'SELECT u.name AS user_name, u.email, u.phone, u.status, n.organisation_name, n.registration_number,
            n.address, n.city, n.state, n.description, n.category
     FROM users u LEFT JOIN ngo_details n ON n.id = u.id WHERE u.id = ?',
    [$uid]
);
$needy_requests = pending_requests('need');
$my_campaigns   = campaigns(null, $uid);
$my_requests    = requests_by_user($uid);
$volunteers     = db_all(
    "SELECT u.name, u.phone, u.email, v.skills, v.availability, v.city, v.preferred_category
     FROM users u JOIN volunteer_details v ON v.id = u.id
     WHERE u.role = 'volunteer' AND u.status = 'active' AND v.status = 'active'
     ORDER BY u.name"
);

$page_title = 'NGO Dashboard';
require INCLUDES_PATH . '/partials/head.php';
require INCLUDES_PATH . '/partials/navbar.php';
?>
<main class="container">
    <div class="dashboard-nav">
        <button class="active" data-section="profile">Profile</button>
        <button data-section="requests">View Needy Requests</button>
        <button data-section="create-campaign">Create Campaign</button>
        <button data-section="completed-drives">My Campaigns</button>
        <button data-section="volunteers">View Volunteers</button>
        <button data-section="donor-requests">Request Donors</button>
        <button data-section="my-requests">View My Requests</button>
    </div>

    <div class="container">
        <section id="profile" class="dashboard-section">
            <h2><i class="fas fa-building"></i> NGO Profile</h2>
            <?php if (!$profile['organisation_name']): ?>
                <div class="alert-message alert-error">Your NGO details are incomplete.
                    <a href="<?= e(url('onboarding/ngo.php')) ?>">Complete them now</a>.</div>
            <?php endif; ?>
            <form method="POST" action="<?= e(url('dashboard/ngo.php')) ?>" id="editForm" class="profile-card">
                <?= csrf_field() ?>
                <div class="profile-info">
                    <h3>General Information</h3>
                    <p><strong>Organisation Name:</strong> <input type="text" name="organisation_name" required maxlength="255" value="<?= e($profile['organisation_name']) ?>" disabled></p>
                    <p><strong>Contact Person:</strong> <input type="text" name="user_name" required maxlength="100" value="<?= e($profile['user_name']) ?>" disabled></p>
                    <p><strong>Category:</strong>
                        <select name="category" disabled><?= options(CATEGORIES, $profile['category']) ?></select></p>
                    <p><strong>Description:</strong> <textarea name="description" rows="3" maxlength="2000" disabled><?= e($profile['description']) ?></textarea></p>
                    <p><strong>Status:</strong> <?= badge($profile['status']) ?></p>
                    <p><strong>Registration No:</strong> <?= e($profile['registration_number'] ?: 'Not provided') ?> <em>(cannot be changed)</em></p>
                </div>
                <div class="profile-info">
                    <h3>Contact Details</h3>
                    <p><strong>Email:</strong> <input type="email" name="email" required maxlength="89" value="<?= e($profile['email']) ?>" disabled></p>
                    <p><strong>Phone:</strong> <input type="tel" name="phone" maxlength="20" value="<?= e($profile['phone']) ?>" disabled></p>
                    <p><strong>Address:</strong> <input type="text" name="address" maxlength="255" value="<?= e($profile['address']) ?>" disabled></p>
                    <p><strong>City:</strong> <input type="text" name="city" maxlength="100" value="<?= e($profile['city']) ?>" disabled></p>
                    <p><strong>State:</strong> <input type="text" name="state" maxlength="100" value="<?= e($profile['state']) ?>" disabled></p>
                </div>
                <div class="profile-actions">
                    <button type="button" id="editBtn" class="btn-edit">Edit</button>
                    <button type="submit" name="update_profile" value="1" id="saveBtn" class="btn-save hidden">Save Changes</button>
                    <button type="button" id="cancelBtn" class="btn-cancel hidden">Cancel</button>
                </div>
            </form>

            <div class="profile-card-danger">
                <h3>Manage Status</h3>
                <p>Deactivating hides your organisation from the platform and logs you out.</p>
                <form method="POST" action="<?= e(url('dashboard/ngo.php')) ?>" data-confirm="Are you sure you want to deactivate your account?">
                    <?= csrf_field() ?>
                    <button type="submit" name="deactivate" value="1" class="btn-disable">Deactivate Account</button>
                </form>
            </div>
        </section>

        <section id="requests" class="dashboard-section hidden-section">
            <h2>Requests from Needy</h2>
            <p>The latest requests from individuals and families in need of assistance.</p>
            <?php render_requests_table($needy_requests); ?>
        </section>

        <section id="create-campaign" class="dashboard-section hidden-section">
            <h2><i class="fas fa-bullhorn"></i> Create New Campaign</h2>
            <iframe src="<?= e(url('dashboard/campaign_create.php')) ?>" title="Create campaign form"></iframe>
        </section>

        <section id="completed-drives" class="dashboard-section hidden-section">
            <h2><i class="fas fa-clipboard-list"></i> My Campaigns</h2>
            <p>Campaigns organised by your NGO. Change the status to mark a campaign completed or disable it.</p>
            <?php render_campaigns_table($my_campaigns, 'dashboard/campaign_status.php', false); ?>
        </section>

        <section id="volunteers" class="dashboard-section hidden-section">
            <h2><i class="fas fa-user-friends"></i> Volunteers</h2>
            <p>Active volunteers who have signed up to help with causes on Aatman.</p>
            <div class="table-container">
                <table class="dashboard-table">
                    <thead><tr><th>Name</th><th>Skills</th><th>Phone</th><th>Email</th><th>Availability</th><th>Preferred Category</th><th>City</th></tr></thead>
                    <tbody>
                    <?php if (!$volunteers): ?><tr><td colspan="7" class="empty-row">No volunteers found.</td></tr><?php endif; ?>
                    <?php foreach ($volunteers as $v): ?>
                        <tr>
                            <td><?= e($v['name']) ?></td>
                            <td><?= e($v['skills'] ?: 'N/A') ?></td>
                            <td><?= e($v['phone'] ?: 'N/A') ?></td>
                            <td><?= e($v['email']) ?></td>
                            <td><?= e($v['availability'] ?: 'N/A') ?></td>
                            <td><?= e($v['preferred_category'] ?: 'Any') ?></td>
                            <td><?= e($v['city'] ?: 'N/A') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="donor-requests" class="dashboard-section hidden-section">
            <h2>Request Donors</h2>
            <?php render_request_form('dashboard/ngo.php', $user, 'Post a request that donors and volunteers will see on their dashboards.'); ?>
        </section>

        <section id="my-requests" class="dashboard-section hidden-section">
            <h2>My Requests</h2>
            <p>Track the status of the requests your NGO has posted.</p>
            <?php render_my_requests_table($my_requests); ?>
        </section>
    </div>
</main>
<?php
$page_js = ['assets/js/dashboard.js'];
require INCLUDES_PATH . '/partials/foot.php';

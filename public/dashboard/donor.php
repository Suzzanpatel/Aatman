<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$user = require_role('donor');
$uid  = (int) $user['id'];

// --- Actions -------------------------------------------------------------------
if (is_post() && isset($_POST['update_profile'])) {
    $name     = post_str('name', 100);
    $email    = post_str('email', 89);
    $phone    = post_str('phone', 20);
    $type     = post_enum('donation_type', DONATION_TYPES);
    $category = post_enum('preferred_category', CATEGORIES);
    $address  = post_str('address');
    $city     = post_str('city', 100);
    $state    = post_str('state', 100);

    $errors = validate_user_core($name, $email, $phone);
    if ($type === null) {
        $errors[] = 'Please choose a valid donation type.';
    }

    if ($errors) {
        flash(implode(' ', $errors), 'error');
    } else {
        $conn = db();
        $conn->begin_transaction();
        try {
            update_user_core($uid, $name, $email, $phone);
            db_exec(
                'INSERT INTO donor_details (id, donation_type, preferred_category, address, city, state)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE donation_type = VALUES(donation_type), preferred_category = VALUES(preferred_category),
                    address = VALUES(address), city = VALUES(city), state = VALUES(state)',
                [$uid, $type, $category, $address, $city, $state]
            );
            $conn->commit();
            flash('Profile updated successfully!');
        } catch (Throwable $e) {
            $conn->rollback();
            flash(is_duplicate_key($e) ? 'That email is already used by another account.'
                : report_error($e, 'Error updating profile.'), 'error');
        }
    }
    redirect('dashboard/donor.php#profile');
}

if (is_post() && isset($_POST['disable_account'])) {
    deactivate_self($user, 'donor_details');
}

// --- Data ----------------------------------------------------------------------
$donor = db_one(
    'SELECT u.name, u.email, u.phone, u.status, d.address, d.city, d.state, d.donation_type, d.preferred_category
     FROM users u LEFT JOIN donor_details d ON d.id = u.id WHERE u.id = ?',
    [$uid]
);
$open_requests    = pending_requests();      // from people in need *and* NGOs
$active_campaigns = campaigns('active');     // was joined on the wrong column before

$page_title = 'Donor Dashboard';
require INCLUDES_PATH . '/partials/head.php';
require INCLUDES_PATH . '/partials/navbar.php';
?>
<main>
    <div class="dashboard-nav">
        <button class="active" data-section="profile">Profile</button>
        <button data-section="requests">Requests</button>
        <button data-section="completed-drives">Campaigns</button>
    </div>

    <div class="container">
        <section id="profile" class="dashboard-section">
            <h2>Donor Profile</h2>
            <form method="POST" action="<?= e(url('dashboard/donor.php')) ?>" id="editForm" class="profile-card">
                <?= csrf_field() ?>
                <div class="profile-info">
                    <h3>General Information</h3>
                    <p><strong>Full Name:</strong> <input type="text" name="name" required maxlength="100" value="<?= e($donor['name']) ?>" disabled></p>
                    <p><strong>Donation Type:</strong>
                        <select name="donation_type" disabled><?= options(DONATION_TYPES, $donor['donation_type']) ?></select></p>
                    <p><strong>Preferred Category:</strong>
                        <select name="preferred_category" disabled>
                            <option value="">No preference</option>
                            <?= options(CATEGORIES, $donor['preferred_category']) ?>
                        </select></p>
                    <p><strong>Status:</strong> <?= badge($donor['status']) ?></p>
                </div>
                <div class="profile-info">
                    <h3>Contact Details</h3>
                    <p><strong>Email:</strong> <input type="email" name="email" required maxlength="89" value="<?= e($donor['email']) ?>" disabled></p>
                    <p><strong>Phone:</strong> <input type="tel" name="phone" maxlength="20" value="<?= e($donor['phone']) ?>" disabled></p>
                    <p><strong>Address:</strong> <input type="text" name="address" maxlength="255" value="<?= e($donor['address']) ?>" disabled></p>
                    <p><strong>City:</strong> <input type="text" name="city" maxlength="100" value="<?= e($donor['city']) ?>" disabled></p>
                    <p><strong>State:</strong> <input type="text" name="state" maxlength="100" value="<?= e($donor['state']) ?>" disabled></p>
                </div>
                <div class="profile-actions">
                    <button type="button" id="editBtn" class="btn-edit">Edit</button>
                    <button type="submit" name="update_profile" value="1" id="saveBtn" class="btn-save hidden">Save Changes</button>
                    <button type="button" id="cancelBtn" class="btn-cancel hidden">Cancel</button>
                </div>
            </form>

            <div class="profile-card-danger">
                <h3>Manage Status</h3>
                <p>Disabling your account logs you out and hides your profile.</p>
                <form method="POST" action="<?= e(url('dashboard/donor.php')) ?>" data-confirm="Are you sure you want to disable your account?">
                    <?= csrf_field() ?>
                    <button type="submit" name="disable_account" value="1" class="btn-disable">Disable Account</button>
                </form>
            </div>
        </section>

        <section id="requests" class="dashboard-section hidden-section">
            <h2>Open Requests</h2>
            <p>Requests from people in need and NGOs that are still waiting for help. Contact them directly to coordinate your donation.</p>
            <?php render_requests_table($open_requests); ?>
        </section>

        <section id="completed-drives" class="dashboard-section hidden-section">
            <h2><i class="fas fa-clipboard-list"></i> Active Campaigns</h2>
            <p>Campaigns organised by NGOs that you can support.</p>
            <?php render_campaigns_table($active_campaigns); ?>
        </section>
    </div>
</main>
<?php
$page_js = ['assets/js/dashboard.js'];
require INCLUDES_PATH . '/partials/foot.php';

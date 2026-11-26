<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$user = require_role('need');
$uid  = (int) $user['id'];

// --- Actions -------------------------------------------------------------------
if (is_post() && isset($_POST['update_profile'])) {
    $name  = post_str('user_name', 100);
    $email = post_str('email', 89);
    $phone = post_str('phone', 20);
    $d = [
        'contact_person_name'  => post_str('contact_person_name'),
        'contact_person_phone' => post_str('contact_person_phone', 20),
        'alt_phone'            => post_str('alt_phone', 20),
        'address'              => post_str('address'),
        'city'                 => post_str('city', 100),
        'state'                => post_str('state', 100),
    ];
    $errors = validate_user_core($name, $email, $phone);
    if ($d['contact_person_name'] === '' || !valid_phone($d['contact_person_phone'], true)) {
        $errors[] = 'Emergency contact name and a valid phone number are required.';
    }
    if (!valid_phone($d['alt_phone'])) {
        $errors[] = 'Alternate phone is not valid.';
    }

    if ($errors) {
        flash(implode(' ', $errors), 'error');
    } else {
        $conn = db();
        $conn->begin_transaction();
        try {
            update_user_core($uid, $name, $email, $phone);
            db_exec(
                'INSERT INTO people_in_need_details (id, contact_person_name, contact_person_phone, alt_phone, address, city, state)
                 VALUES (?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE contact_person_name = VALUES(contact_person_name),
                    contact_person_phone = VALUES(contact_person_phone), alt_phone = VALUES(alt_phone),
                    address = VALUES(address), city = VALUES(city), state = VALUES(state)',
                [$uid, $d['contact_person_name'], $d['contact_person_phone'], $d['alt_phone'], $d['address'], $d['city'], $d['state']]
            );
            $conn->commit();
            flash('Profile updated successfully!');
        } catch (Throwable $e) {
            $conn->rollback();
            flash(is_duplicate_key($e) ? 'That email is already used by another account.'
                : report_error($e, 'Error updating profile.'), 'error');
        }
    }
    redirect('dashboard/needy.php#profile');
}

if (is_post() && isset($_POST['deactivate_account'])) {
    deactivate_self($user, 'people_in_need_details');
}

if (is_post() && isset($_POST['create_request'])) {
    try {
        $errors = create_request_from_post($user);
        $errors ? flash(implode(' ', $errors), 'error') : flash('Request submitted successfully!');
    } catch (Throwable $e) {
        flash(report_error($e, 'Error submitting request.'), 'error');
    }
    redirect('dashboard/needy.php#' . (empty($errors) ? 'my-requests' : 'create-request'));
}

// --- Data ----------------------------------------------------------------------
$details = db_one(
    'SELECT u.name AS user_name, u.email, u.phone, u.status, p.alt_phone, p.address, p.city, p.state,
            p.contact_person_name, p.contact_person_phone
     FROM users u LEFT JOIN people_in_need_details p ON p.id = u.id WHERE u.id = ?',
    [$uid]
);
$my_requests      = requests_by_user($uid);
$active_campaigns = campaigns('active');

$page_title = 'My Dashboard';
require INCLUDES_PATH . '/partials/head.php';
require INCLUDES_PATH . '/partials/navbar.php';
?>
<main>
    <div class="dashboard-nav">
        <button class="active" data-section="profile">Profile</button>
        <button data-section="create-request">Make a Request</button>
        <button data-section="my-requests">My Requests</button>
        <button data-section="campaigns">View Campaigns</button>
    </div>

    <div class="container">
        <section id="profile" class="dashboard-section">
            <h2>My Profile</h2>
            <form method="POST" action="<?= e(url('dashboard/needy.php')) ?>" id="editForm" class="profile-card">
                <?= csrf_field() ?>
                <div class="profile-info">
                    <h3>General Information</h3>
                    <p><strong>Full Name:</strong> <input type="text" name="user_name" required maxlength="100" value="<?= e($details['user_name']) ?>" disabled></p>
                    <p><strong>Status:</strong> <?= badge($details['status']) ?></p>
                </div>
                <div class="profile-info">
                    <h3>Contact Details</h3>
                    <p><strong>Email:</strong> <input type="email" name="email" required maxlength="89" value="<?= e($details['email']) ?>" disabled></p>
                    <p><strong>Phone:</strong> <input type="tel" name="phone" maxlength="20" value="<?= e($details['phone']) ?>" disabled></p>
                    <p><strong>Alternate Phone:</strong> <input type="tel" name="alt_phone" maxlength="20" value="<?= e($details['alt_phone']) ?>" disabled></p>
                    <p><strong>Address:</strong> <input type="text" name="address" maxlength="255" value="<?= e($details['address']) ?>" disabled></p>
                    <p><strong>City:</strong> <input type="text" name="city" maxlength="100" value="<?= e($details['city']) ?>" disabled></p>
                    <p><strong>State:</strong> <input type="text" name="state" maxlength="100" value="<?= e($details['state']) ?>" disabled></p>
                </div>
                <div class="profile-info">
                    <h3>Emergency Contact</h3>
                    <p><strong>Name:</strong> <input type="text" name="contact_person_name" required maxlength="255" value="<?= e($details['contact_person_name']) ?>" disabled></p>
                    <p><strong>Phone:</strong> <input type="tel" name="contact_person_phone" required maxlength="20" value="<?= e($details['contact_person_phone']) ?>" disabled></p>
                </div>
                <div class="profile-actions">
                    <button type="button" id="editBtn" class="btn-edit">Edit</button>
                    <button type="submit" name="update_profile" value="1" id="saveBtn" class="btn-save hidden">Save Changes</button>
                    <button type="button" id="cancelBtn" class="btn-cancel hidden">Cancel</button>
                </div>
            </form>

            <div class="profile-card-danger">
                <h3>Manage Status</h3>
                <p>Deactivate your account to stop receiving assistance requests. You will be logged out.</p>
                <form method="POST" action="<?= e(url('dashboard/needy.php')) ?>" data-confirm="Are you sure you want to deactivate your account?">
                    <?= csrf_field() ?>
                    <button type="submit" name="deactivate_account" value="1" class="btn-disable">Deactivate Account</button>
                </form>
            </div>
        </section>

        <section id="create-request" class="dashboard-section hidden-section">
            <h2>Make a New Request</h2>
            <?php render_request_form('dashboard/needy.php', $user, 'Fill out the form below to request assistance from NGOs and donors.'); ?>
        </section>

        <section id="my-requests" class="dashboard-section hidden-section">
            <h2>My Requests</h2>
            <p>Track the status of your submitted requests for assistance.</p>
            <?php render_my_requests_table($my_requests); ?>
        </section>

        <section id="campaigns" class="dashboard-section hidden-section">
            <h2>NGO Campaigns</h2>
            <p>Active campaigns organised by NGOs on Aatman.</p>
            <?php render_campaigns_table($active_campaigns); ?>
        </section>
    </div>
</main>
<?php
$page_js = ['assets/js/dashboard.js'];
require INCLUDES_PATH . '/partials/foot.php';

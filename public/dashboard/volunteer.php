<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

// Previously any logged-in user (of any role) could open this page.
$user = require_role('volunteer');
$uid  = (int) $user['id'];
$volunteer_categories = array_merge(['Any'], CATEGORIES);

// --- Actions -------------------------------------------------------------------
if (is_post() && isset($_POST['update_profile'])) {
    $category = post_enum('preferred_category', $volunteer_categories, 'Any');
    if ($category === 'Other' && post_str('preferred_category_other') !== '') {
        $category = post_str('preferred_category_other');   // free-text "Other" is now actually saved
    }
    $d = [
        'availability'            => post_str('availability'),
        'preferred_category'      => $category,
        'skills'                  => post_str('skills', 2000),
        'status'                  => post_enum('status', ['active', 'disabled'], 'active'),
        'alt_phone'               => post_str('alt_phone', 20),
        'emergency_contact_name'  => post_str('emergency_contact_name'),
        'emergency_contact_phone' => post_str('emergency_contact_phone', 20),
        'address'                 => post_str('address'),
        'city'                    => post_str('city', 100),
        'state'                   => post_str('state', 100),
    ];
    $errors = [];
    foreach (['alt_phone', 'emergency_contact_phone'] as $k) {
        if (!valid_phone($d[$k])) {
            $errors[] = 'Please enter valid phone numbers.';
            break;
        }
    }
    if ($errors) {
        flash(implode(' ', $errors), 'error');
    } else {
        try {
            db_exec(
                'INSERT INTO volunteer_details (id, availability, preferred_category, skills, status, alt_phone,
                        emergency_contact_name, emergency_contact_phone, address, city, state)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE availability = VALUES(availability), preferred_category = VALUES(preferred_category),
                    skills = VALUES(skills), status = VALUES(status), alt_phone = VALUES(alt_phone),
                    emergency_contact_name = VALUES(emergency_contact_name), emergency_contact_phone = VALUES(emergency_contact_phone),
                    address = VALUES(address), city = VALUES(city), state = VALUES(state)',
                [$uid, $d['availability'], $d['preferred_category'], $d['skills'], $d['status'], $d['alt_phone'],
                 $d['emergency_contact_name'], $d['emergency_contact_phone'], $d['address'], $d['city'], $d['state']]
            );
            flash('Profile updated successfully!');
        } catch (Throwable $e) {
            flash(report_error($e, 'Error updating profile.'), 'error');
        }
    }
    redirect('dashboard/volunteer.php#profile');
}

// --- Data ----------------------------------------------------------------------
$volunteer = db_one(
    'SELECT u.name, u.email, u.phone, u.status AS user_status, v.availability, v.preferred_category, v.skills,
            v.status AS volunteer_status, v.alt_phone, v.emergency_contact_name, v.emergency_contact_phone,
            v.address, v.city, v.state
     FROM users u LEFT JOIN volunteer_details v ON v.id = u.id WHERE u.id = ?',
    [$uid]
) ?? [];
$saved_category   = (string) ($volunteer['preferred_category'] ?? 'Any');
$is_custom        = $saved_category !== '' && !in_array($saved_category, $volunteer_categories, true);
$active_campaigns = campaigns('active');
$open_requests    = pending_requests();

$page_title = 'Volunteer Dashboard';
require INCLUDES_PATH . '/partials/head.php';
require INCLUDES_PATH . '/partials/navbar.php';
?>
<main>
    <div class="dashboard-nav">
        <button class="active" data-section="profile">Profile</button>
        <button data-section="campaigns">NGO Campaigns</button>
        <button data-section="needy-requests">Open Requests</button>
    </div>

    <div class="container">
        <section id="profile" class="dashboard-section">
            <h2>My Profile</h2>
            <div class="profile-card">
                <div class="profile-info">
                    <h3>General Information</h3>
                    <p><strong>Full Name:</strong> <?= e($volunteer['name'] ?? 'N/A') ?></p>
                    <p><strong>Email:</strong> <?= e($volunteer['email'] ?? 'N/A') ?></p>
                    <p><strong>Phone:</strong> <?= e($volunteer['phone'] ?: 'N/A') ?></p>
                    <p><strong>Availability:</strong> <?= e($volunteer['availability'] ?: 'N/A') ?></p>
                    <p><strong>Preferred Category:</strong> <?= e($saved_category) ?></p>
                    <p><strong>Volunteer Status:</strong> <?= badge($volunteer['volunteer_status'] ?? 'active') ?></p>
                    <p><strong>Emergency Contact:</strong> <?= e(($volunteer['emergency_contact_name'] ?? '') ?: 'N/A') ?>
                        <?= e($volunteer['emergency_contact_phone'] ?? '') ?></p>
                    <p><strong>Address:</strong> <?= e(trim(implode(', ', array_filter([$volunteer['address'] ?? '', $volunteer['city'] ?? '', $volunteer['state'] ?? '']))) ?: 'N/A') ?></p>
                </div>

                <div class="profile-info">
                    <h3>Update My Details</h3>
                    <form action="<?= e(url('dashboard/volunteer.php')) ?>" method="post" class="profile-form">
                        <?= csrf_field() ?>
                        <div class="form-group">
                            <label for="availability">Availability</label>
                            <input type="text" name="availability" id="availability" maxlength="255" placeholder="e.g. weekends" required
                                   value="<?= e($volunteer['availability'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="preferred_category">Preferred Category</label>
                            <select name="preferred_category" id="preferred_category">
                                <?= options($volunteer_categories, $is_custom ? 'Other' : $saved_category) ?>
                            </select>
                            <input type="text" name="preferred_category_other" id="preferred_category_other" maxlength="100"
                                   placeholder="Enter your category" value="<?= e($is_custom ? $saved_category : '') ?>"
                                   class="<?= $is_custom ? '' : 'hidden' ?>">
                        </div>
                        <div class="form-group">
                            <label for="skills">Skills (e.g. medical knowledge, teaching, technical support)</label>
                            <textarea name="skills" id="skills" rows="3" maxlength="2000"><?= e($volunteer['skills'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="status">Volunteer Status</label>
                            <select name="status" id="status">
                                <?= options(['active', 'disabled'], $volunteer['volunteer_status'] ?? 'active', ['active' => 'Available', 'disabled' => 'Not available']) ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="alt_phone">Alternate Phone</label>
                            <input type="tel" name="alt_phone" id="alt_phone" maxlength="20" value="<?= e($volunteer['alt_phone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="emergency_contact_name">Emergency Contact Name</label>
                            <input type="text" name="emergency_contact_name" id="emergency_contact_name" maxlength="255"
                                   value="<?= e($volunteer['emergency_contact_name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="emergency_contact_phone">Emergency Contact Number</label>
                            <input type="tel" name="emergency_contact_phone" id="emergency_contact_phone" maxlength="20"
                                   value="<?= e($volunteer['emergency_contact_phone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" name="address" id="address" maxlength="255" value="<?= e($volunteer['address'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" name="city" id="city" maxlength="100" value="<?= e($volunteer['city'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="state">State</label>
                            <input type="text" name="state" id="state" maxlength="100" value="<?= e($volunteer['state'] ?? '') ?>">
                        </div>
                        <button type="submit" name="update_profile" value="1" class="btn-save">Update Profile</button>
                    </form>
                </div>
            </div>
        </section>

        <section id="campaigns" class="dashboard-section hidden-section">
            <h2>NGO Campaigns</h2>
            <p>Browse active campaigns and contact the organisers to help.</p>
            <?php render_campaigns_table($active_campaigns); ?>
        </section>

        <section id="needy-requests" class="dashboard-section hidden-section">
            <h2>Open Requests</h2>
            <p>Requests from people in need and NGOs that are still waiting for help.</p>
            <?php render_requests_table($open_requests); ?>
        </section>
    </div>
</main>
<script>
    // Show the free-text box only when "Other" is selected.
    document.getElementById('preferred_category').addEventListener('change', function () {
        document.getElementById('preferred_category_other').classList.toggle('hidden', this.value !== 'Other');
    });
</script>
<?php
$page_js = ['assets/js/dashboard.js'];
require INCLUDES_PATH . '/partials/foot.php';

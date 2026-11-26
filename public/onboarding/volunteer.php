<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$account = require_onboarding('volunteer', 'volunteer_details');
$errors = [];
$fields = ['alt_phone', 'emergency_contact_name', 'emergency_contact_phone', 'address', 'city', 'state',
           'skills', 'availability', 'preferred_category'];
$old = array_fill_keys($fields, '');

if (is_post()) {
    $old['alt_phone']               = post_str('alt_phone', 20);
    $old['emergency_contact_name']  = post_str('emergency_contact_name');
    $old['emergency_contact_phone'] = post_str('emergency_contact_phone', 20);
    $old['address']                 = post_str('address');
    $old['city']                    = post_str('city', 100);
    $old['state']                   = post_str('state', 100);
    $old['skills']                  = post_str('skills', 2000);
    $old['availability']            = post_str('availability');
    $old['preferred_category']      = post_enum('preferred_category', array_merge(['Any'], CATEGORIES)) ?? 'Any';

    foreach (['alt_phone' => 'Alternate phone', 'emergency_contact_phone' => 'Emergency contact phone'] as $k => $label) {
        if (!valid_phone($old[$k])) {
            $errors[] = "$label is not a valid phone number.";
        }
    }

    if (!$errors) {
        try {
            db_exec(
                'INSERT INTO volunteer_details
                    (id, alt_phone, emergency_contact_name, emergency_contact_phone, address, city, state, skills, availability, preferred_category)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [(int) $account['id'], $old['alt_phone'], $old['emergency_contact_name'], $old['emergency_contact_phone'],
                 $old['address'], $old['city'], $old['state'], $old['skills'], $old['availability'], $old['preferred_category']]
            );
            login_user($account);
            flash('Welcome to Aatman! Your volunteer profile has been saved.');
            redirect(home_for('volunteer'));
        } catch (mysqli_sql_exception $e) {
            $errors[] = report_error($e, 'Could not save your details. Please try again.');
        }
    }
}

$page_title = 'Volunteer Details';
$page_css   = ['assets/css/forms.css'];
require INCLUDES_PATH . '/partials/head.php';
?>
<div class="form-container wide">
    <h2>Volunteer Details</h2>
    <p>Hi <?= e($account['name']) ?>, tell us how you can help.</p>
    <?php if ($errors): ?>
        <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="POST">
        <?= csrf_field() ?>
        <input type="tel" name="alt_phone" placeholder="Alternate Phone Number" maxlength="20" value="<?= e($old['alt_phone']) ?>">
        <input type="text" name="emergency_contact_name" placeholder="Emergency Contact Name" maxlength="255" value="<?= e($old['emergency_contact_name']) ?>">
        <input type="tel" name="emergency_contact_phone" placeholder="Emergency Contact Phone Number" maxlength="20" value="<?= e($old['emergency_contact_phone']) ?>">
        <input type="text" name="address" placeholder="Address" maxlength="255" value="<?= e($old['address']) ?>">
        <input type="text" name="city" placeholder="City" maxlength="100" value="<?= e($old['city']) ?>">
        <input type="text" name="state" placeholder="State" maxlength="100" value="<?= e($old['state']) ?>">
        <textarea name="skills" placeholder="Skills (e.g. teaching, first aid, driving)" rows="3" maxlength="2000"><?= e($old['skills']) ?></textarea>
        <input type="text" name="availability" placeholder="Availability (e.g. weekends, Mon 7–9 pm)" maxlength="255" value="<?= e($old['availability']) ?>">
        <select name="preferred_category">
            <?= options(array_merge(['Any'], CATEGORIES), $old['preferred_category'] ?: 'Any') ?>
        </select>
        <button type="submit">Save Details</button>
    </form>
</div>
</body>
</html>

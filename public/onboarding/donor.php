<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$account = require_onboarding('donor', 'donor_details');
$errors = [];
$old = array_fill_keys(['address', 'city', 'state', 'donation_type', 'preferred_category'], '');

if (is_post()) {
    $old['address']            = post_str('address');
    $old['city']               = post_str('city', 100);
    $old['state']              = post_str('state', 100);
    $old['donation_type']      = post_enum('donation_type', DONATION_TYPES) ?? '';
    $old['preferred_category'] = post_enum('preferred_category', CATEGORIES) ?? '';

    if ($old['donation_type'] === '') {
        $errors[] = 'Please choose a donation type.';
    }

    if (!$errors) {
        try {
            db_exec(
                'INSERT INTO donor_details (id, address, city, state, donation_type, preferred_category)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [(int) $account['id'], $old['address'], $old['city'], $old['state'], $old['donation_type'],
                 $old['preferred_category'] === '' ? null : $old['preferred_category']]
            );
            login_user($account);
            flash('Welcome to Aatman! Your donor profile has been saved.');
            redirect(home_for('donor'));
        } catch (mysqli_sql_exception $e) {
            $errors[] = report_error($e, 'Could not save your details. Please try again.');
        }
    }
}

$page_title = 'Donor Details';
$page_css   = ['assets/css/forms.css'];
require INCLUDES_PATH . '/partials/head.php';
?>
<div class="form-container wide">
    <h2>Donor Details</h2>
    <p>Hi <?= e($account['name']) ?>, how would you like to help?</p>
    <?php if ($errors): ?>
        <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="POST">
        <?= csrf_field() ?>
        <input type="text" name="address" placeholder="Address" maxlength="255" value="<?= e($old['address']) ?>">
        <input type="text" name="city" placeholder="City" maxlength="100" value="<?= e($old['city']) ?>">
        <input type="text" name="state" placeholder="State" maxlength="100" value="<?= e($old['state']) ?>">
        <select name="donation_type" required>
            <option value="">Select Donation Type</option>
            <?= options(DONATION_TYPES, $old['donation_type']) ?>
        </select>
        <select name="preferred_category">
            <option value="">Select Preferred Category</option>
            <?= options(CATEGORIES, $old['preferred_category']) ?>
        </select>
        <button type="submit">Save Details</button>
    </form>
</div>
</body>
</html>

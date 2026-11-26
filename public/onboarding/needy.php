<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$account = require_onboarding('need', 'people_in_need_details');
$errors = [];
$old = array_fill_keys(['contact_person_name', 'contact_person_phone', 'alt_phone', 'address', 'city', 'state'], '');
$old['contact_person_name']  = $account['name'];
$old['contact_person_phone'] = (string) $account['phone'];

if (is_post()) {
    $old['contact_person_name']  = post_str('contact_person_name');
    $old['contact_person_phone'] = post_str('contact_person_phone', 20);
    $old['alt_phone']            = post_str('alt_phone', 20);
    $old['address']              = post_str('address');
    $old['city']                 = post_str('city', 100);
    $old['state']                = post_str('state', 100);

    if ($old['contact_person_name'] === '') {
        $errors[] = 'Contact person name is required.';
    }
    if (!valid_phone($old['contact_person_phone'], true)) {
        $errors[] = 'Please enter a valid contact phone number.';
    }
    if (!valid_phone($old['alt_phone'])) {
        $errors[] = 'Alternate phone is not a valid phone number.';
    }

    if (!$errors) {
        try {
            db_exec(
                'INSERT INTO people_in_need_details (id, contact_person_name, contact_person_phone, alt_phone, address, city, state)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [(int) $account['id'], $old['contact_person_name'], $old['contact_person_phone'], $old['alt_phone'],
                 $old['address'], $old['city'], $old['state']]
            );
            login_user($account);
            flash('Welcome to Aatman! You can now post a request for help.');
            redirect(home_for('need'));
        } catch (mysqli_sql_exception $e) {
            $errors[] = report_error($e, 'Could not save your details. Please try again.');
        }
    }
}

$page_title = 'Person in Need Details';
$page_css   = ['assets/css/forms.css'];
require INCLUDES_PATH . '/partials/head.php';
?>
<div class="form-container wide">
    <h2>Person in Need Details</h2>
    <p>Hi <?= e($account['name']) ?>, how can NGOs and donors reach you?</p>
    <?php if ($errors): ?>
        <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="POST">
        <?= csrf_field() ?>
        <input type="text" name="contact_person_name" placeholder="Contact Person Name" required maxlength="255" value="<?= e($old['contact_person_name']) ?>">
        <input type="tel" name="contact_person_phone" placeholder="Contact Person Phone Number" required maxlength="20" value="<?= e($old['contact_person_phone']) ?>">
        <input type="tel" name="alt_phone" placeholder="Alternate Phone Number" maxlength="20" value="<?= e($old['alt_phone']) ?>">
        <input type="text" name="address" placeholder="Address" maxlength="255" value="<?= e($old['address']) ?>">
        <input type="text" name="city" placeholder="City" maxlength="100" value="<?= e($old['city']) ?>">
        <input type="text" name="state" placeholder="State" maxlength="100" value="<?= e($old['state']) ?>">
        <button type="submit">Save Details</button>
    </form>
</div>
</body>
</html>

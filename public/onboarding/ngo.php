<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

// User id comes from the server-side session – never from ?user_id= in the URL.
$account = require_onboarding('ngo', 'ngo_details');
$errors = [];
$old = array_fill_keys(['organisation_name', 'registration_number', 'address', 'city', 'state', 'description', 'category'], '');

if (is_post()) {
    $old['organisation_name']   = post_str('organisation_name');
    $old['registration_number'] = post_str('registration_number', 100);
    $old['address']             = post_str('address');
    $old['city']                = post_str('city', 100);
    $old['state']               = post_str('state', 100);
    $old['description']         = post_str('description', 2000);
    $old['category']            = post_enum('category', CATEGORIES) ?? '';

    if ($old['organisation_name'] === '')   { $errors[] = 'Organisation name is required.'; }
    if ($old['registration_number'] === '') { $errors[] = 'Registration number is required.'; }
    if ($old['category'] === '')            { $errors[] = 'Please choose a category.'; }

    if (!$errors) {
        try {
            db_exec(
                'INSERT INTO ngo_details (id, organisation_name, registration_number, address, city, state, description, category)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [(int) $account['id'], $old['organisation_name'], $old['registration_number'], $old['address'],
                 $old['city'], $old['state'], $old['description'], $old['category']]
            );
            login_user($account);
            flash('Welcome to Aatman! Your NGO profile has been saved.');
            redirect(home_for('ngo'));
        } catch (mysqli_sql_exception $e) {
            $errors[] = is_duplicate_key($e)
                ? 'An NGO with this registration number is already registered.'
                : report_error($e, 'Could not save your details. Please try again.');
        }
    }
}

$page_title = 'NGO Details';
$page_css   = ['assets/css/forms.css'];
require INCLUDES_PATH . '/partials/head.php';
?>
<div class="form-container wide">
    <h2>NGO Details</h2>
    <p>Hi <?= e($account['name']) ?>, tell us about your organisation.</p>
    <?php if ($errors): ?>
        <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="POST">
        <?= csrf_field() ?>
        <input type="text" name="organisation_name" placeholder="Organisation Name" required maxlength="255" value="<?= e($old['organisation_name']) ?>">
        <input type="text" name="registration_number" placeholder="Registration Number" required maxlength="100" value="<?= e($old['registration_number']) ?>">
        <input type="text" name="address" placeholder="Address" maxlength="255" value="<?= e($old['address']) ?>">
        <input type="text" name="city" placeholder="City" maxlength="100" value="<?= e($old['city']) ?>">
        <input type="text" name="state" placeholder="State" maxlength="100" value="<?= e($old['state']) ?>">
        <textarea name="description" placeholder="Description" rows="4" maxlength="2000"><?= e($old['description']) ?></textarea>
        <select name="category" required>
            <option value="">Select Category</option>
            <?= options(CATEGORIES, $old['category']) ?>
        </select>
        <button type="submit">Save Details</button>
    </form>
</div>
</body>
</html>

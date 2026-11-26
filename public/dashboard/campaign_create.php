<?php
/** Campaign creation form (embedded in the NGO dashboard). NGOs only. */
require_once __DIR__ . '/../../includes/bootstrap.php';

$user = require_role('ngo');
$errors = [];
$created = false;
$old = array_fill_keys(['title', 'description', 'category', 'contact_person_name', 'contact_person_phone', 'start_date', 'end_date'], '');
$old['contact_person_name']  = $user['name'];
$old['contact_person_phone'] = (string) $user['phone'];

if (is_post()) {
    $old['title']                = post_str('title');
    $old['description']          = post_str('description', 2000);
    $old['category']             = post_enum('category', CATEGORIES) ?? '';
    $old['contact_person_name']  = post_str('contact_person_name');
    $old['contact_person_phone'] = post_str('contact_person_phone', 20);
    $old['start_date']           = post_str('start_date', 10);
    $old['end_date']             = post_str('end_date', 10);

    if ($old['title'] === '' || $old['description'] === '') { $errors[] = 'Title and description are required.'; }
    if ($old['category'] === '')                             { $errors[] = 'Please choose a valid category.'; }
    if ($old['contact_person_name'] === '')                  { $errors[] = 'Contact person name is required.'; }
    if (!valid_phone($old['contact_person_phone'], true))    { $errors[] = 'Please enter a valid contact phone number.'; }
    if (!valid_date($old['start_date']) || !valid_date($old['end_date'])) {
        $errors[] = 'Please enter valid start and end dates.';
    } elseif ($old['end_date'] < $old['start_date']) {
        $errors[] = 'End date cannot be before the start date.';
    }

    if (!$errors) {
        try {
            // ngo_id is always the logged-in NGO – never taken from the form.
            db_exec(
                'INSERT INTO campaigns (ngo_id, title, description, category, contact_person_name, contact_person_phone, start_date, end_date)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [(int) $user['id'], $old['title'], $old['description'], $old['category'], $old['contact_person_name'],
                 $old['contact_person_phone'], $old['start_date'], $old['end_date']]
            );
            $created = true;
            $old = array_merge($old, ['title' => '', 'description' => '', 'category' => '', 'start_date' => '', 'end_date' => '']);
        } catch (Throwable $e) {
            $errors[] = report_error($e, 'Could not create the campaign. Please try again.');
        }
    }
}

$page_title = 'Create Campaign';
$page_css   = ['assets/css/forms.css'];
$body_class = 'embedded';
require INCLUDES_PATH . '/partials/head.php';
?>
<div class="form-container wide">
    <h2>Create a New Campaign</h2>
    <?php if ($created): ?>
        <div class="alert alert-info">Campaign created successfully! It now appears under “My Campaigns” (refresh the dashboard).</div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="POST" action="<?= e(url('dashboard/campaign_create.php')) ?>">
        <?= csrf_field() ?>
        <input type="text" name="title" placeholder="Campaign Title" required maxlength="255" value="<?= e($old['title']) ?>">
        <textarea name="description" placeholder="Campaign Description" required rows="4" maxlength="2000"><?= e($old['description']) ?></textarea>
        <select name="category" required>
            <option value="">Select Category</option>
            <?= options(CATEGORIES, $old['category']) ?>
        </select>
        <input type="text" name="contact_person_name" placeholder="Contact Person Name" required maxlength="255" value="<?= e($old['contact_person_name']) ?>">
        <input type="tel" name="contact_person_phone" placeholder="Contact Person Phone" required maxlength="20" value="<?= e($old['contact_person_phone']) ?>">
        <label for="start_date">Start Date</label>
        <input type="date" name="start_date" id="start_date" required value="<?= e($old['start_date']) ?>">
        <label for="end_date">End Date</label>
        <input type="date" name="end_date" id="end_date" required value="<?= e($old['end_date']) ?>">
        <button type="submit">Create Campaign</button>
    </form>
</div>
</body>
</html>

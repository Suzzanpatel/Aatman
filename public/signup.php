<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if ($me = current_user()) {
    redirect(home_for($me['role']));
}

$errors = [];
$old = ['name' => '', 'email' => '', 'role' => '', 'phone' => ''];

if (is_post()) {
    $old['name']  = post_str('name', 100);
    $old['email'] = mb_strtolower(post_str('email', 89));
    $old['phone'] = post_str('phone', 20);
    // Allow-list: "admin" can never be chosen at sign-up (prevents privilege escalation).
    $old['role']  = post_enum('role', SELF_SIGNUP_ROLES) ?? '';
    $password     = (string) ($_POST['password'] ?? '');
    $confirm      = (string) ($_POST['password_confirm'] ?? '');

    if ($old['name'] === '') {
        $errors[] = 'Please enter your full name.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($old['role'] === '') {
        $errors[] = 'Please select a valid role.';
    }
    if (!valid_phone($old['phone'])) {
        $errors[] = 'Phone number may only contain digits, spaces, dashes and a leading +.';
    }
    if (mb_strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
    } elseif (mb_strlen($password) > 72) {
        $errors[] = 'Password must be at most 72 characters.';
    } elseif ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        try {
            db_exec(
                'INSERT INTO users (name, email, password, role, phone) VALUES (?, ?, ?, ?, ?)',
                [$old['name'], $old['email'], password_hash($password, PASSWORD_DEFAULT), $old['role'],
                 $old['phone'] === '' ? null : $old['phone']]
            );
            // Remember (server-side) which account may complete onboarding.
            session_regenerate_id(true);
            $_SESSION['onboarding_user_id'] = (int) db()->insert_id;
            redirect(ROLE_ONBOARDING[$old['role']]);
        } catch (mysqli_sql_exception $e) {
            $errors[] = is_duplicate_key($e)
                ? 'This email is already registered. Please log in or use another email.'
                : report_error($e, 'Could not create your account. Please try again.');
        }
    }
}

$page_title = 'Sign up';
$page_css   = ['assets/css/forms.css'];
require INCLUDES_PATH . '/partials/head.php';
?>
<div class="form-container">
    <h2>Sign up</h2>

    <?php if ($errors): ?>
        <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form action="<?= e(url('signup.php')) ?>" method="POST">
        <?= csrf_field() ?>
        <input type="text" name="name" placeholder="Full Name" required maxlength="100" value="<?= e($old['name']) ?>">
        <input type="email" name="email" placeholder="Email" required maxlength="89" value="<?= e($old['email']) ?>" autocomplete="username">
        <input type="password" name="password" placeholder="Password (min. <?= PASSWORD_MIN_LENGTH ?> characters)" required
               minlength="<?= PASSWORD_MIN_LENGTH ?>" maxlength="72" autocomplete="new-password">
        <input type="password" name="password_confirm" placeholder="Confirm Password" required
               minlength="<?= PASSWORD_MIN_LENGTH ?>" maxlength="72" autocomplete="new-password">
        <select name="role" required>
            <option value="">Select Role</option>
            <?= options(SELF_SIGNUP_ROLES, $old['role'], ROLE_LABELS) ?>
        </select>
        <input type="tel" name="phone" placeholder="Phone Number" maxlength="20" value="<?= e($old['phone']) ?>">
        <button type="submit">Sign up</button>
    </form>
    <p>Already have an account? <a href="<?= e(url('login.php')) ?>">Login here</a></p>
</div>
</body>
</html>

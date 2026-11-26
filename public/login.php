<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Already logged in? Go straight to the dashboard.
if ($me = current_user()) {
    redirect(home_for($me['role']));
}

$error = '';
$email = '';

/** Count recent failed attempts for this email and for this IP. */
function login_is_throttled(string $email): bool
{
    $window = LOGIN_LOCKOUT_MINUTES;
    $row = db_one(
        "SELECT
            SUM(email = ?) AS by_email,
            SUM(ip_address = ?) AS by_ip
         FROM login_attempts
         WHERE attempted_at > (NOW() - INTERVAL $window MINUTE)",
        [$email, client_ip()]
    );
    return (int) ($row['by_email'] ?? 0) >= LOGIN_MAX_ATTEMPTS_PER_EMAIL
        || (int) ($row['by_ip'] ?? 0) >= LOGIN_MAX_ATTEMPTS_PER_IP;
}

if (is_post()) {
    $email    = mb_strtolower(post_str('email', 89));
    $password = (string) ($_POST['password'] ?? '');   // never trimmed: must match how it was hashed

    if ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } elseif (login_is_throttled($email)) {
        $error = 'Too many failed login attempts. Please wait ' . LOGIN_LOCKOUT_MINUTES . ' minutes and try again.';
    } else {
        $user = db_one('SELECT id, name, email, password, role, status FROM users WHERE email = ?', [$email]);

        // Always run password_verify (against a dummy hash if needed) so response
        // time does not reveal whether the email exists.
        $hash = $user['password'] ?? '$2y$10$foiUVMcgrrnJNo0KAbyYgOLduqK.0J7SdMfdQgDO9vw6Hrchfij9C';
        $ok   = password_verify($password, $hash) && $user !== null;

        if ($ok && $user['status'] !== 'active') {
            $error = 'This account has been deactivated. Please contact the Aatman team to reactivate it.';
        } elseif ($ok) {
            db_exec('DELETE FROM login_attempts WHERE email = ?', [$email]);
            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                db_exec('UPDATE users SET password = ? WHERE id = ?',
                    [password_hash($password, PASSWORD_DEFAULT), (int) $user['id']]);
            }
            login_user($user);
            redirect(home_for($user['role']));
        } else {
            db_exec('INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)', [$email, client_ip()]);
            $error = 'Invalid email or password.';   // same message for unknown email / wrong password
        }
    }
}

$flash = take_flash();
if (($_GET['message'] ?? '') === 'account_disabled') {
    $flash = ['message' => 'Your account has been deactivated.', 'type' => 'info'];
}

$page_title = 'Login';
$page_css   = ['assets/css/forms.css'];
require INCLUDES_PATH . '/partials/head.php';
?>
<div class="form-container">
    <h2>Login</h2>

    <?php if ($flash): ?>
        <div class="alert <?= $flash['type'] === 'error' ? 'alert-error' : 'alert-info' ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= e(url('login.php')) ?>" autocomplete="on">
        <?= csrf_field() ?>
        <input type="email" name="email" placeholder="Email" required maxlength="89" value="<?= e($email) ?>" autocomplete="username">
        <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
        <button type="submit">Login</button>
    </form>

    <p>Don't have an account? <a href="<?= e(url('signup.php')) ?>">Sign up here</a></p>
    <p><a href="<?= e(url('index.php')) ?>">← Back to home</a></p>
</div>
</body>
</html>

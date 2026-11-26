<?php
/** Top navigation for logged-in pages. Expects $user (from require_role). */
$nav_user = $user ?? current_user();
?>
<header class="app-header">
    <div class="container navbar">
        <a href="<?= e(url($nav_user ? home_for($nav_user['role']) : 'index.php')) ?>" class="logo">
            <i class="fas fa-hand-holding-heart"></i> Aatman
        </a>

        <nav class="nav-links">
            <a href="<?= e(url('index.php')) ?>">Home</a>
            <?php if ($nav_user): ?>
                <span class="nav-user" title="<?= e($nav_user['email']) ?>">
                    <i class="fas fa-user-circle"></i> <?= e($nav_user['name']) ?>
                    <small>(<?= e(ROLE_LABELS[$nav_user['role']] ?? $nav_user['role']) ?>)</small>
                </span>
                <form method="post" action="<?= e(url('logout.php')) ?>" class="inline-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="logout-button">Logout</button>
                </form>
            <?php endif; ?>
            <!-- Multilingual support: Google Translate widget (initialised in foot.php) -->
            <div id="google_translate_element" class="translate-box"></div>
        </nav>
    </div>
</header>

<?php if ($flash = take_flash()): ?>
    <div class="container">
        <div class="alert-message <?= $flash['type'] === 'error' ? 'alert-error' : 'alert-info' ?>" role="status">
            <?= e($flash['message']) ?>
        </div>
    </div>
<?php endif; ?>

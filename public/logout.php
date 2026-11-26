<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Logout is a POST with a CSRF token (bootstrap enforces it), so a third-party
// page cannot log users out by embedding a link or image.
if (is_post()) {
    logout_user();
    flash('You have been logged out.');
}
redirect('login.php');

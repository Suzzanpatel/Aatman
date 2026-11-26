<?php
/** /dashboard/ – send each user to the dashboard for their role. */
require_once __DIR__ . '/../../includes/bootstrap.php';

$user = require_login();
redirect(home_for($user['role']));

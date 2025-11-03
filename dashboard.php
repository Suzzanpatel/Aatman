<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard | Aatman</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="form-container">
        <h2>Welcome, <?php echo $_SESSION['name']; ?>!</h2>
        <p>You are logged in as <b><?php echo ucfirst($_SESSION['role']); ?></b>.</p>
        <a href="logout.php">Logout</a>
    </div>
</body>
</html>

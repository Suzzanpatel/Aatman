<?php
// Start output buffering and session
ob_start();
session_start();
include 'config.php'; // database connection

// Enable error display (for debugging)
error_reporting(E_ALL);
ini_set('display_errors', 1);

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Using trim() here is correct. 
    // You MUST also use trim() in your signup.php before hashing the password.
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Fetch user data
    $sql = "SELECT id, name, email, password, role FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Verify password
            if (password_verify($password, $user['password'])) {
                // Store session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = strtolower(trim($user['role']));

                // Clear buffer before redirection
                ob_end_clean();

                // Redirect based on role
                // *** FIX: Set a specific session variable for each role ***
                // This ensures dashboard pages (like donor_dashboard.php)
                // find the session variable they are looking for.
                switch ($_SESSION['role']) {
                    case 'ngo':
                        $_SESSION['ngo_logged_in'] = true;
                        header("Location: ngo_dashboard.php");
                        exit();
                    case 'volunteer':
                        $_SESSION['volunteer_logged_in'] = true;
                        header("Location: volunteer_dashboard.php");
                        exit();
                    case 'donor':
                        $_SESSION['donor_logged_in'] = true; // This was the missing line
                        header("Location: donor_dashboard.php");
                        exit();
                    case 'admin':
                        $_SESSION['admin_logged_in'] = true;
                        header("Location: admin.php");
                        exit();
                    case 'need':
                    case 'person in need':
                         $_SESSION['needy_logged_in'] = true;
                        header("Location: needy_dashboard.php");
                        exit();
                    default:
                        // You might have a generic dashboard or just redirect to login
                        header("Location: landing.php");
                        exit();
                }
            } else {
                $error_message = "Invalid password.";
            }
        } else {
            $error_message = "No user found with that email.";
        }

        $stmt->close();
    } else {
        $error_message = "Database error: unable to prepare statement.";
    }
}

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Aatman</title>
    <link rel="stylesheet" href="forms.css">
    
   
</head>
<body>
    <div class="form-container">
        <h2>Login</h2>

        <?php if (!empty($error_message)): ?>
            <p style="color: red;"><?= htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <form method="POST">
            <input type="email" name="email" placeholder="Email" required 
                   value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>

        <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
    </div>
</body>
</html>


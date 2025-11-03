<?php include 'config.php'; ?>

<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role'];
    $phone = trim($_POST['phone']);

    // ✅ Check if email already exists
    $check_sql = "SELECT id FROM users WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        echo "<script>alert('This email is already registered. Please log in or use another email.'); window.location.href='login.php';</script>";
        exit();
    }

    // ✅ Proceed with inserting
    $sql = "INSERT INTO users (name, email, password, role, phone) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $name, $email, $password, $role, $phone);

    if ($stmt->execute()) {
        $new_user_id = $conn->insert_id;

        if ($role == 'ngo') {
            header("Location: ngo_details.php?user_id=" . $new_user_id);
        } elseif ($role == 'donor') {
            header("Location: donor_details.php?user_id=" . $new_user_id);
        } elseif ($role == 'volunteer') {
            header("Location: volunteer_details.php?user_id=" . $new_user_id);
        } elseif ($role == 'need') {
            header("Location: people_in_need_details.php?user_id=" . $new_user_id);
        } else {
            header("Location: login.php");
        }
        exit();
    }
}


?>

<!DOCTYPE html>
<html>
<head>
    <title>Signup | Aatman</title>
    <link rel="stylesheet" href="forms.css">
</head>
<body>
    <div class="form-container">
        <h2>Signup</h2>
  <form action="signup.php" method="POST">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <select name="role" required>
                <option value="">Select Role</option>
                <option value="ngo">NGO</option>
                <option value="donor">Donor</option>
                <option value="need">Person in Need</option>
                <option value="volunteer">Volunteer</option>
            </select>
            <input type="text" name="phone" placeholder="Phone Number">
            <button type="submit">Signup</button>
        </form>
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
</body>
</html>

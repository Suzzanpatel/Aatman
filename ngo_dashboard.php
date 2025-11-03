<?php
session_start();
include('config.php'); // Database connection (expects $conn)

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- 1) Ensure logged in ---
// Use the specific session variable set by your login.php
if (!isset($_SESSION['ngo_logged_in']) || $_SESSION['ngo_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}
// Also ensure user_id is set
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id']; // safety

// --- 2) Display session messages ---
$message = '';
$message_type = 'info'; // 'info' or 'error'
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'] ?? 'info';
    unset($_SESSION['message']); // Clear it after reading
    unset($_SESSION['message_type']);
}

// --- 3) Handle Profile Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Get data from POST request
    $user_name = trim($_POST['user_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $organisation_name = trim($_POST['organisation_name']);
    $address = trim($_POST['address']);

    $conn->begin_transaction();
    try {
        // 1. Update users table
        $stmt_users = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
        $stmt_users->bind_param("sssi", $user_name, $email, $phone, $user_id);
        $stmt_users->execute();

        // 2. Update ngo_details table
        // *** FIX: Changed from INSERT...ON DUPLICATE KEY to simple UPDATE. ***
        // This avoids the error when the INSERT part tries to run without
        // the NOT NULL 'registration_number' field.
        // This query will not fail if the row doesn't exist; it will just affect 0 rows.
        $stmt_ngo = $conn->prepare("
            UPDATE ngo_details SET 
                organisation_name = ?, 
                address = ?
            WHERE id = ?
        ");
        $stmt_ngo->bind_param("ssi", $organisation_name, $address, $user_id);
        $stmt_ngo->execute();
        
        $conn->commit();
        $_SESSION['message'] = "Profile updated successfully!";
        $_SESSION['message_type'] = "info";

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['message'] = "Error updating profile: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }
    header("Location: ngo_dashboard.php");
    exit();
}


// --- 4) Fetch user + NGO details together ---
$query = "
    SELECT 
        u.name AS user_name,
        u.email,
        u.phone,
        u.status,
        n.organisation_name,
        n.registration_number,
        n.address
    FROM users u
    LEFT JOIN NGO_details n ON u.id = n.id
    WHERE u.id = ?
";

$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    die("Prepare failed: " . mysqli_error($conn));
}
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$userData = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// --- 5) Handle Deactivation ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deactivate'])) {
    $update = "UPDATE users SET status = 'Inactive' WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}


// --- 6) Handle Campaign Status Update (for 'Previous Campaigns' tab) ---
if (isset($_POST['update_status'])) {
    $campaign_id = $_POST['campaign_id'];
    $new_status = $_POST['new_status'];

    // Security: Check if this campaign_id actually belongs to this NGO user
    $check_query = "SELECT id FROM campaigns WHERE id = ? AND ngo_id = ?";
    $stmt_check = $conn->prepare($check_query);
    $stmt_check->bind_param("ii", $campaign_id, $user_id);
    $stmt_check->execute();
    $check_result = $stmt_check->get_result();

    if ($check_result->num_rows > 0) {
        // User owns this campaign, proceed with update
        $update_query = "UPDATE campaigns SET status = ? WHERE id = ?";
        $stmt2 = $conn->prepare($update_query);
        $stmt2->bind_param("si", $new_status, $campaign_id);
        $stmt2->execute();
    }
    
    // Reload page after update
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// --- 7) Handle Create Request (for 'Request Donors' tab) ---
if (isset($_POST['create_request'])) {
    // Get user info from session
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role']; // 'ngo'

    // Fetch user_name using user_id
    $stmt_user = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $stmt_user->bind_result($user_name);
    $stmt_user->fetch();
    $stmt_user->close();

    // Get form data safely
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $request_type = $_POST['request_type'];
    $quantity = (int)$_POST['quantity'];
    $unit = trim($_POST['unit']);
    $location = trim($_POST['location']);
    $priority = $_POST['urgency']; // maps to 'priority' column
    $contact_person_name = trim($_POST['contact_person_name']);
    $contact_person_phone = trim($_POST['contact_person_phone']);
    $status = $_POST['status']; // Should default to 'pending'

    // Prepare and execute SQL insert with user_name included
    $stmt = $conn->prepare("
        INSERT INTO requests (
            user_id, user_name, user_role, title, description, request_type, 
            quantity, unit, location, priority, contact_person_name, contact_person_phone, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "isssssissssss",
        $user_id,
        $user_name,
        $user_role,
        $title,
        $description,
        $request_type,
        $quantity,
        $unit,
        $location,
        $priority,
        $contact_person_name,
        $contact_person_phone,
        $status
    );

    if ($stmt->execute()) {
        $_SESSION['message'] = "Your request has been submitted successfully!";
    } else {
        $_SESSION['message'] = "Error submitting request. Please try again.";
        $_SESSION['message_type'] = "error";
    }

    $stmt->close();
    header("Location: ngo_dashboard.php"); // Redirect to avoid form resubmission
    exit();
}


// --- 8) Fetch requests made by this NGO (for 'My Requests' tab) ---
$sql_my_requests = "SELECT * FROM requests WHERE user_id = ?  ORDER BY created_at DESC";
$stmt_my_requests = $conn->prepare($sql_my_requests);
$stmt_my_requests->bind_param("i", $user_id);
$stmt_my_requests->execute();
$requests = $stmt_my_requests->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NGO Dashboard - Aatman</title>
    <!-- Google Fonts: Poppins for body, Playfair Display for headings -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <!-- <script src="script.js"></script> --> <!-- Commented out as we will add JS inline -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Embedded styles for new features -->
    <style>
        .hidden-section, .hidden {
            display: none;
        }

        /* Simple alert box styles */
        .alert-message {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-info {
            color: #31708f;
            background-color: #d9edf7;
            border-color: #bce8f1;
        }
        .alert-error {
            color: #a94442;
            background-color: #f2dede;
            border-color: #ebccd1;
        }

        /* Style for inputs to look like text when disabled */
        input[disabled], select[disabled] {
            background-color: transparent;
            border: none;
            color: #333;
            padding-left: 0;
            cursor: default;
        }
        
        input {
             width: 100%; /* Make inputs fill their container */
        }

        /* Action buttons for profile */
        .profile-actions {
            display: flex;
            justify-content: flex-start;
            gap: 10px;
            margin-top: 20px;
        }

        /* Danger zone for disable button */
        .profile-card-danger {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        /* General button styles (if not in style.css) */
        .btn-edit, .btn-save, .btn-disable {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
        }
        .btn-edit { background-color: #3498db; color: white; }
        .btn-save { background-color: #2ecc71; color: white; }
        .btn-disable { background-color: #e74c3c; color: white; }

    </style>
</head>

<body>

    <!-- Header -->
    <?php include 'navbar.php'; ?>
    
    <main class="container">
        <div class="dashboard-nav">
            <button class="active" data-section="profile">Profile</button>
            <button data-section="requests">View Needy Requests</button>
            <button data-section="create-campaign">Create Campaign</button>
            <button data-section="completed-drives">Previous Campaigns</button>
            <button data-section="volunteers">View Volunteers</button>
            <button data-section="donor-requests">Request Donors</button>
            <button data-section="my-requests">View My Requests</button>
        </div>

        <div class="container">
            
            <!-- Session Message Display -->
            <?php if (!empty($message)): ?>
                <div class="alert-message <?= ($message_type == 'error') ? 'alert-error' : 'alert-info'; ?>">
                    <?= htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>


            <!-- Dashboard Section -->
            <section id="profile" class="dashboard-section">
                <h2><i class="fas fa-building"></i> NGO Profile</h2>
                <div class="profile-card">
                    
                    <!-- EDITABLE PROFILE FORM -->
                    <form method="POST" action="ngo_dashboard.php" id="editForm">
                        <div class="profile-info">
                            <h3>General Information</h3>
                            <p><strong>Organisation Name:</strong>
                                <input type="text" name="organisation_name" value="<?= htmlspecialchars($userData['organisation_name'] ?? '') ?>" disabled>
                            </p>
                             <p><strong>Contact Person:</strong>
                                <input type="text" name="user_name" value="<?= htmlspecialchars($userData['user_name'] ?? '') ?>" disabled>
                            </p>
                            <p><strong>Status:</strong> <?= htmlspecialchars($userData['status']) ?></p>
                            <p><strong>Registration No:</strong>
                                <?= htmlspecialchars($userData['registration_number'] ?: 'Not Provided') ?>
                                <em>(Cannot be changed)</em>
                            </p>
                        </div>

                        <div class="profile-info">
                            <h3>Contact Details</h3>
                            <p><strong>Email:</strong>
                                <input type="email" name="email" value="<?= htmlspecialchars($userData['email'] ?? '') ?>" disabled>
                            </p>
                            <p><strong>Phone:</strong>
                                <input type="text" name="phone" value="<?= htmlspecialchars($userData['phone'] ?? '') ?>" disabled>
                            </p>
                            <p><strong>Address:</strong>
                                <input type="text" name="address" value="<?= htmlspecialchars($userData['address'] ?? '') ?>" disabled>
                            </p>
                        </div>

                        <div class="profile-actions">
                            <button type="button" id="editBtn" class="btn-edit">Edit</button>
                            <button type="submit" name="update_profile" id="saveBtn" class="btn-save hidden">Save Changes</button>
                        </div>
                    </form>

                    <!-- DEACTIVATE ACCOUNT FORM (SEPARATE) -->
                    <div class="profile-card-danger">
                        <h3>Manage Status</h3>
                        <p>Toggle your organization's status to accept or temporarily pause new requests.</p>
                        <form method="POST"
                            onsubmit="return confirm('Are you sure you want to deactivate your account?');">
                            <button type="submit" name="deactivate" class="btn-disable">Deactivate Account</button>
                        </form>
                    </div>
                </div>
            </section>


             <!-- REQUESTS SECTION -->
 
            <section id="requests" class="dashboard-section hidden-section">
                <h2>Requests from Needy</h2>
                <p>Here are the latest requests from individuals and families in need of assistance.</p>

                <div class="table-container">
                    <table class="dashboard-table" border="1" cellpadding="10" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Location</th>
                                <th>Contact Person</th>
                                <th>Contact Details</th>
                                <th>Request Type</th>
                                <th>Quantity</th>
                                <th>Unit</th>
                                <th>Priority</th>
                                <th>User Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch all pending requests from any user
                            $sql_all_requests = "
                                SELECT 
                                    r.title, 
                                    r.description, 
                                    r.location, 
                                    r.contact_person_name, 
                                    r.contact_person_phone, 
                                    r.request_type, 
                                    r.quantity, 
                                    r.unit,
                                    r.priority,
                                    u.role 
                                FROM requests AS r
                                LEFT JOIN users AS u ON r.user_id = u.id
                                WHERE r.status = 'pending'
                                AND u.role='need'
                                ORDER BY r.created_at DESC
                            ";

                            $res = $conn->query($sql_all_requests);

                            if (!$res) {
                                echo "<tr><td colspan='10'>Query failed: " . htmlspecialchars($conn->error) . "</td></tr>";
                            } elseif ($res->num_rows > 0) {
                                while ($row = $res->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['location']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['contact_person_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['contact_person_phone']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['request_type']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['quantity']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['unit']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['priority'] ?? 'Medium') . "</td>";
                                    echo "<td>" . htmlspecialchars(ucfirst($row['role'] ?? 'N/A')) . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan'10'>No pending requests found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- create campaigns !-->
            <section id="create-campaign" class="dashboard-section hidden-section">
                   <h2><i class="fas fa-bullhorn"></i> Create New Campaign</h2>
                <iframe src="campaign.php"
                    style="width:100%; height:600px; border:none; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.1);"></iframe>
            </section>

            <!-- previous campaigns !-->
            <section id="completed-drives" class="dashboard-section hidden-section">
                  <h2><i class="fas fa-clipboard-list"></i> Previous Campaigns</h2>
                <p>Browse campaigns organized by NGOs and manage their status.</p>

                <table class="dashboard-table" border="1" cellpadding="10" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Campaign Name</th>
                            <th>Description</th>
                            <th>Category</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <!-- <th>NGO</th> --> <!-- Removed, as it's always this NGO -->
                            <th>Contact Person Name</th>
                            <th>Contact Person Number</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch campaigns *only for this NGO*
                        $sql_campaigns = "
                            SELECT 
                                c.id,
                                c.title,
                                c.description,
                                c.category,
                                c.start_date,
                                c.end_date,
                                c.contact_person_name,
                                c.contact_person_phone,
                                c.status
                            FROM campaigns AS c
                            WHERE c.ngo_id = ?
                            ORDER BY c.created_at DESC
                        ";

                        $stmt_campaigns = $conn->prepare($sql_campaigns);
                        $stmt_campaigns->bind_param("i", $user_id);
                        $stmt_campaigns->execute();
                        $result_campaigns = $stmt_campaigns->get_result();

                        if (!$result_campaigns) {
                            echo "<tr><td colspan='8'>Error fetching campaigns: " . htmlspecialchars($conn->error) . "</td></tr>";
                        } elseif ($result_campaigns->num_rows > 0) {
                            while ($row = $result_campaigns->fetch_assoc()) {
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['title']) ?></td>
                                    <td><?= htmlspecialchars($row['description']) ?></td>
                                    <td><?= htmlspecialchars($row['category']) ?></td>
                                    <td><?= htmlspecialchars($row['start_date']) ?></td>
                                    <td><?= htmlspecialchars($row['end_date']) ?></td>
                                    <td><?= htmlspecialchars($row['contact_person_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($row['contact_person_phone'] ?? 'N/A') ?></td>
                                    <td>
                                        <!-- Changed action to this page to use the PHP logic at the top -->
                                        <form method="POST" action="ngo_dashboard.php" style="margin:0;">
                                            <input type="hidden" name="campaign_id" value="<?= $row['id'] ?>">
                                            <select name="new_status" onchange="this.form.submit()">
                                                <option value="active" <?= $row['status'] === 'active' ? 'selected' : '' ?>>Active
                                                </option>
                                                <option value="completed" <?= $row['status'] === 'completed' ? 'selected' : '' ?>>
                                                    Completed</option>
                                                <option value="disabled" <?= $row['status'] === 'disabled' ? 'selected' : '' ?>>
                                                    Disabled</option>
                                            </select>
                                            <input type="submit" name="update_status" value="Go" class="hidden">
                                        </form>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo "<tr><td colspan='8'>No campaigns found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </section>

            <!-- Volunteers !-->
            <section id="volunteers" class="dashboard-section hidden-section">
                  <h2><i class="fas fa-user-friends"></i> View Volunteers</h2>
                <p>Contact these dedicated individuals who have signed up to help with your causes.</p>

                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Skills</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Availability</th>
                            <th>City</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch volunteers with details
                        $sql_volunteers = "
                            SELECT 
                                u.id,
                                u.name,
                                u.phone,
                                u.email,
                                u.status,
                                v.skills,
                                v.availability,
                                v.city
                            FROM users u
                            LEFT JOIN volunteer_details v ON u.id = v.id
                            WHERE u.role = 'volunteer'
                        ";
                        $result_volunteers = $conn->query($sql_volunteers);

                        if (!$result_volunteers) {
                            echo "<tr><td colspan='7'>Query failed: " . $conn->error . "</td></tr>";
                        } elseif ($result_volunteers->num_rows > 0) {
                            while ($volunteer = $result_volunteers->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($volunteer['name']) . "</td>";
                                echo "<td>" . htmlspecialchars($volunteer['skills'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($volunteer['phone'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($volunteer['email'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($volunteer['status'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($volunteer['availability'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($volunteer['city'] ?? 'N/A') . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan'7'>No volunteers found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </section>


            <!-- Donors Request !-->
            <section id="donor-requests" class="dashboard-section hidden-section">
                <h2>Make a New Request</h2>
                <p>Fill out the form below to request assistance from NGOs and donors.</p>
                <!-- Form action points to this page, to use the PHP logic at the top -->
                <form action="ngo_dashboard.php" method="post" class="request-form">
                    <input type="text" name="title" placeholder="Title of your request" required>
                    <textarea name="description" placeholder="Description of your need" rows="5" required></textarea>
                    <select name="request_type" required>
                        <option value="">Select Request Type</option>
                        <option value="Food">Food</option>
                        <option value="Clothes">Clothes</option>
                        <option value="Medicine">Medicine</option>
                        <option value="Shelter">Shelter</option>
                        <option value="Education">Education</option>
                        <option value="Other">Other</option>
                    </select>
                    <input type="number" name="quantity" placeholder="Quantity" required>
                    <input type="text" name="unit" placeholder="Unit" required>
                    <input type="text" name="location" placeholder="Your Location" required>
                    <select name="urgency" required>
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                        <option value="High">High</option>
                    </select>
                    <input type="text" name="contact_person_name" placeholder="Contact Person Name" required value="<?= htmlspecialchars($userData['user_name'] ?? '') ?>">
                    <input type="tel" name="contact_person_phone" placeholder="Contact Person Phone" required value="<?= htmlspecialchars($userData['phone'] ?? '') ?>">
                    <input type="hidden" name="status" value="pending">
                    
                    <button type="submit" name="create_request">Submit Request</button>
                </form>
            </section>

            <!-- My Requests (made by this NGO) !-->
            <section id="my-requests" class="dashboard-section hidden-section">
                <h2>My Requests</h2>
                <p>Track the status of your submitted requests for assistance.</p>
                <table class="dashboard-table">
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Location</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    <?php if ($requests->num_rows > 0): ?>
                        <?php while ($req = $requests->fetch_assoc()) { ?>
                            <tr>
                                <td><?= htmlspecialchars($req['title']) ?></td>
                                <td><?= htmlspecialchars($req['request_type']) ?></td>
                                <td><?= htmlspecialchars($req['quantity']) . ' ' . htmlspecialchars($req['unit']) ?></td>
                                <td><?= htmlspecialchars($req['location']) ?></td>
                                <td><?= htmlspecialchars($req['priority']) ?></td>
                                <td><?= htmlspecialchars($req['status']) ?></td>
                                <td>
                                    <?php if ($req['status'] === 'pending') { ?>
                                        <!-- TODO: Add logic for completing/deleting requests -->
                                        <!-- <a href="?complete_id=<?= $req['id'] ?>" class="btn">Mark as Completed</a> -->
                                    <?php } ?>
                                    <!-- <a href="?delete_id=<?= $req['id'] ?>" class="btn" style="background-color:red;">Delete</a> -->
                                </td>
                            </tr>
                        <?php } ?>
                    <?php else: ?>
                        <tr><td colspan="7">You have not made any requests yet.</td></tr>
                    <?php endif; ?>
                </table>
            </section>
       
           
        </div>
    </main>

    <!-- Embedded JS for Profile Edit and Tab Navigation -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            
            // --- Profile Edit Functionality ---
            const editBtn = document.getElementById('editBtn');
            const saveBtn = document.getElementById('saveBtn');
            const editForm = document.getElementById('editForm');
            
            if (editBtn && saveBtn && editForm) {
                // Select all inputs within the form
                const formElements = editForm.querySelectorAll('input');

                editBtn.addEventListener('click', function() {
                    // Enable all form elements
                    formElements.forEach(function(element) {
                        element.disabled = false;
                    });

                    // Toggle button visibility
                    editBtn.classList.add('hidden');
                    saveBtn.classList.remove('hidden');
                });
            }

            // --- Dashboard Tab Navigation ---
            const navButtons = document.querySelectorAll('.dashboard-nav button');
            const dashboardSections = document.querySelectorAll('.dashboard-section');

            navButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const sectionId = this.getAttribute('data-section');

                    // 1. Update button active state
                    navButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');

                    // 2. Show/hide sections
                    dashboardSections.forEach(section => {
                        if (section.id === sectionId) {
                            section.classList.remove('hidden-section');
                        } else {
                            section.classList.add('hidden-section');
                        }
                    });
                });
            });

            // Ensure the default active tab's section is shown on load
            const defaultActiveButton = document.querySelector('.dashboard-nav button.active');
            if (defaultActiveButton) {
                const defaultSectionId = defaultActiveButton.getAttribute('data-section');
                document.getElementById(defaultSectionId).classList.remove('hidden-section');
            }

        });
    </script>

</body>

</html>


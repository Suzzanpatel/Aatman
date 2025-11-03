<?php
session_start();
include 'config.php';

// ------------------------------------
// Check login & role
// ------------------------------------
// Use the specific session variable set by your login.php
if (!isset($_SESSION['needy_logged_in']) || $_SESSION['needy_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}
// Also ensure user_id is set
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$user_name = $_SESSION['name']; // This might be stale if updated, fetch from DB is better
$user_role = $_SESSION['role'];

// --- Display session messages ---
$message = '';
$message_type = 'info'; // 'info' or 'error'
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'] ?? 'info';
    unset($_SESSION['message']); // Clear it after reading
    unset($_SESSION['message_type']);
}

// ------------------------------------
// Handle Profile Update
// ------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Get data from POST request
    $user_name = trim($_POST['user_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    $contact_person_name = trim($_POST['contact_person_name']);
    $contact_person_phone = trim($_POST['contact_person_phone']);
    $alt_phone = trim($_POST['alt_phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);

    $conn->begin_transaction();
    try {
        // 1. Update users table
        $stmt_users = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
        $stmt_users->bind_param("sssi", $user_name, $email, $phone, $user_id);
        $stmt_users->execute();

        // 2. Update/Insert people_in_need_details table (robustly)
        // Use INSERT...ON DUPLICATE KEY UPDATE to handle missing records
        $stmt_details = $conn->prepare("
            INSERT INTO people_in_need_details 
                (id, contact_person_name, contact_person_phone, alt_phone, address, city, state) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                contact_person_name = VALUES(contact_person_name), 
                contact_person_phone = VALUES(contact_person_phone), 
                alt_phone = VALUES(alt_phone), 
                address = VALUES(address), 
                city = VALUES(city), 
                state = VALUES(state)
        ");
        $stmt_details->bind_param("issssss", $user_id, $contact_person_name, $contact_person_phone, $alt_phone, $address, $city, $state);
        $stmt_details->execute();
        
        // Explicitly check for commit success
        if (!$conn->commit()) {
             throw new Exception("Database commit failed.");
        }
        
        // Update session name in case it changed
        $_SESSION['name'] = $user_name;
        
        $_SESSION['message'] = "Profile updated successfully!";
        $_SESSION['message_type'] = "info";

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['message'] = "Error updating profile: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }
    header("Location: needy_dashboard.php");
    exit();
}

// ------------------------------------
// Handle Deactivation
// ------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deactivate_account'])) {
    $conn->begin_transaction();
    try {
        $update_user = "UPDATE users SET status = 'inactive' WHERE id = ?";
        $stmt_user = $conn->prepare($update_user);
        $stmt_user->bind_param("i", $user_id);
        $stmt_user->execute();

        $update_details = "UPDATE people_in_need_details SET status = 'disabled' WHERE id = ?";
        $stmt_details = $conn->prepare($update_details);
        $stmt_details->bind_param("i", $user_id);
        $stmt_details->execute();

        if (!$conn->commit()) {
            throw new Exception("Deactivation commit failed.");
        }

        session_unset();
        session_destroy();
        header("Location: login.php?message=account_disabled");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['message'] = "Error deactivating account: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: needy_dashboard.php");
        exit();
    }
}


// ------------------------------------
// Handle new request submission
// ------------------------------------
if (isset($_POST['create_request'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $request_type = $_POST['request_type'];
    $quantity = $_POST['quantity'];
    $unit = $_POST['unit'];
    $location = $_POST['location'];
    $priority = $_POST['urgency'];
    $contact_person_name = $_POST['contact_person_name'];
    $contact_person_phone = $_POST['contact_person_phone'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("INSERT INTO requests 
        (user_id, user_name, user_role, title, description, request_type, quantity, unit,location, priority, contact_person_name, contact_person_phone, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    if ($stmt) {
        $stmt->bind_param(
            "isssssissssss",
            $user_id,
            $user_name, // User name from session
            $user_role,
            $title,
            $description,
            $request_type,
            $quantity,
            $unit,
            $location,
            $priority,
            $contact_person_name, // Contact person for this specific request
            $contact_person_phone,
            $status
        );

        if ($stmt->execute()) {
             $_SESSION['message'] = "Request submitted successfully!";
        } else {
             $_SESSION['message'] = "Error submitting request: " . $stmt->error;
             $_SESSION['message_type'] = "error";
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "Database prepare failed: " . $conn->error;
        $_SESSION['message_type'] = "error";
    }
    header("Location: needy_dashboard.php");
    exit();
}

// ------------------------------------
// Handle request status update
// ------------------------------------
if (isset($_GET['complete_id'])) {
    $request_id = intval($_GET['complete_id']);
    $conn->query("UPDATE requests SET status='completed' WHERE id=$request_id AND user_id=$user_id");
    header("Location: needy_dashboard.php");
    exit();
}

// ------------------------------------
// Handle delete request
// ------------------------------------
if (isset($_GET['delete_id'])) {
    $request_id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM requests WHERE id=$request_id AND user_id=$user_id");
    header("Location: needy_dashboard.php");
    exit();
}

// ------------------------------------
// Fetch user's previous requests
// ------------------------------------
$requests = $conn->query("SELECT * FROM requests WHERE user_id=$user_id ORDER BY created_at DESC");

// ------------------------------------
// Fetch combined user + people_in_need_details data
// ------------------------------------
$query_details = "
    SELECT 
        u.name AS user_name,
        u.email,
        u.phone,
        u.status,
        p.alt_phone,
        p.address,
        p.city,
        p.state,
        p.contact_person_name,
        p.contact_person_phone
    FROM users AS u
    LEFT JOIN people_in_need_details AS p ON u.id = p.id
    WHERE u.id = ?
";

$stmt_details = $conn->prepare($query_details);

if (!$stmt_details) {
    die(" Prepare failed: " . $conn->error);
}

$stmt_details->bind_param("i", $user_id);
$stmt_details->execute();
$result_details = $stmt_details->get_result();

if (!$result_details) {
    die(" Query failed: " . $stmt_details->error);
}

$needy_details = $result_details->fetch_assoc();
$stmt_details->close();

// Update session name from this fresh data
$_SESSION['name'] = $needy_details['user_name'];


// ------------------------------------
// Fetch campaigns along with NGO name
// ------------------------------------
$sql_campaigns = "
    SELECT 
        c.id,
        c.title,
        c.description,
        c.category,
        c.start_date,
        c.end_date,
        c.status,
        c.contact_person_name,
        c.contact_person_phone,
        u.name AS ngo_name
    FROM campaigns AS c
    LEFT JOIN users AS u ON c.ngo_id = u.id
    where c.status = 'active'
    ORDER BY c.created_at DESC
";

$result_campaigns = $conn->query($sql_campaigns);

// Error handling
if (!$result_campaigns) {
    die('Error fetching campaigns: ' . $conn->error);
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Needy Dashboard - Aatman</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <!-- <script src="script.js" defer></script> --> <!-- JS will be embedded -->
    
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
        
        /* Style for action buttons in tables */
        .btn {
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 4px;
            color: white;
            background-color: #3498db;
            display: inline-block;
            margin: 2px;
        }

    </style>
</head>


<body>
    <?php include 'navbar.php'; ?>

    <main>
        <div class="dashboard-nav">
            <button class="active" data-section="profile">Profile</button>
            <button data-section="create-request">Make a Request</button>
            <button data-section="my-requests">My Requests</button>
            <button data-section="campaigns">View Campaigns</button>
        </div>

        <div class="container">
        
            <!-- Session Message Display -->
            <?php if (!empty($message)): ?>
                <div class="alert-message <?= ($message_type == 'error') ? 'alert-error' : 'alert-info'; ?>">
                    <?= htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Profile Section -->
            <section id="profile" class="dashboard-section">
                <h2>My Profile</h2>
                <div class="profile-card">
                    
                    <!-- EDITABLE PROFILE FORM -->
                    <form method="POST" action="needy_dashboard.php" id="editForm">
                        <div class="profile-info">
                            <h3>General Information</h3>
                            <p><strong>Full Name:</strong>
                                <input type="text" name="user_name" value="<?= htmlspecialchars($needy_details['user_name'] ?? '') ?>" disabled>
                            </p>
                            <p><strong>Status:</strong> <?= htmlspecialchars($needy_details['status'] ?? 'active') ?></p>
                        </div>
                        <div class="profile-info">
                            <h3>Contact Details</h3>
                            <p><strong>Email:</strong>
                                <input type="email" name="email" value="<?= htmlspecialchars($needy_details['email'] ?? '') ?>" disabled>
                            </p>
                            <p><strong>Phone:</strong>
                                <input type="text" name="phone" value="<?= htmlspecialchars($needy_details['phone'] ?? '') ?>" disabled>
                            </p>
                            <p><strong>Alternate Phone:</strong>
                                <input type="text" name="alt_phone" value="<?= htmlspecialchars($needy_details['alt_phone'] ?? '') ?>" disabled>
                            </p>
                            <p><strong>Address:</strong>
                                <input type="text" name="address" value="<?= htmlspecialchars($needy_details['address'] ?? '') ?>" disabled>
                            </p>
                            <p><strong>City:</strong>
                                <input type="text" name="city" value="<?= htmlspecialchars($needy_details['city'] ?? '') ?>" disabled>
                            </p>
                            <p><strong>State:</strong>
                                <input type="text" name="state" value="<?= htmlspecialchars($needy_details['state'] ?? '') ?>" disabled>
                            </p>
                        </div>
                        <div class="profile-info">
                             <h3>Emergency Contact</h3>
                            <p><strong>Emergency Contact Name:</strong> 
                                <input type="text" name="contact_person_name" value="<?= htmlspecialchars($needy_details['contact_person_name'] ?? '') ?>" disabled>
                            </p>
                            <p><strong>Emergency Contact Phone:</strong> 
                                <input type="text" name="contact_person_phone" value="<?= htmlspecialchars($needy_details['contact_person_phone'] ?? '') ?>" disabled>
                            </p>
                        </div>

                        <div class="profile-actions">
                            <button type="button" id="editBtn" class="btn-edit">Edit</button>
                            <button type="submit" name="update_profile" id="saveBtn" class="btn-save hidden">Save Changes</button>
                        </div>
                    </form>
                    
                    <!-- DEACTIVATE ACCOUNT FORM -->
                    <div class="profile-card-danger">
                        <h3>Manage Status</h3>
                        <p>Toggle your status to temporarily pause assistance requests.</p>
                        <form method="POST" action="needy_dashboard.php" onsubmit="return confirm('Are you sure you want to deactivate your account?');">
                            <button type="submit" name="deactivate_account" class="btn-disable">Set to Inactive</button>
                        </form>
                    </div>
                </div>
            </section>


            <!-- Create Request -->
            <section id="create-request" class="dashboard-section hidden-section">
                <h2>Make a New Request</h2>
                <p>Fill out the form below to request assistance from NGOs and donors.</p>
                <form action="needy_dashboard.php" method="post" class="request-form">
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
                    <!-- Pre-fill contact info from profile -->
                    <input type="text" name="contact_person_name" placeholder="Contact Person Name" required value="<?= htmlspecialchars($needy_details['user_name'] ?? '') ?>">
                    <input type="tel" name="contact_person_phone" placeholder="Contact Person Phone" required value="<?= htmlspecialchars($needy_details['phone'] ?? '') ?>">
                    <input type="hidden" name="status" value="pending">
                    
                    <button type="submit" name="create_request">Submit Request</button>
                </form>
            </section>


            <!-- My Requests -->
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
                                        <a href="?complete_id=<?= $req['id'] ?>" class="btn" onclick="return confirm('Are you sure you want to mark this as completed?');">Mark as Completed</a>
                                    <?php } ?>
                                    <a href="?delete_id=<?= $req['id'] ?>" class="btn" style="background-color:red;" onclick="return confirm('Are you sure you want to delete this request?');">Delete</a>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php else: ?>
                         <tr><td colspan="7">You have not made any requests yet.</td></tr>
                    <?php endif; ?>
                </table>
            </section>
       
                <!--CAMPAGINS-->
      <section id="campaigns" class="dashboard-section hidden-section">
        <h2>NGO Campaigns</h2>
        <p>Browse campaigns organized by NGOs and sign up to help.</p>

        <table class="dashboard-table" border="1" cellpadding="10" cellspacing="0">
            <thead>
                <tr>
                    <th>Campaign Name</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Contact Person</th>
                    <th>Contact Phone</th>
                    <th>NGO</th>
                
                </tr>
            </thead>
            <tbody>
                <?php if ($result_campaigns && $result_campaigns->num_rows > 0): ?>
                    <?php while ($row = $result_campaigns->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['title']) ?></td>
                            <td><?= htmlspecialchars($row['description']) ?></td>
                            <td><?= htmlspecialchars($row['category']) ?></td>
                            <td><?= htmlspecialchars($row['start_date']) ?></td>
                            <td><?= htmlspecialchars($row['end_date']) ?></td>
                            <td><?= htmlspecialchars($row['contact_person_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($row['contact_person_phone'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($row['ngo_name'] ?? 'Unknown NGO') ?></td>
                        
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8">No active campaigns found.</td></tr>
                <?php endif; ?>
            </tbody>
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

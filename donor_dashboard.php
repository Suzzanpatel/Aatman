<?php
session_start();
include 'config.php'; // this file must define $conn as your mysqli connection

// Simulate login (for testing)
// $_SESSION['donor_logged_in'] = true;
// $_SESSION['user_id'] = 9; // Assuming user 9 exists from your SQL file for testing

if (!isset($_SESSION['donor_logged_in']) || $_SESSION['donor_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Make sure $_SESSION['user_id'] is set
if (!isset($_SESSION['user_id'])) {
    // Redirect to login if user_id is not in session
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// --- Display session messages ---
$message = '';
$message_type = 'info'; // 'info' or 'error'
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'] ?? 'info';
    unset($_SESSION['message']); // Clear it after reading
    unset($_SESSION['message_type']);
}

// --- Handle profile update ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    // Get data from POST request
    $name = trim($_POST['name']);
    $donation_type = trim($_POST['donation_type']);
    $preferred_category = trim($_POST['preferred_category']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);

    // Start a transaction
    $conn->begin_transaction();

    try {
        // 1. Update the 'users' table
        $update_users_query = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
        $stmt_users = $conn->prepare($update_users_query);
        $stmt_users->bind_param("sssi", $name, $email, $phone, $user_id);
        $stmt_users->execute();

        // 2. Update/Insert the 'donor_details' table
        // Use INSERT ... ON DUPLICATE KEY UPDATE to handle cases where
        // a record in donor_details might not exist yet.
        $details_query = "
            INSERT INTO donor_details (id, donation_type, preferred_category, address, city) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                donation_type = VALUES(donation_type), 
                preferred_category = VALUES(preferred_category), 
                address = VALUES(address), 
                city = VALUES(city)
        ";
        $stmt_details = $conn->prepare($details_query);
        $stmt_details->bind_param("issss", $user_id, $donation_type, $preferred_category, $address, $city);
        $stmt_details->execute();

        // If both queries are successful, commit the transaction
        $conn->commit();
        $_SESSION['message'] = "Profile updated successfully!";
        $_SESSION['message_type'] = "info";
        header("Location: donor_dashboard.php");
        exit();

    } catch (mysqli_sql_exception $exception) {
        // If any query fails, roll back the transaction
        $conn->rollback();
        // Provide a more detailed error message for debugging
        $_SESSION['message'] = "Error updating profile. Database error: " . $exception->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: donor_dashboard.php");
        exit();
    }
}

// --- Handle account disable ---
if (isset($_POST['disable_account'])) {
    
    // Start a transaction
    $conn->begin_transaction();
    
    try {
        // 1. Set user status to 'inactive' in 'users' table
        $disable_user_query = "UPDATE users SET status = 'inactive' WHERE id = ?";
        $stmt_user_disable = $conn->prepare($disable_user_query);
        $stmt_user_disable->bind_param("i", $user_id);
        $stmt_user_disable->execute();

        // 2. Set user status to 'disabled' in 'donor_details' table
        $disable_donor_query = "UPDATE donor_details SET status = 'disabled' WHERE id = ?";
        $stmt_donor_disable = $conn->prepare($disable_donor_query);
        $stmt_donor_disable->bind_param("i", $user_id);
        $stmt_donor_disable->execute(); 

        // If both are successful, commit
        $conn->commit();

        session_destroy();
        // Redirect to login page with a message
        header("Location: login.php?message=account_disabled");
        exit();

    } catch (mysqli_sql_exception $exception) {
        // If any query fails, roll back
        $conn->rollback();
        $_SESSION['message'] = "Error disabling account: " . $exception->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: donor_dashboard.php");
        exit();
    }
}


// --- Fetch donor details ---
$query = "
    SELECT 
        u.name, u.email, u.phone,
        d.address, d.city, d.donation_type, d.preferred_category
    FROM users u
    LEFT JOIN donor_details d ON u.id = d.id
    WHERE u.id = ?
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$donor = $result->fetch_assoc();

if (!$donor) {
    // This case should ideally not happen if the user is logged in
}

// Define options for dropdowns based on your SQL schema
$donation_types = ['Money', 'Goods', 'Services', 'Other'];
$categories = ['Education', 'Health', 'Environment', 'Women', 'Other'];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Dashboard - Aatman</title>
    <!-- Google Fonts: Poppins for body, Playfair Display for headings -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <!-- Assuming style.css exists and contains .hidden { display: none; } -->
    <link rel="stylesheet" href="style.css">
    
    <!-- We will add script.js content here -->
    <style>
        /* This is often in style.css, adding it here just in case */
        .hidden {
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

        /* Style for select dropdowns to match inputs */
        select[disabled] {
            background-color: #eee;
            color: #777;
        }
        select {
            width: 100%; /* Or match your input styling */
            padding: 8px; /* Or match your input styling */
            border: 1px solid #ccc; /* Or match your input styling */
            border-radius: 4px; /* Or match your input styling */
        }

        /* Danger zone for disable button */
        .profile-card-danger {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ebccd1;
            background-color: #f2dede;
            border-radius: 4px;
            text-align: right;
        }
        .profile-actions {
            display: flex;
            justify-content: flex-start;
            gap: 10px;
        }
    </style>
</head>

<body>

    <!-- Header -->
  <?php include 'navbar.php'; ?>
    <main>
        <div class="dashboard-nav">
            <button class="active" data-section="profile">Profile</button>
            <button data-section="requests">Requests</button>
            <button data-section="completed-drives">Campaigns</button>
        </div>

        <div class="container">
            <!-- Dashboard Sections -->
            <section id="profile" class="dashboard-section">
        <h2>Donor Profile</h2>

        <?php if (!empty($message)): ?>
            <!-- Display session message here -->
            <div class="alert-message <?= ($message_type == 'error') ? 'alert-error' : 'alert-info'; ?>">
                <?= htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($donor): ?>
            <div class="profile-card">
                <!-- ============================================= -->
                <!-- FORM 1: EDIT PROFILE (THIS IS THE MAIN FIX) -->
                <!-- ============================================= -->
                <form method="POST" action="donor_dashboard.php" id="editForm">
                    <div class="profile-info">
                        <h3>General Information</h3>
                        <p><strong>Full Name:</strong>
                            <input type="text" name="name" value="<?= htmlspecialchars($donor['name'] ?? ''); ?>" disabled>
                        </p>
                        <p><strong>Donation Type:</strong> <!-- RENAMED from Status -->
                            <!-- UI/UX IMPROVEMENT: Changed to select -->
                            <select name="donation_type" disabled>
                                <?php foreach ($donation_types as $type): ?>
                                    <option value="<?= $type ?>" <?= ($donor['donation_type'] ?? '') == $type ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($type) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                        <p><strong>Preferred Category:</strong>
                            <!-- UI/UX IMPROVEMENT: Changed to select -->
                            <select name="preferred_category" disabled>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category ?>" <?= ($donor['preferred_category'] ?? '') == $category ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                    </div>

                    <div class="profile-info">
                        <h3>Contact Details</h3>
                        <p><strong>Email:</strong>
                            <input type="email" name="email" value="<?= htmlspecialchars($donor['email'] ?? ''); ?>" disabled>
                        </p>
                        <p><strong>Phone:</strong>
                            <input type="text" name="phone" value="<?= htmlspecialchars($donor['phone'] ?? ''); ?>" disabled>
                        </p>
                        <p><strong>Address:</strong>
                            <input type="text" name="address" value="<?= htmlspecialchars($donor['address'] ?? ''); ?>" disabled>
                        </p>
                        <p><strong>City/State:</strong>
                            <input type="text" name="city" value="<?= htmlspecialchars($donor['city'] ?? ''); ?>" disabled>
                        </p>
                    </div>

                    <div class="profile-actions">
                        <button type="button" id="editBtn" class="btn-edit">Edit</button>
                        <button type="submit" name="update_profile" id="saveBtn" class="btn-save hidden">Save Changes</button>
                    </div>
                </form>
                
                <!-- ==================================================== -->
                <!-- FORM 2: DISABLE ACCOUNT (MOVED OUTSIDE MAIN FORM) -->
                <!-- ==================================================== -->
                <div class="profile-card-danger">
                    <form method="POST" action="donor_dashboard.php">
                        <button type="submit" name="disable_account" class="btn-disable">Disable Account</button>
                    </form>
                </div>

            </div>
        <?php else: ?>
            <p>No donor profile found. Please contact support.</p>
        <?php endif; ?>
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
                // ✅ Corrected SQL JOIN + Added priority
                $sql = "
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
                    ORDER BY r.created_at DESC
                ";

                $res = $conn->query($sql);

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
                    echo "<tr><td colspan='10'>No requests found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</section>

                    <!-- previous campaigns -->
            <section id="completed-drives" class="dashboard-section hidden-section">
                  <h2><i class="fas fa-clipboard-list"></i> Previous Campaigns</h2>
                <p>Browse campaigns organized by NGOs and manage their status.</p>

                <table class="dashboard-table" >
                    <thead>
                        <tr>
                            <th>Campaign Name</th>
                            <th>Description</th>
                            <th>Category</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>NGO</th>
                            <th>Contact Person Name</th>
                            <th>Contact Person Number</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        //  Fetch campaigns with NGO name and contact details
                        $sql = "
                SELECT 
                    c.id,
                    c.title,
                    c.description,
                    c.category,
                    c.start_date,
                    c.end_date,
                    u.name AS ngo_name,
                    c.contact_person_name,
                    c.contact_person_phone
                FROM campaigns AS c
                LEFT JOIN users AS u ON c.id = u.id
                ORDER BY c.created_at DESC
            ";

                        $result = $conn->query($sql);

                        if (!$result) {
                            echo "<tr><td colspan='9'>Error fetching campaigns: " . htmlspecialchars($conn->error) . "</td></tr>";
                        } elseif ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['title']) ?></td>
                                    <td><?= htmlspecialchars($row['description']) ?></td>
                                    <td><?= htmlspecialchars($row['category']) ?></td>
                                    <td><?= htmlspecialchars($row['start_date']) ?></td>
                                    <td><?= htmlspecialchars($row['end_date']) ?></td>
                                    <td><?= htmlspecialchars($row['ngo_name'] ?? 'Unknown NGO') ?></td>
                                    <td><?= htmlspecialchars($row['contact_person_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($row['contact_person_phone'] ?? 'N/A') ?></td> 
                                </tr>
                                <?php
                            }
                        } else {
                            echo "<tr><td colspan='9'>No campaigns found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </section>
   
           

   

    </main>

   
    <!-- ADDED SCRIPT FOR EDIT BUTTON AND TABS -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            
            // --- Profile Edit Functionality ---
            const editBtn = document.getElementById('editBtn');
            const saveBtn = document.getElementById('saveBtn');
            const editForm = document.getElementById('editForm');
            
            if (editBtn && saveBtn && editForm) {
                // Select all inputs AND selects within the form
                const formElements = editForm.querySelectorAll('input, select');

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
            // (Assuming 'profile' is active by default)
            document.getElementById('profile').classList.remove('hidden-section');
            document.getElementById('requests').classList.add('hidden-section');
            document.getElementById('completed-drives').classList.add('hidden-section');

        });
    </script>
</body>

</html>


<?php
session_start();
require 'config.php'; // Your database connection file

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $conn->prepare("
    SELECT u.id, u.name, u.email, u.phone, u.status AS user_status,
           v.availability, v.preferred_category, v.skills, v.status AS volunteer_status,
           v.emergency_contact_name, v.emergency_contact_phone, v.address, v.city, v.state
    FROM users u
    LEFT JOIN volunteer_details v ON u.id = v.id
    WHERE u.id = ?
");
$sql = "SELECT c.*, u.name AS ngo_name 
        FROM campaigns c
        LEFT JOIN users u ON c.ngo_id = u.id
        WHERE c.status != 'disabled'
        ORDER BY c.created_at DESC";

$result = $conn->query($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Get form values safely
    $availability = $_POST['availability'] ?? '';
    $preferred_category = $_POST['preferred_category'] ?? '';
    $preferred_category_other = $_POST['preferred_category_other'] ?? '';
    $skills = $_POST['skills'] ?? '';
    $status = $_POST['status'] ?? 'active';
    $emergency_contact_name = $_POST['contact_person_name'] ?? '';
    $emergency_contact_phone = $_POST['emergency_contact_phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $state = $_POST['state'] ?? '';

    // If volunteer_details exists, update; else, insert
    if ($user['id']) {
        $stmt = $conn->prepare("
            INSERT INTO volunteer_details 
            (id, availability, preferred_category, skills, status, emergency_contact_name, emergency_contact_phone, address, city, state)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            availability = VALUES(availability),
            preferred_category = VALUES(preferred_category),
            skills = VALUES(skills),
            status = VALUES(status),
            emergency_contact_name = VALUES(emergency_contact_name),
            emergency_contact_phone = VALUES(emergency_contact_phone),
            address = VALUES(address),
            city = VALUES(city),
            state = VALUES(state)
        ");
        $stmt->bind_param(
            'isssssssss',
            $user_id, $availability, $preferred_category, $skills, $status,
            $emergency_contact_name, $emergency_contact_phone, $address, $city, $state
        );
        $stmt->execute();
        $stmt->close();
    }

    // Reload page data
    header("Location: volunteer_dashboard.php");
    exit;
    
}
// -----------------------------
// Fetch campaigns with NGO name
// -----------------------------

// Assuming you have an NGOs table named `ngos` with columns `id` and `name`
$sql = "
    SELECT 
        c.id,
        c.title,
        c.description,
        c.category,
        c.contact_person_name,
        c.contact_person_phone,
        c.start_date,
        c.end_date,
        c.status,
      
        n.name AS ngo_name
    FROM campaigns AS c
    LEFT JOIN users AS n ON c.ngo_id = n.id
    WHERE c.status = 'active'
    ORDER BY c.created_at DESC
";

$result = $conn->query($sql);

if (!$result) {
    die("Error fetching campaigns: " . $conn->error);
}
// -----------------------------
// Fetch needy requests
// -----------------------------
$sql = "
    SELECT 
        id,
        user_id,
        user_name,
        user_role,
        title,
        request_type,
        description,
        priority,
        quantity,
        unit,
        location,
        contact_person_name,
        contact_person_phone,
        status,
        created_at
    FROM requests
    WHERE status = 'pending'
    ORDER BY created_at DESC
";

$result_requests = $conn->query($sql);

if (!$result_requests) {
    die("Error fetching requests: " . $conn->error);
}

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Dashboard - Aatman</title>
    <!-- Google Fonts: Poppins for body, Playfair Display for headings -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
   <?php include 'navbar.php'; ?>
    <main>
        <div class="dashboard-nav">
            <button class="active" data-section="profile">Profile</button>
            <button data-section="campaigns">NGO Campaigns</button>
            <button data-section="needy-requests">Needy Requests</button>
        </div>


 <!-- Dashboard Sections -->
          <section id="profile" class="dashboard-section">
    <h2>My Profile</h2>
    <?php if ($user): ?>
        <div class="profile-card">
            <div class="profile-info">
                <h3>General Information</h3>
                <p><strong>Full Name:</strong> <?= htmlspecialchars($user['name'] ?? 'N/A') ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($user['email'] ?? 'N/A') ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone'] ?? 'N/A') ?></p>
                <p><strong>Availability:</strong> <?= htmlspecialchars(ucfirst($user['availability'] ?? 'N/A')) ?></p>
                <p><strong>Emergency Contact Name:</strong> <?= htmlspecialchars($user['emergency_contact_name'] ?? 'N/A') ?></p>
                <p><strong>Emergency Phone:</strong> <?= htmlspecialchars($user['emergency_contact_phone'] ?? 'N/A') ?></p>
                <p><strong>Address:</strong> <?= htmlspecialchars($user['address'] ?? 'N/A') ?></p>
                <p><strong>City/State:</strong> <?= htmlspecialchars($user['city'] ?? 'N/A') ?></p>
            </div>
        </div>
    <?php else: ?>
        <p>No profile details found.</p>
    <?php endif; ?>

      <div class="profile-info">
    <h3>Update My Details</h3>
    <form action="volunteer_dashboard.php" method="post" class="profile-form">
        
        <!-- Availability -->
        <div class="form-group">
            <label for="availability">Availability</label>
            <input type="text" name="availability" id="availability" placeholder="Enter your availability" required 
                value="<?= htmlspecialchars($volunteer['availability'] ?? '') ?>">
        </div>

        <!-- Preferred Category -->
        <div class="form-group">
            <label for="preferred_category">Preferred Category</label>
            <select name="preferred_category" id="preferred_category" onchange="showOtherCategory()" required>
                <option value="Any" <?= ($volunteer['preferred_category'] ?? '') == 'Any' ? 'selected' : '' ?>>Any</option>
                <option value="Education" <?= ($volunteer['preferred_category'] ?? '') == 'Education' ? 'selected' : '' ?>>Education</option>
                <option value="Health" <?= ($volunteer['preferred_category'] ?? '') == 'Health' ? 'selected' : '' ?>>Health</option>
                <option value="Environment" <?= ($volunteer['preferred_category'] ?? '') == 'Environment' ? 'selected' : '' ?>>Environment</option>
                <option value="Women" <?= ($volunteer['preferred_category'] ?? '') == 'Women' ? 'selected' : '' ?>>Women</option>
                <option value="Other" <?= ($volunteer['preferred_category'] ?? '') == 'Other' ? 'selected' : '' ?>>Other</option>
            </select>
            <input type="text" name="preferred_category_other" id="preferred_category_other" 
                   placeholder="Enter your category" style="display:none;" 
                   value="<?= htmlspecialchars($volunteer['preferred_category_other'] ?? '') ?>">
        </div>

        <!-- Skills -->
        <div class="form-group">
            <label for="skills">Skills (e.g., medical knowledge, teaching, technical support)</label>
            <textarea name="skills" id="skills" rows="3" placeholder="Enter your skills here."><?= htmlspecialchars($volunteer['skills'] ?? '') ?></textarea>
        </div>

        <!-- Status -->
        <div class="form-group">
            <label for="status">Status</label>
            <select name="status" id="status" required>
                <option value="active" <?= ($volunteer['status'] ?? '') == 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= ($volunteer['status'] ?? '') == 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>

        <!-- Contact Person Name -->
        <div class="form-group">
            <label for="contact_person_name">Contact Person Name</label>
            <input type="text" name="contact_person_name" id="contact_person_name" 
                   value="<?= htmlspecialchars($volunteer['contact_person_name'] ?? '') ?>">
        </div>

        <!-- Contact Person Number -->
        <div class="form-group">
            <label for="emergency_contact_phone">Emergency Contact Number</label>
            <input type="text" name="emergency_contact_phone" id="emergency_contact_phone" 
                   value="<?= htmlspecialchars($volunteer['emergency_contact_phone'] ?? '') ?>">
        </div>

        <!-- Address -->
        <div class="form-group">
            <label for="address">Address</label>
            <input type="text" name="address" id="address" value="<?= htmlspecialchars($volunteer['address'] ?? '') ?>">
        </div>

        <!-- City -->
        <div class="form-group">
            <label for="city">City</label>
            <input type="text" name="city" id="city" value="<?= htmlspecialchars($volunteer['city'] ?? '') ?>">
        </div>

        <!-- State -->
        <div class="form-group">
            <label for="state">State</label>
            <input type="text" name="state" id="state" value="<?= htmlspecialchars($volunteer['state'] ?? '') ?>">
        </div>

        <button type="submit" name="update_profile">Update Profile</button>
    </form>
</div>

</section> 

<!-- Campaigns Section -->
  <section id="campaigns" class="dashboard-section hidden-section">
    <h2>NGO Campaigns</h2>
    <p>Browse campaigns organized by NGOs and sign up to help.</p>

    <table class="dashboard-table">
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
            // ✅ Correct JOIN with NGO users
            $sql = "
                SELECT 
                    c.title,
                    c.description,
                    c.category,
                    c.start_date,
                    c.end_date,
                    n.name AS ngo_name,
                    c.contact_person_name,
                    c.contact_person_phone
                FROM campaigns AS c 
                LEFT JOIN users AS n ON c.ngo_id = n.id
                WHERE c.status = 'active'
                ORDER BY c.created_at DESC
            ";

            $result = $conn->query($sql);

            if (!$result) {
                echo "<tr><td colspan='8'>Error fetching campaigns: " . htmlspecialchars($conn->error) . "</td></tr>";
            } elseif ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['category']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['start_date']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['end_date']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['ngo_name'] ?? 'Unknown NGO') . "</td>";
                    echo "<td>" . htmlspecialchars($row['contact_person_name'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($row['contact_person_phone'] ?? 'N/A') . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='8'>No campaigns found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</section>



  <section id="needy-requests" class="dashboard-section hidden-section">
    <h2>Requests from Needy</h2>
    <p>Here are the latest requests from individuals and families in need of assistance.</p>

    <div class="table-container">
        <table class="dashboard-table" >
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




   
    <!-- Custom Script for Dashboard Sections -->
    <script>
        const navButtons = document.querySelectorAll('.dashboard-nav button');
        const sections = document.querySelectorAll('.dashboard-section');

        navButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Remove 'active' from all buttons
                navButtons.forEach(btn => btn.classList.remove('active'));
                
                // Add 'active' to the clicked button
                button.classList.add('active');

                // Hide all sections
                sections.forEach(section => section.classList.add('hidden-section'));

                // Show the corresponding section
                const sectionId = button.getAttribute('data-section');
                document.getElementById(sectionId).classList.remove('hidden-section');
            });
        });
    </script>
</body>
</html>

<?php
session_start();
include 'config.php'; // this file must define $conn as your mysqli connection      
//Simulate admin login (for testing)
$_SESSION['admin_logged_in'] = true;        
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
exit();
}

if (!isset($_SESSION['user_id'])) {
    // Redirect to login if user_id is not in session
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Aatman</title>
    <!-- Google Fonts: Poppins for body, Playfair Display for headings -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="script.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">


</head>

<body>

    <!-- Header -->
    <?php include 'navbar.php'; ?>
    <main class="container">
        <div class="dashboard-nav">
            <button class="active" data-section="donors">Donors</button>
            <button class="active" data-section="ngos">NGO's</button>
            <button class="active" data-section="volunteers">Volunteers</button>
            <button class="active" data-section="needy">Needy</button>
            <button data-section="requests">View Requests</button>
            <button data-section="completed-drives">Previous Campaigns</button>


        </div>

        <div class="container">
            <?php if (!empty($message)) { ?>
                <div class="message <?php echo $message_class; ?>">
                    <?php echo $message; ?>
                </div>
            <?php } ?>


            <!-- Donors Section -->
            <section id="donors" class="dashboard-section">
                <h2>Donors</h2>
                <p>View details of all donors registered on Aatman.</p>

                <table class="dashboard-table" border="1" cellpadding="10" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Donation Type</th>
                            <th>Preferred Category</th>
                            <th>Address</th>
                            <th>City</th>
                            <th>State</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "
                SELECT 
                    u.name, 
                    u.email, 
                    u.phone, 
                    u.status, 
                    u.created_at,
                    d.address,
                    d.city,
                    d.state,
                    d.donation_type,
                    d.preferred_category
                FROM users AS u
                LEFT JOIN donor_details AS d ON u.id = d.id
                WHERE u.role = 'donor'
                ORDER BY u.created_at DESC
            ";

                        $donors_result = $conn->query($query);

                        if (!$donors_result) {
                            echo '<tr><td colspan="10">Error fetching donors: ' . htmlspecialchars($conn->error) . '</td></tr>';
                        } elseif ($donors_result->num_rows > 0) {
                            while ($donor = $donors_result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($donor['name'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($donor['email'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($donor['phone'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars(ucfirst($donor['status'] ?? 'N/A')) . "</td>";
                                echo "<td>" . htmlspecialchars($donor['donation_type'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($donor['preferred_category'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($donor['address'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($donor['city'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($donor['state'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($donor['created_at'] ?? 'N/A') . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo '<tr><td colspan="10">No donors found.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </section>
            <!-- NGOs Section -->
            <section id="ngos" class="dashboard-section">
                <h2>Registered NGOs</h2>
                <p>Explore NGOs that are part of the Aatman community.</p>

                <table class="dashboard-table" border="1" cellpadding="10" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Organisation Name</th>
                            <th>Registration Number</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>City</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "
                SELECT 
                    u.id,
                    u.email,
                    u.phone,
                    u.status,
                    u.created_at,
                    n.organisation_name,
                    n.registration_number,
                    n.address,
                    n.city,
                    n.category,
                    n.description
                FROM users AS u
                LEFT JOIN NGO_details AS n ON u.id = n.id
                WHERE u.role = 'ngo'
                ORDER BY u.created_at DESC
            ";

                        $ngo_result = $conn->query($query);

                        if (!$ngo_result) {
                            echo '<tr><td colspan="11">Error fetching NGOs: ' . htmlspecialchars($conn->error) . '</td></tr>';
                        } elseif ($ngo_result->num_rows > 0) {
                            while ($ngo = $ngo_result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($ngo['id'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($ngo['organisation_name'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($ngo['registration_number'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($ngo['email'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($ngo['phone'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($ngo['address'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($ngo['city'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($ngo['category'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($ngo['description'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars(ucfirst($ngo['status'] ?? 'N/A')) . "</td>";
                                echo "<td>" . htmlspecialchars($ngo['created_at'] ?? 'N/A') . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo '<tr><td colspan="11">No NGOs found.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </section>

                 
            <section id="volunteers" class="dashboard-section hidden-section">
                <h2>Volunteers</h2>
                <?php
                include 'config.php';
                $query = "
        SELECT 
            u.id,
            u.name,
            u.email,
            u.phone,
            u.created_at,
            u.status,
            v.alt_phone,
            v.emergency_contact_name,
            v.emergency_contact_phone,
            v.address,
            v.city,
            v.state,
            v.skills,
            v.availability,
            v.preferred_category
        FROM users AS u
        LEFT JOIN volunteer_details AS v ON v.id = u.id
        WHERE u.role = 'volunteer'
        ORDER BY u.created_at DESC
    ";

                $result_volunteers = $conn->query($query);
                ?>

                <?php if ($result_volunteers && $result_volunteers->num_rows > 0): ?>
                    <div class="table-container">
                        <table class="dashboard-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Alt Phone</th>
                                    <th>Emergency Contact Name</th>
                                    <th>Emergency Contact Phone</th>
                                    <th>Address</th>
                                    <th>City</th>
                                    <th>State</th>
                                    <th>Skills</th>
                                    <th>Availability</th>
                                    <th>Preferred Category</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result_volunteers->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['id']) ?></td>
                                        <td><?= htmlspecialchars($row['name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($row['email']) ?></td>
                                        <td><?= htmlspecialchars($row['phone']) ?></td>
                                        <td><?= htmlspecialchars($row['alt_phone'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($row['emergency_contact_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($row['emergency_contact_phone'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($row['address'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($row['city'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($row['state'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($row['skills'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($row['availability'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($row['preferred_category'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($row['status'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($row['created_at']) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No volunteers found.</p>
                <?php endif; ?>
            </section>
            <section id="needy" class="dashboard-section hidden-section">
                <h2>People in Need</h2>
                <?php
                include 'config.php';

                // Correct JOIN condition + proper fetch
                $query = "
        SELECT 
            u.id,
            u.name,
            u.email,
            u.phone,
            u.created_at,
            u.status AS user_status,
            p.contact_person_name,
            p.contact_person_phone,
            p.alt_phone,
            p.address,
            p.city,
            p.state,
            p.status AS need_status
        FROM users AS u
        LEFT JOIN people_in_need_details AS p ON p.id = u.id
        WHERE u.role = 'need'
        ORDER BY u.created_at DESC
    ";

                $result_needy = $conn->query($query);
                ?>

                <?php if ($result_needy && $result_needy->num_rows > 0): ?>
                    <div class="table-container">
                        <table class="dashboard-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Alt Phone</th>
                                    <th>Contact Person Name</th>
                                    <th>Contact Person Phone</th>
                                    <th>Address</th>
                                    <th>City</th>
                                    <th>State</th>
                                    <th>Status (User)</th>
                                    <th>Status (Need)</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result_needy->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['id']) ?></td>
                                        <td><?= htmlspecialchars($row['name'] ?? 'N/A') ?></td> <!-- ✅ Corrected -->
                                        <td><?= htmlspecialchars($row['email']) ?></td>
                                        <td><?= htmlspecialchars($row['phone']) ?></td>
                                        <td><?= htmlspecialchars($row['alt_phone'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($row['contact_person_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($row['contact_person_phone'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($row['address'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($row['city'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($row['state'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($row['user_status'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($row['need_status'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($row['created_at']) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No needy individuals found.</p>
                <?php endif; ?>
            </section>



            <!-- Requests Section -->
            <section id="requests" class="dashboard-section hidden-section">
                <h2><i class="fas fa-hands-helping"></i> Requests from Needy</h2>
                <p>Here are the latest requests from individuals and families in need of assistance.</p>
                <div class="dashboard-table-wrapper">
                    <table class="dashboard-table">
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch requests
                            $sql = "SELECT title, description, location, contact_person_name, contact_person_phone, request_type, quantity, unit FROM requests  WHERE status = 'pending'";
                            $res = $conn->query($sql);

                            if (!$res) {
                                echo "<tr><td colspan='8'>Query failed: " . $conn->error . "</td></tr>";
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
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8'>No requests found.</td></tr>";
                            }
                            ?>
                        </tbody>
                        </tbody>
                    </table>
                </div>
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
                            <th>NGO</th>
                            <th>Contact Person Name</th>
                            <th>Contact Person Number</th>
                            <th>Status</th>
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
                    c.contact_person_phone,
                    c.status
                FROM campaigns AS c
                LEFT JOIN users AS u ON c.ngo_id = u.id
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
                                    <td>
                                        <form method="POST" action="update_campaign_status.php" style="margin:0;">
                                            <input type="hidden" name="campaign_id" value="<?= $row['id'] ?>">
                                            <select name="status" onchange="this.form.submit()">
                                                <option value="active" <?= $row['status'] === 'active' ? 'selected' : '' ?>>Active
                                                </option>
                                                <option value="completed" <?= $row['status'] === 'completed' ? 'selected' : '' ?>>
                                                    Completed</option>
                                            </select>
                                        </form>
                                    </td>
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

</body>

</html>
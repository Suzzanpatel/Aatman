<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campaign_id = intval($_POST['campaign_id']);
    $status = $_POST['status'];

    // ✅ Validate allowed values
    if (!in_array($status, ['active', 'completed'])) {
        die("Invalid status value");
    }

    // ✅ Update campaign status
    $stmt = $conn->prepare("UPDATE campaigns SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $campaign_id);

    if ($stmt->execute()) {
        // Redirect back to same page after update
        header("Location: ngo_dashboard.php");
        exit();
    } else {
        echo "Error updating campaign status: " . htmlspecialchars($conn->error);
    }

    $stmt->close();
}
?>

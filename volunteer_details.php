<?php
include 'config.php';

if (!isset($_GET['user_id'])) {
    die("Invalid access.");
}
$user_id = intval($_GET['user_id']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $alt_phone = trim($_POST['alt_phone']);
    $emergency_name = trim($_POST['emergency_contact_name']);
    $emergency_phone = trim($_POST['emergency_contact_phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $skills = trim($_POST['skills']);
    $availability = trim($_POST['availability']);

    $stmt = $conn->prepare("INSERT INTO volunteer_details 
        (id, alt_phone, emergency_contact_name, emergency_contact_phone, address, city, state, skills, availability)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssss", $user_id, $alt_phone, $emergency_name, $emergency_phone, $address, $city, $state, $skills, $availability);

    if ($stmt->execute()) {
        echo "<script>alert('Volunteer details saved successfully!'); window.location='volunteer_dashboard.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Volunteer Details | Aatman</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="form-container">
    <h2>Volunteer Details</h2>
    <form action="" method="POST">
        <input type="text" name="alt_phone" placeholder="Alternate Phone Number"><br>
        <input type="text" name="emergency_contact_name" placeholder="Emergency Contact Name"><br>
        <input type="text" name="emergency_contact_phone" placeholder="Emergency Contact Phone Number"><br>
        <input type="text" name="address" placeholder="Address"><br>
        <input type="text" name="city" placeholder="City"><br>
        <input type="text" name="state" placeholder="State"><br>
        <textarea name="skills" placeholder="Skills" rows="3"></textarea><br>
        <input type="text" name="availability" placeholder="Availability"><br>
        <button type="submit">Save Details</button>
    </form>
</div>
</body>
</html>
<style>
    /* ==============================
   AATMAN ROOT PALETTE
============================== */
:root {
    --color-primary: #3d5a49;   /* Deep Earth Green */
    --color-secondary: #7e8c5d; /* Muted Olive */
    --color-accent: #e5b871;    /* Warm Gold */
    --color-text: #2f2f2f;      /* Deep Charcoal */
    --color-light: #f4f4f2;     /* Soft Off-White */
    --color-footer: #2b3a32;    /* Forest Shadow */
    --radius: 14px;
    --transition: all 0.3s ease;
}

/* ==============================
   PAGE LAYOUT
============================== */
body {
    font-family: "Poppins", sans-serif;
    background: linear-gradient(145deg, var(--color-light) 60%, #eae8e3 100%);
    color: var(--color-text);
    margin: 0;
    padding: 0;
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* ==============================
   FORM CONTAINER
============================== */
.form-container {
    background-color: #fff;
    padding: 50px 55px;
    width: 430px;
    border-radius: var(--radius);
    box-shadow: 0 10px 30px rgba(61, 90, 73, 0.15);
    border-top: 6px solid var(--color-accent);
    transition: var(--transition);
    text-align: center;
}

.form-container:hover {
    box-shadow: 0 14px 40px rgba(61, 90, 73, 0.2);
}

/* ==============================
   HEADING
============================== */
.form-container h2 {
    color: var(--color-primary);
    margin-bottom: 22px;
    font-weight: 600;
    letter-spacing: 0.5px;
}

/* ==============================
   INPUT FIELDS & TEXTAREAS
============================== */
input[type="text"],
textarea,
select {
    width: 100%;
    padding: 12px 14px;
    margin: 10px 0;
    border: 1px solid #ddd;
    border-radius: var(--radius);
    background-color: var(--color-light);
    font-size: 14px;
    color: var(--color-text);
    transition: var(--transition);
}

/* Focus Effect */
input:focus,
textarea:focus,
select:focus {
    border-color: var(--color-secondary);
    box-shadow: 0 0 8px rgba(126, 140, 93, 0.3);
    outline: none;
}

/* ==============================
   TEXTAREA
============================== */
textarea {
    resize: none;
}

/* ==============================
   BUTTON
============================== */
button {
    width: 100%;
    padding: 12px;
    margin-top: 12px;
    background-color: var(--color-primary);
    color: #fff;
    border: none;
    border-radius: var(--radius);
    font-size: 15px;
    cursor: pointer;
    transition: var(--transition);
}

/* Button Hover + Active */
button:hover {
    background-color: var(--color-secondary);
    transform: translateY(-2px);
}

button:active {
    transform: translateY(1px);
}

/* ==============================
   RESPONSIVE DESIGN
============================== */
@media (max-width: 480px) {
    .form-container {
        width: 90%;
        padding: 35px 25px;
    }

    button {
        font-size: 14px;
    }
}

</style>
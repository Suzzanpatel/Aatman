<?php
include 'config.php';

if (!isset($_GET['user_id'])) {
    die("Invalid access.");
}
$user_id = intval($_GET['user_id']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $contact_name = trim($_POST['contact_person_name']);
    $contact_phone = trim($_POST['contact_person_phone']);
    $alt_phone = trim($_POST['alt_phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);

    $stmt = $conn->prepare("INSERT INTO people_in_need_details 
        (id, contact_person_name, contact_person_phone, alt_phone, address, city, state)
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $user_id, $contact_name, $contact_phone, $alt_phone, $address, $city, $state);

    if ($stmt->execute()) {
        echo "<script>alert('Details saved successfully!'); window.location='login.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Person in Need Details | Aatman</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="form-container">
    <h2>Person in Need Details</h2>
    <form action="" method="POST">
        <input type="text" name="contact_person_name" placeholder="Contact Person Name" required><br>
        <input type="text" name="contact_person_phone" placeholder="Contact Person Phone Number" required><br>
        <input type="text" name="alt_phone" placeholder="Alternate Phone Number"><br>
        <input type="text" name="address" placeholder="Address"><br>
        <input type="text" name="city" placeholder="City"><br>
        <input type="text" name="state" placeholder="State"><br>
        <button type="submit">Save Details</button>
    </form>
</div>
</body>
</html>
<style>
    
    /* ==============================
   ROOT COLORS — AATMAN PALETTE
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
   GLOBAL PAGE STYLING
============================== */
body {
    font-family: "Poppins", sans-serif;
    background: linear-gradient(135deg, var(--color-light), #eae8e3);
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
    background: #fff;
    padding: 45px 50px;
    width: 400px;
    border-radius: var(--radius);
    box-shadow: 0 10px 30px rgba(61, 90, 73, 0.12);
    text-align: center;
    border-top: 6px solid var(--color-accent);
    transition: var(--transition);
}

.form-container:hover {
    box-shadow: 0 12px 40px rgba(61, 90, 73, 0.18);
}

/* ==============================
   HEADING
============================== */
.form-container h2 {
    color: var(--color-primary);
    margin-bottom: 20px;
    font-weight: 600;
    letter-spacing: 0.5px;
}

/* ==============================
   INPUT FIELDS
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

/* Focus effect for input fields */
input:focus,
textarea:focus,
select:focus {
    border-color: var(--color-secondary);
    box-shadow: 0 0 8px rgba(126, 140, 93, 0.3);
    outline: none;
}

/* ==============================
   BUTTON
============================== */
button {
    width: 100%;
    padding: 12px;
    margin-top: 10px;
    background-color: var(--color-primary);
    color: #fff;
    border: none;
    border-radius: var(--radius);
    font-size: 15px;
    cursor: pointer;
    transition: var(--transition);
}

/* Hover and active states */
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
        padding: 30px 25px;
    }

    button {
        font-size: 14px;
    }
}

</style>
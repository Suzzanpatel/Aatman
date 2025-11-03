<?php
include 'config.php';

if (!isset($_GET['user_id'])) {
    die("Invalid access.");
}
$user_id = intval($_GET['user_id']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $donation_type = $_POST['donation_type'];
    $preferred_category = $_POST['preferred_category'];

    $stmt = $conn->prepare("INSERT INTO donor_details 
        (id, address, city, state, donation_type, preferred_category) 
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $user_id, $address, $city, $state, $donation_type, $preferred_category);

    if ($stmt->execute()) {
        echo "<script>alert('Donor details saved successfully!'); window.location='donor_dashboard.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Donor Details | Aatman</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="form-container">
    <h2>Donor Details</h2>
    <form action="" method="POST">
        <input type="text" name="address" placeholder="Address"><br>
        <input type="text" name="city" placeholder="City"><br>
        <input type="text" name="state" placeholder="State"><br>
        <select name="donation_type" required>
            <option value="">Select Donation Type</option>
            <option value="Money">Money</option>
            <option value="Goods">Goods</option>
            <option value="Services">Services</option>
            <option value="Other">Other</option>
        </select><br>
        <select name="preferred_category">
            <option value="">Select Preferred Category</option>
            <option value="Education">Education</option>
            <option value="Health">Health</option>
            <option value="Environment">Environment</option>
            <option value="Women">Women</option>
            <option value="Other">Other</option>
        </select><br>
        <button type="submit">Save Details</button>
    </form>
</div>
</body>
</html>
<style>
   /* ==============================
   ROOT COLOR VARIABLES
============================== */
:root {
    --color-primary: #3d5a49;   /* Deep Earth Green */
    --color-secondary: #7e8c5d; /* Muted Olive */
    --color-accent: #e5b871;    /* Warm Gold */
    --color-text: #2f2f2f;      /* Dark Gray */
    --color-light: #f4f4f2;     /* Off-White */
    --color-footer: #2b3a32;    /* Darker Green */
    --radius: 14px;
}

/* ==============================
   GLOBAL STYLES
============================== */
body {
    font-family: "Poppins", sans-serif;
    background-color: var(--color-light);
    color: var(--color-text);
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

/* ==============================
   FORM CONTAINER
============================== */
.form-container {
    background: #fff;
    padding: 45px 55px;
    border-radius: var(--radius);
    box-shadow: 0 10px 30px rgba(61, 90, 73, 0.1);
    width: 420px;
    text-align: center;
    border-top: 6px solid var(--color-accent);
    transition: all 0.3s ease;
}

.form-container:hover {
    box-shadow: 0 12px 40px rgba(61, 90, 73, 0.15);
}

/* ==============================
   HEADING
============================== */
.form-container h2 {
    color: var(--color-primary);
    font-weight: 600;
    margin-bottom: 20px;
    letter-spacing: 0.5px;
}

/* ==============================
   INPUTS, TEXTAREA, SELECT
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
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
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
   DROPDOWN
============================== */
select {
    appearance: none;
    background-image: url('data:image/svg+xml;utf8,<svg fill="%237e8c5d" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>');
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 18px;
    cursor: pointer;
}

/* ==============================
   BUTTON
============================== */
button {
    width: 100%;
    padding: 12px;
    background-color: var(--color-primary);
    color: #fff;
    border: none;
    border-radius: var(--radius);
    font-size: 15px;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
    margin-top: 10px;
}

/* Hover & Active States */
button:hover {
    background-color: var(--color-secondary);
    transform: translateY(-2px);
}

button:active {
    transform: translateY(1px);
}



</style>
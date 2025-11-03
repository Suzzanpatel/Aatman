<?php
session_start();
include 'config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$ngo_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = $_POST['category'];
    $contact_person_name = trim($_POST['contact_person_name']);
    $contact_person_phone = trim($_POST['contact_person_phone']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // Insert query (target_amount removed, new columns added)
    $stmt = $conn->prepare("INSERT INTO campaigns 
        (ngo_id, title, description, category, contact_person_name, contact_person_phone, start_date, end_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param("isssssss", 
        $ngo_id, 
        $title, 
        $description, 
        $category, 
        $contact_person_name, 
        $contact_person_phone, 
        $start_date, 
        $end_date
    );

    if ($stmt->execute()) {
        echo "<script>alert('Campaign created successfully!'); window.location='campaign.php';</script>";
    } else {
        echo "Error: " . htmlspecialchars($stmt->error);
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Campaign | Aatman</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="form-container">
    <h2>Create a New Campaign</h2>
    <form action="" method="POST">
        <input type="text" name="title" placeholder="Campaign Title" required><br>

        <textarea name="description" placeholder="Campaign Description" required rows="4"></textarea><br>

        <select name="category" required>
            <option value="">Select Category</option>
            <option value="Education">Education</option>
            <option value="Health">Health</option>
            <option value="Environment">Environment</option>
            <option value="Women">Women</option>
            <option value="Other">Other</option>
        </select><br>

        <input type="text" name="contact_person_name" placeholder="Contact Person Name" required><br>
        <input type="tel" name="contact_person_phone" placeholder="Contact Person Phone" pattern="[0-9]{10}" required><br>

        <label>Start Date:</label>
        <input type="date" name="start_date" required><br>

        <label>End Date:</label>
        <input type="date" name="end_date" required><br>

        <button type="submit">Create Campaign</button>
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
input[type="tel"] {
    width: 100%;
    padding: 14px 16px;
    margin: 10px 0 18px;
    border: 1.5px solid #ddd;          /* pastel pink border */
    border-radius: 12px;
    background-color: #f0f0f0ff;            /* soft warm white with pink undertone */
    font-size: 15px;
    color: #333;
    font-family: 'Poppins', sans-serif;
    transition: all 0.3s ease;
    box-sizing: border-box;
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
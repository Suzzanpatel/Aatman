<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<style>
    /* Reset default styles */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    /* Navbar container */
   

    .nav-container {
        max-width: 1100px;
        margin: auto;
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;
        padding-top: 30px;
    }

    /* Navigation links */
    .nav-links {
        display: flex;
        gap: 40px;
        list-style: none;
        font-size: 18px;
        font-weight: 500;
        align-items: center;
    }

    .nav-links a {
        text-decoration: none;
        color: #000;
        transition: color 0.3s ease;
    }

    .nav-links a:hover {
        color: #555;
    }

    /* 🌿 Google Translate Dropdown Styling (Aatman Theme) */
.translate-box {
    display: inline-block;
    margin-left: 15px;
    background-color: #fff9fb;
    border-radius: 8px;
    padding: 5px 10px;
    border: 1px solid #f2c8d2;
    box-shadow: 0 2px 6px rgba(232, 156, 178, 0.2);
}

.goog-te-gadget {
    font-family: 'Poppins', sans-serif !important;
    color: #3d3d3d !important;
    font-size: 14px !important;
}

.goog-te-combo {
    border-radius: 6px;
    padding: 4px 8px;
    border: 1px solid #eec3cfff;
    background-color: #fff;
    color: #333;
    cursor: pointer;
}

.goog-te-combo:hover {
    border-color: #eab8c8ff;
}

.goog-logo-link,
.goog-te-gadget span {
    display: none !important;
}

.goog-te-banner-frame.skiptranslate {
    display: none !important;
} 
</style>
</head>
<body>

<header class="app-header">
    <div class="container navbar">
        <a href="#" class="logo">
            <i class="fas fa-hand-holding-heart"></i> Aatman
        </a>

        <div class="nav-links">
            <a href="landing.php">Home</a>
     
            <a href="logout.php" class="logout-button">Logout</a>

            <!-- 🌍 Google Translate Dropdown -->
            <div id="google_translate_element" class="translate-box"></div>
        </div>
    </div>
</header>


<script>
function toggleMenu() {
    document.getElementById('navLinks').classList.toggle('show');
}

// Initialize Google Translate (all languages)
function googleTranslateElementInit() {
    new google.translate.TranslateElement({
        pageLanguage: 'en',
        layout: google.translate.TranslateElement.InlineLayout.SIMPLE
    }, 'google_translate_element');
}
</script>

<!-- Google Translate Script -->
<script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

</body>
</html>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aatman - Your Bridge to a Better World</title>
    <!-- Google Fonts: Poppins for body, Playfair Display for headings -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="landing.css">
    <style>
       
    </style>
</head>
<body>

    <!-- Header -->
    <header class="header">
        <div class="container navbar">
            <a href="landing.php" class="logo">Aatman</a>
            <nav class="nav-links">
                <a href="#hero">Home</a>
                <a href="#how-it-works">How It Works</a>
                <a href="#about-us">About Us</a>
                <!-- Link to open the login form -->
                <a href="login.php" class="login-button" id="open-login">Login</a>
                <a href="signup.php" class="login-button" id="open-login">Sign Up</a>
            </nav>
            <div id="google_translate_element"></div>
        </div>
    </header>

    <main>
        <!-- Hero Section -->
        <section id="hero" class="hero">
            <div class="container">
                <h1>Aatman: The Soul of Giving</h1>
                <p>Connecting compassionate hearts with a world in need. Your small act of kindness can create a monumental impact.</p>
                <a href="#" class="cta-button">Donate Now</a>
            </div>
        </section>

        <!-- How It Works Section -->
        <section id="how-it-works" class="how-it-works">
            <div class="container">
                <h2>The Simple Path to Impact</h2>
                <div class="steps">
                    <div class="step">
                        <div class="step-icon">🌱</div>
                        <h3>Explore Needs</h3>
                        <p>Browse through a curated list of verified organizations and their specific needs, from books to food.</p>
                    </div>
                    <div class="step">
                        <div class="step-icon">🤝</div>
                        <h3>Connect & Pledge</h3>
                        <p>Choose the cause that resonates with you. Connect directly with the organization to coordinate your contribution.</p>
                    </div>
                    <div class="step">
                        <div class="step-icon">✨</div>
                        <h3>Create Change</h3>
                        <p>Your donation, whether money, books, or time, is delivered directly to those who need it most.</p>
                    </div>
                </div>
            </div>
        </section>

        
        
        <!-- About Us Section -->
        <section id="about-us" class="about-us">
            <div class="container">
                <div class="about-image-container">
                    <!-- The about us section has been updated to include a placeholder for a relevant image. -->
                    <img src="https://placehold.co/600x400/7e8c5d/2b3a32?text=Helping+Hands" alt="People helping each other" class="about-image">
                </div>
                <div class="about-content">
                    <h2>Our Mission</h2>
                    <p>At Aatman, we believe in the power of a single act of generosity. Our platform is built on the principle of direct, transparent giving, ensuring that your donation reaches its intended recipient without any barriers. We connect you to verified NGOs, schools, and organizations that are making a real difference in the lives of many, focusing on core needs like food, education, and health. We are a bridge, not a barrier, and our purpose is to simplify the process of giving so that more people can experience the profound joy of helping others.</p>
                    
                    <h3>Our Values</h3>
                    <p>We are guided by the principles of **compassion**, **transparency**, and **accountability**. We believe in fostering a community where every donation, no matter the size, is handled with the utmost care and integrity. Our platform is a reflection of our commitment to building a more caring and connected world, one act of kindness at a time.</p>
                </div>
            </div>
        </section>

        <!-- Join Us Section -->
        <section id="join-us" class="join-us">
            <div class="container">
                <h2>Join Our Movement</h2>
                <p>Whether you're an organization in need of support or an individual looking to make a difference, Aatman provides the tools to connect and grow. Together, we can build a better future.</p>
                <div class="join-us-buttons">
                    <a href="signup.php" class="cta-button" id="join-donor">Become a Donor</a>
                    <a href="signup.php" class="cta-button" id="join-org">Register Your Organization</a>
                </div>
            </div>
        </section>
    </main>
   

<!-- Aatman Chatbot -->
<div class="aatman-chat-widget">
  <button id="chat-toggle" class="chat-toggle">💬 Chat with Aatman</button>

  <div class="chat-container hidden" id="chat-container">
    <div class="chat-header">
      <span>🪷 Aatman Assistant</span>
      <button id="chat-close" class="chat-close">✕</button>
    </div>

    <div id="chat-box" class="chat-box"></div>

    <form id="chat-form" class="chat-form">
      <input
        type="text"
        id="chat-input"
        placeholder="Ask Aatman anything..."
        autocomplete="off"
      />
      <button type="submit" class="send-btn">Send</button>
    </form>
  </div>
</div>

<script src="chatbot.js"></script>





    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 Aatman. All Rights Reserved.</p>
            <div class="footer-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Contact Us</a>
            </div>
        </div>
    </footer>

   
           
          
        </div>
    </div>

    <!-- Google Translate Script -->
    <script type="text/javascript">
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'en',
                layout: google.translate.TranslateElement.InlineLayout.SIMPLE
            }, 'google_translate_element');
        }
    </script>
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
   



</body>

</html>

<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$me = current_user();
$page_title = 'Your Bridge to a Better World';
$page_css   = ['assets/css/landing.css', 'assets/css/chat.css'];
require INCLUDES_PATH . '/partials/head.php';
?>
<header class="header">
    <div class="container navbar">
        <a href="<?= e(url('index.php')) ?>" class="logo">Aatman</a>
        <nav class="nav-links">
            <a href="#hero">Home</a>
            <a href="#how-it-works">How It Works</a>
            <a href="#about-us">About Us</a>
            <?php if ($me): ?>
                <a href="<?= e(url(home_for($me['role']))) ?>" class="login-button">My Dashboard</a>
            <?php else: ?>
                <a href="<?= e(url('login.php')) ?>" class="login-button">Login</a>
                <a href="<?= e(url('signup.php')) ?>" class="login-button">Sign Up</a>
            <?php endif; ?>
        </nav>
        <!-- Multilingual support -->
        <div id="google_translate_element"></div>
    </div>
</header>

<main>
    <section id="hero" class="hero">
        <div class="container">
            <h1>Aatman: The Soul of Giving</h1>
            <p>Connecting compassionate hearts with a world in need. Your small act of kindness can create a monumental impact.</p>
            <a href="<?= e(url($me ? home_for($me['role']) : 'signup.php')) ?>" class="cta-button">Donate Now</a>
        </div>
    </section>

    <section id="how-it-works" class="how-it-works">
        <div class="container">
            <h2>The Simple Path to Impact</h2>
            <div class="steps">
                <div class="step">
                    <div class="step-icon">🌱</div>
                    <h3>Explore Needs</h3>
                    <p>Browse requests from people in need and campaigns from registered NGOs, from books to food.</p>
                </div>
                <div class="step">
                    <div class="step-icon">🤝</div>
                    <h3>Connect &amp; Pledge</h3>
                    <p>Choose the cause that resonates with you and contact the organisation directly to coordinate your contribution.</p>
                </div>
                <div class="step">
                    <div class="step-icon">✨</div>
                    <h3>Create Change</h3>
                    <p>Your donation, whether money, goods or time, reaches the people who need it most.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="about-us" class="about-us">
        <div class="container">
            <div class="about-image-container">
                <img src="https://placehold.co/600x400/7e8c5d/2b3a32?text=Helping+Hands" alt="People helping each other" class="about-image">
            </div>
            <div class="about-content">
                <h2>Our Mission</h2>
                <p>At Aatman, we believe in the power of a single act of generosity. Our platform is built on direct, transparent
                    giving, so your help reaches its intended recipient without barriers. We connect donors and volunteers with NGOs
                    and people in need, focusing on core needs like food, education and health. We are a bridge, not a barrier.</p>
                <h3>Our Values</h3>
                <p>We are guided by <strong>compassion</strong>, <strong>transparency</strong> and <strong>accountability</strong>.
                    Every contribution, no matter the size, is handled with care and integrity.</p>
            </div>
        </div>
    </section>

    <section id="join-us" class="join-us">
        <div class="container">
            <h2>Join Our Movement</h2>
            <p>Whether you're an organisation in need of support or an individual looking to make a difference, Aatman gives you
                the tools to connect and grow. Together, we can build a better future.</p>
            <div class="join-us-buttons">
                <a href="<?= e(url('signup.php')) ?>" class="cta-button">Become a Donor</a>
                <a href="<?= e(url('signup.php')) ?>" class="cta-button">Register Your Organisation</a>
            </div>
        </div>
    </section>
</main>

<footer class="footer">
    <div class="container">
        <p>&copy; <?= date('Y') ?> Aatman. All Rights Reserved.</p>
        <div class="footer-links">
            <a href="#">Privacy Policy</a>
            <a href="#">Terms of Service</a>
            <a href="#">Contact Us</a>
        </div>
    </div>
</footer>
<?php require INCLUDES_PATH . '/partials/foot.php';

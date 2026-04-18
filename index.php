<?php 
require_once __DIR__ . '/db_connect_new.php'; // session_start() is inside this file
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Loan System | Home</title>
<link rel="stylesheet" href="index.css">
</head>
<body>

<!-- HEADER -->
<header class="topbar">
    <div class="logo">
        <img src="images/logo1.png" alt="Logo" class="logo-img">
        <span>Loan System</span>
    </div>
    <nav class="nav-links">
        <a href="#">Home</a>
        <a href="login.php" class="btn btn-outline">Login</a>
    </nav>
</header>

<!-- HERO / SLIDES -->
<section class="hero-slideshow">
    <div class="ambient ambient-one"></div>
    <div class="ambient ambient-two"></div>

    <div class="slides-wrapper">
        <!-- SLIDE 1 -->
        <div class="slide active">
            <div class="slide-bg" style="background-image: url('images/background.jpg');"></div>
            <div class="slide-content hero-panel">
                <span class="eyebrow">Secure • Fast • Transparent</span>
                <h1>Make Your Finances <span>Simple & Smart</span></h1>
                <p>Apply for loans, track repayments, and manage your savings in one modern platform.</p>

                <?php if (!$dbConnected && !empty($dbError)): ?>
                    <p class="db-error"><?php echo htmlspecialchars($dbError); ?></p>
                <?php endif; ?>

                <div class="hero-actions">
                    <a href="login.php" class="btn btn-primary">Get Started</a>
                    <a href="#" class="btn btn-secondary">Explore Features</a>
                </div>
            </div>
        </div>

        <!-- SLIDE 2 -->
        <div class="slide">
            <div class="slide-bg" style="background-image: url('images/bg.jpg');"></div>
            <div class="slide-content info-panel">
                <span class="eyebrow">Why People Choose Us</span>
                <h1>Built for Faster and Safer Loan Processing</h1>
                <p>Everything is designed to make approval and monitoring stress-free.</p>
                <div class="cards">
                    <div class="card">
                        <h3>Easy Application</h3>
                        <p>Quick and simple loan request process.</p>
                    </div>
                    <div class="card">
                        <h3>Live Monitoring</h3>
                        <p>Track your loans anytime.</p>
                    </div>
                    <div class="card">
                        <h3>Smart Savings</h3>
                        <p>Manage your finances easily.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- SLIDE 3 -->
        <div class="slide">
            <div class="slide-bg" style="background-image: url('images/loan.jpg');"></div>
            <div class="slide-content info-panel narrow-panel">
                <span class="eyebrow">Simple 3-Step Flow</span>
                <h1>Your Financial Journey Starts Here</h1>
                <div class="cards step-cards">
                    <div class="card step-card">
                        <div class="step-number">01</div>
                        <h3>Create Account</h3>
                        <p>Register your details.</p>
                    </div>
                    <div class="card step-card">
                        <div class="step-number">02</div>
                        <h3>Apply Loan</h3>
                        <p>Choose amount and submit.</p>
                    </div>
                    <div class="card step-card">
                        <div class="step-number">03</div>
                        <h3>Pay Easily</h3>
                        <p>Track and pay anytime.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SLIDE INDICATORS -->
    <div class="slide-indicators">
        <button class="indicator active" data-index="0"></button>
        <button class="indicator" data-index="1"></button>
        <button class="indicator" data-index="2"></button>
    </div>
</section>

<script src="index.js"></script>
</body>
</html>
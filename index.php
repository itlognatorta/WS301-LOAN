<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan System</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>

<!-- NAVBAR -->
<header>
    <div class="logo">
        <img src="images/logo1.png" alt="Logo" class="logo-img">
        Loan System
    </div>
    <nav>
        <a href="#">Home</a>
        <a href="login.php" class="btn">Login</a>
    </nav>
</header>

<!-- HERO SLIDES -->
<section class="hero-slideshow">
    <div class="slides-wrapper">
        <div class="slide">
            <div class="slide-bg" style="background-image: url('images/background.jpg');"></div>
            <div class="slide-content">
                <h1>Make Your Finances Smart & Easy</h1>
                <p>Apply loans, track payments, and manage your savings effortlessly.</p>
                <a class="btn">Get Started</a>
            </div>
        </div>

        <div class="slide">
            <div class="slide-bg" style="background-image: url('images/bg.jpg');"></div>
            <div class="slide-content">
                <h1>About Our Loan System</h1>
                <p>Fast approvals, transparent terms, and flexible repayment options.</p>
                <div class="cards">
                    <div class="card">
                        <h3>Easy Application</h3>
                        <p>Submit your loan request online within minutes.</p>
                    </div>
                    <div class="card">
                        <h3>Track Payments</h3>
                        <p>Monitor your loan status in real-time.</p>
                    </div>
                    <div class="card">
                        <h3>Smart Savings</h3>
                        <p>Plan your finances with personalized recommendations.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="slide">
            <div class="slide-bg" style="background-image: url('images/loan.jpg');"></div>
            <div class="slide-content">
                <h1>Plan Your Financial Future</h1>
                <p>Smart tools to save, invest, and manage your money effectively.</p>
            </div>
        </div>
    </div>
</section>

<!-- SCRIPT -->
<script>
const slides = document.querySelectorAll(".hero-slideshow .slide");
let current = 0;

function showSlide(index) {
    slides.forEach((slide, i) => {
        slide.style.transform = `translateX(${100 * (i - index)}%)`;
    });
}

// Slide every 5 seconds
setInterval(() => {
    current = (current + 1) % slides.length; // loops back to 0
    showSlide(current);
}, 5000);

// Initialize positions
showSlide(current);
</script>

</body>
</html>
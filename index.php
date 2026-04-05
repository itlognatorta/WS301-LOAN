require_once __DIR__ . '/db_connect_new.php';
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan System | Home</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>

<header class="topbar">
    <div class="logo">
        <img src="images/logo1.png" alt="Logo" class="logo-img">
        <span>Loan System</span>
    </div>

    <nav class="nav-links">
        <a href="#">Home</a>
        <a href="#">Loans</a>
        <a href="#">Savings</a>
        <a href="login.php" class="btn btn-outline">Login</a>
    </nav>
</header>

<section class="hero-slideshow">
    <div class="ambient ambient-one"></div>
    <div class="ambient ambient-two"></div>

    <button class="slide-control prev" id="prevSlide" aria-label="Previous slide">&#10094;</button>
    <button class="slide-control next" id="nextSlide" aria-label="Next slide">&#10095;</button>

    <div class="slides-wrapper">
        <div class="slide">
            <div class="slide-bg" style="background-image: url('images/background.jpg');"></div>
            <div class="slide-content hero-panel">
                <span class="eyebrow">Secure • Fast • Transparent</span>
                <h1>Make Your Finances <span>Simple & Smart</span></h1>
                <p>Apply for loans, track repayments, and manage your savings in one modern and easy-to-use platform.</p>

                <div class="db-status <?php echo $dbConnected ? 'success' : 'error'; ?>">
                    <span class="status-dot"></span>
                    <?php echo $dbConnected ? 'Connected to loan_db' : 'Database connection not available yet'; ?>
                </div>

                <div class="stats-row">
                    <div class="stat-box">
                        <strong><?php echo number_format($siteStats['users']); ?></strong>
                        <span>Registered Users</span>
                    </div>
                    <div class="stat-box">
                        <strong><?php echo number_format($siteStats['loans']); ?></strong>
                        <span>Total Loans</span>
                    </div>
                    <div class="stat-box">
                        <strong><?php echo number_format($siteStats['savings_accounts']); ?></strong>
                        <span>Savings Accounts</span>
                    </div>
                </div>

                <?php if (!$dbConnected && !empty($dbError)): ?>
                    <p class="db-error">
                        Update your MySQL/XAMPP settings in <strong>db_connect.php</strong>.
                        <span><?php echo htmlspecialchars($dbError); ?></span>
                    </p>
                <?php endif; ?>

                <div class="hero-actions">
                    <a class="btn btn-primary" href="login.php">Get Started</a>
                    <a class="btn btn-secondary" href="#">Explore Features</a>
                </div>
            </div>
        </div>

        <div class="slide">
            <div class="slide-bg" style="background-image: url('images/bg.jpg');"></div>
            <div class="slide-content info-panel">
                <span class="eyebrow">Why People Choose Us</span>
                <h1>Built for Faster and Safer Loan Processing</h1>
                <p>Everything is designed to make approval, monitoring, and savings management feel clear and stress-free.</p>

                <div class="cards">
                    <div class="card">
                        <h3>Easy Application</h3>
                        <p>Submit requirements quickly with a clean and simple process.</p>
                    </div>
                    <div class="card">
                        <h3>Live Monitoring</h3>
                        <p>Check your account and loan updates anytime you need them.</p>
                    </div>
                    <div class="card">
                        <h3>Smart Savings</h3>
                        <p>Manage your balance and activity records with confidence.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="slide">
            <div class="slide-bg" style="background-image: url('images/loan.jpg');"></div>
            <div class="slide-content info-panel narrow-panel">
                <span class="eyebrow">Simple 3-Step Flow</span>
                <h1>Your Financial Journey Starts Here</h1>
                <p>From application to approval and repayment, the process stays organized and transparent.</p>

                <div class="cards step-cards">
                    <div class="card step-card">
                        <div class="step-number">01</div>
                        <h3>Create an Account</h3>
                        <p>Register your details and upload the required documents.</p>
                    </div>
                    <div class="card step-card">
                        <div class="step-number">02</div>
                        <h3>Apply for a Loan</h3>
                        <p>Select the amount and track your status in real time.</p>
                    </div>
                    <div class="card step-card">
                        <div class="step-number">03</div>
                        <h3>Pay with Confidence</h3>
                        <p>Review balances, transactions, and payment history anytime.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="slide-indicators" aria-label="Slide indicators">
        <button class="indicator active" data-index="0" aria-label="Go to slide 1"></button>
        <button class="indicator" data-index="1" aria-label="Go to slide 2"></button>
        <button class="indicator" data-index="2" aria-label="Go to slide 3"></button>
    </div>

    <div class="hero-footer">
        <div class="footer-pill">24/7 account access</div>
        <div class="footer-pill">Transparent loan monitoring</div>
        <div class="footer-pill">Savings and payment tracking</div>
    </div>
</section>

<script>
const slides = document.querySelectorAll('.hero-slideshow .slide');
const indicators = document.querySelectorAll('.indicator');
const prevSlideBtn = document.getElementById('prevSlide');
const nextSlideBtn = document.getElementById('nextSlide');
let current = 0;
let autoSlide;

function showSlide(index) {
    current = (index + slides.length) % slides.length;

    slides.forEach((slide, i) => {
        slide.style.transform = `translateX(${100 * (i - current)}%)`;
    });

    indicators.forEach((indicator, i) => {
        indicator.classList.toggle('active', i === current);
    });
}

function restartAutoSlide() {
    clearInterval(autoSlide);
    autoSlide = setInterval(() => {
        showSlide(current + 1);
    }, 5000);
}

prevSlideBtn.addEventListener('click', () => {
    showSlide(current - 1);
    restartAutoSlide();
});

nextSlideBtn.addEventListener('click', () => {
    showSlide(current + 1);
    restartAutoSlide();
});

indicators.forEach((indicator) => {
    indicator.addEventListener('click', () => {
        showSlide(Number(indicator.dataset.index));
        restartAutoSlide();
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowLeft') {
        showSlide(current - 1);
        restartAutoSlide();
    }

    if (event.key === 'ArrowRight') {
        showSlide(current + 1);
        restartAutoSlide();
    }
});

showSlide(0);
restartAutoSlide();
</script>

</body>
</html>
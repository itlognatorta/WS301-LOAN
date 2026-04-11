<?php
session_start();
require_once __DIR__ . '/db_connect_new.php';

$emailError = $passwordError = '';
$email = $password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // INPUTS
    $email = htmlspecialchars(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    // VALIDATION
    if (empty($email)) {
        $emailError = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailError = 'Invalid email format.';
    }

    if (empty($password)) {
        $passwordError = 'Password is required.';
    }

    // PROCESS LOGIN
    if (!$emailError && !$passwordError) {

        if (isset($pdo) && $pdo) {

            $stmt = $pdo->prepare("
                SELECT id, email, password_hash, status, account_type 
                FROM users 
                WHERE email = ? 
                LIMIT 1
            ");

            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // CHECK USER EXISTS
            if ($user) {

                // CHECK PASSWORD
                if (!password_verify($password, $user['password_hash'])) {
                    $passwordError = 'Incorrect password.';
                }

                // CHECK STATUS
                elseif (strtolower($user['status']) !== 'active') {
                    $passwordError = 'Account is inactive.';
                }

                // LOGIN SUCCESS
                else {

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['account_type'] = $user['account_type'];

                    // ROLE-BASED REDIRECT
                    if ($user['account_type'] === 'admin') {
                        header('Location: admin/index.php');
                        exit;
                    }

                    if ($user['account_type'] === 'premium') {
                        header('Location: prem_user/dashboard.php');
                        exit;
                    }

                    // DEFAULT: BASIC USER
                    header('Location: basic_user/dashboard.php');
                    exit;
                }

            } else {
                $emailError = 'Email not found.';
            }

        } else {
            $emailError = 'Database connection failed.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>We-Loan | Login</title>
<link rel="stylesheet" href="login.css">
</head>
<body>

<div class="background-image"></div>

<div class="login-wrapper">
    <div class="login-card">

        <button class="close-btn" onclick="window.location.href='index.php'">&times;</button>

        <div class="logo-area">
            <img src="images/logo1.png" alt="Logo" class="logo-img">
        </div>

        <h2 class="login-title">Login to We-Loan</h2>

        <form method="POST" class="login-form" novalidate>

            <!-- EMAIL -->
            <div class="input-group <?php echo $emailError ? 'error' : ''; ?>">
                <input 
                    type="email" 
                    name="email" 
                    placeholder="Email"
                    value="<?php echo htmlspecialchars($email); ?>"
                >
                <?php if ($emailError): ?>
                    <span class="error-text"><?php echo $emailError; ?></span>
                <?php endif; ?>
            </div>

            <!-- PASSWORD -->
            <div class="input-group password-wrapper <?php echo $passwordError ? 'error' : ''; ?>">
                <input 
                    type="password" 
                    name="password" 
                    id="password-field" 
                    placeholder="Password"
                >

                <span class="toggle-password" onclick="togglePassword()">
                    <img id="eye-icon" src="images/hide.png" alt="toggle">
                </span>

                <?php if ($passwordError): ?>
                    <span class="error-text"><?php echo $passwordError; ?></span>
                <?php endif; ?>
            </div>

            <label class="remember">
                <input type="checkbox" name="remember"> Remember me
            </label>

            <button type="submit" class="btn-login">Sign In</button>
        </form>

        <div class="login-links">
            <a href="register.php">New to We-Loan? Register now</a>
            <a href="admin/login.php">Admin Login</a>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const field = document.getElementById('password-field');
    const icon = document.getElementById('eye-icon');

    if (field.type === 'password') {
        field.type = 'text';
        icon.src = 'images/show.png';
    } else {
        field.type = 'password';
        icon.src = 'images/hide.png';
    }
}
</script>

</body>
</html>
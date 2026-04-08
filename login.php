<?php
// SHOW ERRORS (REMOVE IN PRODUCTION)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// START SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_connect_new.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = htmlspecialchars(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username and password are required.';
    } 
    elseif ($dbConnected && $pdo) {

        $stmt = $pdo->prepare("
            SELECT id, username, password_hash, status, account_type
            FROM users
            WHERE username = ? OR email = ?
            LIMIT 1
        ");

        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if (
            $user &&
            password_verify($password, $user['password_hash']) &&
            strtolower($user['status']) === 'active'
        ) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['account_type'] = $user['account_type'];

            if ($user['account_type'] === 'admin') {
                header('Location: admin/index.php');
            } else {
                header('Location: dashboard.php');
            }
            exit;

        } else {
            $error = 'Invalid credentials or inactive account.';
        }

    } else {
        $error = 'Database connection failed.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - We-Loan</title>
<link rel="stylesheet" href="login.css">
</head>
<body>

<div class="login-container">

    <div class="login-card">
        <!-- Close button -->
        <a href="index.php" class="close-btn">&times;</a>

        <h1>Welcome To We-Loan</h1>
        <p>Login to your account</p>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <label>Username or Email</label>
                <input type="text" name="username" required>
            </div>

            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <button class="btn-login" type="submit">Login</button>
        </form>

        <div class="extra-links">
         <a class="text-link" href="register.php">Create Account</a>
         <a class="text-link" href="admin/login.php">Admin Login</a>
        </div>
    </div>

</div>

</body>
</html>
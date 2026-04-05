<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db_connect_new.php';
require_once __DIR__ . '/includes/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username and password are required.';
    } elseif ($dbConnected && $pdo) {
        $stmt = $pdo->prepare("SELECT id, password_hash, status, account_type FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && verify_password($password, $user['password_hash']) && $user['status'] === 'active') {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['account_type'] = $user['account_type'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid credentials or account not active.';
        }
    } else {
        $error = 'Database connection error.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Loan System</title>
    <link rel="stylesheet" href="index.css">
    <style>
        .login-container { max-width: 420px; margin: 0 auto; padding: 60px 20px; }
        .login-form { background: rgba(255,255,255,0.1); padding: 40px; border-radius: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; }
        .form-group input { width: 100%; padding: 14px; border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.08); border-radius: 12px; color: white; }
        .form-group input::placeholder { color: #aaa; }
        .btn-login { width: 100%; }
        .admin-link { text-align: center; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Login to your account</h1>
        <?php if ($error): ?><p style="color: #ff6b6b; margin-bottom: 20px;"><?php echo $error; ?></p><?php endif; ?>
        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-login">Login</button>
        </form>
        <div class="admin-link">
            <a href="admin/login.php">Admin Login</a> | <a href="register.php">Register</a>
        </div>
    </div>
</body>
</html>

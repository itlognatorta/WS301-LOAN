<?php
session_start();
if (isset($_SESSION['admin_id'])) {
    header('Location: admin/admindashboard.php');
    exit;
}

require_once __DIR__ . '/../db_connect_new.php';
require_once __DIR__ . '/../includes/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username and password required.';
    } elseif ($dbConnected && $pdo) {
        $stmt = $pdo->prepare("SELECT id, password_hash FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && verify_password($password, $admin['password_hash'])) {
            $_SESSION['admin_id'] = $admin['id'];
header('Location: admindashboard.php');
            exit;
        } else {
            $error = 'Invalid credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Loan System</title>
    <link rel="stylesheet" href="../index.css">
    <style>.admin-login { max-width: 400px; margin: 100px auto; padding: 40px; background: rgba(255,255,255,0.1); border-radius: 20px; } /* similar to login */</style>
</head>
<body>
    <div class="admin-login">
        <h1>Admin Login</h1>
        <?php if ($error): ?><p style="color: #ff6b6b;"><?php echo $error; ?></p><?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        <p><a href="../login.php">User Login</a></p>
    </div>
</body>
</html>


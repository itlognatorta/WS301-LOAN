<?php
require_once __DIR__ . '/../db_connect_new.php';
require_once __DIR__ . '/../includes/config.php';

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: admindashboard.php');
    exit;
}

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
    <title>Admin Login | Loan System</title>
    <link rel="stylesheet" href="../index.css">
    <style>
        .admin-login { 
            max-width: 400px; 
            margin: 100px auto; 
            padding: 40px; 
            background: rgba(255,255,255,0.1); 
            border-radius: 20px; 
            backdrop-filter: blur(10px);
            color: white;
        }
        .admin-login h1 { text-align: center; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input { 
            width: 100%; 
            padding: 12px; 
            border-radius: 12px; 
            border: 1px solid rgba(255,255,255,0.2); 
            background: rgba(255,255,255,0.08); 
            color: white;
        }
        .btn-primary {
            width: 100%; 
            padding: 12px; 
            background: #3b82f6; 
            border: none; 
            border-radius: 12px; 
            color: white; 
            cursor: pointer;
        }
        .btn-primary:hover { background: #2563eb; }
        a { color: #60a5fa; text-decoration: none; display: block; text-align: center; margin-top: 15px; }
        a:hover { text-decoration: underline; }
        .error { color: #ff6b6b; margin-bottom: 15px; text-align: center; }
    </style>
</head>
<body>
    <div class="admin-login">
        <h1>Admin Login</h1>
        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn-primary">Login</button>
        </form>
        <a href="../login.php">User Login</a>
    </div>
</body>
</html>
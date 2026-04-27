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
        body { margin: 0; min-height: 100vh; background: radial-gradient(circle at top left, rgba(56,189,248,0.15), transparent 18%), linear-gradient(180deg, #020814 0%, #071227 45%, #101f42 100%); color: #e8f1ff; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; display:flex; align-items:center; justify-content:center; padding:24px; }
        * { box-sizing: border-box; }
        .admin-login { width: min(100%, 420px); padding: 38px 32px; background: rgba(255,255,255,0.06); border: 1px solid rgba(56,189,248,0.2); border-radius: 30px; box-shadow: 0 32px 90px rgba(0,0,0,0.2); backdrop-filter: blur(10px); }
        .admin-login h1 { margin: 0 0 24px; font-size: clamp(2rem, 3vw, 2.4rem); text-align: center; color: #f8fbff; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 8px; color: #cbd5e1; font-size: 0.95rem; }
        .form-group input { width: 100%; padding: 14px 16px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.14); background: rgba(255,255,255,0.06); color: #e8f1ff; outline: none; }
        .form-group input:focus { border-color: rgba(56,189,248,0.9); box-shadow: 0 0 0 4px rgba(56,189,248,0.12); }
        .btn-primary { width: 100%; padding: 14px 18px; background: #38bdf8; border: none; border-radius: 16px; color: #031425; font-weight: 700; cursor: pointer; transition: transform 0.16s ease, background 0.16s ease; }
        .btn-primary:hover { transform: translateY(-1px); background: #0ea5e9; }
        a { color: #93c5fd; text-decoration: none; display: block; text-align: center; margin-top: 18px; }
        a:hover { text-decoration: underline; }
        .error { color: #fecaca; margin-bottom: 16px; text-align: center; }
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
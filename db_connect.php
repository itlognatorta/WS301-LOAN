<?php
session_start();
require_once __DIR__ . '/db_connect.php';
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
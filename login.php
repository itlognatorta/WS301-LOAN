<?php
require_once __DIR__ . '/db_connect.php';

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

            // STORE SESSION
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['account_type'] = $user['account_type'];

            // REDIRECT
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
<html>
<head>
    <title>Login</title>
</head>
<body>

<h2>Login</h2>

<?php if ($error): ?>
    <p style="color:red;"><?php echo $error; ?></p>
<?php endif; ?>

<form method="POST">
    <input type="text" name="username" placeholder="Username or Email"><br><br>
    <input type="password" name="password" placeholder="Password"><br><br>
    <button type="submit">Login</button>
</form>

</body>
</html>
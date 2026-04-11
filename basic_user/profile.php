<?php
require_once __DIR__ . '/../db_connect_new.php';

$user_id = $_SESSION['user_id'] ?? 1;

$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html>
<head>
<title>Profile</title>
<link rel="stylesheet" href="dashboard.css">
</head>

<body>

<div class="container">

<?php include 'sidebar.php'; ?>

<div class="main">

<h2>Profile</h2>

<form action="update_profile.php" method="POST" class="card">

<input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

<input type="text" name="name" value="<?php echo $user['name'] ?? ''; ?>" required>
<input type="email" name="email" value="<?php echo $user['email'] ?? ''; ?>" required>
<input type="text" name="contact" value="<?php echo $user['phone'] ?? ''; ?>" required>

<button type="submit">Update</button>

</form>

</div>
</div>

</body>
</html>
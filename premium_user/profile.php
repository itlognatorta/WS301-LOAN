<?php 
require_once __DIR__ . '/../db_connect_new.php';

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="profile.css">
</head>
<body>

<div class="container">
<?php include 'sidebar.php'; ?>

<div class="main">
<h2>Profile</h2>

<form method="POST">

<label>Name</label>
<input type="text" value="<?= $user['name'] ?>" readonly>

<label>Email</label>
<input type="text" value="<?= $user['email'] ?>" readonly>

<label>Address</label>
<textarea><?= $user['address'] ?></textarea>

<label>Phone</label>
<input type="text" value="<?= $user['phone'] ?>">

<button>Update</button>

</form>

</div>
</div>

</body>
</html>
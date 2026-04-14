<?php 
require_once __DIR__ . '/../db_connect_new.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->query("SELECT * FROM company_earnings ORDER BY year DESC LIMIT 1");
$data = $stmt->fetch();

$count = $pdo->query("SELECT COUNT(*) FROM users WHERE account_type='premium'")->fetchColumn();

$moneyBack = 0;

if($count > 0){
    $moneyBack = ($data['total_income'] * 0.02) / $count;
}
?>

<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="premiumdb.css">
</head>
<body>

<div class="container">

<?php include 'sidebar.php'; ?>

<div class="main">

<h2>Money Back Earned</h2>

<div class="card-box">
<p>₱<?= number_format($moneyBack,2) ?></p>
</div>

</div>
</div>

</body>
</html>
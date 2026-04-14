<?php 
require_once __DIR__ . '/../db_connect_new.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM billing WHERE user_id=? ORDER BY generated_date DESC");
$stmt->execute([$user_id]);

$bills = $stmt->fetchAll();
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

<h2>Billing</h2>

<?php if(count($bills) == 0): ?>
<p>No bills to pay</p>
<?php else: ?>

<table>
<tr>
<th>Date</th>
<th>Due Date</th>
<th>Total</th>
<th>Status</th>
</tr>

<?php foreach($bills as $b): ?>
<tr>
<td><?= $b['generated_date'] ?></td>
<td><?= $b['due_date'] ?></td>
<td>₱<?= number_format($b['total_due'],2) ?></td>
<td><?= ucfirst($b['status']) ?></td>
</tr>
<?php endforeach; ?>

</table>

<?php endif; ?>

</div>
</div>

</body>
</html>
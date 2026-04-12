<?php 
require_once __DIR__ . '/../db_connect_new.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// GET USER
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// GET TRANSACTIONS
$transactions = $pdo->prepare("
    SELECT * FROM loan_transactions 
    WHERE user_id=? 
    ORDER BY no DESC LIMIT 5
");
$transactions->execute([$user_id]);

// TOTAL LOAN
$totalLoan = $pdo->prepare("SELECT SUM(amount) FROM loan_transactions WHERE user_id=? AND status='Approved'");
$totalLoan->execute([$user_id]);
$totalLoan = $totalLoan->fetchColumn() ?? 0;
?>
<!DOCTYPE html>
<html>
<head>
<title>Dashboard</title>
<link rel="stylesheet" href="dashboard.css">
</head>

<body>

<div class="container">

<?php include 'sidebar.php'; ?>

<div class="main">

<h2>Dashboard</h2>

<!-- 🔷 CARDS -->
<div class="cards">

<div class="card-box">
<h3>Total Loan</h3>
<p>₱<?php echo number_format($totalLoan); ?></p>
</div>

<div class="card-box">
<h3>Max Loan</h3>
<p>₱10,000</p>
</div>

<div class="card-box">
<h3>Status</h3>
<p>Active User</p>
</div>

</div>

<!-- 🔷 SYSTEM INFO -->
<div class="card">
<h3>Loan Information</h3>
<p>✔ Minimum Loan: ₱5,000</p>
<p>✔ Maximum Loan: ₱10,000</p>
<p>✔ Payable: 1, 3, 6, 12 months</p>
<p>✔ Pay on time to increase your loan limit</p>
</div>

<!-- 🔷 TRANSACTIONS -->
<div class="card">
<h3>Recent Transactions</h3>

<table>
<tr>
<th>ID</th>
<th>Amount</th>
<th>Months</th>
<th>Status</th>
</tr>

<?php if($transactions->rowCount() > 0): ?>
<?php foreach($transactions as $t): ?>
<tr>
<td><?php echo $t['no']; ?></td>
<td>₱<?php echo $t['amount']; ?></td>
<td><?php echo $t['months']; ?></td>
<td class="<?php echo strtolower($t['status']); ?>">
<?php echo $t['status']; ?>
</td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td colspan="4">No transactions yet</td></tr>
<?php endif; ?>

</table>

</div>

</div>
</div>

</body>
</html>
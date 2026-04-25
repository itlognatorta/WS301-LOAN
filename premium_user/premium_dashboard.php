<?php 
require_once __DIR__ . '/../db_connect_new.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// USER
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// TOTAL SAVINGS
$savings = $user['savings_balance'];

// TOTAL LOAN
$loan = $user['current_loan_amount'];

// RECENT SAVINGS TRANSACTIONS
$transactions = $pdo->prepare("
    SELECT * FROM savings_transactions 
    WHERE user_id=? 
    ORDER BY no DESC LIMIT 5
");
$transactions->execute([$user_id]);

/* RECENT APPROVED LOAN TRANSACTIONS */
$loanTransactions = $pdo->prepare("
    SELECT * FROM loan_transactions
    WHERE user_id=? AND status='approved'
    ORDER BY no DESC LIMIT 5
");
$loanTransactions->execute([$user_id]);

?>
<!DOCTYPE html>
<html>
<head>
<title>Dashboard</title>
<link rel="stylesheet" href="premiumdb.css">
</head>
<body>

<div class="container">
<?php include 'sidebar.php'; ?>

<div class="main">

<h2>Premium Dashboard</h2>

<div class="cards">

<div class="card-box">
<h3>Savings</h3>
<p>₱<?php echo number_format($savings,2); ?></p>
</div>

<div class="card-box">
<h3>Loan Balance</h3>
<p>₱<?php echo number_format($loan,2); ?></p>
</div>

<div class="card-box">
<h3>Status</h3>
<p><?php echo ucfirst($user['status']); ?></p>
</div>

</div>

<!-- 🔷 SYSTEM INFO (NEWLY ADDED CARD) -->
<div class="card">
<h3>Loan Information</h3>
<p>✔ Minimum Loan: ₱5,000</p>
<p>✔ Maximum Loan: ₱10,000</p>
<p>✔ Payable: 1, 3, 6, 12 months</p>
<p>✔ Pay on time to increase your loan limit</p>
</div>

<div class="card">
<h3>Recent Savings Transactions</h3>

<table>
<tr>
<th>No</th>
<th>TX ID</th>
<th>Category</th>
<th>Amount</th>
<th>Status</th>
</tr>

<?php foreach($transactions as $t): ?>
<tr>
<td><?= $t['no'] ?></td>
<td><?= $t['tx_id'] ?></td>
<td><?= ucfirst($t['category']) ?></td>
<td>₱<?= number_format($t['amount'],2) ?></td>
<td class="<?= $t['status'] ?>"><?= ucfirst($t['status']) ?></td>
</tr>
<?php endforeach; ?>

</table>
</div>

<div class="card">
<h3>Approved Loan Transactions</h3>

<table>
<tr>
<th>No</th>
<th>TX ID</th>
<th>Loan Amount</th>
<th>Interest</th>
<th>Net Amount</th>
<th>Months</th>
<th>Date Approved</th>
</tr>

<?php foreach($loanTransactions as $l): ?>
<tr>
<td><?= $l['no'] ?></td>
<td><?= $l['tx_id'] ?></td>
<td>₱<?= number_format($l['amount'],2) ?></td>
<td>₱<?= number_format($l['interest'],2) ?></td>
<td>₱<?= number_format($l['net_amount'],2) ?></td>
<td><?= $l['tenure_months'] ?></td>
<td><?= date("M d, Y", strtotime($l['created_at'])) ?></td>
</tr>
<?php endforeach; ?>

<?php if($loanTransactions->rowCount() == 0): ?>
<tr>
<td colspan="7">No approved loan transactions yet.</td>
</tr>
<?php endif; ?>

</table>
</div>

</div>
</div>

</body>
</html>
<?php
require_once __DIR__ . '/../db_connect_new.php';

$user_id = $_SESSION['user_id'] ?? 1;

$transactions = $pdo->prepare("
    SELECT * FROM loan_transactions 
    WHERE user_id=? 
    ORDER BY no DESC
");
$transactions->execute([$user_id]);
?>

<!DOCTYPE html>
<html>
<head>
<title>Loan</title>
<link rel="stylesheet" href="dashboard.css">
</head>

<body>

<div class="container">

<?php include 'sidebar.php'; ?>

<div class="main">

<h2>Apply Loan</h2>

<form action="process_loan.php" method="POST" class="card">
<input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

<label>Amount</label>
<input type="number" name="amount" min="5000" max="10000" required>

<label>Months</label>
<select name="months" required>
<option value="1">1 Month</option>
<option value="3">3 Months</option>
<option value="6">6 Months</option>
<option value="12">12 Months</option>
</select>

<button type="submit">Apply</button>
</form>

<h2>Transactions</h2>

<div class="card">
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
<tr><td colspan="4">No transactions</td></tr>
<?php endif; ?>

</table>
</div>

</div>
</div>

</body>
</html>
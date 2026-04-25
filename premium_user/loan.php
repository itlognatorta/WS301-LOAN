<?php 
session_start();
require_once __DIR__ . '/../db_connect_new.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /WS301-LOAN/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

/* ================= CURRENT USED LOAN ================= */
$stmt = $pdo->prepare("SELECT current_loan_amount FROM users WHERE id=?");
$stmt->execute([$user_id]);
$userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
$current_loan = $userInfo['current_loan_amount'] ?? 0;

/* ================= APPLY LOAN ================= */
if(isset($_POST['apply'])){

    $amount = (int) $_POST['amount'];
    $months = (int) $_POST['months'];

    if($amount < 5000 || $amount > 10000 || $amount % 1000 != 0){
        $error = "Loan amount must be ₱5,000 to ₱10,000 by thousands only.";
    }
    elseif(!in_array($months,[1,3,6,12])){
        $error = "Invalid payable months.";
    }
    elseif($current_loan >= 10000){
        $error = "You already reached the ₱10,000 maximum unpaid loan. Please pay your existing loan first.";
    }
    elseif(($current_loan + $amount) > 10000){
        $remaining = 10000 - $current_loan;
        $error = "You can only loan ₱".number_format($remaining,2)." more.";
    }
    else{

        /* prevent duplicate pending request */
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM loan_requests WHERE user_id=? AND status='pending'");
        $stmt->execute([$user_id]);
        $pendingCount = $stmt->fetchColumn();

        if($pendingCount > 0){
            $error = "You still have a pending loan request waiting for admin approval.";
        }else{

            $stmt = $pdo->prepare("
                INSERT INTO loan_requests
                (user_id, amount, tenure_months, status, created_at)
                VALUES (?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([$user_id,$amount,$months]);

            $success = "Loan request submitted successfully and is now waiting for admin approval.";
        }
    }
}

/* ================= USER REQUEST HISTORY ================= */
$stmt = $pdo->prepare("
    SELECT * FROM loan_requests
    WHERE user_id=?
    ORDER BY id DESC
");
$stmt->execute([$user_id]);
$request_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ================= APPROVED LOAN TRANSACTIONS ================= */
$stmt = $pdo->prepare("
    SELECT * FROM loan_transactions
    WHERE user_id=?
    ORDER BY no DESC
");
$stmt->execute([$user_id]);
$loan_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<title>Loan</title>
<link rel="stylesheet" href="premiumdb.css">
</head>
<body>

<div class="container">
<?php include 'sidebar.php'; ?>

<div class="main">

<h2>Apply Loan</h2>

<?php if($error): ?>
<div class="card" style="color:#f87171;"><?= $error ?></div>
<?php endif; ?>

<?php if($success): ?>
<div class="card success"><?= $success ?></div>
<?php endif; ?>

<div class="card">
<h3>Loan Application</h3>
<p><strong>Current Used Loan:</strong> ₱<?= number_format($current_loan,2) ?> / ₱10,000</p>

<form method="POST">
<label>Loan Amount</label>
<input type="number" name="amount" required>

<label>Months to Pay</label>
<select name="months" required>
<option value="1">1 month</option>
<option value="3">3 months</option>
<option value="6">6 months</option>
<option value="12">12 months</option>
</select>

<button name="apply">Apply Loan</button>
</form>
</div>

<div class="card">
<h3>Loan Request Status</h3>
<table width="100%" border="1" cellpadding="10" cellspacing="0">
<tr>
<th>Request ID</th>
<th>Amount</th>
<th>Months</th>
<th>Status</th>
<th>Reason</th>
<th>Date</th>
</tr>

<?php if($request_history): ?>
<?php foreach($request_history as $r): ?>
<tr>
<td><?= $r['id'] ?></td>
<td>₱<?= number_format($r['amount'],2) ?></td>
<td><?= $r['tenure_months'] ?></td>
<td>
<?php
if($r['status']=='pending') echo "<span style='color:orange;'>Pending</span>";
elseif($r['status']=='approved') echo "<span style='color:green;'>Approved</span>";
else echo "<span style='color:red;'>Rejected</span>";
?>
</td>
<td><?= $r['rejection_reason'] ?: '-' ?></td>
<td><?= date("M d, Y", strtotime($r['created_at'])) ?></td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td colspan="6">No loan requests yet.</td></tr>
<?php endif; ?>
</table>
</div>

<div class="card">
<h3>Approved Loan Transactions</h3>
<table width="100%" border="1" cellpadding="10" cellspacing="0">
<tr>
<th>TX ID</th>
<th>Amount</th>
<th>Interest</th>
<th>Net Amount</th>
<th>Months</th>
<th>Status</th>
<th>Date</th>
</tr>

<?php if($loan_history): ?>
<?php foreach($loan_history as $t): ?>
<tr>
<td><?= $t['tx_id'] ?></td>
<td>₱<?= number_format($t['amount'],2) ?></td>
<td>₱<?= number_format($t['interest'],2) ?></td>
<td>₱<?= number_format($t['net_amount'],2) ?></td>
<td><?= $t['tenure_months'] ?></td>
<td><span style="color:green;">Approved</span></td>
<td><?= date("M d, Y", strtotime($t['created_at'])) ?></td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td colspan="7">No approved loan transactions yet.</td></tr>
<?php endif; ?>
</table>
</div>

</div>
</div>
</body>
</html>
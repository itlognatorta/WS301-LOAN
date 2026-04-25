<?php 
session_start();
require_once __DIR__ . '/../db_connect_new.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";

/* ================= USER INFO ================= */
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

/* ================= PAY BILL ================= */
if(isset($_POST['pay_bill'])){

    $bill_id = intval($_POST['bill_id']);

    $stmt = $pdo->prepare("
        SELECT * FROM billing 
        WHERE id=? AND user_id=? AND status!='completed'
    ");
    $stmt->execute([$bill_id,$user_id]);
    $bill = $stmt->fetch(PDO::FETCH_ASSOC);

    if($bill){

        /* mark billing completed */
        $stmt = $pdo->prepare("
            UPDATE billing
            SET status='completed', paid_at=NOW()
            WHERE id=?
        ");
        $stmt->execute([$bill_id]);

        /* deduct current loan amount */
        $stmt = $pdo->prepare("
            UPDATE users
            SET current_loan_amount = current_loan_amount - ?
            WHERE id=?
        ");
        $stmt->execute([$bill['monthly_amount'], $user_id]);

        /* check if loan fully paid */
        $stmt = $pdo->prepare("
            SELECT SUM(monthly_amount) as paid_total, loan_id
            FROM billing
            WHERE loan_id=? AND status='completed'
        ");
        $stmt->execute([$bill['loan_id']]);
        $paidInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT principal FROM loans WHERE id=?");
        $stmt->execute([$bill['loan_id']]);
        $principal = $stmt->fetchColumn();

        if($paidInfo['paid_total'] >= $principal){
            $pdo->prepare("
                UPDATE loans SET status='paid'
                WHERE id=?
            ")->execute([$bill['loan_id']]);
        }

        $message = "Billing payment completed successfully.";
    }
}

/* ================= AUTO OVERDUE CHECK ================= */
$all = $pdo->prepare("SELECT * FROM billing WHERE user_id=?");
$all->execute([$user_id]);
$tempBills = $all->fetchAll(PDO::FETCH_ASSOC);

foreach($tempBills as $tb){

    if($tb['status'] == 'pending' && strtotime($tb['due_date']) < time()){

        $penalty = $tb['monthly_amount'] * 0.02;
        $newTotal = $tb['monthly_amount'] + $tb['interest'] + $penalty;

        $pdo->prepare("
            UPDATE billing
            SET status='overdue', penalty=?, total_due=?
            WHERE id=?
        ")->execute([$penalty,$newTotal,$tb['id']]);
    }
}

/* ================= CURRENT BILL ================= */
$stmt = $pdo->prepare("
    SELECT b.*, l.received_amount
    FROM billing b
    JOIN loans l ON b.loan_id = l.id
    WHERE b.user_id=? AND b.status!='completed'
    ORDER BY b.generated_date ASC
");
$stmt->execute([$user_id]);
$currentBills = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ================= BILL HISTORY ================= */
$stmt = $pdo->prepare("
    SELECT b.*, l.received_amount
    FROM billing b
    JOIN loans l ON b.loan_id = l.id
    WHERE b.user_id=?
    ORDER BY b.generated_date DESC
");
$stmt->execute([$user_id]);
$historyBills = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<title>Billing</title>
<link rel="stylesheet" href="premiumdb.css">
</head>
<body>

<div class="container">
<?php include 'sidebar.php'; ?>

<div class="main">

<h2>Billing Summary</h2>

<?php if($message): ?>
<div class="card success"><?= $message ?></div>
<?php endif; ?>

<!-- ================= CURRENT BILLING ================= -->
<?php if(count($currentBills) == 0): ?>

<div class="card">
<h3>No bills to pay</h3>
</div>

<?php else: ?>

<?php foreach($currentBills as $b): ?>
<div class="card">

<h3>Current Billing Details</h3>

<p><strong>Date Generated:</strong> <?= $b['generated_date'] ?></p>
<p><strong>Due Date:</strong> <?= $b['due_date'] ?></p>
<p><strong>Borrower:</strong> <?= htmlspecialchars($user['name']) ?></p>
<p><strong>Account Type:</strong> <?= ucfirst($user['account_type']) ?></p>
<p><strong>Loaned Amount:</strong> ₱<?= number_format($b['loan_principal'],2) ?></p>
<p><strong>Received Amount:</strong> ₱<?= number_format($b['received_amount'],2) ?></p>
<p><strong>Amount to Pay this Month:</strong> ₱<?= number_format($b['monthly_amount'],2) ?></p>
<p><strong>Interest (3%):</strong> ₱<?= number_format($b['interest'],2) ?></p>
<p><strong>Penalty (2%):</strong> ₱<?= number_format($b['penalty'],2) ?></p>
<p><strong>Total Due:</strong> ₱<?= number_format($b['total_due'],2) ?></p>

<p><strong>Status:</strong>
<?php
if($b['status']=='pending') echo "<span style='color:orange;'>Pending</span>";
elseif($b['status']=='overdue') echo "<span style='color:red;'>Overdue</span>";
?>
</p>

<form method="POST">
<input type="hidden" name="bill_id" value="<?= $b['id'] ?>">
<button name="pay_bill">Pay Bill</button>
</form>

</div>
<?php endforeach; ?>

<?php endif; ?>

<!-- ================= BILLING HISTORY ================= -->
<div class="card">
<h3>Billing History</h3>

<table width="100%" border="1" cellpadding="10" cellspacing="0">
<tr>
<th>Year</th>
<th>Month</th>
<th>Generated</th>
<th>Due</th>
<th>Total</th>
<th>Status</th>
<th>Paid At</th>
</tr>

<?php foreach($historyBills as $h): ?>
<tr>
<td><?= date("Y", strtotime($h['generated_date'])) ?></td>
<td><?= date("F", strtotime($h['generated_date'])) ?></td>
<td><?= $h['generated_date'] ?></td>
<td><?= $h['due_date'] ?></td>
<td>₱<?= number_format($h['total_due'],2) ?></td>
<td><?= ucfirst($h['status']) ?></td>
<td><?= $h['paid_at'] ? date("M d, Y", strtotime($h['paid_at'])) : '-' ?></td>
</tr>
<?php endforeach; ?>

<?php if(count($historyBills)==0): ?>
<tr><td colspan="7">No billing history yet.</td></tr>
<?php endif; ?>

</table>
</div>

</div>
</div>

</body>
</html>
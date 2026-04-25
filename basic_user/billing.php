<?php
session_start();
require_once __DIR__ . '/../db_connect_new.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* =========================
   AUTO OVERDUE + PENALTY
========================= */
$stmt = $pdo->prepare("
    UPDATE billing
    SET 
        status = 'overdue',
        penalty = monthly_amount * 0.02,
        total_due = monthly_amount + interest + (monthly_amount * 0.02)
    WHERE due_date < CURDATE()
    AND status = 'pending'
");
$stmt->execute();

/* =========================
   PAYMENT HANDLER
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_billing_id'])) {

    $billing_id = $_POST['pay_billing_id'];
    $payment = (float) ($_POST['payment_amount'] ?? 0);

    $stmt = $pdo->prepare("SELECT * FROM billing WHERE id = ? AND user_id = ?");
    $stmt->execute([$billing_id, $user_id]);
    $bill = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bill) {
        die("Billing not found.");
    }

    $penalty = (date('Y-m-d') > $bill['due_date'])
        ? $bill['monthly_amount'] * 0.02
        : 0;

    $total_due = $bill['monthly_amount'] + $bill['interest'] + $penalty;

    if ($payment != $total_due) {
        die("You must pay the exact total amount: ₱" . number_format($total_due, 2));
    }

    $loan_id = $bill['loan_id'];

    $reference_no = "PAY-" . time() . "-" . rand(1000, 9999);

    /* SAVE PAYMENT */
    $insert = $pdo->prepare("
        INSERT INTO loan_payment
        (u_id, loan_id, amount_paid, reference_no, notes, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $insert->execute([
        $user_id,
        $loan_id,
        $payment,
        $reference_no,
        "Billing payment"
    ]);

    /* UPDATE BILL */
    $update = $pdo->prepare("
        UPDATE billing 
        SET penalty = ?, total_due = 0, status = 'completed', paid_at = NOW()
        WHERE id = ?
    ");
    $update->execute([$penalty, $billing_id]);

    /* RETURN CREDIT */
    $creditBack = $pdo->prepare("
        UPDATE users 
        SET current_loan_amount = current_loan_amount - ?
        WHERE id = ?
    ");
    $creditBack->execute([$bill['monthly_amount'], $user_id]);

    /* =========================
       AUTO GENERATE NEXT BILLING
    ========================== */

    // get active loan
    $loanStmt = $pdo->prepare("
        SELECT * FROM loans 
        WHERE user_id = ? AND status = 'active'
        ORDER BY id DESC LIMIT 1
    ");
    $loanStmt->execute([$user_id]);
    $loanData = $loanStmt->fetch(PDO::FETCH_ASSOC);

    if($loanData){

        $loan_tx_id = $bill['loan_id'];

        // count existing bills
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) FROM billing WHERE loan_id = ?
        ");
        $countStmt->execute([$loan_tx_id]);
        $existingMonths = $countStmt->fetchColumn();

        // generate only if not finished
        if($existingMonths < $loanData['tenure_months']){

            // get last due date
            $lastDueStmt = $pdo->prepare("
                SELECT due_date FROM billing 
                WHERE loan_id = ?
                ORDER BY due_date DESC LIMIT 1
            ");
            $lastDueStmt->execute([$loan_tx_id]);
            $lastDue = $lastDueStmt->fetchColumn();

            // next due date
            $nextDueDate = date('Y-m-d', strtotime($lastDue . ' +28 days'));

            // compute amounts
            $monthly = $loanData['principal'] / $loanData['tenure_months'];

            // ⚠️ CHANGE THIS IF INTEREST IS TOTAL ONLY
            $interest = $loanData['interest']; 

            $total_due = $monthly + $interest;

            // insert next bill
            $insertNext = $pdo->prepare("
                INSERT INTO billing
                (user_id, loan_id, generated_date, due_date, loan_principal, monthly_amount, interest, total_due, status)
                VALUES (?, ?, CURDATE(), ?, ?, ?, ?, ?, 'pending')
            ");

            $insertNext->execute([
                $user_id,
                $loan_tx_id,
                $nextDueDate,
                $loanData['principal'],
                $monthly,
                $interest,
                $total_due
            ]);
        }
    }

    header("Location: billing.php");
    exit;
}

/* =========================
   GET LOAN SUMMARY
========================= */
$loanStmt = $pdo->prepare("
    SELECT * FROM loans 
    WHERE user_id = ? AND status = 'active'
    ORDER BY id DESC LIMIT 1
");
$loanStmt->execute([$user_id]);
$loan = $loanStmt->fetch(PDO::FETCH_ASSOC);

/* =========================
   GET BILLINGS
========================= */
$stmt = $pdo->prepare("
    SELECT * FROM billing 
    WHERE user_id = ?
    ORDER BY due_date ASC
");
$stmt->execute([$user_id]);
$bills = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   HISTORY GROUPING
========================= */
$grouped = [];
foreach ($bills as $h) {
    $year = date('Y', strtotime($h['generated_date']));
    $month = date('F', strtotime($h['generated_date']));
    $grouped[$year][$month][] = $h;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Billing</title>
<link rel="stylesheet" href="dashboard.css">

<script>
function openPay(id, amount){
    document.getElementById("billing_id").value = id;
    document.getElementById("payment_amount").value = amount;
    document.getElementById("payModal").classList.add("active");
}
function closePay(){
    document.getElementById("payModal").classList.remove("active");
}
</script>
</head>

<body>
<div class="container">
<?php include 'sidebar.php'; ?>

<div class="main-content">

<h2>Billing Page</h2>

<!-- ================= SUMMARY ================= -->
<?php if($loan): ?>
<h3>Billing Summary</h3>

<p><b>Loan Amount:</b> ₱<?= number_format($loan['principal'],2) ?></p>
<p><b>Interest (3%):</b> ₱<?= number_format($loan['interest'],2) ?></p>
<p><b>Total Amount on Hand:</b> ₱<?= number_format($loan['received_amount'],2) ?></p>

<table>
<tr>
<th>Due Date</th>
<th>Amount</th>
<th>Status</th>
<th>Action</th>
</tr>

<?php foreach($bills as $bill): 

$penalty = (date('Y-m-d') > $bill['due_date']) ? $bill['monthly_amount'] * 0.02 : 0;
$total = $bill['monthly_amount'] + $bill['interest'] + $penalty;
?>

<tr>
<td><?= date("m/d/y", strtotime($bill['due_date'])) ?></td>
<td>₱<?= number_format($total,2) ?></td>
<td><?= $bill['status'] ?></td>
<td>
<?php if($bill['status'] != 'completed'): ?>
<button class="pay-btn" onclick="openPay(<?= $bill['id'] ?>, <?= $total ?>)">Pay Now</button>
<?php else: ?>
-
<?php endif; ?>
</td>
</tr>

<?php endforeach; ?>

</table>

<?php else: ?>
<p>No bills to pay</p>
<?php endif; ?>

<!-- ================= HISTORY ================= -->
<h3>Billing History</h3>

<?php foreach ($grouped as $year => $months): ?>
<h4><?= $year ?></h4>

<?php foreach ($months as $month => $records): ?>
<h5><?= $month ?></h5>

<?php foreach ($records as $r): ?>
<p>
<?= $r['generated_date'] ?> - 
₱<?= number_format($r['total_due'],2) ?> - 
<?= $r['status'] ?>
</p>
<?php endforeach; ?>

<?php endforeach; ?>
<?php endforeach; ?>

</div>
</div>

<!-- ================= MODAL ================= -->
<div id="payModal" class="modal-overlay">
<div class="modal-box">

<h3>Pay Billing</h3>

<form method="POST">
<input type="hidden" name="pay_billing_id" id="billing_id">

<label>Payment Amount</label>
<input type="number" name="payment_amount" id="payment_amount" readonly required>

<div>
<button type="button" onclick="closePay()">Cancel</button>
<button type="submit">Confirm Payment</button>
</div>

</form>

</div>
</div>

</body>
</html>
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
   PAYMENT HANDLER (FIXED)
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

    $check = $pdo->prepare("SELECT no FROM loan_transactions WHERE no = ?");
    $check->execute([$loan_id]);

    if (!$check->fetchColumn()) {
        die("Error: Invalid loan reference.");
    }

    $reference_no = "PAY-" . time() . "-" . rand(1000, 9999);

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

    $update = $pdo->prepare("
        UPDATE billing 
        SET penalty = ?, total_due = 0, status = 'completed', paid_at = NOW()
        WHERE id = ?
    ");

    $update->execute([$penalty, $billing_id]);

    header("Location: billing.php");
    exit;
}

/* =========================
   CURRENT BILL
========================= */
$currentStmt = $pdo->prepare("
    SELECT * FROM billing 
    WHERE user_id = ? 
    AND status IN ('pending','overdue')
    ORDER BY generated_date DESC
    LIMIT 1
");
$currentStmt->execute([$user_id]);
$currentBill = $currentStmt->fetch(PDO::FETCH_ASSOC);

/* =========================
   HISTORY
========================= */
$historyStmt = $pdo->prepare("
    SELECT * FROM billing 
    WHERE user_id = ?
    ORDER BY generated_date DESC
");
$historyStmt->execute([$user_id]);
$history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

$grouped = [];
foreach ($history as $h) {
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
function openPay(id, maxAmount) {
    document.getElementById("billing_id").value = id;
    document.getElementById("payment_amount").value = maxAmount;
    document.getElementById("payModal").classList.add("active");
}

function closePay() {
    document.getElementById("payModal").classList.remove("active");
}
</script>

</head>
<body>

<div class="container">

<?php include 'sidebar.php'; ?>

<div class="main-content">

<h2>Billing Page</h2>

<!-- ================= CURRENT BILL ================= -->
<div class="current-bill">

<h3>Current Billing</h3>

<?php if (!$currentBill): ?>
    <p class="no-bills">No bills to pay</p>
<?php else: ?>

<?php
$penalty = (date('Y-m-d') > $currentBill['due_date'])
    ? $currentBill['monthly_amount'] * 0.02
    : 0;

$total = $currentBill['monthly_amount'] + $currentBill['interest'] + $penalty;
?>

<div class="bill-card">

<p><b>Date Generated:</b> <?= $currentBill['generated_date'] ?></p>
<p><b>Due Date:</b> <?= $currentBill['due_date'] ?></p>

<hr>

<p><b>Loaned Amount:</b> ₱<?= number_format($currentBill['loan_principal'],2) ?></p>
<p><b>Monthly Amount:</b> ₱<?= number_format($currentBill['monthly_amount'],2) ?></p>
<p><b>Interest (3%):</b> ₱<?= number_format($currentBill['interest'],2) ?></p>
<p><b>Penalty (2%):</b> ₱<?= number_format($penalty,2) ?></p>

<h3>Total Due: ₱<?= number_format($total,2) ?></h3>

<p><b>Status:</b> 
<span class="<?= strtolower($currentBill['status']) ?>">
    <?= $currentBill['status'] ?>
</span>
</p>

<button type="button"
        onclick="openPay(<?= $currentBill['id'] ?>, <?= $total ?>)">
    Pay Now
</button>

</div>

<?php endif; ?>

</div>

<!-- ================= HISTORY ================= -->
<div class="history">

<h3>Billing History</h3>

<?php foreach ($grouped as $year => $months): ?>
    <div class="year-box">
        <h4><?= $year ?></h4>

        <?php foreach ($months as $month => $records): ?>
            <div class="month-box">
                <h5><?= $month ?></h5>

                <?php foreach ($records as $r): ?>
                    <div class="history-item">
                        <p>
                            <?= $r['generated_date'] ?> -
                            ₱<?= number_format($r['total_due'],2) ?> -
                            <span class="<?= strtolower($r['status']) ?>">
                                <?= $r['status'] ?>
                            </span>
                        </p>
                    </div>
                <?php endforeach; ?>

            </div>
        <?php endforeach; ?>

    </div>
<?php endforeach; ?>

</div>

</div>
</div>

<!-- ================= PAYMENT MODAL ================= -->
<div id="payModal" class="modal-overlay">
    <div class="modal-box">

        <h3>Pay Billing</h3>

        <form method="POST">

            <input type="hidden" name="pay_billing_id" id="billing_id">

            <label>Payment Amount</label>
            <input type="number"
                 name="payment_amount"
                 id="payment_amount"
                 readonly
                 required>

            <div class="modal-actions">
                <button type="button" onclick="closePay()">Cancel</button>
                <button type="submit">Confirm Payment</button>
            </div>

        </form>

    </div>
</div>

</body>
</html>
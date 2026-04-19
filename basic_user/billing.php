<?php
session_start();
require_once __DIR__ . '/../db_connect_new.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* =========================
   PAYMENT HANDLER
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_billing_id'])) {

    $billing_id = $_POST['pay_billing_id'];

    $stmt = $pdo->prepare("SELECT * FROM billing WHERE ID = ? AND user_id = ?");
    $stmt->execute([$billing_id, $user_id]);
    $bill = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($bill) {

        $penalty = 0;

        if (date('Y-m-d') > $bill['due_date']) {
            $penalty = $bill['monthly_amount'] * 0.02;
        }

        $total_due = $bill['monthly_amount'] + $bill['interest'] + $penalty;

        $update = $pdo->prepare("
            UPDATE billing 
            SET penalty = ?, total_due = ?, status = 'Completed', paid_at = NOW()
            WHERE ID = ?
        ");

        $update->execute([$penalty, $total_due, $billing_id]);

        header("Location: billing.php");
        exit;
    }
}

/* =========================
   CURRENT BILL
========================= */
$currentStmt = $pdo->prepare("
    SELECT * FROM billing 
    WHERE user_id = ? AND status != 'Completed'
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

/* GROUP BY YEAR + MONTH */
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
</head>
<body>

<div class="container">

    <!-- SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <h2>Billing Page</h2>

        <!-- CURRENT BILL -->
        <div class="current-bill">

            <h3>Current Billing</h3>

            <?php if (!$currentBill): ?>
                <p class="no-bills">No bills to pay</p>
            <?php else: ?>

                <?php
                    $penalty = 0;

                    if (date('Y-m-d') > $currentBill['due_date']) {
                        $penalty = $currentBill['monthly_amount'] * 0.02;
                    }

                    $total = $currentBill['monthly_amount'] + $currentBill['interest'] + $penalty;
                ?>

                <div class="bill-card">

                    <p><b>Date Generated:</b> <?= $currentBill['generated_date'] ?></p>
                    <p><b>Due Date:</b> <?= $currentBill['due_date'] ?></p>

                    <hr>

                    <p><b>Loaned Amount:</b> ₱<?= $currentBill['loan_principal'] ?></p>
                    <p><b>Monthly Amount:</b> ₱<?= $currentBill['monthly_amount'] ?></p>
                    <p><b>Interest (3%):</b> ₱<?= $currentBill['interest'] ?></p>
                    <p><b>Penalty (2%):</b> ₱<?= $penalty ?></p>

                    <h3>Total Due: ₱<?= $total ?></h3>

                    <form method="POST">
                        <input type="hidden" name="pay_billing_id" value="<?= $currentBill['ID'] ?>">
                        <button type="submit">Pay Now</button>
                    </form>

                </div>

            <?php endif; ?>

        </div>

        <!-- HISTORY -->
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
                                        ₱<?= $r['total_due'] ?> -
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

</body>
</html>
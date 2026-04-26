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

/* ================= GET ACTIVE LOAN ================= */
$loanStmt = $pdo->prepare("
    SELECT * FROM loans 
    WHERE user_id = ? AND status = 'active'
    ORDER BY id DESC LIMIT 1
");
$loanStmt->execute([$user_id]);
$loanData = $loanStmt->fetch(PDO::FETCH_ASSOC);

/* ================= AUTO BILLING GENERATION ================= */
if ($loanData && isset($loanData['id'])) {

    $loan_id = $loanData['id'];

    $principal = (float)$loanData['principal'];
    $months = (int)$loanData['tenure_months'];

    if ($months <= 0) $months = 1;

    /* stop if all bills generated */
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) FROM billing 
        WHERE loan_id = ?
    ");
    $countStmt->execute([$loan_id]);
    $billCount = $countStmt->fetchColumn();

    if ($billCount < $months) {

        $lastBill = $pdo->prepare("
            SELECT generated_date 
            FROM billing 
            WHERE loan_id = ? 
            ORDER BY generated_date DESC 
            LIMIT 1
        ");
        $lastBill->execute([$loan_id]);
        $lastDate = $lastBill->fetchColumn();

        $baseDate = $lastDate ?: ($loanData['created_at'] ?? date('Y-m-d'));
        $nextBilling = date("Y-m-d", strtotime("+1 month", strtotime($baseDate)));

        $check = $pdo->prepare("
            SELECT COUNT(*) FROM billing
            WHERE loan_id = ? AND generated_date = ?
        ");
        $check->execute([$loan_id, $nextBilling]);

        if ($check->fetchColumn() == 0) {

            $monthly = $principal / $months;
            $interest = $monthly * 0.03;

            $pdo->prepare("
                INSERT INTO billing
                (user_id, loan_id, generated_date, due_date,
                 loan_principal, monthly_amount, interest, penalty, total_due, status)
                VALUES (?,?,?,?,?,?,?,?,?,?)
            ")->execute([
                $user_id,
                $loan_id,
                $nextBilling,
                date("Y-m-d", strtotime("+28 days", strtotime($nextBilling))),
                $principal,
                $monthly,
                $interest,
                0,
                $monthly + $interest,
                'pending'
            ]);
        }
    }
}

/* ================= AUTO OVERDUE ================= */
$pdo->prepare("
    UPDATE billing
    SET 
        status='overdue',
        penalty = monthly_amount * 0.02,
        total_due = monthly_amount + interest + (monthly_amount * 0.02)
    WHERE status='pending' AND due_date < CURDATE()
")->execute();

/* ================= PAY BILL ================= */
if (isset($_POST['pay_bill'])) {

    $bill_id = $_POST['bill_id'];

    $stmt = $pdo->prepare("
        SELECT * FROM billing 
        WHERE id=? AND user_id=? AND status!='completed'
    ");
    $stmt->execute([$bill_id, $user_id]);
    $bill = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($bill) {

        $pay_amount = (float)$bill['total_due'];

        /* ================= CALCULATE PRINCIPAL PORTION ================= */
        $monthly = (float)$bill['monthly_amount'];
        $interest = (float)$bill['interest'];
        $penalty = (float)$bill['penalty'];

        // ONLY PRINCIPAL RETURNS TO CREDIT
        $principal_return = $monthly;

        /* ================= UPDATE BILL ================= */
        $pdo->prepare("
            UPDATE billing 
            SET total_due = 0,
                status='completed',
                paid_at=NOW()
            WHERE id=?
        ")->execute([$bill_id]);

        /* ================= RECORD PAYMENT ================= */
        $pdo->prepare("
            INSERT INTO loan_payment
            (u_id, loan_id, amount_paid, reference_no, notes, created_at)
            VALUES (?,?,?,?,?,NOW())
        ")->execute([
            $user_id,
            $bill['loan_id'],
            $pay_amount,
            uniqid("REF"),
            "Monthly payment"
        ]);

        /* ================= RETURN CREDIT (ONLY PRINCIPAL) ================= */
        $pdo->prepare("
            UPDATE users
            SET current_loan_amount = GREATEST(current_loan_amount - ?, 0)
            WHERE id=?
        ")->execute([$principal_return, $user_id]);

        $message = "Payment successful!";
    }
}

/* ================= CURRENT BILLS ================= */
$stmt = $pdo->prepare("
    SELECT * FROM billing
    WHERE user_id=? AND status!='completed'
    ORDER BY generated_date ASC
");
$stmt->execute([$user_id]);
$currentBills = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ================= HISTORY ================= */
$stmt = $pdo->prepare("
    SELECT * FROM billing
    WHERE user_id=?
    ORDER BY generated_date DESC
");
$stmt->execute([$user_id]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

<!-- CURRENT BILL -->
<?php if(count($currentBills) == 0): ?>
<div class="card"><h3>No bills to pay</h3></div>
<?php else: ?>

<?php foreach($currentBills as $b): ?>
<div class="card">

<p><b>Date Generated:</b> <?= $b['generated_date'] ?></p>
<p><b>Due Date:</b> <?= $b['due_date'] ?></p>
<p><b>Loan Amount:</b> ₱<?= number_format($b['loan_principal'],2) ?></p>
<p><b>Monthly:</b> ₱<?= number_format($b['monthly_amount'],2) ?></p>
<p><b>Interest:</b> ₱<?= number_format($b['interest'],2) ?></p>
<p><b>Penalty:</b> ₱<?= number_format($b['penalty'],2) ?></p>
<p><b>Total Due:</b> ₱<?= number_format($b['total_due'],2) ?></p>

<p><b>Status:</b> 
<?= $b['status']=='overdue' ? "<span style='color:red'>Overdue</span>" : "<span style='color:orange'>Pending</span>" ?>
</p>

<form method="POST">
    <input type="hidden" name="bill_id" value="<?= $b['id'] ?>">
    <button name="pay_bill">Pay Bill</button>
</form>

</div>
<?php endforeach; ?>

<?php endif; ?>

<!-- HISTORY -->
<div class="card">
<h3>Billing History</h3>

<table border="1" width="100%">
<tr>
<th>Date</th>
<th>Due</th>
<th>Total</th>
<th>Status</th>
<th>Paid At</th>
</tr>

<?php foreach($history as $h): ?>
<tr>
<td><?= $h['generated_date'] ?></td>
<td><?= $h['due_date'] ?></td>
<td>₱<?= number_format($h['total_due'],2) ?></td>
<td><?= ucfirst($h['status']) ?></td>
<td><?= $h['paid_at'] ?? '-' ?></td>
</tr>
<?php endforeach; ?>

</table>
</div>

</div>
</div>

</body>
</html>
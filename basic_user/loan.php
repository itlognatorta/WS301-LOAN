<?php
session_start();
require_once __DIR__ . '/../db_connect_new.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$maxLoan = 10000;

/* =========================
   TOTAL BORROWED (APPROVED)
========================= */
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount),0)
    FROM loan_transactions
    WHERE user_id = ?
    AND status = 'approved'
");
$stmt->execute([$user_id]);
$totalBorrowed = (float)$stmt->fetchColumn();

/* =========================
   TOTAL PAID (RESTORES CREDIT)
========================= */
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount_paid),0)
    FROM loan_payment
    WHERE u_id = ?
");
$stmt->execute([$user_id]);
$totalPaid = (float)$stmt->fetchColumn();

/* =========================
   ACTIVE LOAN (REAL DEBT)
========================= */
$activeLoan = max(0, $totalBorrowed - $totalPaid);

/* =========================
   AVAILABLE CREDIT (REAL LIMIT)
========================= */
$availableCredit = $maxLoan - $activeLoan;


/* =========================
   TOTAL REQUESTED (OPTIONAL DISPLAY ONLY)
   ❗ DO NOT USE FOR LIMIT CHECK
========================= */
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount),0)
    FROM loan_requests
    WHERE user_id = ?
    AND status IN ('pending','approved')
");
$stmt->execute([$user_id]);
$totalRequested = (float)$stmt->fetchColumn();


/* =========================
   APPLY LOAN
========================= */
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $amount = (float)($_POST['amount'] ?? 0);
    $months = (int)($_POST['tenure_months'] ?? 0);

    /* =========================
       BASIC VALIDATION
    ========================= */
    if ($amount < 5000) {
        $message = "❌ Minimum loan is ₱5,000.";
    }
    elseif ($months <= 0) {
        $message = "❌ Invalid loan duration.";
    }
    else {

        /* =========================
           RE-CALCULATE CREDIT (SAFE)
        ========================= */
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount),0)
            FROM loan_transactions
            WHERE user_id = ?
            AND status = 'approved'
        ");
        $stmt->execute([$user_id]);
        $totalBorrowed = (float)$stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount_paid),0)
            FROM loan_payment
            WHERE u_id = ?
        ");
        $stmt->execute([$user_id]);
        $totalPaid = (float)$stmt->fetchColumn();

        $activeLoan = max(0, $totalBorrowed - $totalPaid);
        $availableCredit = $maxLoan - $activeLoan;

        /* =========================
           REQUEST LIMIT CHECK (10K TOTAL REQUEST LIMIT)
        ========================= */
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount),0)
            FROM loan_requests
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $totalRequested = (float)$stmt->fetchColumn();

        if (($totalRequested + $amount) > $maxLoan) {
            $message = "❌ Cannot request loan. You've already reached the ₱10,000 request limit.";
        }

        /* =========================
           CREDIT CHECK
        ========================= */
        elseif ($amount > $availableCredit) {
            $message = "❌ Not enough credit. Remaining: ₱" . number_format($availableCredit,2);
        }

        /* =========================
           INSERT REQUEST
        ========================= */
        else {

            $stmt = $pdo->prepare("
                INSERT INTO loan_requests
                (user_id, amount, tenure_months, status, created_at)
                VALUES (?, ?, ?, 'pending', NOW())
            ");

            $stmt->execute([$user_id, $amount, $months]);

            header("Location: loan.php");
            exit;
        }
    }
}


$availableCredit = $maxLoan - $activeLoan;
$remainingCredit = $availableCredit;
$remainingRequest = max(0, $maxLoan - $totalRequested);

if (!isset($remainingCredit)) {
    $remainingCredit = 0;
}

if (!isset($remainingRequest)) {
    $remainingRequest = 0;
}

?>

<!DOCTYPE html>
<html>
<head>
<title>Loan</title>
<link rel="stylesheet" href="dashboard.css">


<script>
function openConfirm() {
    document.getElementById("confirmModal").classList.add("active");
}
function closeConfirm() {
    document.getElementById("confirmModal").classList.remove("active");
}
function submitLoan() {
    document.getElementById("confirmModal").classList.remove("active");
    document.getElementById("successModal").classList.add("active");
}
function finalSubmit() {
    document.getElementById("successModal").classList.remove("active");
    document.getElementById("loanForm").submit();
}
</script>

</head>

<body>

<div class="container">
<?php include 'sidebar.php'; ?>

<div class="main">

<h2>Loan Dashboard</h2>

<?php if ($message): ?>
<div class="success" style="background:#dc2626;">
    <?= $message ?>
</div>
<?php endif; ?>

<!-- ================= CARDS ================= -->
<div class="cards">

    <div class="card-box">
        <h3>Maximum Loan</h3>
        <p>₱ <?= number_format($maxLoan, 2) ?></p>
    </div>

    <div class="card-box">
        <h3>Remaining Credit</h3>
        <p>₱ <?= number_format($remainingCredit, 2) ?></p>
    </div>

    <div class="card-box">
        <h3>Remaining Request</h3>
        <p>₱ <?= number_format($remainingRequest, 2) ?></p>
    </div>

</div>

<!-- ================= LOAN FORM ================= -->
<form method="POST" class="card" id="loanForm">

    <label>Amount</label>
    <input type="number"
           name="amount"
           min="5000"
           max="<?= min($remainingRequest, $remainingCredit) ?>"
           required>

    <label>Tenure (Months)</label>
    <select name="tenure_months" required>
        <option value="1">1 Month</option>
        <option value="3">3 Months</option>
        <option value="6">6 Months</option>
        <option value="12">12 Months</option>
    </select>

    <?php if ($remainingRequest <= 0 || $remainingCredit <= 0): ?>
        <button disabled style="background:gray;">Limit Reached</button>
    <?php else: ?>
        <button type="button" onclick="openConfirm()">Apply Loan</button>
    <?php endif; ?>

</form>

<!-- ================= TRANSACTIONS ================= -->
<h2>Transactions</h2>

<div class="card">
<table>
<tr>
    <th>ID</th>
    <th>Amount</th>
    <th>Months</th>
    <th>Status</th>
</tr>

<?php if (!empty($transactions)): ?>
    <?php foreach ($transactions as $t): ?>
        <tr>
            <td><?= $t['id'] ?></td>
            <td>₱<?= number_format($t['amount'],2) ?></td>
            <td><?= $t['tenure_months'] ?></td>
            <td><?= $t['status'] ?></td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
<tr>
    <td colspan="4">No transactions</td>
</tr>
<?php endif; ?>

</table>
</div>

</div>
</div>

<!-- MODALS -->
<div class="modal-overlay" id="confirmModal">
    <div class="modal-box">
        <h3>Confirm Loan</h3>
        <p>Proceed with loan request?</p>
        <div class="modal-actions">
            <button onclick="closeConfirm()">No</button>
            <button onclick="submitLoan()">Yes</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="successModal">
    <div class="modal-box">
        <h3>Success</h3>
        <p>Loan submitted for approval.</p>
        <div class="modal-actions">
            <button onclick="finalSubmit()">OK</button>
        </div>
    </div>
</div>

</body>
</html>
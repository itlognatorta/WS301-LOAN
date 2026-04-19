<?php
session_start();
require_once __DIR__ . '/../db_connect_new.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

/* ================= MAX LOAN LIMIT ================= */
$maxLoan = 10000;

/* ================= USED LOAN ================= */
$stmt = $pdo->prepare("
    SELECT SUM(amount)
    FROM loan_requests
    WHERE user_id = ? 
    AND status IN ('pending', 'approved')
");

$stmt->execute([$user_id]);
$usedLoan = (float) ($stmt->fetchColumn() ?? 0);

$remainingLoan = $maxLoan - $usedLoan;
if ($remainingLoan < 0) $remainingLoan = 0;

/* ================= APPLY LOAN ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $amount = (float) ($_POST['amount'] ?? 0);
    $months = (int) ($_POST['tenure_months'] ?? 0);

    // 🚨 HARD LIMIT CHECK
if ($usedLoan >= $maxLoan) {
    die("Your amount exceeded the maximum loan limit (₱10,000).");
}

// 🚨 CHECK IF NEW LOAN EXCEEDS LIMIT
if (($usedLoan + $amount) > $maxLoan) {
    die("Your requested amount exceeds your remaining loan limit.");
}

    if ($amount >= 5000 && $amount <= $remainingLoan && $months > 0) {

        try {
            $stmt = $pdo->prepare("
                INSERT INTO loan_requests 
                (user_id, amount, tenure_months, status, created_at)
                VALUES (?, ?, ?, 'pending', NOW())
            ");

            $stmt->execute([$user_id, $amount, $months]);

            header("Location: loan.php");
            exit;

        } catch (PDOException $e) {
            die("Database Error: " . $e->getMessage());
        }
    }
}

/* ================= TRANSACTIONS ================= */
$stmt = $pdo->prepare("
    SELECT * FROM loan_requests
    WHERE user_id=?
    ORDER BY id DESC
");
$stmt->execute([$user_id]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<title>Loan</title>
<link rel="stylesheet" href="dashboard.css">

<script>
// OPEN CONFIRM MODAL
function openConfirm() {
    document.getElementById("confirmModal").classList.add("active");
}

// CLOSE CONFIRM MODAL
function closeConfirm() {
    document.getElementById("confirmModal").classList.remove("active");
}

// GO TO SUCCESS MODAL
function submitLoan() {
    document.getElementById("confirmModal").classList.remove("active");
    document.getElementById("successModal").classList.add("active");
}

// FINAL SUBMIT (POST)
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

<!-- ================= CARDS ================= -->
<div class="cards">

    <div class="card-box">
        <h3>Maximum Loan</h3>
        <p>₱ <?= number_format($maxLoan, 2) ?></p>
    </div>

    <div class="card-box">
        <h3>Remaining Loan</h3>
        <p>₱ <?= number_format($remainingLoan, 2) ?></p>
    </div>

    <div class="card-box">
        <h3>Used Loan</h3>
        <p>₱ <?= number_format($usedLoan, 2) ?></p>
    </div>

</div>

<?php if ($remainingLoan <= 0): ?>
    <div class="success" style="background:#dc2626;">
        ❌ Your amount exceeded the maximum loan limit (₱10,000). You cannot apply for a new loan.
    </div>
<?php endif; ?>

<!-- ================= LOAN FORM ================= -->
<form action="loan.php" method="POST" class="card" id="loanForm">

    <label>Amount</label>
    <input type="number"
           name="amount"
           min="5000"
           max="<?= $remainingLoan ?>"
           placeholder="₱5,000 - ₱10,000" required>
           

    <label>Tenure (Months)</label>
    <select name="tenure_months" required>
        <option value="1">1 Month</option>
        <option value="3">3 Months</option>
        <option value="6">6 Months</option>
        <option value="12">12 Months</option>
        <option value="24">24 Months</option>
        <option value="32">32 Months</option>
    </select>

    <!-- IMPORTANT: type=button -->
    <?php if ($remainingLoan <= 0): ?>
    <button disabled style="background:gray; cursor:not-allowed;">
        Loan Limit Reached
    </button>
<?php else: ?>
    <button type="button" onclick="openConfirm()">Apply Loan</button>
<?php endif; ?>

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

<?php if(!empty($transactions)): ?>
    <?php foreach($transactions as $t): ?>
        <tr>
            <td><?= $t['id'] ?></td>
            <td>₱ <?= number_format($t['amount'], 2) ?></td>
            <td><?= $t['tenure_months'] ?></td>
            <td class="<?= strtolower($t['status']) ?>">
                <?= $t['status'] ?>
            </td>
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

<!-- ================= CONFIRM MODAL ================= -->
<div class="modal-overlay" id="confirmModal">
    <div class="modal-box">
        <h3>Confirm Loan</h3>
        <p>Are you sure you want to loan this amount?</p>

        <div class="modal-actions">
            <button onclick="closeConfirm()">No</button>
            <button onclick="submitLoan()">Yes</button>
        </div>
    </div>
</div>

<!-- ================= SUCCESS MODAL ================= -->
<div class="modal-overlay" id="successModal">
    <div class="modal-box">
        <h3>Success</h3>
        <p>Loan submitted. Wait for admin approval.</p>

        <div class="modal-actions">
            <button onclick="finalSubmit()">OK</button>
        </div>
    </div>
</div>

</body>
</html>
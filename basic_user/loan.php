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
    FROM loan_transactions
    WHERE user_id = ? AND status = 'approved'
");
$stmt->execute([$user_id]);
$usedLoan = (float) ($stmt->fetchColumn() ?? 0);

$remainingLoan = $maxLoan - $usedLoan;
if ($remainingLoan < 0) $remainingLoan = 0;

/* ================= APPLY LOAN ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $amount = (float) ($_POST['amount'] ?? 0);
    $months = (int) ($_POST['tenure_months'] ?? 0);

    // VALIDATION
    if ($amount >= 5000 && $amount <= $remainingLoan && $months > 0) {

        $stmt = $pdo->prepare("
            INSERT INTO loan_transactions 
            (user_id, amount, tenure_months, status, created_at)
            VALUES (?, ?, ?, 'pending', NOW())
        ");

        $stmt->execute([$user_id, $amount, $months]);

        // Refresh to show new data
        header("Location: loan.php");
        exit;
    }
}

/* ================= TRANSACTIONS ================= */
$stmt = $pdo->prepare("
    SELECT * FROM loan_transactions
    WHERE user_id=?
    ORDER BY no DESC
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
window.addEventListener("DOMContentLoaded", function () {

    const form = document.getElementById("loanForm");

    form.addEventListener("submit", function(e) {
        e.preventDefault();
        document.getElementById("confirmModal").classList.add("active");
    });
});

function closeConfirm() {
    document.getElementById("confirmModal").classList.remove("active");
}

function submitLoan() {
    document.getElementById("confirmModal").classList.remove("active");
    document.getElementById("successModal").classList.add("active");
}

function closeSuccess() {
    document.getElementById("successModal").classList.remove("active");
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

<!-- ================= LOAN FORM ================= -->
<form action="loan.php" method="POST" class="card" id="loanForm">

    <label>Amount</label>
    <input type="number"
           name="amount"
           min="5000"
           max="<?= $remainingLoan ?>"
           required>

    <label>Tenure (Months)</label>
    <select name="tenure_months" required>
        <option value="1">1 Month</option>
        <option value="3">3 Months</option>
        <option value="6">6 Months</option>
        <option value="12">12 Months</option>
        <option value="24">24 Months</option>
        <option value="32">32 Months</option>
    </select>

    <button type="submit">Apply Loan</button>
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

<?php if(!empty($transactions)): ?>
    <?php foreach($transactions as $t): ?>
        <tr>
            <td><?= $t['no'] ?></td>
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
        <p>Are you sure you want to loan with this amount?</p>

        <div class="modal-actions">
            <button class="btn-back" onclick="closeConfirm()">No</button>
            <button class="btn-next" onclick="submitLoan()">Yes</button>
        </div>
    </div>
</div>

<!-- ================= SUCCESS MODAL ================= -->
<div class="modal-overlay" id="successModal">
    <div class="modal-box">
        <h3>Success</h3>
        <p>Apply Loan Successfully, wait for the admin to confirm application.</p>

        <div class="modal-actions">
            <button class="btn-next" onclick="finalSubmit()">OK</button>
        </div>
    </div>
</div>

</body>
</html>
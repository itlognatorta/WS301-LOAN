<?php
session_start();
require_once _DIR_ . '/../db_connect_new.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$maxLoan = 10000;
$minLoan = 5000;
$modalMessage = '';

/* ==========================================================
   1. REVOLVING CREDIT LOGIC
========================================================== */

// Sum of PENDING requests
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM loan_requests WHERE user_id = ? AND status = 'pending'");
$stmt->execute([$user_id]);
$pendingAmount = (float)$stmt->fetchColumn();

// RULE: Approved loans move to the billing table. 
// We sum UNPAID principal. Credit ONLY "turns back" when status becomes 'paid'.
$stmt = $pdo->prepare("SELECT COALESCE(SUM(loan_principal), 0) FROM billing WHERE user_id = ? AND status = 'unpaid'");
$stmt->execute([$user_id]);
$unpaidPrincipal = (float)$stmt->fetchColumn();

$totalUtilized = $pendingAmount + $unpaidPrincipal;
$availableCredit = max(0, $maxLoan - $totalUtilized);

/* ==========================================================
   2. HANDLE LOAN APPLICATION (POST)
========================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)($_POST['amount'] ?? 0);
    $months = (int)($_POST['tenure_months'] ?? 0);

    // Backend validation just in case JS is bypassed
    if ($amount >= $minLoan && ($totalUtilized + $amount) <= $maxLoan) {
        $stmt = $pdo->prepare("INSERT INTO loan_requests (user_id, amount, tenure_months, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
        $stmt->execute([$user_id, $amount, $months]);
        header("Location: loan.php?status=success");
        exit;
    }
}

$stmt = $pdo->prepare("SELECT id, amount, tenure_months, status, created_at FROM loan_requests WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$user_id]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Loan Dashboard</title>
    <link rel="stylesheet" href="dashboard.css">
    <script>
        // MODAL VALIDATION LOGIC
        function validateAndOpenConfirm() {
            const amount = parseFloat(document.getElementsByName("amount")[0].value);
            const available = <?= $availableCredit ?>;
            const min = <?= $minLoan ?>;

            if (isNaN(amount) || amount < min) {
                showErrorModal("Minimum loan is ₱" + min.toLocaleString());
                return;
            }
            if (amount > available) {
                showErrorModal("Insufficient Credit. Your available limit is ₱" + available.toLocaleString());
                return;
            }

            document.getElementById("confirmModal").classList.add("active");
        }

        function showErrorModal(msg) {
            document.getElementById("errorMsg").innerText = msg;
            document.getElementById("errorModal").classList.add("active");
        }

        function closeModals() {
            const modals = document.querySelectorAll('.modal-overlay');
            modals.forEach(m => m.classList.remove('active'));
        }

        function submitForm() {
            document.getElementById("loanForm").submit();
        }

        // Auto-show success modal if redirected
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('status') === 'success') {
                document.getElementById("successModal").classList.add("active");
            }
        }
    </script>
</head>
<body>

<div class="container">
    <?php include 'sidebar.php'; ?>

    <div class="main">
        <h2>Loan Dashboard</h2>

        <div class="cards">
            <div class="card-box">
                <h3>Total Credit Pool</h3>
                <p>₱ <?= number_format($maxLoan, 2) ?></p>
            </div>
            <div class="card-box">
                <h3>Utilized Loan</h3>
                <p style="color: #dc2626;">₱ <?= number_format($totalUtilized, 2) ?></p>
            </div>
            <div class="card-box">
                <h3>Available Credit</h3>
                <p style="color: #059669;">₱ <?= number_format($availableCredit, 2) ?></p>
            </div>
        </div>

        <div class="card application-section">
            <h3 style="margin-bottom: 15px;">New Loan Request</h3>
            <form method="POST" id="loanForm">
                <label>Amount to Borrow</label>
                <input type="number" name="amount" placeholder="Enter amount" required>

                <label>Tenure (Months)</label>
                <select name="tenure_months" required>
                    <option value="1">1 Month</option>
                    <option value="3">3 Months</option>
                    <option value="6">6 Months</option>
                    <option value="12">12 Months</option>
                </select>

                <?php if ($availableCredit < $minLoan): ?>
                    <button type="button" class="btn-disabled" disabled style="background:#9ca3af;">
                        Limit Reached (Must pay existing debt)
                    </button>
                <?php else: ?>
                    <button type="button" class="btn-primary" onclick="validateAndOpenConfirm()">Apply for Loan</button>
                <?php endif; ?>
            </form>
        </div>

        <h3 style="margin-top: 30px;">Request History</h3>
        <div class="card">
            <table width="100%">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Tenure</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td><?= date('M d, Y', strtotime($t['created_at'])) ?></td>
                            <td>₱<?= number_format($t['amount'], 2) ?></td>
                            <td><?= $t['tenure_months'] ?> mo.</td>
                            <td>
                                <span class="status-text" style="font-weight:bold; color: <?= ($t['status'] == 'pending' ? '#d97706' : ($t['status'] == 'approved' ? '#059669' : '#dc2626')) ?>">
                                    <?= ucfirst($t['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-overlay" id="errorModal">
    <div class="modal-box">
        <h3 style="color:#dc2626;">Validation Error</h3>
        <p id="errorMsg"></p>
        <div class="modal-actions">
            <button onclick="closeModals()" style="background:#black;">Close</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="confirmModal">
    <div class="modal-box">
        <h3>Confirm Request</h3>
        <p>Would you like to submit this loan for review?</p>
        <div class="modal-actions">
            <button onclick="closeModals()" style="background:#black;">Cancel</button>
            <button onclick="submitForm()" style="background:#2563eb; color:white;">Yes, Submit</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="successModal">
    <div class="modal-box">
        <h3>Success</h3>
        <p>Loan Request submitted successfully! It is now pending admin review.</p>
        <div class="modal-actions">
            <button onclick="window.location.href='loan.php'" style="background:#059669; color:white;">Continue</button>
        </div>
    </div>
</div>

</body>
</html>
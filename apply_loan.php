<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/config.php';

$user_id = $_SESSION['user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    $tenure = intval($_POST['tenure_months']);

    // Validate amount thousands 5k-10k initial
    if ($amount < MIN_LOAN || $amount > MAX_INITIAL_LOAN || $amount % 1000 !== 0) {
        $message = 'Invalid amount.';
    } elseif (!in_array($tenure, [1,3,6,12])) {
        $message = 'Invalid tenure.';
    } elseif ($dbConnected && $pdo) {
        // Check current max
        $stmt = $pdo->prepare("SELECT current_loan_amount, max_loan_amount FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        if ($amount + $user['current_loan_amount'] > $user['max_loan_amount']) {
            $message = 'Exceeds max loan limit.';
        } else {
            $tx_id = gen_tx_id('LN');
            $stmt = $pdo->prepare("INSERT INTO loan_transactions (tx_id, user_id, type, amount, tenure_months) VALUES (?, ?, 'apply', ?, ?)");
            $stmt->execute([$tx_id, $user_id, $amount, $tenure]);
            $message = 'Loan request #' . $tx_id . ' submitted! Pending admin approval.';
        }
    } else {
        $message = 'DB error.';
    }
}

header('Location: dashboard.php?msg=' . urlencode($message));
exit;
?>


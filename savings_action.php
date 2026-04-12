<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/db_connect_new.php';
require_once __DIR__ . '/includes/config.php';

$user_id = $_SESSION['user_id'];
$account_type = $_SESSION['account_type'] ?? '';
$message = '';

if ($account_type !== 'premium') {
    header('Location: dashboard.php?msg=Premium only');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $amount = floatval($_POST['amount']);

    $stmt = $pdo->prepare("SELECT savings_balance FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $balance = $stmt->fetchColumn();

    if ($action === 'deposit') {
        if ($amount < SAVINGS_MIN_DEPOSIT || $amount > SAVINGS_MAX_DEPOSIT) {
            $message = 'Deposit 100-1000 only.';
        } elseif ($balance + $amount > SAVINGS_MAX) {
            $message = 'Max 100k savings.';
        } else {
            $new_balance = $balance + $amount;
            $tx_id = gen_tx_id('SV');
            $pdo->prepare("UPDATE users SET savings_balance = ? WHERE id = ?")->execute([$new_balance, $user_id]);
            $pdo->prepare("INSERT INTO savings_transactions (tx_id, user_id, category, amount, balance_after, status) VALUES (?, ?, 'deposit', ?, ?, 'completed')")->execute([$tx_id, $user_id, $amount, $new_balance]);
            $message = 'Deposited PHP ' . number_format($amount);
        }
    } elseif ($action === 'withdraw') {
        if ($amount < WITHDRAW_MIN || $amount > WITHDRAW_MAX_DAY || $amount > $balance) {
            $message = 'Withdraw 500-5k if sufficient balance.';
        } else {
            $tx_id = gen_tx_id('SV');
            $pdo->prepare("INSERT INTO savings_requests (user_id, amount) VALUES (?, ?)")->execute([$user_id, $amount]);
            $pdo->prepare("INSERT INTO savings_transactions (tx_id, user_id, category, amount, status) VALUES (?, ?, 'withdrawal', ?, 'pending')")->execute([$tx_id, $user_id, $amount]);
            $message = 'Withdraw request #' . $tx_id . ' submitted.';
        }
    }
}

header('Location: dashboard.php?msg=' . urlencode($message));
exit;
?>


<?php
session_start();
require_once __DIR__ . '/../db_connect_new.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$totalReceived = $pdo->prepare("SELECT SUM(amount_received) FROM moneyback_transactions WHERE user_id=?");
$totalReceived->execute([$user_id]);
$totalReceived = $totalReceived->fetchColumn();
if(!$totalReceived) $totalReceived = 0;

$history = $pdo->prepare("SELECT * FROM moneyback_transactions WHERE user_id=? ORDER BY received_at DESC");
$history->execute([$user_id]);

$currentSavings = $pdo->prepare("SELECT savings_balance FROM users WHERE id=?");
$currentSavings->execute([$user_id]);
$currentSavings = $currentSavings->fetchColumn();

$latest = $pdo->query("SELECT * FROM company_earnings ORDER BY year DESC LIMIT 1")->fetch();

$premiumCount = $pdo->query("SELECT COUNT(*) FROM users WHERE account_type='premium'")->fetchColumn();
$share = ($premiumCount > 0) ? (($latest['total_income'] * 0.02) / $premiumCount) : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Money Back Earned</title>
    <link rel="stylesheet" href="premiumdb.css">
</head>
<body>

<div class="container">
<?php include 'sidebar.php'; ?>

<div class="main">

    <h2 class="page-title">Money Back Earned</h2>

    <div class="moneyback-grid">

        <div class="money-card">
            <span>Latest Company Earnings Year</span>
            <h3><?= $latest['year'] ?></h3>
        </div>

        <div class="money-card">
            <span>Your Latest Annual Share</span>
            <h3>₱<?= number_format($share,2) ?></h3>
        </div>

        <div class="money-card savings">
            <span>Total Money Back Received</span>
            <h3>₱<?= number_format($totalReceived,2) ?></h3>
        </div>

        <div class="money-card highlight">
            <span>Current Savings Balance</span>
            <h3>₱<?= number_format($currentSavings,2) ?></h3>
        </div>

    </div>

    <div class="history-box">
        <h3>Money Back Transactions</h3>

        <table>
            <tr>
                <th>Year</th>
                <th>Amount Received</th>
                <th>Date Credited</th>
            </tr>

            <?php while($row = $history->fetch()): ?>
            <tr>
                <td><?= $row['earnings_year'] ?></td>
                <td>₱<?= number_format($row['amount_received'],2) ?></td>
                <td><?= date('F d, Y h:i A', strtotime($row['received_at'])) ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>

</div>
</div>

</body>
</html>
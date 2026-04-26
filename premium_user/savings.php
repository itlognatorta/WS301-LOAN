<?php
session_start();
require_once __DIR__ . '/../db_connect_new.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";

/* =========================================================
   FETCH USER
========================================================= */
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$current_balance = $user['savings_balance'];

/* =========================================================
   AUTO DOWNGRADE IF ZERO SAVINGS FOR 3 MONTHS
========================================================= */
if($current_balance <= 0 && !empty($user['last_savings_activity'])){
    $last = strtotime($user['last_savings_activity']);
    if($last <= strtotime("-90 days")){
        $pdo->prepare("UPDATE users SET account_type='basic' WHERE id=?")->execute([$user_id]);
        session_destroy();
        header("Location: ../login.php?downgraded=1");
        exit;
    }
}

/* =========================================================
   DEPOSIT PROCESS
========================================================= */
if(isset($_POST['deposit_submit'])){

    $amount = floatval($_POST['deposit_amount']);

    if($amount < 100 || $amount > 1000){
        $error = "Deposit must be between ₱100 and ₱1000 only.";
    }
    elseif(($current_balance + $amount) > 100000){
        $error = "Savings cannot exceed ₱100,000 maximum.";
    }
    else{

        $newBalance = $current_balance + $amount;
        $tx_id = "SV-".date("Ymd")."-".rand(1000,9999);

        $pdo->prepare("
            INSERT INTO savings_transactions
            (tx_id,user_id,category,amount,balance_after,status)
            VALUES (?,?,?,?,?,'completed')
        ")->execute([$tx_id,$user_id,'deposit',$amount,$newBalance]);

        $pdo->prepare("
            UPDATE users 
            SET savings_balance=?, last_savings_activity=CURDATE()
            WHERE id=?
        ")->execute([$newBalance,$user_id]);

        $message = "Deposit completed successfully.";
        $current_balance = $newBalance;
    }
}

/* =========================================================
   WITHDRAWAL REQUEST PROCESS
========================================================= */
if(isset($_POST['withdraw_submit'])){

    $amount = floatval($_POST['withdraw_amount']);

    $todayCount = $pdo->prepare("
        SELECT COUNT(*) FROM savings_requests 
        WHERE user_id=? AND DATE(created_at)=CURDATE()
    ");
    $todayCount->execute([$user_id]);
    $withdrawCount = $todayCount->fetchColumn();

    if($withdrawCount >= 5){
        $error = "Maximum of 5 withdrawal requests per day only.";
    }
    elseif($amount < 500 || $amount > 5000){
        $error = "Withdrawal request must be ₱500 minimum and ₱5000 maximum.";
    }
    elseif($amount > $current_balance){
        $error = "Insufficient savings balance.";
    }
    else{

        $pdo->prepare("
            INSERT INTO savings_requests (user_id,amount,status)
            VALUES (?,?,'pending')
        ")->execute([$user_id,$amount]);

        $request_id = $pdo->lastInsertId();

        $tx_id = "SV-".date("Ymd")."-".rand(1000,9999);

        $pdo->prepare("
            INSERT INTO savings_transactions
            (tx_id,user_id,category,amount,balance_after,status,request_id)
            VALUES (?,?,?,?,?,'pending',?)
        ")->execute([
            $tx_id,
            $user_id,
            'withdrawal',
            $amount,
            $current_balance,
            $request_id
        ]);

        $message = "Withdrawal request submitted and pending admin approval.";
    }
}

/* =========================================================
   SEARCH + FILTER
========================================================= */
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';

$sql = "SELECT * FROM savings_transactions WHERE user_id=?";
$params = [$user_id];

if($filter == 'deposit' || $filter == 'withdrawal'){
    $sql .= " AND category=?";
    $params[] = $filter;
}

if(!empty($search)){
    $sql .= " AND tx_id LIKE ?";
    $params[] = "%$search%";
}

$sql .= " ORDER BY no DESC";

$transactions = $pdo->prepare($sql);
$transactions->execute($params);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Premium Savings</title>
    <link rel="stylesheet" href="premiumdb.css">
</head>
<body>

<div class="container">
<?php include 'sidebar.php'; ?>

<div class="main">
<div class="savings-wrapper">

<h2>Savings Management</h2>

<?php if($message): ?>
<div class="savings-alert success"><?= $message ?></div>
<?php endif; ?>

<?php if($error): ?>
<div class="savings-alert danger"><?= $error ?></div>
<?php endif; ?>

<div class="balance-card">
    <h3>Current Savings Balance</h3>
    <h1>₱<?= number_format($current_balance,2) ?></h1>
    <p>Maximum Savings Limit: ₱100,000</p>
</div>

<div class="savings-panels">

    <!-- DEPOSIT -->
    <div class="savings-panel">
        <h3>Deposit to Savings</h3>
        <form method="POST">
            <label>Amount (₱100 - ₱1000)</label>
            <input type="number" step="0.01" name="deposit_amount" required>
            <button name="deposit_submit">Deposit Now</button>
        </form>
    </div>

    <!-- WITHDRAW -->
    <div class="savings-panel">
        <h3>Request Withdrawal</h3>
        <form method="POST">
            <label>Amount (₱500 - ₱5000)</label>
            <input type="number" step="0.01" name="withdraw_amount" required>
            <button name="withdraw_submit">Submit Withdrawal Request</button>
        </form>
    </div>

</div>

<br><br>

<h3 class="premium-loan-history-title">Savings Transactions</h3>

<form method="GET" class="filter-box">
    <input type="text" name="search" placeholder="Search Transaction ID..." value="<?= htmlspecialchars($search) ?>">
    <select name="filter">
        <option value="">All Transactions</option>
        <option value="deposit" <?= $filter=='deposit'?'selected':'' ?>>Deposit Only</option>
        <option value="withdrawal" <?= $filter=='withdrawal'?'selected':'' ?>>Withdrawal Only</option>
    </select>
    <button type="submit">Search</button>
</form>

<table class="premium-loan-table">
<tr>
    <th>No.</th>
    <th>Date</th>
    <th>Transaction ID</th>
    <th>Category</th>
    <th>Amount</th>
    <th>Current Amount</th>
    <th>Status</th>
</tr>

<?php foreach($transactions as $t): ?>
<tr>
    <td><?= $t['no'] ?></td>
    <td><?= date("m/d/y", strtotime($t['created_at'])) ?></td>
    <td><?= $t['tx_id'] ?></td>
    <td><?= ucfirst($t['category']) ?></td>
    <td>₱<?= number_format($t['amount'],2) ?></td>
    <td>₱<?= number_format($t['balance_after'],2) ?></td>
    <td class="<?= $t['status'] ?>"><?= ucfirst($t['status']) ?></td>
</tr>
<?php endforeach; ?>

</table>

</div>
</div>
</div>

</body>
</html>
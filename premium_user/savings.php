<?php 
require_once __DIR__ . '/../db_connect_new.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if(isset($_POST['deposit'])){
    $amount = $_POST['amount'];

    if($amount < 100 || $amount > 1000){
        $error = "Invalid deposit (100 - 1000 only)";
    } else {

        $tx_id = "SV".date("Ymd").rand(1000,9999);

        $pdo->prepare("
            INSERT INTO savings_transactions 
            (tx_id,user_id,category,amount,status)
            VALUES (?,?,?,?, 'completed')
        ")->execute([$tx_id,$user_id,'deposit',$amount]);

        $pdo->prepare("
            UPDATE users SET savings_balance = savings_balance + ?
            WHERE id=?
        ")->execute([$amount,$user_id]);
    }
}

$transactions = $pdo->prepare("SELECT * FROM savings_transactions WHERE user_id=? ORDER BY no DESC");
$transactions->execute([$user_id]);
?>

<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="premiumdb.css">
</head>
<body>

<div class="container">

<?php include 'sidebar.php'; ?>

<div class="main">

<h2>Savings</h2>

<?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>

<form method="POST">
<input type="number" name="amount" placeholder="100 - 1000" required>
<button name="deposit">Deposit</button>
</form>

<table>
<tr>
<th>TX ID</th>
<th>Type</th>
<th>Amount</th>
<th>Status</th>
</tr>

<?php foreach($transactions as $t): ?>
<tr>
<td><?= $t['tx_id'] ?></td>
<td><?= ucfirst($t['category']) ?></td>
<td>₱<?= number_format($t['amount'],2) ?></td>
<td><?= ucfirst($t['status']) ?></td>
</tr>
<?php endforeach; ?>

</table>

</div>
</div>

</body>
</html>
<?php 
session_start();
require_once __DIR__ . '/../db_connect_new.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /WS301-LOAN/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if(isset($_POST['apply'])){

    $amount = $_POST['amount'];
    $months = $_POST['months'];

    // VALIDATION
    if($amount < 5000 || $amount > 10000 || $amount % 1000 != 0){
        $error = "Invalid loan amount (₱5,000 - ₱10,000, by thousands only)";
    } else {

        $tx_id = "LN".date("Ymd").rand(1000,9999);

        $stmt = $pdo->prepare("
            INSERT INTO loan_transactions (tx_id,user_id,type,amount,tenure_months)
            VALUES (?,?,?,?,?)
        ");
        $stmt->execute([$tx_id,$user_id,'apply',$amount,$months]);

        $success = "Loan request submitted successfully!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Loan</title>

<link rel="stylesheet" href="premiumdb.css">

</head>

<body>

<div class="container">

<?php include 'sidebar.php'; ?>

<div class="main">

<h2>Apply Loan</h2>

<!-- 🔷 SUCCESS / ERROR -->
<?php if(isset($error)): ?>
<div class="card" style="color:#f87171;">
<?= $error ?>
</div>
<?php endif; ?>

<?php if(isset($success)): ?>
<div class="card success">
<?= $success ?>
</div>
<?php endif; ?>

<!-- 🔷 LOAN FORM -->
<div class="card">

<h3>Loan Application</h3>

<form method="POST">

<label>Loan Amount</label>
<input type="number" name="amount" placeholder="₱5,000 - ₱10,000" required>

<label>Months to Pay</label>
<select name="months" required>
<option value="3">3 months</option>
<option value="6">6 months</option>
<option value="12">12 months</option>
</select>

<button name="apply">Apply Loan</button>

</form>

</div>

<!-- 🔷 LOAN RULES -->
<div class="card">
<h3>Loan Rules</h3>

<p>✔ Minimum Loan: ₱5,000</p>
<p>✔ Maximum Loan: ₱10,000</p>
<p>✔ Must be in thousands (e.g., 5000, 6000)</p>
<p>✔ Pay on time to increase your loan limit</p>

</div>

</div>
</div>

</body>
</html>
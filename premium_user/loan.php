<?php 
session_start();
require_once __DIR__ . '/../db_connect_new.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /WS301-LOAN/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

/* =========================
   APPLY LOAN
========================= */
if(isset($_POST['apply'])){

    $amount = (int) $_POST['amount'];
    $months = (int) $_POST['months'];

    // VALIDATION
    if($amount < 5000 || $amount > 10000 || $amount % 1000 != 0){
        $error = "Invalid loan amount (₱5,000 - ₱10,000, by thousands only)";
    } elseif(!in_array($months, [1,3,6,12])){
        $error = "Invalid loan duration selected.";
    } else {

        // 🔷 INTEREST CALCULATION (3%)
        $interest = $amount * 0.03;
        $net_amount = $amount - $interest;

        // 🔷 GENERATE TRANSACTION ID
        $tx_id = "LN".date("YmdHis").rand(100,999);

        // 🔷 INSERT TRANSACTION (DEFAULT STATUS = PENDING)
        $stmt = $pdo->prepare("
            INSERT INTO loan_transactions 
            (tx_id, user_id, type, amount, interest, net_amount, tenure_months, status)
            VALUES (?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([
            $tx_id,
            $user_id,
            'apply',
            $amount,
            $interest,
            $net_amount,
            $months,
            'Pending'
        ]);

        $success = "Loan request submitted! Net Amount: ₱".number_format($net_amount,2);
    }
}

/* =========================
   FETCH USER LOAN TRANSACTIONS
========================= */
$stmt = $pdo->prepare("
    SELECT * FROM loan_transactions 
    WHERE user_id = ? 
    ORDER BY no DESC
");
$stmt->execute([$user_id]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
<option value="1">1 month</option>
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
<p>✔ 3% interest deducted immediately</p>
<p>✔ Pay on time to increase your loan limit</p>

</div>

<!-- 🔷 TRANSACTIONS TABLE -->
<div class="card">

<h3>Loan Transactions</h3>

<table width="100%" border="1" cellpadding="10" cellspacing="0">
<tr>
    <th>TX ID</th>
    <th>Amount</th>
    <th>Interest</th>
    <th>Net</th>
    <th>Months</th>
    <th>Status</th>
    <th>Date</th>
</tr>

<?php if($transactions): ?>
    <?php foreach($transactions as $row): ?>
    <tr>
        <td><?= $row['tx_id'] ?></td>
        <td>₱<?= number_format($row['amount'],2) ?></td>
        <td>₱<?= number_format($row['interest'],2) ?></td>
        <td>₱<?= number_format($row['net_amount'],2) ?></td>
        <td><?= $row['tenure_months'] ?></td>
        <td>
            <?php 
                if($row['status'] == 'Pending') echo "<span style='color:orange;'>Pending</span>";
                elseif($row['status'] == 'Approved') echo "<span style='color:green;'>Approved</span>";
                else echo "<span style='color:red;'>Rejected</span>";
            ?>
        </td>
        <td><?= date("M d, Y", strtotime($row['created_at'])) ?></td>
    </tr>
    <?php endforeach; ?>
<?php else: ?>
<tr>
<td colspan="7">No transactions found.</td>
</tr>
<?php endif; ?>

</table>

</div>

</div>
</div>

</body>
</html>
<?php 
require_once __DIR__ . '/../db_connect_new.php';

$user_id = $_SESSION['user_id'];

if(isset($_POST['apply'])){

    $amount = $_POST['amount'];
    $months = $_POST['months'];

    // VALIDATION
    if($amount < 5000 || $amount > 10000 || $amount % 1000 != 0){
        $error = "Invalid loan amount";
    } else {

        $tx_id = "LN".date("Ymd").rand(1000,9999);

        $stmt = $pdo->prepare("
            INSERT INTO loan_transactions (tx_id,user_id,type,amount,tenure_months)
            VALUES (?,?,?,?,?)
        ");
        $stmt->execute([$tx_id,$user_id,'apply',$amount,$months]);

        $success = "Loan request submitted";
    }
}
?>

<!DOCTYPE html>
<html>
<body>

<?php include 'sidebar.php'; ?>

<h2>Apply Loan</h2>

<form method="POST">
<input type="number" name="amount" placeholder="5000 - 10000">
<select name="months">
<option value="3">3 months</option>
<option value="6">6 months</option>
<option value="12">12 months</option>
</select>

<button name="apply">Apply</button>
</form>

</body>
</html>
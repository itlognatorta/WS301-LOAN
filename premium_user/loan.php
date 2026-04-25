<?php 
session_start();
require_once __DIR__ . '/../db_connect_new.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /WS301-LOAN/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

$max_credit = 10000;

/* CURRENT USED LOAN */
$stmt = $pdo->prepare("SELECT current_loan_amount FROM users WHERE id=?");
$stmt->execute([$user_id]);
$current_loan = $stmt->fetchColumn();

if(!$current_loan){
    $current_loan = 0;
}

$available_credit = $max_credit - $current_loan;

/* APPLY LOAN */
if(isset($_POST['apply'])){

    $amount = (int) $_POST['amount'];
    $months = (int) $_POST['months'];

    if($amount < 5000 || $amount > 10000 || $amount % 1000 != 0){
        $error = "Loan must be ₱5,000 to ₱10,000 only.";
    }
    elseif(!in_array($months,[1,3,6,12])){
        $error = "Invalid months selected.";
    }
    elseif($current_loan >= $max_credit){
        $error = "Maximum credit limit reached.";
    }
    elseif(($current_loan + $amount) > $max_credit){
        $remaining = $max_credit - $current_loan;
        $error = "You can only loan up to ₱".number_format($remaining,2);
    }
    else{

        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM loan_requests 
            WHERE user_id=? AND status='pending'
        ");
        $stmt->execute([$user_id]);

        if($stmt->fetchColumn() > 0){
            $error = "You still have a pending request.";
        }else{

            $stmt = $pdo->prepare("
                INSERT INTO loan_requests 
                (user_id, amount, tenure_months, status, created_at)
                VALUES (?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([$user_id,$amount,$months]);

            $success = "Loan request submitted successfully.";
        }
    }
}

/* REQUEST HISTORY */
$stmt = $pdo->prepare("
    SELECT * FROM loan_requests 
    WHERE user_id=? ORDER BY id DESC
");
$stmt->execute([$user_id]);
$requests = $stmt->fetchAll();
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

<div class="premium-loan-wrapper">

    <h2 class="premium-loan-title">Loan Dashboard</h2>

    <?php if($error): ?><div class="premium-loan-alert error"><?= $error ?></div><?php endif; ?>
    <?php if($success): ?><div class="premium-loan-alert success"><?= $success ?></div><?php endif; ?>

    <div class="premium-loan-topcards">
        <div class="premium-loan-creditbox">
            <h4>Maximum Credit</h4>
            <span>₱<?= number_format($max_credit,2) ?></span>
        </div>

        <div class="premium-loan-creditbox">
            <h4>Used Loan</h4>
            <span class="loan-used">₱<?= number_format($current_loan,2) ?></span>
        </div>

        <div class="premium-loan-creditbox">
            <h4>Available Credit</h4>
            <span class="loan-available">₱<?= number_format($available_credit,2) ?></span>
        </div>
    </div>

    <div class="premium-loan-panel">
        <h3>New Loan Request</h3>

        <form method="POST" class="premium-loan-form">
            <label>Amount to Borrow</label>
            <input type="number" name="amount" placeholder="Enter amount" min="5000" max="<?= $available_credit ?>" required>

            <label>Tenure (Months)</label>
            <select name="months">
                <option value="1">1 Month</option>
                <option value="3">3 Months</option>
                <option value="6">6 Months</option>
                <option value="12">12 Months</option>
            </select>

            <button name="apply">Apply for Loan</button>
        </form>
    </div>

    <h3 class="premium-loan-history-title">Request History</h3>

    <div class="premium-loan-panel">
        <table class="premium-loan-table">
            <tr>
                <th>Date</th>
                <th>Amount</th>
                <th>Tenure</th>
                <th>Status</th>
            </tr>

            <?php foreach($requests as $r): ?>
            <tr>
                <td><?= date("M d, Y", strtotime($r['created_at'])) ?></td>
                <td>₱<?= number_format($r['amount'],2) ?></td>
                <td><?= $r['tenure_months'] ?> mo.</td>
                <td class="<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

</div>

</div>
</div>
</body>
</html>
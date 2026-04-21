<?php
session_start();
if (!isset($_SESSION['admin_id'])) header('Location: login.php');

require_once __DIR__ . '/../db_connect_new.php';
require_once __DIR__ . '/../includes/config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $request_id = intval($_POST['request_id']);
    $action = $_POST['action'];

    if ($action === 'approve') {

        /* ================= GET REQUEST ================= */
        $stmt = $pdo->prepare("SELECT * FROM loan_requests WHERE id = ?");
        $stmt->execute([$request_id]);
        $request = $stmt->fetch();

        if ($request) {

            $user_id = $request['user_id'];
            $amount = $request['amount'];
            $months = $request['tenure_months'];

            /* ================= TRANSACTION ID (FIXED UNIQUE) ================= */
            $tx_id = 'TX-' . date('YmdHis') . '-' . bin2hex(random_bytes(3));

            /* ================= LOAN COMPUTATION ================= */
            $interest = $amount * 0.03;
            $received_amount = $amount - $interest;
            $monthly = $amount / $months;
            $dueDate = date('Y-m-d', strtotime('+28 days'));
            $total = $monthly + $interest;

            /* ================= INSERT LOANS ================= */
            $stmt = $pdo->prepare("
                INSERT INTO loans 
                (user_id, principal, interest, received_amount, tenure_months) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $amount, $interest, $received_amount, $months]);

            /* ================= INSERT LOAN TRANSACTIONS =================
               IMPORTANT: PK is `no`
            */
            $stmt = $pdo->prepare("
                INSERT INTO loan_transactions
                (tx_id, user_id, amount, tenure_months, status, created_at)
                VALUES (?, ?, ?, ?, 'approved', NOW())
            ");
            $stmt->execute([$tx_id, $user_id, $amount, $months]);

            /* 🔥 GET CORRECT FK (loan_transactions.no) */
            $loan_no = $pdo->lastInsertId();

            /* ================= CREATE BILLING ================= */
            $stmt = $pdo->prepare("
                INSERT INTO billing (
                    user_id,
                    loan_id,
                    generated_date,
                    due_date,
                    loan_principal,
                    monthly_amount,
                    interest,
                    total_due,
                    status
                )
                VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, 'pending')
            ");

            $stmt->execute([
                $user_id,
                $loan_no,
                $dueDate,
                $amount,
                $monthly,
                $interest,
                $total
            ]);

            /* ================= UPDATE REQUEST ================= */
            $stmt = $pdo->prepare("
                UPDATE loan_requests 
                SET status = 'approved', approved_by = ?, approved_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['admin_id'], $request_id]);

            /* ================= UPDATE USER LOAN ================= */
            $stmt = $pdo->prepare("
                UPDATE users 
                SET current_loan_amount = current_loan_amount + ?
                WHERE id = ?
            ");
            $stmt->execute([$amount, $user_id]);

            $message = "Loan approved. Transaction ID: $tx_id";
        }

    } elseif ($action === 'reject') {

        $reason = $_POST['reason'] ?? '';

        $stmt = $pdo->prepare("
            UPDATE loan_requests 
            SET status = 'rejected', rejection_reason = ?
            WHERE id = ?
        ");
        $stmt->execute([$reason, $request_id]);

        $message = 'Loan rejected.';
    }
}

/* ================= PENDING REQUESTS ================= */
$stmt = $pdo->prepare("
    SELECT lr.*, u.name, u.email 
    FROM loan_requests lr 
    JOIN users u ON lr.user_id = u.id 
    WHERE lr.status = 'pending' 
    ORDER BY lr.created_at DESC
");
$stmt->execute();
$pending_requests = $stmt->fetchAll();

/* ================= ALL LOANS ================= */
$stmt = $pdo->prepare("
    SELECT l.*, u.name, u.email 
    FROM loans l 
    JOIN users u ON l.user_id = u.id 
    ORDER BY l.id DESC
");
$stmt->execute();
$all_loans = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - Loan Management</title>
<link rel="stylesheet" href="../index.css">

<style>
body {
    background: linear-gradient(160deg, #04112b 0%, #0b1b42 35%, #122d5f 100%);
    color: #e8efff;
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
.admin-container { max-width: 1400px; margin: 24px auto; padding: 20px; }
.header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
.tabs { display:flex; gap:10px; margin-bottom:20px; }
.tab { padding:10px 20px; background:#1a1a2e; border:none; color:#fff; cursor:pointer; border-radius:5px; }
.tab.active { background:#4a92ff; }
.table-container { background:rgba(255,255,255,0.05); padding:20px; border-radius:10px; }
table { width:100%; border-collapse:collapse; }
th, td { padding:12px; border-bottom:1px solid rgba(255,255,255,0.1); }
th { color:#4a92ff; }
.btn-approve { background:green; color:white; padding:5px 10px; border:none; }
.btn-reject { background:red; color:white; padding:5px 10px; border:none; }
.message { margin:10px 0; padding:10px; background:#28a745; color:#fff; }
</style>
</head>

<body>

<div class="admin-container">

<div class="header">
    <h1>Loan Management</h1>
</div>

<?php if ($message): ?>
<div class="message"><?= $message ?></div>
<?php endif; ?>

<div class="tabs">
    <button class="tab active" onclick="showTab('pending')">Pending</button>
    <button class="tab" onclick="showTab('all')">All Loans</button>
</div>

<!-- PENDING -->
<div id="pending-tab" class="table-container">
<h3>Pending Requests</h3>

<table>
<tr>
<th>ID</th><th>User</th><th>Email</th><th>Amount</th><th>Months</th><th>Action</th>
</tr>

<?php foreach ($pending_requests as $r): ?>
<tr>
<td><?= $r['id'] ?></td>
<td><?= $r['name'] ?></td>
<td><?= $r['email'] ?></td>
<td>₱<?= number_format($r['amount'],2) ?></td>
<td><?= $r['tenure_months'] ?></td>
<td>
<form method="POST">
<input type="hidden" name="request_id" value="<?= $r['id'] ?>">
<input type="hidden" name="action" value="approve">
<button class="btn-approve">Approve</button>
</form>
</td>
</tr>
<?php endforeach; ?>

</table>
</div>

<!-- ALL -->
<div id="all-tab" class="table-container" style="display:none;">
<h3>All Loans</h3>

<table>
<tr>
<th>ID</th><th>User</th><th>Principal</th><th>Interest</th><th>Status</th>
</tr>

<?php foreach ($all_loans as $l): ?>
<tr>
<td><?= $l['id'] ?></td>
<td><?= $l['name'] ?></td>
<td>₱<?= number_format($l['principal'],2) ?></td>
<td>₱<?= number_format($l['interest'],2) ?></td>
<td><?= $l['status'] ?></td>
</tr>
<?php endforeach; ?>

</table>
</div>

</div>

<script>
function showTab(tab){
    document.querySelectorAll('.table-container').forEach(e=>e.style.display='none');
    document.getElementById(tab+'-tab').style.display='block';
}
</script>

</body>
</html>
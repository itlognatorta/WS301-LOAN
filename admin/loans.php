<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../db_connect_new.php';

$message = "";

/* ================= PROCESS APPROVE / REJECT ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $request_id = intval($_POST['request_id']);
    $action = $_POST['action'];

    /* get selected request */
    $stmt = $pdo->prepare("SELECT * FROM loan_requests WHERE id=?");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if($request){

        $user_id = $request['user_id'];
        $amount = $request['amount'];
        $months = $request['tenure_months'];

        /* ================= APPROVE ================= */
        if($action === 'approve'){

            $interest = $amount * 0.03;
            $net_amount = $amount - $interest;
            $monthly = $amount / $months;
            $dueDate = date('Y-m-d', strtotime('+28 days'));

            $tx_id = 'TX-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));

            /* 1. INSERT TRANSACTION */
       
            $stmt = $pdo->prepare("
            INSERT INTO loan_transactions
            (tx_id, user_id, type, amount, interest, net_amount, tenure_months, status, admin_note, created_at)
            VALUES (?, ?, 'apply', ?, ?, ?, ?, 'approved', 'Admin Approved', NOW())
            ");

            $stmt->execute([
            $tx_id,
            $user_id,
            $amount,
            $interest,
            $net_amount,
            $months
            ]);

            // ✅ THIS is the REAL FIX
            $loan_tx_id = $pdo->lastInsertId(); // MUST be used

            /* 2. INSERT MASTER LOAN */
            $stmt = $pdo->prepare("
            INSERT INTO loans
            (user_id, principal, interest, received_amount, tenure_months, current_month, status)
            VALUES (?, ?, ?, ?, ?, 1, 'active')
            ");

            $stmt->execute([
            $user_id,
            $amount,
            $interest,
            $net_amount,
            $months
            ]);

            /* 3. CREATE BILLING */
            $total_due = $monthly + $interest;

            $stmt = $pdo->prepare("
            INSERT INTO billing
            (user_id, loan_id, generated_date, due_date, loan_principal, monthly_amount, interest, total_due, status)
            VALUES (?, ?, CURDATE(), ?, ?, ?, ?, ?, 'pending')
            ");

            $stmt->execute([
            $user_id,
            $loan_tx_id,   // ✅ MUST be INTEGER from lastInsertId
            $dueDate,
            $amount,
            $monthly,
            $interest,
            $total_due
            ]);

            /* 4. UPDATE REQUEST */
            $stmt = $pdo->prepare("
                UPDATE loan_requests
                SET status='approved', approved_by=?, approved_at=NOW()
                WHERE id=?
            ");
            $stmt->execute([$_SESSION['admin_id'], $request_id]);

            /* 5. UPDATE USER CREDIT USAGE */
            $stmt = $pdo->prepare("
                UPDATE users
                SET current_loan_amount = current_loan_amount + ?
                WHERE id=?
            ");
            $stmt->execute([$amount, $user_id]);

            $message = "Loan request approved successfully.";
        }

        /* ================= REJECT ================= */
        if($action === 'reject'){

            $reason = trim($_POST['reason']);

            if($reason == ""){
                $message = "Rejection reason is required.";
            }else{

                $stmt = $pdo->prepare("
                    UPDATE loan_requests
                    SET status='rejected', rejection_reason=?, approved_by=?, approved_at=NOW()
                    WHERE id=?
                ");
                $stmt->execute([
                    $reason,
                    $_SESSION['admin_id'],
                    $request_id
                ]);

                $tx_id = 'TX-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));

                $stmt = $pdo->prepare("
                    INSERT INTO loan_transactions
                    (tx_id, user_id, type, amount, interest, net_amount, tenure_months, status, admin_note, created_at)
                    VALUES (?, ?, 'apply', ?, 0, 0, ?, 'rejected', ?, NOW())
                ");
                $stmt->execute([
                    $tx_id,
                    $user_id,
                    $amount,
                    $months,
                    $reason
                ]);

                $message = "Loan request rejected.";
            }
        }
    }
}

/* ================= FETCH DATA ================= */
$stmt = $pdo->prepare("
    SELECT lr.*, u.name, u.email
    FROM loan_requests lr
    JOIN users u ON lr.user_id = u.id
    WHERE lr.status='pending'
    ORDER BY lr.id DESC
");
$stmt->execute();
$pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT lt.*, u.name, u.email
    FROM loan_transactions lt
    JOIN users u ON lt.user_id = u.id
    ORDER BY lt.no DESC
");
$stmt->execute();
$all_loans = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - Loan Management</title>
<link rel="stylesheet" href="../index.css">

<style>
body{
    background: linear-gradient(160deg,#04112b 0%,#0b1b42 35%,#122d5f 100%);
    color:#e8efff;
    margin:0;
    font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
}
.admin-container{max-width:1400px;margin:24px auto;padding:20px;}
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;}
.tabs{display:flex;gap:10px;margin-bottom:20px;}
.tab{padding:10px 20px;background:#1a1a2e;border:none;color:#fff;cursor:pointer;border-radius:5px;}
.tab.active{background:#4a92ff;}
.table-container{background:rgba(255,255,255,0.05);padding:20px;border-radius:10px;}
table{width:100%;border-collapse:collapse;}
th,td{padding:12px;border-bottom:1px solid rgba(255,255,255,0.1);}
th{color:#4a92ff;}
.btn-approve{background:green;color:white;padding:6px 12px;border:none;cursor:pointer;}
.btn-reject{background:red;color:white;padding:6px 12px;border:none;cursor:pointer;}
.message{margin:10px 0;padding:10px;background:#28a745;color:#fff;}
textarea{width:100%;padding:5px;}
</style>
</head>

<body>

<div class="admin-container">

<div class="header">
    <h1>Loan Management</h1>

    <div style="display:flex; gap:10px;">
        <button onclick="goBack()" style="padding:8px 16px;background:#4a92ff;border:none;color:white;border-radius:5px;cursor:pointer;">
            ← Back
        </button>

        <button onclick="location.reload()" style="padding:8px 16px;background:#28a745;border:none;color:white;border-radius:5px;cursor:pointer;">
            ↻ Refresh
        </button>
    </div>
</div>

<?php if($message): ?>
<div class="message"><?= $message ?></div>
<?php endif; ?>

<div class="tabs">
    <button class="tab active" onclick="showTab('pending')">Pending Requests</button>
    <button class="tab" onclick="showTab('all')">Loan Transactions</button>
</div>

<!-- PENDING -->
<div id="pending-tab" class="table-container">
<h3>Pending Loan Requests</h3>

<table>
<tr>
<th>ID</th>
<th>User</th>
<th>Email</th>
<th>Amount</th>
<th>Months</th>
<th>Action</th>
</tr>

<?php foreach($pending_requests as $r): ?>
<tr>
<td><?= $r['id'] ?></td>
<td><?= htmlspecialchars($r['name']) ?></td>
<td><?= htmlspecialchars($r['email']) ?></td>
<td>₱<?= number_format($r['amount'],2) ?></td>
<td><?= $r['tenure_months'] ?></td>
<td>

<form method="POST" style="margin-bottom:5px;">
<input type="hidden" name="request_id" value="<?= $r['id'] ?>">
<input type="hidden" name="action" value="approve">
<button class="btn-approve">Approve</button>
</form>

<button class="btn-reject" onclick="openRejectModal(<?= $r['id'] ?>)">Reject</button>

</td>
</tr>
<?php endforeach; ?>
</table>
</div>

<!-- ALL -->
<div id="all-tab" class="table-container" style="display:none;">
<h3>All Loan Transactions</h3>

<table>
<tr>
<th>TX ID</th>
<th>User</th>
<th>Amount</th>
<th>Interest</th>
<th>Net Amount</th>
<th>Months</th>
<th>Status</th>
<th>Date</th>
</tr>

<?php foreach($all_loans as $l): ?>
<tr>
<td><?= $l['tx_id'] ?></td>
<td><?= htmlspecialchars($l['name']) ?></td>
<td>₱<?= number_format($l['amount'],2) ?></td>
<td>₱<?= number_format($l['interest'],2) ?></td>
<td>₱<?= number_format($l['net_amount'],2) ?></td>
<td><?= $l['tenure_months'] ?></td>
<td><?= ucfirst($l['status']) ?></td>
<td><?= date("M d, Y", strtotime($l['created_at'])) ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

</div>

<script>
function showTab(tab){
    document.querySelectorAll('.table-container').forEach(e=>e.style.display='none');
    document.getElementById(tab+'-tab').style.display='block';
    document.querySelectorAll('.tab').forEach(e=>e.classList.remove('active'));
    event.target.classList.add('active');
}
function goBack(){
    window.location.href = 'admindashboard.php';
}
</script>

</body>
</html>
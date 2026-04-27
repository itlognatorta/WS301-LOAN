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

            $loan_tx_id = $pdo->lastInsertId();

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

            $loan_id = $pdo->lastInsertId();

            /* 3. CREATE BILLING */
            $total_due = $monthly;

            $stmt = $pdo->prepare("
            INSERT INTO billing
            (user_id, loan_id, generated_date, due_date, loan_principal, monthly_amount, interest, penalty, total_due, status)
            VALUES (?, ?, CURDATE(), ?, ?, ?, ?, 0, ?, 'pending')
            ");

            $stmt->execute([
                $user_id,
                $loan_id,
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
<link rel="stylesheet" href="admin.css">

<style>
body{margin:0;min-height:100vh;color:#e8f1ff;background:radial-gradient(circle at 15% 12%, rgba(96,165,250,0.2), transparent 18%),linear-gradient(180deg,#020814 0%,#07132b 45%,#112149 100%);font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;}
*{box-sizing:border-box;}
.admin-container{max-width:1360px;margin:24px auto;padding:24px;}
.header{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:16px;margin-bottom:24px;}
.header h1{margin:0;font-size:clamp(2rem,2.6vw,2.7rem);}
.tabs{display:flex;flex-wrap:wrap;gap:12px;margin-bottom:20px;}
.tab{padding:12px 20px;border:none;border-radius:999px;background:rgba(255,255,255,0.08);color:#dbeafe;cursor:pointer;transition:transform 0.18s ease,background 0.18s ease;}
.tab:hover{transform:translateY(-1px);background:rgba(56,189,248,0.16);}
.tab.active{background:#2563eb;color:#fff;}
.table-container{background:rgba(255,255,255,0.05);border:1px solid rgba(56,189,248,0.12);border-radius:24px;padding:24px;box-shadow:0 28px 70px rgba(0,0,0,0.16);}
table{width:100%;border-collapse:collapse;margin-top:18px;}
th,td{padding:16px 14px;text-align:left;border-bottom:1px solid rgba(255,255,255,0.08);}
thead th{color:#94a3b8;font-size:.84rem;letter-spacing:.08em;text-transform:uppercase;}
tbody tr:hover{background:rgba(255,255,255,0.04);}
.btn-approve{background:#22c55e;color:#03181e;padding:10px 14px;border:none;border-radius:999px;cursor:pointer;transition:transform .16s ease;}
.btn-approve:hover{transform:translateY(-1px);}
.btn-reject{background:#ef4444;color:#fff;padding:10px 14px;border:none;border-radius:999px;cursor:pointer;transition:transform .16s ease;}
.btn-reject:hover{transform:translateY(-1px);}
.message{margin:14px 0;padding:14px 18px;border-radius:18px;background:rgba(34,197,94,0.14);color:#dcfce7;border:1px solid rgba(34,197,94,0.25);}
textarea{width:100%;padding:12px;border-radius:14px;border:1px solid rgba(255,255,255,0.12);background:rgba(255,255,255,0.05);color:#e8f1ff;}
@media(max-width:860px){.header{flex-direction:column;align-items:flex-start;}.tabs{width:100%;}.table-container{padding:18px;}}
</style>
</head>

<body>
    <div class="admin-shell">
        <aside class="sidebar">
            <div class="brand">Loan Admin</div>
            <nav class="sidebar-nav">
                <a href="admindashboard.php" class="nav-item"><span class="icon">🏠</span><span>Dashboard</span></a>
                <a href="users.php" class="nav-item"><span class="icon">👥</span><span>Manage Users</span></a>
                <a href="loans.php" class="nav-item active"><span class="icon">💰</span><span>Loan Requests</span></a>
                <a href="savings.php" class="nav-item"><span class="icon">🏦</span><span>Savings Requests</span></a>
                <a href="billing.php" class="nav-item"><span class="icon">🧾</span><span>Billing Overview</span></a>
            </nav>
        </aside>
        <main class="admin-content">
            <div class="admin-container">
                <div class="page-header">
                    <div class="title-block">
                        <p class="breadcrumb">Dashboard <span>›</span> Loan Requests</p>
                        <h1 class="page-title">Loan Management</h1>
                        <p class="page-subtitle">Approve or reject loan applications and browse completed loan transactions with fast controls.</p>
                    </div>
                    <div style="display:flex; gap:12px; flex-wrap: wrap;">
                        <button onclick="goBack()" class="btn btn-secondary">← Back</button>
                        <button onclick="location.reload()" class="btn btn-primary">↻ Refresh</button>
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
        </main>
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
<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../db_connect_new.php';

$billings = [];
if ($pdo) {
    $update = $pdo->prepare("UPDATE billing SET penalty = monthly_amount * 0.02 WHERE due_date < CURDATE() AND status != 'completed'");
    $update->execute();

    $stmt = $pdo->prepare(
        "SELECT b.*, u.name AS user_name, u.email, COALESCE(l.principal, b.loan_principal) AS loan_amount
        FROM billing b
        JOIN users u ON b.user_id = u.id
        LEFT JOIN loans l ON b.loan_id = l.id
        ORDER BY b.due_date ASC"
    );
    $stmt->execute();
    $billings = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Billing Overview</title>
<link rel="stylesheet" href="../index.css">
<link rel="stylesheet" href="admin.css">
<style>
body { margin: 0; min-height: 100vh; color: #e8f1ff; background: radial-gradient(circle at top right, rgba(56, 189, 248, 0.16), transparent 18%), linear-gradient(180deg, #020816 0%, #071227 46%, #0f1e43 100%); font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }
* { box-sizing: border-box; }
.container { max-width: 1360px; margin: 24px auto; padding: 24px; }
.header { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 24px; }
.header h1 { margin: 0; font-size: clamp(2rem, 2.5vw, 2.6rem); }
.card { background: rgba(255,255,255,0.05); padding: 26px; border-radius: 24px; border: 1px solid rgba(56,189,248,0.12); box-shadow: 0 28px 72px rgba(0,0,0,0.16); }
table { width: 100%; border-collapse: collapse; margin-top: 18px; }
th, td { padding: 16px 14px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.08); }
thead th { color: #94a3b8; font-size: 0.84rem; letter-spacing: 0.08em; text-transform: uppercase; }
tbody tr:hover { background: rgba(255,255,255,0.04); }
.status-pending { color: #fbbf24; }
.status-overdue { color: #f87171; }
.status-completed { color: #34d399; }
.btn-primary { background: #38bdf8; color: #031425; border: none; border-radius: 999px; padding: 12px 20px; cursor: pointer; }
.btn-primary:hover { background: #0ea5e9; }
@media (max-width: 860px) { .container { padding: 18px; } .header { flex-direction: column; align-items: flex-start; } }
</style>
</head>
<body>
    <div class="admin-shell">
        <aside class="sidebar">
            <div class="brand">Loan Admin</div>
            <nav class="sidebar-nav">
                <a href="admindashboard.php" class="nav-item"><span class="icon">🏠</span><span>Dashboard</span></a>
                <a href="users.php" class="nav-item"><span class="icon">👥</span><span>Manage Users</span></a>
                <a href="loans.php" class="nav-item"><span class="icon">💰</span><span>Loan Requests</span></a>
                <a href="savings.php" class="nav-item"><span class="icon">🏦</span><span>Savings Requests</span></a>
                <a href="billing.php" class="nav-item active"><span class="icon">🧾</span><span>Billing Overview</span></a>
            </nav>
        </aside>
        <main class="admin-content">
            <div class="admin-container">
                <div class="page-header">
                    <div class="title-block">
                        <p class="breadcrumb">Dashboard <span>›</span> Billing Overview</p>
                        <h1 class="page-title">Billing Overview</h1>
                        <p class="page-subtitle">View pending dues, overdue penalties, and invoice history for every active loan.</p>
                    </div>
                    <div>
                        <a href="admindashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                    </div>
                </div>
                <div class="card">
        <?php if (empty($billings)): ?>
            <p>No billing records found.</p>
        <?php else: ?>
            <table class="billing-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Loan Amount</th>
                        <th>Monthly</th>
                        <th>Interest</th>
                        <th>Penalty</th>
                        <th>Total Due</th>
                        <th>Due Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($billings as $row): ?>
                    <?php
                        $total = $row['total_due'] + $row['penalty'];
                        $displayStatus = $row['status'];
                        if ($row['status'] !== 'completed' && $row['due_date'] < date('Y-m-d')) {
                            $displayStatus = 'overdue';
                        }
                        $statusClass = 'status-' . $displayStatus;
                    ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td>
                            <div class="user-cell">
                                <span class="name"><?= htmlspecialchars($row['user_name']) ?></span>
                                <span class="email"><?= htmlspecialchars($row['email']) ?></span>
                            </div>
                        </td>
                        <td>₱<?= number_format($row['loan_amount'], 2) ?></td>
                        <td>₱<?= number_format($row['monthly_amount'], 2) ?></td>
                        <td>₱<?= number_format($row['interest'], 2) ?></td>
                        <td>₱<?= number_format($row['penalty'], 2) ?></td>
                        <td>₱<?= number_format($total, 2) ?></td>
                        <td><?= htmlspecialchars($row['due_date']) ?></td>
                        <td><span class="status-pill <?= $statusClass ?>"><?= ucfirst($displayStatus) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>



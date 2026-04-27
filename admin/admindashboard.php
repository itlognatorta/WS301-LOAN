<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../db_connect_new.php';
require_once __DIR__ . '/../includes/config.php';

$total_users = 0;
$total_premium = 0;
$pending_regs = 0;
$total_loans = 0;
$total_earnings = 0;
$money_back = 0;
$error = '';

if ($dbConnected && $pdo) {
    $total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
    $total_premium = $pdo->query("SELECT COUNT(*) FROM users WHERE account_type = 'premium' AND status = 'active'")->fetchColumn();
    $pending_regs = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();
    $total_loans = $pdo->query("SELECT COUNT(*) FROM loans")->fetchColumn();
    $total_savings = $pdo->query("SELECT SUM(savings_balance) FROM users")->fetchColumn() ?: 0;
    $total_earnings = $pdo->query("SELECT SUM(interest) FROM loans WHERE status = 'active'")->fetchColumn() ?: 0;
    $savings_accounts = $pdo->query("SELECT COUNT(*) FROM users WHERE savings_balance > 0")->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $year = intval($_POST['year']);
    $income = floatval($_POST['income']);
    $stmt = $pdo->prepare("REPLACE INTO company_earnings (year, total_income) VALUES (?, ?)");
    if ($stmt->execute([$year, $income])) {
        $money_back = $total_premium > 0 ? ($income * 0.02) / $total_premium : 0;
        $success = 'Earnings updated. Money back can be distributed.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Loan System</title>
    <link rel="stylesheet" href="../index.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        body { margin: 0; min-height: 100vh; color: #e8f1ff; background: radial-gradient(circle at 20% 10%, rgba(56, 189, 248, 0.18), transparent 16%), linear-gradient(180deg, #020717 0%, #06102c 48%, #0f1e44 100%); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        * { box-sizing: border-box; }
        .admin-dash { max-width: 1360px; margin: 24px auto; padding: 24px; }
        .header-row { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 26px; }
        .header-row h1 { margin: 0; font-size: clamp(2rem, 2.8vw, 3rem); color: #f8fbff; letter-spacing: -0.03em; }
        .btn { border: none; border-radius: 999px; padding: 12px 22px; font-weight: 700; font-size: 0.95rem; cursor: pointer; transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease; }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary { background: #38bdf8; color: #031425; box-shadow: 0 18px 40px rgba(56, 189, 248, 0.22); }
        .btn-outline { color: #d5e2ff; background: rgba(255,255,255,0.1); border: 1px solid rgba(213,226,255,0.32); }
        .btn-outline:hover { background: rgba(255,255,255,0.16); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px; margin-bottom: 28px; }
        .stat { background: rgba(255,255,255,0.05); border: 1px solid rgba(96, 165, 250, 0.16); border-radius: 24px; padding: 24px; min-height: 140px; box-shadow: 0 24px 80px rgba(0,0,0,0.16); display: flex; flex-direction: column; justify-content: space-between; transition: transform 0.22s ease, border-color 0.22s ease; }
        .stat:hover { transform: translateY(-2px); border-color: rgba(56, 189, 248, 0.28); }
        .stat h3 { margin: 0; font-size: 2.4rem; color: #f8fbff; }
        .stat p { margin: 10px 0 0; text-transform: uppercase; letter-spacing: 0.08em; font-size: 0.88rem; color: #bbd7ff; }
        .section-card { background: rgba(255,255,255,0.05); border: 1px solid rgba(96,165,250,0.12); border-radius: 26px; box-shadow: 0 18px 50px rgba(0,0,0,0.16); margin-bottom: 24px; padding: 28px; }
        .section-card h2 { margin: 0 0 14px; color: #e5efff; font-size: 1.5rem; }
        .section-card form { display: grid; gap: 16px; max-width: 560px; }
        .section-card input { width: 100%; border-radius: 14px; border: 1px solid rgba(255,255,255,0.12); background: rgba(255,255,255,0.06); color: #e8f1ff; padding: 14px 16px; font-size: 0.96rem; }
        .section-card input::placeholder { color: rgba(226,232,255,0.7); }
        .section-card p { color: #cbd5e1; margin-top: 10px; font-size: 0.94rem; }
        @media (max-width: 840px) { .admin-dash { padding: 18px; } .header-row { flex-direction: column; align-items: flex-start; } .stats-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="admin-shell">
        <aside class="sidebar">
            <div class="brand">Loan Admin</div>
            <nav class="sidebar-nav">
                <a href="admindashboard.php" class="nav-item active"><span class="icon">🏠</span><span>Dashboard</span></a>
                <a href="users.php" class="nav-item"><span class="icon">👥</span><span>Manage Users</span></a>
                <a href="loans.php" class="nav-item"><span class="icon">💰</span><span>Loan Requests</span></a>
                <a href="savings.php" class="nav-item"><span class="icon">🏦</span><span>Savings Requests</span></a>
                <a href="billing.php" class="nav-item"><span class="icon">🧾</span><span>Billing Overview</span></a>
            </nav>
        </aside>
        <main class="admin-content">
            <div class="admin-dash">
                <div class="page-header">
                    <div class="title-block">
                        <p class="breadcrumb">Admin <span>›</span> Dashboard</p>
                        <h1 class="page-title">Admin Dashboard</h1>
                        <p class="page-subtitle">Monitor users, loan requests, savings activity, and billing status from a single unified dashboard.</p>
                    </div>
                    <div>
                        <a href="../logout.php" class="btn btn-outline">Logout</a>
                    </div>
                </div>
                <div class="stats-grid">
            <div class="stat">
                <h3><?php echo $total_users; ?></h3>
                <p>Total Users</p>
            </div>
            <div class="stat">
                <h3><?php echo $total_premium; ?></h3>
                <p>Premium Users</p>
            </div>
            <div class="stat">
                <h3><?php echo $pending_regs; ?></h3>
                <p>Pending Regs</p>
            </div>
            <div class="stat">
                <h3><?php echo $total_loans; ?></h3>
                <p>Active Loans</p>
            </div>
            <div class="stat">
                <h3>PHP <?php echo number_format($total_savings); ?></h3>
                <p>Total Savings</p>
            </div>
            <div class="stat">
                <h3>PHP <?php echo number_format($total_earnings); ?></h3>
                <p>Total Earnings</p>
            </div>
            <div class="stat">
                <h3><?php echo $savings_accounts; ?></h3>
                <p>Savings Accounts</p>
            </div>
        </div>
        <div class="section-card" style="background: rgba(255,255,255,0.08); padding: 30px; border-radius: 20px;">
            <h2>Company Earnings (for Money Back 2%)</h2>
            <form method="POST">
                <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 20px; max-width: 500px;">
                    <input type="number" name="year" placeholder="Year" required>
                    <input type="number" name="income" placeholder="Total Income" step="0.01" required>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
            <p>Formula: (Income * 0.02) / Premium Users → Add to savings.</p>
            <?php if (!empty($money_back) && $total_premium > 0): ?>
            <p>Estimated money back per premium user: PHP <?= number_format($money_back, 2) ?></p>
            <?php endif; ?>
        </div>
        </main>
    </div>
</body>
</html>


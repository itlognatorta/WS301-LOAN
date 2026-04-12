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
$error = '';

if ($dbConnected && $pdo) {
    $total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
    $total_premium = $pdo->query("SELECT COUNT(*) FROM users WHERE account_type = 'premium' AND status = 'active'")->fetchColumn();
    $pending_regs = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();
    $total_loans = $pdo->query("SELECT COUNT(*) FROM loans")->fetchColumn();
    $total_savings = $pdo->query("SELECT SUM(savings_balance) FROM users")->fetchColumn() ?: 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $year = intval($_POST['year']);
    $income = floatval($_POST['income']);
    $stmt = $pdo->prepare("REPLACE INTO company_earnings (year, total_income) VALUES (?, ?)");
    if ($stmt->execute([$year, $income])) {
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
    <style>
        body { background: linear-gradient(160deg, #04112b 0%, #0b1b42 35%, #122d5f 100%); color: #e8efff; margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .admin-dash { max-width: 1180px; margin: 24px auto; padding: 20px; }
        .header-row { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 24px; }
        .header-row h1 { margin: 0; font-size: clamp(1.75rem, 2.5vw, 2.25rem); color: #ffffff; text-shadow: 0 2px 10px rgba(0,0,0,0.35); }
        .btn { border: none; border-radius: 999px; padding: 10px 20px; font-weight: 700; font-size: 0.95rem; cursor: pointer; transition: transform .16s ease, box-shadow .16s ease, background .2s;
            box-shadow: 0 6px 18px rgba(29, 99, 255, 0.35); }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary { background: linear-gradient(135deg, #4a92ff, #758cff); color: #fff; }
        .btn-outline { color: #d5e2ff; background: rgba(255,255,255,0.12); border: 1px solid rgba(213,226,255,0.5); }
        .btn-outline:hover { background: rgba(255,255,255,0.2); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 24px; }
        .stat { position: relative; background: linear-gradient(145deg, rgba(7, 17, 33, 0.8), rgba(12, 36, 66, 0.9)); border: 1px solid rgba(136, 172, 255, 0.18); border-radius: 20px; padding: 20px 18px; min-height: 110px; box-shadow: 0 10px 25px rgba(0,0,0,0.28); overflow: hidden; }
        .stat h3 { margin: 0; font-size: 2.1rem; color: #ffffff; }
        .stat p { margin: 6px 0 0 0; text-transform: uppercase; letter-spacing: .06em; font-size: 0.88rem; color: #bbc7ea; }
        .section-card { background: rgba(5, 18, 45, 0.78); border: 1px solid rgba(170, 193, 255, 0.2); border-radius: 20px; box-shadow: 0 12px 28px rgba(0,0,0,0.22); margin-bottom: 24px; }
        .section-card h2 { margin: 0 0 12px; color: #d7e4ff; font-size: 1.4rem; }
        .section-card form { display: grid; gap: 12px; max-width: 560px; }
        .section-card input { width: 100%; border-radius: 12px; border: 1px solid rgba(202, 215, 255, 0.28); background: rgba(25, 46, 77, 0.9); color: #eef4ff; padding: 11px 13px; font-size: 0.95rem; }
        .section-card input::placeholder { color: rgba(215, 228, 255, 0.72); }
        .section-card p { color: #b9c9f5; margin-top: 10px; font-size: 0.9rem; }
        .nav-links { display: flex; flex-wrap: wrap; gap: 12px; }
        .nav-links .btn-primary { box-shadow: 0 8px 24px rgba(87, 117, 255, 0.35); }
        @media (max-width: 730px) {
            .admin-dash { padding: 18px; }
            .header-row { flex-direction: column; align-items: flex-start; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="admin-dash">
        <div style="display: flex; justify-content: space-between;">
            <h1>Admin Dashboard</h1>
            <a href="../logout.php" class="btn btn-outline">Logout</a>
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
        </div>
        <div class="nav-links" style="margin-bottom: 34px;">
            <a href="users.php" class="btn btn-primary">Manage Users</a>
            <a href="loans.php" class="btn btn-primary">Loan Requests</a>
            <a href="savings.php" class="btn btn-primary">Savings Requests</a>
            <a href="billing.php" class="btn btn-primary">Billing Overview</a>
        </div>
    </div>
</body>
</html>


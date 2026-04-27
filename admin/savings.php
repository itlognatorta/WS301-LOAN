<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../db_connect_new.php';
require_once __DIR__ . '/../includes/config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = intval($_POST['request_id']);
    $action = $_POST['action'];

    $stmt = $pdo->prepare("SELECT * FROM savings_requests WHERE id = ?");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        $message = 'Savings request not found.';
    } else {
        if ($action === 'approve') {
            if ($request['amount'] > 0) {
                $stmt = $pdo->prepare("SELECT savings_balance FROM users WHERE id = ?");
                $stmt->execute([$request['user_id']]);
                $balance = $stmt->fetchColumn();

                $stmt = $pdo->prepare("SELECT COUNT(*) FROM savings_transactions WHERE user_id = ? AND category = 'withdrawal' AND DATE(created_at) = CURDATE() AND status = 'completed'");
                $stmt->execute([$request['user_id']]);
                $withdrawals_today = $stmt->fetchColumn();

                if ($withdrawals_today >= 5) {
                    $message = 'Withdrawal limit reached for today (5 withdrawals).';
                    $stmt = $pdo->prepare("UPDATE savings_requests SET status = 'failed', admin_note = ? WHERE id = ?");
                    $stmt->execute(['Daily withdrawal limit reached', $request_id]);
                } elseif ($balance >= $request['amount']) {
                    $new_balance = $balance - $request['amount'];
                    $stmt = $pdo->prepare("UPDATE users SET savings_balance = ? WHERE id = ?");
                    $stmt->execute([$new_balance, $request['user_id']]);

                    $tx_id = 'SV-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                    $stmt = $pdo->prepare("INSERT INTO savings_transactions (tx_id, user_id, category, amount, balance_after, status, request_id, created_at) VALUES (?, ?, 'withdrawal', ?, ?, 'completed', ?, NOW())");
                    $stmt->execute([$tx_id, $request['user_id'], $request['amount'], $new_balance, $request_id]);

                    $stmt = $pdo->prepare("UPDATE savings_requests SET status = 'completed', approved_by = ?, approved_at = NOW() WHERE id = ?");
                    $stmt->execute([$_SESSION['admin_id'], $request_id]);

                    $message = 'Savings withdrawal approved successfully.';
                } else {
                    $message = 'Insufficient balance.';
                    $stmt = $pdo->prepare("UPDATE savings_requests SET status = 'failed', admin_note = ? WHERE id = ?");
                    $stmt->execute(['Insufficient balance', $request_id]);
                }
            } else {
                $message = 'Invalid savings request amount.';
            }
        } elseif ($action === 'reject') {
            $reason = trim($_POST['reason'] ?? '');
            if ($reason === '') {
                $message = 'Rejection reason is required.';
            } else {
                $stmt = $pdo->prepare("UPDATE savings_requests SET status = 'rejected', rejection_reason = ?, approved_by = ?, approved_at = NOW() WHERE id = ?");
                $stmt->execute([$reason, $_SESSION['admin_id'], $request_id]);

                $tx_id = 'SV-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $stmt = $pdo->prepare("INSERT INTO savings_transactions (tx_id, user_id, category, amount, status, admin_note, request_id, created_at) VALUES (?, ?, 'withdrawal', ?, 'rejected', ?, ?, NOW())");
                $stmt->execute([$tx_id, $request['user_id'], $request['amount'], $reason, $request_id]);

                $message = 'Savings request rejected.';
            }
        }
    }
}

// Get pending savings requests
$stmt = $pdo->prepare("SELECT sr.*, u.name, u.email FROM savings_requests sr JOIN users u ON sr.user_id = u.id WHERE sr.status = 'pending' ORDER BY sr.created_at DESC");
$stmt->execute();
$pending_requests = $stmt->fetchAll();

// Get all savings transactions
$stmt = $pdo->prepare("SELECT st.*, u.name, u.email FROM savings_transactions st JOIN users u ON st.user_id = u.id ORDER BY st.created_at DESC LIMIT 100");
$stmt->execute();
$all_transactions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Savings Management | Loan System</title>
    <link rel="stylesheet" href="../index.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        body { margin: 0; min-height: 100vh; color: #e8f1ff; background: radial-gradient(circle at top left, rgba(56, 189, 248, 0.14), transparent 16%), linear-gradient(180deg, #020816 0%, #071227 44%, #0f1d3f 100%); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        * { box-sizing: border-box; }
        .admin-container { max-width: 1360px; margin: 24px auto; padding: 24px; }
        .header { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 24px; }
        .header h1 { margin: 0; font-size: clamp(2rem, 2.5vw, 2.7rem); }
        .tabs { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 22px; }
        .tab { padding: 12px 20px; background: rgba(255,255,255,0.08); color: #dbeafe; border: none; border-radius: 999px; cursor: pointer; transition: background 0.18s ease, transform 0.18s ease; }
        .tab:hover { transform: translateY(-1px); background: rgba(56,189,248,0.16); }
        .tab.active { background: #2563eb; color: #fff; }
        .table-container { background: rgba(255,255,255,0.05); border: 1px solid rgba(56,189,248,0.12); border-radius: 24px; padding: 24px; box-shadow: 0 26px 72px rgba(0,0,0,0.16); }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 16px 14px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.08); }
        thead th { color: #94a3b8; font-size: 0.84rem; letter-spacing: 0.08em; text-transform: uppercase; }
        tbody tr:hover { background: rgba(255,255,255,0.04); }
        .btn { border: none; border-radius: 999px; padding: 10px 18px; font-weight: 700; cursor: pointer; transition: transform 0.16s ease; }
        .btn-approve { background: #22c55e; color: #03181e; }
        .btn-reject { background: #ef4444; color: #fff; }
        .message { padding: 14px 18px; margin-bottom: 22px; border-radius: 18px; background: rgba(34,197,94,0.14); color: #dcfce7; border: 1px solid rgba(34,197,94,0.25); }
        .success { background: rgba(34,197,94,0.12); color: #d9f99d; }
        .error { background: rgba(239,68,68,0.12); color: #fecaca; }
        .btn-group button { margin-right: 10px; }
        @media (max-width: 860px) { .admin-container { padding: 18px; } .header, .tabs { flex-direction: column; align-items: flex-start; } }
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
                <a href="savings.php" class="nav-item active"><span class="icon">🏦</span><span>Savings Requests</span></a>
                <a href="billing.php" class="nav-item"><span class="icon">🧾</span><span>Billing Overview</span></a>
            </nav>
        </aside>
        <main class="admin-content">
            <div class="admin-container">
                <div class="page-header">
                    <div class="title-block">
                        <p class="breadcrumb">Dashboard <span>›</span> Savings Requests</p>
                        <h1 class="page-title">Savings Management</h1>
                        <p class="page-subtitle">Manage withdrawals and savings approvals while tracking request history and transaction activity.</p>
                    </div>
                    <div>
                        <a href="admindashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                    </div>
                </div>

    <?php if ($message): ?>
    <div class="message success"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="tabs">
        <button class="tab active" onclick="showTab('pending')">Pending Requests (<?php echo count($pending_requests); ?>)</button>
        <button class="tab" onclick="showTab('all')">All Transactions</button>
    </div>

    <div id="pending-tab" class="table-container">
        <h3>Pending Savings Requests</h3>
        <?php if (empty($pending_requests)): ?>
        <p>No pending requests.</p>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Email</th>
                    <th>Amount</th>
                    <th>Type</th>
                    <th>Requested</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending_requests as $request): ?>
                <tr>
                    <td><?php echo $request['id']; ?></td>
                    <td><?php echo htmlspecialchars($request['name']); ?></td>
                    <td><?php echo htmlspecialchars($request['email']); ?></td>
                    <td>₱<?php echo number_format($request['amount'], 2); ?></td>
                    <td><?php echo $request['amount'] > 0 ? 'Withdrawal' : 'Deposit'; ?></td>
                    <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-approve" onclick="return confirm('Approve this savings request?')">Approve</button>
                        </form>
                        <form method="POST" style="display: inline;" onsubmit="return confirmReject()">
                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="reason" id="rejectReason<?php echo $request['id']; ?>">
                            <button type="submit" class="btn btn-reject">Reject</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div id="all-tab" class="table-container" style="display: none;">
        <h3>All Savings Transactions</h3>
        <table>
            <thead>
                <tr>
                    <th>TX ID</th>
                    <th>User</th>
                    <th>Email</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Balance After</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_transactions as $tx): ?>
                <tr>
                    <td><?php echo htmlspecialchars($tx['tx_id']); ?></td>
                    <td><?php echo htmlspecialchars($tx['name']); ?></td>
                    <td><?php echo htmlspecialchars($tx['email']); ?></td>
                    <td><?php echo ucfirst($tx['category']); ?></td>
                    <td>₱<?php echo number_format($tx['amount'], 2); ?></td>
                    <td>₱<?php echo number_format($tx['balance_after'] ?? 0, 2); ?></td>
                    <td><?php echo ucfirst($tx['status']); ?></td>
                    <td><?php echo date('M d, Y H:i', strtotime($tx['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
        </main>
    </div>

<script>
function showTab(tab) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.table-container').forEach(c => c.style.display = 'none');

    document.querySelector(`[onclick="showTab('${tab}')"]`).classList.add('active');
    document.getElementById(`${tab}-tab`).style.display = 'block';
}

function confirmReject() {
    const reason = prompt('Enter rejection reason:');
    if (reason === null) return false;
    const form = event.target;
    form.querySelector('input[name="reason"]').value = reason;
    return confirm('Reject this savings request?');
}
</script>
</body>
</html>


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
        // Get request details
        $stmt = $pdo->prepare("SELECT * FROM savings_requests WHERE id = ?");
        $stmt->execute([$request_id]);
        $request = $stmt->fetch();

        if ($request) {
            // Check balance for withdrawal
            if ($request['amount'] > 0) { // Assuming positive amount is withdrawal
                $stmt = $pdo->prepare("SELECT savings_balance FROM users WHERE id = ?");
                $stmt->execute([$request['user_id']]);
                $balance = $stmt->fetchColumn();

                if ($balance >= $request['amount']) {
                    // Approve withdrawal
                    $new_balance = $balance - $request['amount'];
                    $stmt = $pdo->prepare("UPDATE users SET savings_balance = ? WHERE id = ?");
                    $stmt->execute([$new_balance, $request['user_id']]);

                    // Create transaction
                    $tx_id = 'SV-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                    $stmt = $pdo->prepare("INSERT INTO savings_transactions (tx_id, user_id, category, amount, balance_after, status, request_id) VALUES (?, ?, 'withdrawal', ?, ?, 'completed', ?)");
                    $stmt->execute([$tx_id, $request['user_id'], $request['amount'], $new_balance, $request_id]);

                    // Update request
                    $stmt = $pdo->prepare("UPDATE savings_requests SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
                    $stmt->execute([$_SESSION['admin_id'], $request_id]);

                    // TODO: Send notification and bank transfer
                    $message = 'Savings withdrawal approved successfully.';
                } else {
                    $message = 'Insufficient balance.';
                }
            }
        }
    } elseif ($action === 'reject') {
        $reason = $_POST['reason'] ?? '';
        $stmt = $pdo->prepare("UPDATE savings_requests SET status = 'rejected', rejection_reason = ? WHERE id = ?");
        $stmt->execute([$reason, $request_id]);
        // Add note to transaction
        $stmt = $pdo->prepare("INSERT INTO savings_transactions (user_id, category, amount, status, admin_note) VALUES (?, 'withdrawal', (SELECT amount FROM savings_requests WHERE id = ?), 'rejected', ?)");
        $stmt->execute([$request['user_id'] ?? 0, $request_id, $reason]);
        // TODO: Send notification
        $message = 'Savings request rejected.';
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
    <style>
        body { background: linear-gradient(160deg, #04112b 0%, #0b1b42 35%, #122d5f 100%); color: #e8efff; margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .admin-container { max-width: 1400px; margin: 24px auto; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .header h1 { margin: 0; color: #ffffff; }
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .tab { padding: 10px 20px; background: #1a1a2e; border: none; color: #e8efff; cursor: pointer; border-radius: 5px; }
        .tab.active { background: #4a92ff; }
        .table-container { background: rgba(255,255,255,0.05); border-radius: 10px; padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); }
        th { background: rgba(255,255,255,0.1); color: #4a92ff; }
        .btn { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9em; }
        .btn-approve { background: #28a745; color: white; }
        .btn-reject { background: #dc3545; color: white; }
        .message { padding: 10px; margin-bottom: 20px; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
<div class="admin-container">
    <div class="header">
        <h1>Savings Management</h1>
        <a href="admindashboard.php" class="btn" style="background: #6c757d; color: white; text-decoration: none;">Back to Dashboard</a>
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


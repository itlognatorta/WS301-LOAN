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
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action'];

    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE users SET status = 'active', verified = 1 WHERE id = ?");
        $stmt->execute([$user_id]);
        $message = 'User approved successfully.';
    } elseif ($action === 'reject') {
        $reason = $_POST['reason'] ?? '';
        $stmt = $pdo->prepare("UPDATE users SET status = 'disabled' WHERE id = ?");
        $stmt->execute([$user_id]);
        // TODO: Send email notification
        $message = 'User rejected.';
    } elseif ($action === 'edit') {
        $account_type = $_POST['account_type'];
        $status = $_POST['status'];
        $stmt = $pdo->prepare("UPDATE users SET account_type = ?, status = ? WHERE id = ?");
        $stmt->execute([$account_type, $status, $user_id]);
        $message = 'User updated successfully.';
    } elseif ($action === 'block_email') {
        $email = $_POST['email'];
        $stmt = $pdo->prepare("INSERT INTO blocked_emails (email, blocked_by) VALUES (?, ?) ON DUPLICATE KEY UPDATE blocked_at = CURRENT_TIMESTAMP");
        $stmt->execute([$email, $_SESSION['admin_id']]);
        $message = 'Email blocked successfully.';
    }
}

// Get pending users
$stmt = $pdo->prepare("SELECT * FROM users WHERE status = 'pending' ORDER BY created_at DESC");
$stmt->execute();
$pending_users = $stmt->fetchAll();

// Get all users for management
$stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC");
$stmt->execute();
$all_users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - User Management | Loan System</title>
    <link rel="stylesheet" href="../index.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        body { margin: 0; min-height: 100vh; color: #e8f1ff; background: radial-gradient(circle at top left, rgba(56, 189, 248, 0.16), transparent 18%), linear-gradient(180deg, #020816 0%, #071227 46%, #101f42 100%); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        * { box-sizing: border-box; }
        .admin-container { max-width: 1360px; margin: 24px auto; padding: 24px; }
        .header { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 22px; }
        .header h1 { margin: 0; font-size: clamp(2rem, 2.5vw, 2.6rem); letter-spacing: -0.04em; }
        .tabs { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 22px; }
        .tab { padding: 12px 20px; border: none; border-radius: 999px; background: rgba(255,255,255,0.08); color: #dbeafe; cursor: pointer; transition: background 0.18s ease, transform 0.18s ease; }
        .tab:hover { transform: translateY(-1px); background: rgba(56, 189, 248, 0.18); }
        .tab.active { background: #2563eb; color: #fff; }
        .table-container { background: rgba(255,255,255,0.05); border: 1px solid rgba(56,189,248,0.12); border-radius: 24px; padding: 24px; box-shadow: 0 24px 70px rgba(0,0,0,0.16); }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th, td { padding: 16px 14px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.08); }
        thead th { color: #94a3b8; font-size: 0.82rem; letter-spacing: 0.08em; text-transform: uppercase; }
        tbody tr:hover { background: rgba(255,255,255,0.04); }
        .btn { border: none; border-radius: 999px; padding: 10px 16px; cursor: pointer; font-weight: 700; transition: transform 0.16s ease, filter 0.16s ease; }
        .btn:hover { transform: translateY(-1px); }
        .btn-approve { background: #22c55e; color: #03181e; }
        .btn-reject { background: #ef4444; color: #fff; }
        .btn-edit { background: #0ea5e9; color: #fff; }
        .btn-block { background: #f59e0b; color: #0f172a; }
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 1000; padding: 24px; }
        .modal-content { background: rgba(8, 18, 42, 0.96); margin: auto; padding: 26px; border-radius: 24px; width: min(100%, 700px); max-height: 90vh; overflow-y: auto; border: 1px solid rgba(56,189,248,0.14); }
        .close { color: #cbd5e1; font-size: 28px; font-weight: bold; cursor: pointer; float: right; }
        .user-details { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; margin: 20px 0; }
        .detail-group { background: rgba(255,255,255,0.05); padding: 18px; border-radius: 16px; }
        .detail-group h4 { margin: 0 0 10px 0; color: #7dd3fc; }
        .files { margin-top: 20px; }
        .file-preview { display: inline-block; margin: 10px; text-align: center; }
        .file-preview img { max-width: 100%; height: auto; border-radius: 14px; border: 1px solid rgba(255,255,255,0.12); }
        .message { padding: 14px 18px; margin-bottom: 22px; border-radius: 18px; background: rgba(34,197,94,0.14); color: #dcfce7; border: 1px solid rgba(34,197,94,0.25); }
        .success { background: rgba(34,197,94,0.12); color: #d9f99d; }
        .error { background: rgba(239,68,68,0.12); color: #fecaca; }
        @media (max-width: 860px) { .admin-container { padding: 18px; } .header, .tabs { flex-direction: column; align-items: flex-start; } .user-details { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="admin-shell">
        <aside class="sidebar">
            <div class="brand">Loan Admin</div>
            <nav class="sidebar-nav">
                <a href="admindashboard.php" class="nav-item"><span class="icon">🏠</span><span>Dashboard</span></a>
                <a href="users.php" class="nav-item active"><span class="icon">👥</span><span>Manage Users</span></a>
                <a href="loans.php" class="nav-item"><span class="icon">💰</span><span>Loan Requests</span></a>
                <a href="savings.php" class="nav-item"><span class="icon">🏦</span><span>Savings Requests</span></a>
                <a href="billing.php" class="nav-item"><span class="icon">🧾</span><span>Billing Overview</span></a>
            </nav>
        </aside>
        <main class="admin-content">
            <div class="admin-container">
                <div class="page-header">
                    <div class="title-block">
                        <p class="breadcrumb">Dashboard <span>›</span> Manage Users</p>
                        <h1 class="page-title">User Management</h1>
                        <p class="page-subtitle">Review pending registrations, update account status, and manage user profiles from one place.</p>
                    </div>
                    <div>
                        <a href="admindashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                    </div>
                </div>

    <?php if ($message): ?>
    <div class="message success"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="tabs">
        <button class="tab active" onclick="showTab('pending')">Pending Approvals (<?php echo count($pending_users); ?>)</button>
        <button class="tab" onclick="showTab('all')">All Users</button>
    </div>

    <div id="pending-tab" class="table-container">
        <h3>Pending Registrations</h3>
        <?php if (empty($pending_users)): ?>
        <p>No pending registrations.</p>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Account Type</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending_users as $user): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo ucfirst($user['account_type']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                    <td>
                        <button class="btn btn-view" onclick="viewUser(<?php echo $user['id']; ?>)">View Details</button>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-approve" onclick="return confirm('Approve this user?')">Approve</button>
                        </form>
                        <form method="POST" style="display: inline;" onsubmit="return confirmReject()">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="reason" id="rejectReason<?php echo $user['id']; ?>">
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
        <h3>All Users</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Account Type</th>
                    <th>Status</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_users as $user): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo ucfirst($user['account_type']); ?></td>
                    <td><?php echo ucfirst($user['status']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                    <td>
                        <button class="btn btn-view" onclick="viewUser(<?php echo $user['id']; ?>)">View Details</button>
                        <button class="btn btn-edit" onclick="editUser(<?php echo $user['id']; ?>, '<?php echo $user['account_type']; ?>', '<?php echo $user['status']; ?>')">Edit</button>
                        <button class="btn btn-block" onclick="blockEmail('<?php echo $user['email']; ?>')">Block Email</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
        </main>
    </div>

<!-- User Details Modal -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>User Details</h2>
        <div id="userDetails"></div>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h2>Edit User</h2>
        <form method="POST" id="editForm">
            <input type="hidden" name="user_id" id="editUserId">
            <input type="hidden" name="action" value="edit">
            <label>Account Type:</label>
            <select name="account_type" id="editAccountType">
                <option value="basic">Basic</option>
                <option value="premium">Premium</option>
            </select>
            <label>Status:</label>
            <select name="status" id="editStatus">
                <option value="active">Active</option>
                <option value="disabled">Disabled</option>
                <option value="pending">Pending</option>
            </select>
            <button type="submit" class="btn btn-primary">Update</button>
        </form>
    </div>
</div>

<script>
function showTab(tab) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.table-container').forEach(c => c.style.display = 'none');

    document.querySelector(`[onclick="showTab('${tab}')"]`).classList.add('active');
    document.getElementById(`${tab}-tab`).style.display = 'block';
}

function viewUser(userId) {
    // Fetch user details via AJAX or show modal with PHP data
    fetch(`get_user_details.php?id=${userId}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('userDetails').innerHTML = data;
            document.getElementById('userModal').style.display = 'block';
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading user details');
        });
}

function closeModal() {
    document.getElementById('userModal').style.display = 'none';
}

function editUser(userId, accountType, status) {
    document.getElementById('editUserId').value = userId;
    document.getElementById('editAccountType').value = accountType;
    document.getElementById('editStatus').value = status;
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

function blockEmail(email) {
    if (confirm(`Block email ${email}?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="block_email">
            <input type="hidden" name="email" value="${email}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function confirmReject() {
    const reason = prompt('Enter rejection reason:');
    if (reason === null) return false;
    // Set the reason in the hidden input
    const form = event.target;
    form.querySelector('input[name="reason"]').value = reason;
    return confirm('Reject this user?');
}

// Close modal when clicking outside
window.onclick = function(event) {
    const userModal = document.getElementById('userModal');
    const editModal = document.getElementById('editModal');
    if (event.target == userModal) {
        userModal.style.display = 'none';
    }
    if (event.target == editModal) {
        editModal.style.display = 'none';
    }
}
</script>

</body>
</html>


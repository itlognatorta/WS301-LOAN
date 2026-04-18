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
        .btn-edit { background: #17a2b8; color: white; }
        .btn-block { background: #ffc107; color: black; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; }
        .modal-content { background: #1a1a2e; margin: 5% auto; padding: 20px; border-radius: 10px; width: 80%; max-width: 600px; max-height: 80%; overflow-y: auto; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .user-details { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 20px 0; }
        .detail-group { background: rgba(255,255,255,0.05); padding: 10px; border-radius: 5px; }
        .detail-group h4 { margin: 0 0 8px 0; color: #4a92ff; }
        .files { margin-top: 20px; }
        .file-preview { display: inline-block; margin: 10px; text-align: center; }
        .file-preview img { max-width: 200px; max-height: 200px; border: 1px solid #ccc; }
        .message { padding: 10px; margin-bottom: 20px; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<div class="admin-container">
    <div class="header">
        <h1>User Management</h1>
        <a href="admindashboard.php" class="btn" style="background: #6c757d; color: white; text-decoration: none;">Back to Dashboard</a>
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


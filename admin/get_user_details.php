<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo 'Unauthorized';
    exit;
}

require_once __DIR__ . '/../db_connect_new.php';

$user_id = intval($_GET['id'] ?? 0);

if (!$user_id) {
    echo 'Invalid user ID';
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo 'User not found';
    exit;
}

// Get user's loans
$stmt = $pdo->prepare("SELECT * FROM loans WHERE user_id = ?");
$stmt->execute([$user_id]);
$loans = $stmt->fetchAll();

// Get user's savings transactions
$stmt = $pdo->prepare("SELECT * FROM savings_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user_id]);
$savings = $stmt->fetchAll();

// Get user's billing
$stmt = $pdo->prepare("SELECT * FROM billing WHERE user_id = ? ORDER BY generated_date DESC LIMIT 5");
$stmt->execute([$user_id]);
$billing = $stmt->fetchAll();
?>

<div class="user-details">
    <div class="detail-group">
        <h4>Personal Information</h4>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
        <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
        <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
        <p><strong>Address:</strong> <?php echo htmlspecialchars($user['address']); ?></p>
        <p><strong>Gender:</strong> <?php echo ucfirst($user['gender'] ?? 'Not specified'); ?></p>
        <p><strong>Birthday:</strong> <?php echo $user['birthday']; ?> (Age: <?php echo $user['age']; ?>)</p>
    </div>

    <div class="detail-group">
        <h4>Account Information</h4>
        <p><strong>Account Type:</strong> <?php echo ucfirst($user['account_type']); ?></p>
        <p><strong>Status:</strong> <?php echo ucfirst($user['status']); ?></p>
        <p><strong>Registered:</strong> <?php echo date('M d, Y H:i', strtotime($user['created_at'])); ?></p>
    </div>

    <div class="detail-group">
        <h4>Bank Information</h4>
        <p><strong>Bank Name:</strong> <?php echo htmlspecialchars($user['bank_name']); ?></p>
        <p><strong>Account Number:</strong> <?php echo htmlspecialchars($user['bank_account']); ?></p>
        <p><strong>Account Holder:</strong> <?php echo htmlspecialchars($user['account_holder']); ?></p>
        <p><strong>TIN:</strong> <?php echo htmlspecialchars($user['tin']); ?></p>
    </div>

    <div class="detail-group">
        <h4>Employment Information</h4>
        <p><strong>Company Name:</strong> <?php echo htmlspecialchars($user['company_name']); ?></p>
        <p><strong>Company Address:</strong> <?php echo htmlspecialchars($user['company_address']); ?></p>
        <p><strong>Company Phone:</strong> <?php echo htmlspecialchars($user['company_phone']); ?></p>
        <p><strong>Position:</strong> <?php echo htmlspecialchars($user['position']); ?></p>
        <p><strong>Monthly Earnings:</strong> ₱<?php echo number_format($user['monthly_earnings'], 2); ?></p>
    </div>

    <div class="detail-group">
        <h4>Financial Information</h4>
        <p><strong>Savings Balance:</strong> ₱<?php echo number_format($user['savings_balance'], 2); ?></p>
        <p><strong>Current Loan Amount:</strong> ₱<?php echo number_format($user['current_loan_amount'], 2); ?></p>
        <p><strong>Max Loan Amount:</strong> ₱<?php echo number_format($user['max_loan_amount'], 2); ?></p>
    </div>
</div>

<div class="user-details">
    <div class="detail-group">
        <h4>Loans</h4>
        <?php if (empty($loans)): ?>
        <p>No loans.</p>
        <?php else: ?>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="border: 1px solid #ccc; padding: 5px;">Principal</th>
                    <th style="border: 1px solid #ccc; padding: 5px;">Interest</th>
                    <th style="border: 1px solid #ccc; padding: 5px;">Tenure</th>
                    <th style="border: 1px solid #ccc; padding: 5px;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($loans as $loan): ?>
                <tr>
                    <td style="border: 1px solid #ccc; padding: 5px;">₱<?php echo number_format($loan['principal'], 2); ?></td>
                    <td style="border: 1px solid #ccc; padding: 5px;">₱<?php echo number_format($loan['interest'], 2); ?></td>
                    <td style="border: 1px solid #ccc; padding: 5px;"><?php echo $loan['tenure_months']; ?> months</td>
                    <td style="border: 1px solid #ccc; padding: 5px;"><?php echo ucfirst($loan['status']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="detail-group">
        <h4>Recent Savings Transactions</h4>
        <?php if (empty($savings)): ?>
        <p>No savings transactions.</p>
        <?php else: ?>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="border: 1px solid #ccc; padding: 5px;">Type</th>
                    <th style="border: 1px solid #ccc; padding: 5px;">Amount</th>
                    <th style="border: 1px solid #ccc; padding: 5px;">Balance After</th>
                    <th style="border: 1px solid #ccc; padding: 5px;">Status</th>
                    <th style="border: 1px solid #ccc; padding: 5px;">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($savings as $tx): ?>
                <tr>
                    <td style="border: 1px solid #ccc; padding: 5px;"><?php echo ucfirst($tx['category']); ?></td>
                    <td style="border: 1px solid #ccc; padding: 5px;">₱<?php echo number_format($tx['amount'], 2); ?></td>
                    <td style="border: 1px solid #ccc; padding: 5px;">₱<?php echo number_format($tx['balance_after'] ?? 0, 2); ?></td>
                    <td style="border: 1px solid #ccc; padding: 5px;"><?php echo ucfirst($tx['status']); ?></td>
                    <td style="border: 1px solid #ccc; padding: 5px;"><?php echo date('M d, Y', strtotime($tx['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="detail-group">
        <h4>Recent Billing</h4>
        <?php if (empty($billing)): ?>
        <p>No billing records.</p>
        <?php else: ?>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="border: 1px solid #ccc; padding: 5px;">Generated</th>
                    <th style="border: 1px solid #ccc; padding: 5px;">Due Date</th>
                    <th style="border: 1px solid #ccc; padding: 5px;">Total Due</th>
                    <th style="border: 1px solid #ccc; padding: 5px;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($billing as $bill): ?>
                <tr>
                    <td style="border: 1px solid #ccc; padding: 5px;"><?php echo date('M d, Y', strtotime($bill['generated_date'])); ?></td>
                    <td style="border: 1px solid #ccc; padding: 5px;"><?php echo date('M d, Y', strtotime($bill['due_date'])); ?></td>
                    <td style="border: 1px solid #ccc; padding: 5px;">₱<?php echo number_format($bill['total_due'], 2); ?></td>
                    <td style="border: 1px solid #ccc; padding: 5px;"><?php echo ucfirst($bill['status']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<div class="files">
    <h4>Uploaded Documents</h4>

    <?php if ($user['proof_billing_path']): ?>
    <div class="file-preview">
        <h5>Proof of Billing</h5>
        <?php
        $file_path = __DIR__ . '/../uploads/' . $user['proof_billing_path'];
        if (file_exists($file_path)) {
            $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                echo '<img src="../uploads/' . htmlspecialchars($user['proof_billing_path']) . '" alt="Proof of Billing">';
            } else {
                echo '<a href="../uploads/' . htmlspecialchars($user['proof_billing_path']) . '" target="_blank">View File</a>';
            }
        } else {
            echo '<p>File not found</p>';
        }
        ?>
    </div>
    <?php endif; ?>

    <?php if ($user['valid_id_path']): ?>
    <div class="file-preview">
        <h5>Valid ID</h5>
        <?php
        $file_path = __DIR__ . '/../uploads/' . $user['valid_id_path'];
        if (file_exists($file_path)) {
            $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                echo '<img src="../uploads/' . htmlspecialchars($user['valid_id_path']) . '" alt="Valid ID">';
            } else {
                echo '<a href="../uploads/' . htmlspecialchars($user['valid_id_path']) . '" target="_blank">View File</a>';
            }
        } else {
            echo '<p>File not found</p>';
        }
        ?>
    </div>
    <?php endif; ?>

    <?php if ($user['coe_path']): ?>
    <div class="file-preview">
        <h5>Certificate of Employment</h5>
        <?php
        $file_path = __DIR__ . '/../uploads/' . $user['coe_path'];
        if (file_exists($file_path)) {
            $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                echo '<img src="../uploads/' . htmlspecialchars($user['coe_path']) . '" alt="COE">';
            } else {
                echo '<a href="../uploads/' . htmlspecialchars($user['coe_path']) . '" target="_blank">View File</a>';
            }
        } else {
            echo '<p>File not found</p>';
        }
        ?>
    </div>
    <?php endif; ?>

    <?php if (!$user['proof_billing_path'] && !$user['valid_id_path'] && !$user['coe_path']): ?>
    <p>No documents uploaded yet.</p>
    <?php endif; ?>
</div>

<style>
.user-details { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 20px 0; }
.detail-group { background: rgba(255,255,255,0.05); padding: 15px; border-radius: 5px; }
.detail-group h4 { margin: 0 0 10px 0; color: #4a92ff; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 5px; }
.detail-group p { margin: 8px 0; }
.files { margin-top: 30px; }
.files h4 { color: #4a92ff; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 5px; }
.file-preview { display: inline-block; margin: 15px; text-align: center; vertical-align: top; }
.file-preview h5 { margin: 0 0 10px 0; color: #e8efff; }
.file-preview img { max-width: 250px; max-height: 250px; border: 2px solid #4a92ff; border-radius: 5px; }
.file-preview a { display: inline-block; padding: 10px 15px; background: #17a2b8; color: white; text-decoration: none; border-radius: 4px; }
</style>
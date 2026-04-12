<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/db_connect_new.php';
require_once __DIR__ . '/includes/config.php';

$user_id = $_SESSION['user_id'];
$user = null;
$loans = [];
$savings_txs = [];
$billing_current = [];
$billing_history = [];
$error = '';

if ($dbConnected && $pdo) {
    // User data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit;
    }

    // Loans
    $stmt = $pdo->query("SELECT * FROM loan_requests WHERE user_id = $user_id ORDER BY created_at DESC");
    $loans = $stmt->fetchAll();

    // Loans active
    $stmt = $pdo->prepare("SELECT * FROM loans WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $active_loans = $stmt->fetchAll();

    // Savings tx
    $stmt = $pdo->prepare("SELECT * FROM savings_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
    $stmt->execute([$user_id]);
    $savings_txs = $stmt->fetchAll();

    // Balance
    $balance = $user['savings_balance'];

    // Billing current (latest unpaid)
    $stmt = $pdo->prepare("SELECT * FROM billing WHERE user_id = ? AND status != 'completed' ORDER BY due_date ASC LIMIT 1");
    $stmt->execute([$user_id]);
    $billing_current = $stmt->fetch();

    // History
    $stmt = $pdo->prepare("SELECT * FROM billing WHERE user_id = ? ORDER BY year_month DESC, generated_date DESC LIMIT 50");
    $stmt->execute([$user_id]);
    $billing_history = $stmt->fetchAll();
} else {
    $error = 'DB error.';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Loan System</title>
    <link rel="stylesheet" href="index.css">
    <style>
        .dashboard { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .user-info { display: flex; gap: 10px; align-items: center; }
        .nav-tabs { display: flex; gap: 10px; margin-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,0.2); }
        .nav-tab { padding: 12px 24px; background: rgba(255,255,255,0.1); border-radius: 12px 12px 0 0; cursor: pointer; }
        .nav-tab.active { background: var(--primary); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .section-card { background: rgba(255,255,255,0.08); padding: 30px; border-radius: 20px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); }
        th { background: rgba(255,255,255,0.05); }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .btn-small { padding: 8px 16px; font-size: 0.9rem; }
        .premium-only { opacity: 0.5; }
        .premium-only.active { opacity: 1; }
        @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="header">
            <h1>Dashboard - <?php echo htmlspecialchars($user['name']); ?></h1>
            <div class="user-info">
                <span><?php echo $user['account_type']; ?></span>
                <a href="logout.php" class="btn btn-outline btn-small">Logout</a>
            </div>
        </div>
        <div class="nav-tabs">
            <div class="nav-tab active" onclick="showTab('account')">Account</div>
            <div class="nav-tab" onclick="showTab('loans')">Loans</div>
            <div class="nav-tab" onclick="showTab('savings')" id="savings-tab" class="<?php echo $user['account_type'] === 'premium' ? '' : 'premium-only'; ?>">Savings</div>
            <div class="nav-tab" onclick="showTab('billing')">Billing</div>
            <div class="nav-tab" onclick="showTab('history')">Transactions</div>
        </div>
        <?php if ($error): ?><p style="color: #ff6b6b;"><?php echo $error; ?></p><?php endif; ?>

        <!-- Account Tab -->
        <div id="account" class="tab-content active">
            <div class="section-card">
                <h2>Profile</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label>Name</label>
                        <span><?php echo htmlspecialchars($user['name']); ?></span>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <span><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <span><?php echo htmlspecialchars($user['phone']); ?></span>
                    </div>
                    <!-- Add edit form later -->
                </div>
                <p>Status: <strong><?php echo ucfirst($user['status']); ?></strong> | Type: <strong><?php echo ucfirst($user['account_type']); ?></strong></p>
                <p>Savings Balance: <strong>PHP <?php echo number_format($balance, 2); ?></strong></p>
            </div>
        </div>

        <!-- Loans Tab -->
        <div id="loans" class="tab-content">
            <div class="section-card">
                <h2>Loan Application</h2>
                <form method="POST" action="apply_loan.php">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Amount (thousands, min 5k max 10k)</label>
                            <select name="amount" required>
                                <?php for ($a = 5000; $a <= 10000; $a += 1000): ?>
                                <option value="<?php echo $a; ?>">PHP <?php echo number_format($a); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tenure (months)</label>
                            <select name="tenure_months" required>
                                <option value="1">1 month</option>
                                <option value="3">3 months</option>
                                <option value="6">6 months</option>
                                <option value="12">12 months</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Apply for Loan</button>
                </form>
                <h3>Recent Requests</h3>
                <?php if (empty($loans)): ?>
                    <p>No loan requests.</p>
                <?php else: ?>
                    <table>
                        <tr><th>ID</th><th>Amount</th><th>Tenure</th><th>Status</th><th>Date</th></tr>
                        <?php foreach ($loans as $loan): ?>
                        <tr><td><?php echo htmlspecialchars($loan['id']); ?></td><td>PHP <?php echo number_format($loan['amount']); ?></td><td><?php echo $loan['tenure_months']; ?> mos</td><td><span style="color: <?php echo $loan['status'] === 'approved' ? '#4ade80' : ($loan['status'] === 'rejected' ? '#ff6b6b' : '#fabd23'); ?>"><?php echo ucfirst($loan['status']); ?></span></td><td><?php echo date('Y-m-d', strtotime($loan['created_at'])); ?></td></tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Savings Tab -->
        <div id="savings" class="tab-content">
            <div class="section-card">
                <h2>Savings (Premium Only)</h2>
                <?php if ($user['account_type'] !== 'premium'): ?>
                    <p class="premium-only">Upgrade to premium for savings.</p>
                <?php else: ?>
                    <p>Balance: <strong>PHP <?php echo number_format($balance, 2); ?></strong></p>
                    <div class="form-row">
                        <div class="form-group">
                            <h3>Deposit</h3>
                            <input type="number" id="deposit-amount" min="<?php echo SAVINGS_MIN_DEPOSIT; ?>" max="<?php echo SAVINGS_MAX_DEPOSIT; ?>" placeholder="100 - 1000">
                            <button onclick="deposit()" class="btn btn-primary btn-small">Deposit</button>
                        </div>
                        <div class="form-group">
                            <h3>Withdraw Request</h3>
                            <input type="number" id="withdraw-amount" min="<?php echo WITHDRAW_MIN; ?>" max="<?php echo WITHDRAW_MAX_DAY; ?>" placeholder="500 - 5k">
                            <button onclick="withdrawRequest()" class="btn btn-primary btn-small">Request Withdraw</button>
                        </div>
                    </div>
                    <h3>Recent Transactions</h3>
                    <?php if (empty($savings_txs)): ?>
                        <p>No transactions.</p>
                    <?php else: ?>
                        <table>
                            <tr><th>TX ID</th><th>Category</th><th>Amount</th><th>Status</th><th>Date</th></tr>
                            <?php foreach ($savings_txs as $tx): ?>
                            <tr><td><?php echo htmlspecialchars($tx['tx_id']); ?></td><td><?php echo ucfirst($tx['category']); ?></td><td><?php echo $tx['category'] === 'deposit' ? '+' : '-'; ?> PHP <?php echo number_format($tx['amount']); ?></td><td><?php echo ucfirst($tx['status']); ?></td><td><?php echo date('Y-m-d', strtotime($tx['created_at'])); ?></td></tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Billing Tab -->
        <div id="billing" class="tab-content">
            <div class="section-card">
                <h2>Current Billing</h2>
                <?php if (empty($billing_current)): ?>
                    <p>No bills to pay.</p>
                <?php else: ?>
                    <table>
                        <tr><th>Due Date</th><th>Monthly</th><th>Interest</th><th>Penalty</th><th>Total</th><th>Status</th></tr>
                        <tr><td><?php echo $billing_current['due_date']; ?></td><td>PHP <?php echo number_format($billing_current['monthly_amount']); ?></td><td>PHP <?php echo number_format($billing_current['interest']); ?></td><td>PHP <?php echo number_format($billing_current['penalty']); ?></td><td><strong>PHP <?php echo number_format($billing_current['total_due']); ?></strong></td><td><?php echo ucfirst($billing_current['status']); ?></td></tr>
                    </table>
                <?php endif; ?>
                <h3>Billing History</h3>
                <div class="form-row">
                    <input type="text" id="billing-search" placeholder="Search TX ID or category...">
                    <select id="billing-filter">
                        <option>All</option>
                        <option>Deposit</option>
                        <option>Withdrawal</option>
                    </select>
                </div>
                <?php if (empty($billing_history)): ?>
                    <p>No history.</p>
                <?php else: ?>
                    <table id="billing-table">
                        <tr><th>Date</th><th>Due</th><th>Total</th><th>Status</th></tr>
                        <?php foreach ($billing_history as $bill): ?>
                        <tr><td><?php echo $bill['generated_date']; ?></td><td><?php echo $bill['due_date']; ?></td><td>PHP <?php echo number_format($bill['total_due']); ?></td><td><?php echo ucfirst($bill['status']); ?></td></tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- History Tab -->
        <div id="history" class="tab-content">
            <div class="section-card">
                <h2>All Transactions</h2>
                <div class="form-row">
                    <input type="text" id="tx-search" placeholder="Search TX ID">
                    <select id="tx-filter">
                        <option>All</option>
                        <option>Loan</option>
                        <option>Savings Deposit</option>
                        <option>Savings Withdrawal</option>
                    </select>
                </div>
                <table>
                    <tr><th>Type</th><th>ID</th><th>Amount</th><th>Status</th><th>Date</th></tr>
                    <!-- Populate with all tx combined later -->
                    <tr><td colspan="5">Combined transactions coming soon...</td></tr>
                </table>
            </div>
        </div>
    </div>
    <script>
        function showTab(tab) {
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.nav-tab').forEach(c => c.classList.remove('active'));
            document.getElementById(tab).classList.add('active');
            event.target.classList.add('active');
        }

        // JS for search/filter/tables stub
        document.getElementById('savings-tab').classList.toggle('premium-only', '<?php echo $user["account_type"]; ?>' !== 'premium');
    </script>
</body>
</html>


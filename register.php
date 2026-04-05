<?php
session_start();
require_once __DIR__ . '/db_connect_new.php';
require_once __DIR__ . '/includes/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Gather data
    $account_type = sanitize_input($_POST['account_type']);
    $name = sanitize_input($_POST['name']);
    $address = sanitize_input($_POST['address']);
    $gender = sanitize_input($_POST['gender'] ?? '');
    $birthday = $_POST['birthday'];
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $bank_name = sanitize_input($_POST['bank_name']);
    $bank_account = sanitize_input($_POST['bank_account']);
    $account_holder = sanitize_input($_POST['account_holder']);
    $tin = sanitize_input($_POST['tin']);
    $company_name = sanitize_input($_POST['company_name']);
    $company_address = sanitize_input($_POST['company_address']);
    $company_phone = sanitize_input($_POST['company_phone']);
    $position = sanitize_input($_POST['position']);
    $monthly_earnings = floatval($_POST['monthly_earnings']);
    $password = $_POST['password'];
    $username = $email; // Use email as username

    // Validation
    $errors = [];

    if (!in_array($account_type, ['basic', 'premium'])) $errors[] = 'Invalid account type.';
    if (empty($name)) $errors[] = 'Name required.';
    if (empty($address)) $errors[] = 'Address required.';
    if (empty($birthday) || strtotime($birthday) === false) $errors[] = 'Valid birthday required.';
    if (calculate_age($birthday) < 18) $errors[] = 'Must be at least 18 years old.';
    if (!validate_email($email)) $errors[] = 'Valid email required.';
    if (!validate_ph_phone($phone)) $errors[] = 'Valid PH phone required.';
    if (empty($bank_name) || empty($bank_account) || empty($account_holder)) $errors[] = 'Bank details required.';
    if (empty($tin)) $errors[] = 'TIN required.';
    if (empty($company_name) || empty($company_address) || empty($company_phone) || empty($position)) $errors[] = 'Company details required.';
    if ($monthly_earnings <= 0) $errors[] = 'Monthly earnings must be positive.';
    if (strlen($password) < 6) $errors[] = 'Password min 6 chars.';

    // Check duplicates/blocked
    if ($dbConnected && $pdo) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR phone = ? OR tin = ?");
        $stmt->execute([$email, $phone, $tin]);
        if ($stmt->fetch()) $errors[] = 'Email, phone, or TIN already registered.';

        $stmt = $pdo->prepare("SELECT id FROM blocked_emails WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) $errors[] = 'Email blocked.';

        if ($account_type === 'premium' && is_premium_limit_reached($pdo)) $errors[] = 'Premium slots full (max 50).';
    }

    if (empty($errors)) {
        // Uploads
        $proof_path = handle_upload($_FILES['proof_billing'], $pdo->lastInsertId() ?: time(), 'proof_billing') ?: '';
        $id_path = handle_upload($_FILES['valid_id'], $pdo->lastInsertId() ?: time(), 'valid_id') ?: '';
        $coe_path = handle_upload($_FILES['coe'], $pdo->lastInsertId() ?: time(), 'coe') ?: '';

        if (empty($proof_path) || empty($id_path) || empty($coe_path)) $errors[] = 'All uploads required.';

        if (empty($errors)) {
            $password_hash = hash_password($password);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, account_type, name, address, gender, birthday, phone, bank_name, bank_account, account_holder, tin, company_name, company_address, company_phone, position, monthly_earnings, proof_billing_path, valid_id_path, coe_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([$username, $email, $password_hash, $account_type, $name, $address, $gender, $birthday, $phone, $bank_name, $bank_account, $account_holder, $tin, $company_name, $company_address, $company_phone, $position, $monthly_earnings, $proof_path, $id_path, $coe_path]);

            if ($result) {
                $success = 'Registration submitted! Pending admin approval.';
            } else {
                $errors[] = 'Registration failed. Try again.';
            }
        }
    }

    $error = implode('<br>', $errors);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Loan System</title>
    <link rel="stylesheet" href="index.css">
    <style>
        .register-container { max-width: 800px; margin: 0 auto; padding: 40px 20px; }
        .register-form { background: rgba(255,255,255,0.08); padding: 40px; border-radius: 20px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.08); border-radius: 12px; color: white; }
        .upload-group { border: 2px dashed rgba(255,255,255,0.3); padding: 20px; text-align: center; }
        .btn-register { width: 100%; margin-top: 20px; }
        @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
    </style>
    <script>
        function calcAge() {
            const bday = new Date(document.getElementById('birthday').value);
            const today = new Date();
            let age = today.getFullYear() - bday.getFullYear();
            const m = today.getMonth() - bday.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < bday.getDate())) age--;
            document.getElementById('age').value = age;
        }
    </script>
</head>
<body>
    <div class="register-container">
        <h1>Register New Account</h1>
        <p><small>Note: Ensure bank details and company phone are correct. Admin will verify documents.</small></p>
        <?php if ($error): ?><p style="color: #ff6b6b; margin-bottom: 20px;"><?php echo $error; ?></p><?php endif; ?>
        <?php if ($success): ?><p style="color: #4ade80; margin-bottom: 20px;"><?php echo $success; ?></p><?php endif; ?>
        <form method="POST" enctype="multipart/form-data" class="register-form">
            <div class="form-row">
                <div class="form-group">
                    <label>Account Type *</label>
                    <label><input type="radio" name="account_type" value="basic" required> Basic (Loans only)</label>
                    <label><input type="radio" name="account_type" value="premium" required> Premium (Loans + Savings)</label>
                </div>
                <div class="form-group">
                    <label>Name *</label>
                    <input type="text" name="name" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Address *</label>
                    <textarea name="address" required rows="3"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender">
                            <option value="">Select</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Birthday *</label>
                        <input type="date" id="birthday" name="birthday" onChange="calcAge()" required max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
                    </div>
                    <div class="form-group">
                        <label>Age (auto)</label>
                        <input type="number" id="age" readonly>
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Phone * (09xxxxxxxxx)</label>
                    <input type="tel" name="phone" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Bank Name *</label>
                    <input type="text" name="bank_name" required>
                </div>
                <div class="form-group">
                    <label>Bank Account # *</label>
                    <input type="text" name="bank_account" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Card Holder Name * <small>Ensure correct to avoid issues</small></label>
                    <input type="text" name="account_holder" required>
                </div>
                <div class="form-group">
                    <label>TIN # *</label>
                    <input type="text" name="tin" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Company Name *</label>
                    <input type="text" name="company_name" required>
                </div>
                <div class="form-group">
                    <label>Company Address *</label>
                    <input type="text" name="company_address" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Company Phone * <small>HR number for verification</small></label>
                    <input type="tel" name="company_phone" required>
                </div>
                <div class="form-group">
                    <label>Position *</label>
                    <input type="text" name="position" required>
                </div>
            </div>
            <div class="form-group">
                <label>Monthly Earnings *</label>
                <input type="number" name="monthly_earnings" min="0" step="0.01" required>
            </div>
            <div class="form-group">
                <label>Password * (min 6 chars)</label>
                <input type="password" name="password" required minlength="6">
            </div>
            <div class="form-row">
                <div class="upload-group">
                    <label>Proof of Billing *</label>
                    <input type="file" name="proof_billing" accept=".jpg,.jpeg,.png,.pdf" required>
                </div>
                <div class="upload-group">
                    <label>Valid ID Primary *</label>
                    <input type="file" name="valid_id" accept=".jpg,.jpeg,.png,.pdf" required>
                </div>
                <div class="upload-group">
                    <label>COE *</label>
                    <input type="file" name="coe" accept=".jpg,.jpeg,.png,.pdf" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-register">Submit Registration</button>
        </form>
        <p style="text-align: center; margin-top: 20px;"><a href="login.php">Already registered? Login</a> | <a href="index.php">Home</a></p>
    </div>
</body>
</html>


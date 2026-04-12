<?php
require_once __DIR__ . '/db_connect_new.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // =========================
    // STEP 1: PERSONAL
    // =========================
    $account_type = $_POST['account_type'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $birthday = $_POST['birthday'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['contact'] ?? '');
    $password = $_POST['password'] ?? '';

    // =========================
    // STEP 2: WORK & BANK
    // =========================
    $bank_name = trim($_POST['bank_name'] ?? '');
    $bank_account = trim($_POST['bank_account'] ?? '');
    $account_holder = trim($_POST['card_name'] ?? '');
    $tin = trim($_POST['tin'] ?? '');

    $company_name = trim($_POST['company_name'] ?? '');
    $company_address = trim($_POST['company_address'] ?? '');
    $company_phone = trim($_POST['company_phone'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $monthly_earnings = floatval($_POST['salary'] ?? 0);

    $age = 0;
    if ($birthday) {
        $birthDate = DateTime::createFromFormat('Y-m-d', $birthday);
        if ($birthDate) {
            $age = $birthDate->diff(new DateTime('today'))->y;
        }
    }

    // =========================
    // VALIDATIONS
    // =========================

    if (!$account_type) $errors[] = "Account type is required.";
    if (!$username) $errors[] = "Username is required.";
    if (!$name) $errors[] = "Name is required.";
    if (!$address) $errors[] = "Address is required.";
    if (!$birthday) $errors[] = "Birthday is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email.";

    if (!preg_match('/^09\d{9}$/', $phone)) {
        $errors[] = "Invalid PH contact number.";
    }

    if (!$password) $errors[] = "Password is required.";

    if (!$bank_name || !$bank_account || !$account_holder) {
        $errors[] = "Bank details are required.";
    }

    if (!$tin) $errors[] = "TIN is required.";

    if (!$company_name || !$company_phone) {
        $errors[] = "Company details are required.";
    }

    if (!$dbConnected || !$pdo) {
        $errors[] = 'Database connection failed. Please contact the administrator.';
    }

    // =========================
    // CHECK PREMIUM LIMIT
    // =========================
    if ($account_type === 'premium') {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE account_type='premium'");
        $count = $stmt->fetchColumn();

        if ($count >= 50) {
            $errors[] = "Premium slots are full.";
        }
    }

    // =========================
    // HANDLE FILE UPLOADS
    // =========================
    $proof_billing_path = null;
    $valid_id_path = null;
    $coe_path = null;

    $upload_dir = __DIR__ . '/uploads/';

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

    function uploadFile($file_key, $prefix) {
    global $errors, $upload_dir;

    if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] !== UPLOAD_ERR_OK) {
        $errors[] = ucfirst(str_replace('_', ' ', $file_key)) . ' is required.';
        return null;
    }

    $file = $_FILES[$file_key];

    // ✅ ALLOWED TYPES (JPG + PNG ONLY)
    $allowed_types = ['image/jpeg', 'image/png'];
    $allowed_ext = ['jpg', 'jpeg', 'png'];

    $max_size = 5 * 1024 * 1024; // 5MB

    // ✅ VALIDATE MIME TYPE
    if (!in_array($file['type'], $allowed_types)) {
        $errors[] = ucfirst(str_replace('_', ' ', $file_key)) . ' must be JPG or PNG only.';
        return null;
    }

    // ✅ VALIDATE EXTENSION (extra security)
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext)) {
        $errors[] = ucfirst(str_replace('_', ' ', $file_key)) . ' must be JPG or PNG only.';
        return null;
    }

    // ✅ SIZE CHECK
    if ($file['size'] > $max_size) {
        $errors[] = ucfirst(str_replace('_', ' ', $file_key)) . ' must be less than 5MB.';
        return null;
    }

    // ✅ GENERATE UNIQUE NAME
    $filename = $prefix . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
    $filepath = $upload_dir . $filename;

    // ✅ MOVE FILE
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    } else {
        $errors[] = 'Failed to upload ' . str_replace('_', ' ', $file_key) . '.';
        return null;
    }
}

    if (empty($errors)) {
        $proof_billing_path = uploadFile('proof_billing', 'billing');
        $valid_id_path = uploadFile('valid_id', 'id');
        $coe_path = uploadFile('coe', 'coe');
    }

    // =========================
    // INSERT
    // =========================
    if (empty($errors)) {

        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO users
                (username, email, password_hash, account_type, name, address, gender, birthday, age, phone,
                 bank_name, bank_account, account_holder, tin,
                 company_name, company_address, company_phone, position, monthly_earnings,
                 proof_billing_path, valid_id_path, coe_path, status)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, 'pending')"
            );

            $stmt->execute([
                $username, $email, $password_hash, $account_type, $name, $address, $gender, $birthday, $age, $phone,
                $bank_name, $bank_account, $account_holder, $tin,
                $company_name, $company_address, $company_phone, $position, $monthly_earnings,
                $proof_billing_path, $valid_id_path, $coe_path
            ]);

            $success = "Registration submitted. Waiting for admin approval.";
        } catch (PDOException $e) {
            die("DB ERROR: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register</title>
<link rel="stylesheet" href="register.css">
</head>
<body>

<div class="background-image"></div>

<div class="register-container">

<h2>Register Account</h2>

<?php if ($errors): ?>
<div class="error-box">
    <?php foreach ($errors as $e) echo "<p>$e</p>"; ?>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">

<div class="step-tabs">
    <div class="step-tab active" id="tab1">1. Personal</div>
    <div class="step-tab" id="tab2">2. Work & Bank</div>
    <div class="step-tab" id="tab3">3. Verification</div>
</div>

<div class="progress-bar">
    <div class="progress" id="progress"></div>
</div>

<!-- STEP 1 -->
<div class="step active" id="step1">
<h3>Step 1: Personal</h3>

<select name="account_type" required>
<option value="">Account Type</option>
<option value="basic">Basic</option>
<option value="premium">Premium</option>
</select>

<input type="text" name="name" placeholder="Full Name" required>
<input type="text" name="address" placeholder="Address" required>

<select name="gender">
<option value="">Gender</option>
<option>Male</option>
<option>Female</option>
</select>

<input type="date" name="birthday" id="birthday" required>
<input type="text" id="age" placeholder="Age" readonly>

<input type="email" name="email" placeholder="Email" required>
<input type="text" name="contact" placeholder="09XXXXXXXXX" required>

<input type="text" name="username" placeholder="Username" required>

<input type="password" name="password" placeholder="Password" required>

<!-- BUTTONS -->
<div class="btn-group">
    <button type="button" class="btn-secondary" onclick="openModal()">Cancel</button>
    <button type="button" class="btn-primary" onclick="nextStep(2)">Next</button>
</div>

</div>

<!-- STEP 2 -->
<div class="step" id="step2">
<h3>Step 2: Work & Bank</h3>

<input type="text" name="bank_name" placeholder="Bank Name" required>
<input type="text" name="bank_account" placeholder="Account Number" required>

<input type="text" name="card_name" placeholder="Card Holder Name" required>

<input type="text" name="tin" placeholder="TIN Number" required>

<input type="text" name="company_name" placeholder="Company Name" required>
<input type="text" name="company_address" placeholder="Company Address">

<input type="text" name="company_phone" placeholder="HR Contact Number" required>

<input type="text" name="position" placeholder="Position">
<input type="number" name="salary" placeholder="Monthly Salary">

<div class="btn-group">
    <button type="button" class="btn-secondary" onclick="prevStep(1)">Back</button>
    <button type="button" class="btn-primary" onclick="nextStep(3)">Next</button>
</div>

</div>

<!-- STEP 3 -->
<div class="step" id="step3">
<h3>Step 3: Verification</h3>

<label>Proof of Billing</label>
<input type="file" name="proof_billing" accept="image/png, image/jpeg" required>

<label>Valid ID</label>
<input type="file" name="valid_id" accept="image/png, image/jpeg" required>

<label>COE</label>
<input type="file" name="coe" accept="image/png, image/jpeg" required>

<div class="btn-group">
    <button type="button" class="btn-secondary" onclick="prevStep(2)">Back</button>
    <button type="submit" class="btn-primary">Submit</button>
</div>

</div>

</form>
</div>

<script>

// STEP NAVIGATION
function nextStep(step){
    document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
    document.getElementById('step'+step).classList.add('active');

    document.querySelectorAll('.step-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab'+step).classList.add('active');

    // ✅ UPDATE PROGRESS BAR
    const progress = document.getElementById('progress');

    if(step === 1) progress.style.width = "33.33%";
    if(step === 2) progress.style.width = "66.66%";
    if(step === 3) progress.style.width = "100%";
}

function prevStep(step){
    nextStep(step);
}

// AGE AUTO CALCULATION
document.getElementById('birthday').addEventListener('change', function(){
    let birth = new Date(this.value);
    let today = new Date();
    let age = today.getFullYear() - birth.getFullYear();

    let m = today.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
        age--;
    }

    document.getElementById('age').value = age;
});

document.getElementById('progress').style.width = "33.33%";

function openModal() {
    document.getElementById('cancelModal').classList.add('active');
}

function closeModal() {
    document.getElementById('cancelModal').classList.remove('active');
}

function confirmCancel() {
    window.location.href = 'login.php';
}

function goToLogin() {
    window.location.href = 'login.php';
}

</script>

<div class="modal-overlay" id="cancelModal">
    <div class="modal-box">
        <h3>Cancel Registration?</h3>
        <p>Are you sure you want to cancel? Your progress will be lost.</p>

        <div class="modal-actions">
            <button class="btn-back" onclick="closeModal()">No</button>
            <button class="btn-next" onclick="confirmCancel()">Yes</button>
        </div>
    </div>
</div>

<!-- SUCCESS MODAL -->
<div class="modal-overlay" id="successModal">
    <div class="modal-box success-modal-box">
        <h3>Registration Successful</h3>
        <p>Registration submitted. Waiting for admin approval.</p>

        <div class="modal-actions">
            <button class="btn-next" onclick="goToLogin()">OK</button>
        </div>
    </div>
</div>


<?php if ($success): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.getElementById('successModal').classList.add('active');
    });
</script>
<?php endif; ?>

</body>
</html>
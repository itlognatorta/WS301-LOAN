<?php
require_once __DIR__ . '/db_connect_new.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // =========================
    // STEP 1: PERSONAL
    // =========================
    $account_type = $_POST['account_type'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $birthday = $_POST['birthday'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $password = $_POST['password'] ?? '';

    // =========================
    // STEP 2: WORK & BANK
    // =========================
    $bank_name = trim($_POST['bank_name'] ?? '');
    $bank_account = trim($_POST['bank_account'] ?? '');
    $card_name = trim($_POST['card_name'] ?? '');
    $tin = trim($_POST['tin'] ?? '');

    $company_name = trim($_POST['company_name'] ?? '');
    $company_address = trim($_POST['company_address'] ?? '');
    $company_phone = trim($_POST['company_phone'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $salary = trim($_POST['salary'] ?? '');

    // =========================
    // VALIDATIONS
    // =========================

    if (!$account_type) $errors[] = "Account type is required.";
    if (!$name) $errors[] = "Name is required.";
    if (!$address) $errors[] = "Address is required.";
    if (!$birthday) $errors[] = "Birthday is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email.";

    if (!preg_match('/^09\d{9}$/', $contact)) {
        $errors[] = "Invalid PH contact number.";
    }

    if (!$password) $errors[] = "Password is required.";

    if (!$bank_name || !$bank_account || !$card_name) {
        $errors[] = "Bank details are required.";
    }

    if (!$tin) $errors[] = "TIN is required.";

    if (!$company_name || !$company_phone) {
        $errors[] = "Company details are required.";
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
    // INSERT
    // =========================
    if (empty($errors)) {

        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO users 
            (account_type, name, address, gender, birthday, email, contact, password_hash,
             bank_name, bank_account, card_name, tin,
             company_name, company_address, company_phone, position, salary, status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, 'pending')
        ");

        $stmt->execute([
            $account_type, $name, $address, $gender, $birthday, $email, $contact, $password_hash,
            $bank_name, $bank_account, $card_name, $tin,
            $company_name, $company_address, $company_phone, $position, $salary
        ]);

        $success = "Registration submitted. Waiting for admin approval.";
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

<?php if ($success): ?>
<div class="success-box"><?php echo $success; ?></div>
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
    <button type="button" class="btn-secondary" onclick="window.location.href='login.php'">Cancel</button>
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
<input type="file" required>

<label>Valid ID</label>
<input type="file" required>

<label>COE</label>
<input type="file" required>

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

</script>

</body>
</html>
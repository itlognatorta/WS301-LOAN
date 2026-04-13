<?php
session_start();
require_once __DIR__ . '/../db_connect_new.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /WS301-LOAN/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = "";

/* ================= TOTAL LOANS ================= */
$stmt = $pdo->prepare("SELECT COUNT(*) FROM loans WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_loans = (int) $stmt->fetchColumn();

/* ================= TOTAL BILLING ================= */
$stmt = $pdo->prepare("SELECT SUM(received_amount) FROM loans WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_billing = (float) ($stmt->fetchColumn() ?? 0);

/* ================= LOANS ================= */
$stmt = $pdo->prepare("SELECT * FROM loans WHERE user_id = ? ORDER BY started_at DESC");
$stmt->execute([$user_id]);
$loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ================= USER ================= */
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

/* ================= UPDATE PROFILE ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $stmt = $pdo->prepare("
        UPDATE users SET
        name=?,
        email=?,
        address=?,
        gender=?,
        birthday=?,
        phone=?,
        bank_name=?,
        bank_account=?,
        account_holder=?,
        tin=?,
        company_name=?,
        company_address=?,
        company_phone=?,
        position=?,
        monthly_earnings=?
        WHERE id=?
    ");

    $stmt->execute([
        $_POST['name'],
        $_POST['email'],
        $_POST['address'],
        $_POST['gender'],
        $_POST['birthday'],
        $_POST['phone'],
        $_POST['bank_name'],
        $_POST['bank_account'],
        $_POST['account_holder'],
        $_POST['tin'],
        $_POST['company_name'],
        $_POST['company_address'],
        $_POST['company_phone'],
        $_POST['position'],
        $_POST['monthly_earnings'],
        $user_id
    ]);

    // ✅ REDIRECT (THIS FIXES CONFIRM RESUBMISSION)
    header("Location: profile.php?updated=1");
    exit;

    $updated = isset($_GET['updated']);
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Profile</title>
<link rel="stylesheet" href="dashboard.css">

<script>
function calcAge() {
    let bday = document.getElementById("birthday").value;
    if (!bday) return;

    let birth = new Date(bday);
    let today = new Date();

    let age = today.getFullYear() - birth.getFullYear();
    let m = today.getMonth() - birth.getMonth();

    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
        age--;
    }

    document.getElementById("age").value = age;
}

/* ================= MODAL CONTROL ================= */
window.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("profileForm");

    form.addEventListener("submit", function(e) {
    e.preventDefault();
    document.getElementById("confirmModal").classList.add("active");
});
});

/* CLOSE CONFIRM */
function closeConfirm() {
    document.getElementById("confirmModal").classList.remove("active");
}

/* SUBMIT FORM */
function submitProfile() {
    document.getElementById("confirmModal").classList.remove("active");
    document.getElementById("profileForm").submit();
}

/* CLOSE SUCCESS */
function closeSuccess() {
    document.getElementById("successModal").classList.remove("active");
}
</script>

</head>

<body>

<?php if (isset($_GET['updated'])): ?>
<script>
window.addEventListener("DOMContentLoaded", function () {
    document.getElementById("successModal").classList.add("active");
});
</script>
<?php endif; ?>

<div class="container">
<?php include 'sidebar.php'; ?>

<div class="main">

<h2>Profile Dashboard</h2>

<!-- TOP CARDS -->
<div class="cards">
    <div class="card-box">
        <h3>Total Loans</h3>
        <p><?= $total_loans ?></p>
    </div>

    <div class="card-box">
        <h3>Total Billing</h3>
        <p>₱ <?= number_format($total_billing, 2) ?></p>
    </div>

    <div class="card-box">
        <h3>Status</h3>
        <p>Active User</p>
    </div>
</div>

<!-- ================= FORM ================= -->
<form method="POST" id="profileForm">

<div class="cards">

<!-- PERSONAL -->
<div class="card" style="flex:1;">
<h3>Personal Details</h3>

<label>Name *</label>
<input type="text" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>

<label>Address *</label>
<input type="text" name="address" value="<?= htmlspecialchars($user['address'] ?? '') ?>" required>

<label>Gender</label>
<select name="gender">
    <option value="Male" <?= ($user['gender'] ?? '') == 'Male' ? 'selected' : '' ?>>Male</option>
    <option value="Female" <?= ($user['gender'] ?? '') == 'Female' ? 'selected' : '' ?>>Female</option>
</select>

<label>Birthday *</label>
<input type="date" name="birthday" id="birthday"
value="<?= $user['birthday'] ?? '' ?>"
onchange="calcAge()" required>

<label>Age</label>
<input type="number" id="age" readonly
value="<?= isset($user['birthday']) ? date_diff(date_create($user['birthday']), date_create('today'))->y : '' ?>">

<label>Email *</label>
<input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>

<label>Phone Number *</label>
<input type="text" name="phone"
value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
pattern="09[0-9]{9}"
placeholder="09XXXXXXXXX"
required>

</div>

<!-- BANK -->
<div class="card" style="flex:1;">
<h3>Bank Details</h3>

<label>Bank Name *</label>
<input type="text" name="bank_name" value="<?= htmlspecialchars($user['bank_name'] ?? '') ?>" required>

<label>Bank Account Number *</label>
<input type="text" name="bank_account" value="<?= htmlspecialchars($user['bank_account'] ?? '') ?>" required>

<label>Account Holder's Name *</label>
<input type="text" name="account_holder" value="<?= htmlspecialchars($user['account_holder'] ?? '') ?>" required>

<p style="color:orange;font-size:13px;">⚠ Make sure account holder's name is correct.</p>

<label>TIN Number *</label>
<input type="text" name="tin" value="<?= htmlspecialchars($user['tin'] ?? '') ?>" required>

<label>Company Name</label>
<input type="text" name="company_name" value="<?= htmlspecialchars($user['company_name'] ?? '') ?>">

<label>Company Address</label>
<input type="text" name="company_address" value="<?= htmlspecialchars($user['company_address'] ?? '') ?>">

<label>Company Phone Number</label>
<input type="text" name="company_phone" value="<?= htmlspecialchars($user['company_phone'] ?? '') ?>">

<label>Position</label>
<input type="text" name="position" value="<?= htmlspecialchars($user['position'] ?? '') ?>">

<label>Monthly Earnings</label>
<input type="number" name="monthly_earnings" value="<?= htmlspecialchars($user['monthly_earnings'] ?? '') ?>">

</div>

<!-- UPLOAD -->
<div class="card" style="flex:1;">
<h3>Uploads</h3>
<input type="file">
<input type="file">
<input type="file">
</div>

</div>

<button type="submit">Update Profile</button>

</form>

</div>
</div>

<!-- CONFIRM MODAL -->
<div class="modal-overlay" id="confirmModal">
    <div class="modal-box">
        <h3>Update Profile?</h3>
        <p>Are you sure you want to update your profile?</p>
        <div class="modal-actions">
            <button class="btn-back" onclick="closeConfirm()">No</button>
            <button class="btn-next" onclick="submitProfile()">Yes</button>
        </div>
    </div>
</div>

<!-- SUCCESS MODAL -->
<div class="modal-overlay" id="successModal">
    <div class="modal-box">
        <h3>Success</h3>
        <p>Profile Updated Successfully!</p>
        <div class="modal-actions">
            <button class="btn-next" onclick="closeSuccess()">OK</button>
        </div>
    </div>
</div>

</body>
</html>
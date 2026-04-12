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

    $success = "Profile Updated Successfully!";
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

/* ================= CONFIRM UPDATE ================= */
function confirmUpdate(event) {
    event.preventDefault();

    if (confirm("Are you sure you want to update profile?")) {
        alert("Updated Successfully");
        event.target.submit();
    } else {
        alert("Update profile cancelled");
    }
}
</script>

</head>

<body>

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

<?php if ($success): ?>
<div class="success"><?= $success ?></div>
<?php endif; ?>

<!-- ================= FORM ================= -->
<form method="POST" onsubmit="confirmUpdate(event)">

<div class="cards">

<!-- ================= PERSONAL DETAILS ================= -->
<div class="card" style="flex:1;">
<h3>Personal Details</h3>

<label>Name *</label>
<input type="text" name="name"
value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>

<label>Address *</label>
<input type="text" name="address"
value="<?= htmlspecialchars($user['address'] ?? '') ?>" required>

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
<input type="email" name="email"
value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>

<label>Phone Number *</label>
<input type="text" name="phone"
value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
pattern="09[0-9]{9}"
placeholder="09XXXXXXXXX"
required>

</div>

<!-- ================= BANK DETAILS ================= -->
<div class="card" style="flex:1;">

<h3>Bank Details</h3>

<label>Bank Name *</label>
<input type="text" name="bank_name"
value="<?= htmlspecialchars($user['bank_name'] ?? '') ?>" required>

<label>Bank Account Number *</label>
<input type="text" name="bank_account"
value="<?= htmlspecialchars($user['bank_account'] ?? '') ?>" required>

<label>Account Holder's Name *</label>
<input type="text" name="account_holder"
value="<?= htmlspecialchars($user['account_holder'] ?? '') ?>" required>

<p style="color:orange; font-size:13px;">
⚠ Make sure account holder's name is correct to avoid transaction issues.
</p>

<label>TIN Number *</label>
<input type="text" name="tin"
value="<?= htmlspecialchars($user['tin'] ?? '') ?>" required>

<label>Company Name</label>
<input type="text" name="company_name"
value="<?= htmlspecialchars($user['company_name'] ?? '') ?>">

<label>Company Address</label>
<input type="text" name="company_address"
value="<?= htmlspecialchars($user['company_address'] ?? '') ?>">

<label>Company Phone Number</label>
<input type="text" name="company_phone"
value="<?= htmlspecialchars($user['company_phone'] ?? '') ?>">

<p style="color:orange; font-size:13px;">
⚠ Please provide HR contact number for employment confirmation.
</p>

<label>Position</label>
<input type="text" name="position"
value="<?= htmlspecialchars($user['position'] ?? '') ?>">

<label>Monthly Earnings</label>
<input type="number" name="monthly_earnings"
value="<?= htmlspecialchars($user['monthly_earnings'] ?? '') ?>">

</div>

<!-- ================= UPLOADS ================= -->
<div class="card" style="flex:1;">
<h3>Uploads</h3>

<label>Proof of Billing *</label>
<input type="file">

<label>Valid ID *</label>
<input type="file">

<label>COE *</label>
<input type="file">

</div>

</div>

<button type="submit">Update Profile</button>

</form>


</div>
</div>

</body>
</html>
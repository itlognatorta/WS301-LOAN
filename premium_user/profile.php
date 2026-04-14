<?php
session_start();
require_once __DIR__ . '/../db_connect_new.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /WS301-LOAN/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* ================= USER ================= */
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

/* ================= TOTAL LOANS ================= */
$stmt = $pdo->prepare("SELECT COUNT(*) FROM loans WHERE user_id=?");
$stmt->execute([$user_id]);
$total_loans = $stmt->fetchColumn();

/* ================= TOTAL BILLING ================= */
$stmt = $pdo->prepare("SELECT SUM(total_due) FROM billing WHERE user_id=?");
$stmt->execute([$user_id]);
$total_billing = $stmt->fetchColumn() ?? 0;

/* ================= SAVINGS ================= */
$savings = $user['savings_balance'];

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

    header("Location: profile.php?updated=1");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Premium Profile</title>
<link rel="stylesheet" href="premiumdb.css">

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

/* CONFIRM MODAL */
window.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("profileForm");

    form.addEventListener("submit", function(e) {
        e.preventDefault();
        document.getElementById("confirmModal").classList.add("active");
    });
});

function closeConfirm() {
    document.getElementById("confirmModal").classList.remove("active");
}

function submitProfile() {
    document.getElementById("confirmModal").classList.remove("active");
    document.getElementById("profileForm").submit();
}

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

<h2>Premium Profile Dashboard</h2>

<!-- 🔷 TOP CARDS -->
<div class="cards">
    <div class="card-box">
        <h3>Total Loans</h3>
        <p><?= $total_loans ?></p>
    </div>

    <div class="card-box">
        <h3>Total Billing</h3>
        <p>₱<?= number_format($total_billing,2) ?></p>
    </div>

    <div class="card-box">
        <h3>Savings</h3>
        <p>₱<?= number_format($savings,2) ?></p>
    </div>
</div>

<!-- 🔷 FORM -->
<form method="POST" id="profileForm">

<div class="cards">

<!-- PERSONAL -->
<div class="card" style="flex:1;">
<h3>Personal Details</h3>

<label>Name *</label>
<input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>

<label>Address *</label>
<input type="text" name="address" value="<?= htmlspecialchars($user['address']) ?>" required>

<label>Gender</label>
<select name="gender">
<option value="male" <?= $user['gender']=='male'?'selected':'' ?>>Male</option>
<option value="female" <?= $user['gender']=='female'?'selected':'' ?>>Female</option>
<option value="other" <?= $user['gender']=='other'?'selected':'' ?>>Other</option>
</select>

<label>Birthday *</label>
<input type="date" name="birthday" id="birthday"
value="<?= $user['birthday'] ?>"
onchange="calcAge()" required>

<label>Age</label>
<input type="number" id="age" readonly
value="<?= date_diff(date_create($user['birthday']), date_create('today'))->y ?>">

<label>Email *</label>
<input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

<label>Phone *</label>
<input type="text" name="phone"
value="<?= htmlspecialchars($user['phone']) ?>"
pattern="09[0-9]{9}" required>

</div>

<!-- BANK -->
<div class="card" style="flex:1;">
<h3>Bank Details</h3>

<label>Bank Name *</label>
<input type="text" name="bank_name" value="<?= htmlspecialchars($user['bank_name']) ?>" required>

<label>Bank Account *</label>
<input type="text" name="bank_account" value="<?= htmlspecialchars($user['bank_account']) ?>" required>

<label>Account Holder *</label>
<input type="text" name="account_holder" value="<?= htmlspecialchars($user['account_holder']) ?>" required>

<label>TIN *</label>
<input type="text" name="tin" value="<?= htmlspecialchars($user['tin']) ?>" required>

<label>Company Name</label>
<input type="text" name="company_name" value="<?= htmlspecialchars($user['company_name']) ?>">

<label>Company Address</label>
<input type="text" name="company_address" value="<?= htmlspecialchars($user['company_address']) ?>">

<label>Company Phone</label>
<input type="text" name="company_phone" value="<?= htmlspecialchars($user['company_phone']) ?>">

<label>Position</label>
<input type="text" name="position" value="<?= htmlspecialchars($user['position']) ?>">

<label>Monthly Earnings</label>
<input type="number" name="monthly_earnings" value="<?= $user['monthly_earnings'] ?>">

</div>

</div>

<button type="submit">Update Profile</button>

</form>

</div>
</div>

<!-- 🔷 CONFIRM MODAL -->
<div class="modal-overlay" id="confirmModal">
<div class="modal-box">
<h3>Update Profile?</h3>
<p>Are you sure?</p>
<button onclick="closeConfirm()">Cancel</button>
<button onclick="submitProfile()">Confirm</button>
</div>
</div>

<!-- 🔷 SUCCESS MODAL -->
<div class="modal-overlay" id="successModal">
<div class="modal-box">
<h3>Success</h3>
<p>Profile updated!</p>
<button onclick="closeSuccess()">OK</button>
</div>
</div>

</body>
</html>
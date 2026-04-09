<?php
require_once __DIR__ . '/db_connect_new.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Collect data
    $account_type = $_POST['account_type'];
    $name = $_POST['name'];
    $address = $_POST['address'];
    $gender = $_POST['gender'];
    $birthday = $_POST['birthday'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $bank_name = $_POST['bank_name'];
    $bank_acc = $_POST['bank_acc'];
    $card_name = $_POST['card_name'];

    $tin = $_POST['tin'];

    $company_name = $_POST['company_name'];
    $company_address = $_POST['company_address'];
    $company_phone = $_POST['company_phone'];
    $position = $_POST['position'];
    $earnings = $_POST['earnings'];

    $errors = [];

    // VALIDATIONS
    if(strlen($username) < 6) $errors[] = "Username must be at least 6 characters.";
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email.";
    if(!preg_match('/^09[0-9]{9}$/', $contact)) $errors[] = "Invalid PH contact number.";

    // Premium limit
    if($account_type == "Premium"){
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE account_type='Premium'");
        $row = $stmt->fetch();
        if($row['total'] >= 50){
            $errors[] = "Premium slots are full.";
        }
    }

    // Uploads
    $proof = $_FILES['proof']['name'];
    $valid_id = $_FILES['valid_id']['name'];
    $coe = $_FILES['coe']['name'];

    if(empty($proof) || empty($valid_id) || empty($coe)){
        $errors[] = "All uploads are required.";
    }

    if(empty($errors)){

        move_uploaded_file($_FILES['proof']['tmp_name'], "uploads/".$proof);
        move_uploaded_file($_FILES['valid_id']['tmp_name'], "uploads/".$valid_id);
        move_uploaded_file($_FILES['coe']['tmp_name'], "uploads/".$coe);

        $stmt = $pdo->prepare("INSERT INTO users 
        (account_type,name,address,gender,birthday,email,contact,username,password,
        bank_name,bank_acc,card_name,tin,company_name,company_address,company_phone,
        position,earnings,proof,valid_id,coe,status)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'Pending')");

        $stmt->execute([
            $account_type,$name,$address,$gender,$birthday,$email,$contact,$username,$password,
            $bank_name,$bank_acc,$card_name,$tin,$company_name,$company_address,$company_phone,
            $position,$earnings,$proof,$valid_id,$coe
        ]);

        $success = "Registration submitted! Waiting for admin approval.";
    } else {
        $error = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register | Loan System</title>
<link rel="stylesheet" href="index.css">
<link rel="stylesheet" href="register.css">

</head>

<div class="register-wrapper">

<!-- STEPS -->
<div class="steps">
    <div class="step active">1. Personal</div>
    <div class="step">2. Work & Bank</div>
    <div class="step">3. Verification</div>
</div>

<div class="progress-bar">
    <div class="progress" id="progress"></div>
</div>

<form method="POST" enctype="multipart/form-data" class="form-card">

<div class="form-header">
    <h2>Register Account</h2>
    <a href="index.php" class="close-btn" onclick="return confirm('Leave registration?');">&times;</a>
</div>

<!-- STEP 1 -->
<div class="step-content active">

<h3>Step 1 of 3 — Personal</h3>

<!-- ROW 1 -->
<div class="form-row">
    <select name="account_type" required>
        <option value="">Account Type</option>
        <option>Basic</option>
        <option>Premium</option>
    </select>

    <input type="text" name="name" placeholder="Full Name" required>
</div>

<!-- ROW 2 -->
<div class="form-row">
    <textarea name="address" placeholder="Address" class="input-equal" required></textarea>

    <select name="gender">
        <option value="">Gender</option>
        <option>Male</option>
        <option>Female</option>
    </select>
</div>

<!-- ROW 3 -->
<div class="form-row">
    <input type="date" id="birthday" name="birthday" onchange="calcAge()" required>

    <input type="text" id="age" placeholder="Age" readonly>
</div>

<!-- ROW 4 -->
<div class="form-row">
    <input type="email" name="email" placeholder="Email" required>
    <input type="text" name="contact" placeholder="Phone (09xxxxxxxxx)" required>
</div>

<!-- ROW 5 -->
<div class="form-row">
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
</div>

<div class="btn-group">
    <button type="button" class="btn btn-next" onclick="nextStep()">Next</button>
</div>

</div>

<!-- STEP 2 -->
<div class="step-content">

<h2>Step 2 of 3 — Work & Bank</h2>

<div class="form-row">
    <input type="text" name="bank_name" placeholder="Bank Name" required>
    <input type="text" name="bank_acc" placeholder="Bank Account #" required>
</div>

<div class="form-row">
    <input type="text" name="card_name" placeholder="Card Holder Name" required>
    <input type="text" name="tin" placeholder="TIN #" required>
</div>

<div class="form-row">
    <input type="text" name="company_name" placeholder="Company Name" required>
    <input type="text" name="company_address" placeholder="Company Address" required>
</div>

<div class="form-row">
    <input type="text" name="company_phone" placeholder="Company Phone" required>
    <input type="text" name="position" placeholder="Position" required>
</div>

<input type="number" name="earnings" class="full-width" placeholder="Monthly Earnings" required>

<div class="btn-group">
    <button type="button" class="btn btn-back" onclick="prevStep()">Back</button>
    <button type="button" class="btn btn-next" onclick="nextStep()">Next</button>
</div>

</div>

<!-- STEP 3 -->
<div class="step-content">

<h2>Step 3 of 3 — Verification</h2>

<div class="form-row">
    <input type="file" name="proof" required>
    <input type="file" name="valid_id" required>
</div>

<div class="form-row">
    <input type="file" name="coe" class="full-width" required>
</div>

<div class="btn-group">
    <button type="button" class="btn btn-back" onclick="prevStep()">Back</button>
    <button type="submit" name="register" class="btn btn-next">Submit</button>
</div>

</div>

</form>
</div>

<script>
let currentStep = 0;
const steps = document.querySelectorAll(".step-content");
const progress = document.getElementById("progress");
const stepLabels = document.querySelectorAll(".step");

function showStep(index){
    steps.forEach((step,i)=>{
        step.classList.toggle("active", i===index);
        stepLabels[i].classList.toggle("active", i===index);
    });
    progress.style.width = ((index+1)/3)*100 + "%";
}

function nextStep(){
    if(currentStep < 2){
        currentStep++;
        showStep(currentStep);
    }
}

function prevStep(){
    if(currentStep > 0){
        currentStep--;
        showStep(currentStep);
    }
}

function calcAge(){
    let b = new Date(document.getElementById("birthday").value);
    let t = new Date();
    let age = t.getFullYear() - b.getFullYear();
    document.getElementById("age").value = age;
}

showStep(0);
</script>
</html>
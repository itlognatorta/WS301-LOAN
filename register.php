<?php
require_once __DIR__ . '/db_connect_new.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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

    if(strlen($username) < 6) $errors[] = "Username must be at least 6 characters.";
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email.";
    if(!preg_match('/^09[0-9]{9}$/', $contact)) $errors[] = "Invalid PH contact number.";

    if($account_type == "Premium"){
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE account_type='Premium'");
        $row = $stmt->fetch();
        if($row['total'] >= 50){
            $errors[] = "Premium slots are full.";
        }
    }

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

<body>

<div class="background-image"></div> <!-- Full background -->

<div class="register-wrapper">

<div class="steps">
    <div class="step active">1. Personal</div>
    <div class="step">2. Work & Bank</div>
    <div class="step">3. Verification</div>
</div>

<div class="progress-bar">
    <div class="progress" id="progress"></div>
</div>

<form method="POST" enctype="multipart/form-data" class="form-card">

<!-- HEADER -->
<div class="form-header">
    <h2>Register Account</h2>
    <a href="#" class="close-btn" onclick="openCancelModal()">&times;</a>
</div>

<?php if($error): ?>
<p class="error"><?php echo $error; ?></p>
<?php endif; ?>

<?php if($success): ?>
<p class="success"><?php echo $success; ?></p>
<?php endif; ?>

<!-- STEP 1 -->
<div class="step-content active">
<h3 class="step-title">Step 1 of 3 — Personal</h3>

<div class="form-row">
    <select name="account_type" required>
        <option value="">Account Type</option>
        <option>Basic</option>
        <option>Premium</option>
    </select>
    <small class="error-text">Please select account type</small>

    <input type="text" name="name" placeholder="Full Name" required>
    <small class="error-text">This field is required</small>
</div>

<div class="form-row">
    <textarea name="address" placeholder="Address" class="input-equal" required></textarea>
    <small class="error-text">This field is required</small>

    <select name="gender">
        <option value="">Gender</option>
        <option>Male</option>
        <option>Female</option>
    </select>
    <small class="error-text">Please select gender</small>
</div>

<div class="form-row">
    <input type="date" id="birthday" name="birthday" onchange="calcAge()" required>
    <small class="error-text">Please select birthday</small>
    <input type="text" id="age" placeholder="Age" readonly>
    <small class="error-text">Age will be automatically calculated</small>
</div>

<div class="form-row">
    <input type="email" name="email" placeholder="Email" required>
    <small class="error-text">Please enter a valid email</small>
    <input type="text" name="contact" placeholder="Phone (09xxxxxxxxx)" required>
    <small class="error-text">This field is required</small>
</div>

<div class="form-row">
    <input type="text" name="username" placeholder="Username" required>
    <small class="error-text">Username must be at least 6 characters</small>
    <input type="password" name="password" placeholder="Password" required>
    <small class="error-text">Password must be at least 8 characters</small>
</div>

<div class="btn-group">
    <button type="button" class="btn btn-next" onclick="nextStep()">Next</button>
</div>
</div>

<!-- STEP 2 -->
<div class="step-content">
<h3 class="step-title">Step 2 of 3 — Work & Bank</h3>

<div class="form-row">
    <input type="text" name="bank_name" placeholder="Bank Name" required>
    <small class="error-text">This field is required</small>
    <input type="text" name="bank_acc" placeholder="Bank Account #" required>
    <small class="error-text">This field is required</small>
</div>

<div class="form-row">
    <input type="text" name="card_name" placeholder="Card Holder Name" required>
    <small class="error-text">This field is required</small>
    <input type="text" name="tin" placeholder="TIN #" required>
    <small class="error-text">This field is required</small>
</div>

<div class="form-row">
    <input type="text" name="company_name" placeholder="Company Name" required>
    <small class="error-text">This field is required</small>
    <input type="text" name="company_address" placeholder="Company Address" required>
    <small class="error-text">This field is required</small>
</div>

<div class="form-row">
    <input type="text" name="company_phone" placeholder="Company Phone" required>
    <small class="error-text">This field is required</small>
    <input type="text" name="position" placeholder="Position" required>
    <small class="error-text">This field is required</small>
</div>

<div class="form-row">
    <input type="number" name="earnings" placeholder="Monthly Earnings" required>
    <small class="error-text">This field is required</small>
</div>

<div class="btn-group">
    <button type="button" class="btn btn-back" onclick="prevStep()">Back</button>
    <button type="button" class="btn btn-next" onclick="nextStep()">Next</button>
</div>
</div>

<!-- STEP 3 -->
<div class="step-content">
<h3 class="step-title">Step 3 of 3 — Verification</h3>

<div class="form-row">
    <input type="file" name="proof" required>
    <small class="error-text">This field is required</small>
    <input type="file" name="valid_id" required>
    <small class="error-text">This field is required</small>
</div>

<div class="form-row">
    <input type="file" name="coe" class="full-width" required>
    <small class="error-text">This field is required</small>
</div>

<div class="btn-group">
    <button type="button" class="btn btn-back" onclick="prevStep()">Back</button>
    <button type="submit" class="btn btn-next" onclick="return validateFinalStep()">Submit</button>
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

function validateStep(stepIndex){
    let valid = true;

    const currentFields = steps[stepIndex].querySelectorAll("input, select, textarea");

    currentFields.forEach(field => {
        let errorText = field.parentElement.querySelector(".error-text");

        // RESET
        field.classList.remove("input-error");
        if(errorText) errorText.classList.remove("active");

        // CHECK EMPTY
        if(field.type !== "file" && field.value.trim() === ""){
            field.classList.add("input-error");
            if(errorText){
                errorText.textContent = "This field is required";
                errorText.classList.add("active");
            }
            valid = false;
        }

        // FILE VALIDATION
        if(field.type === "file" && field.files.length === 0){
            field.classList.add("input-error");
            if(errorText){
                errorText.textContent = "Please upload required file";
                errorText.classList.add("active");
            }
            valid = false;
        }

        // EMAIL VALIDATION
        if(field.type === "email" && field.value !== ""){
            let emailPattern = /^[^ ]+@[^ ]+\.[a-z]{2,3}$/;
            if(!emailPattern.test(field.value)){
                field.classList.add("input-error");
                if(errorText){
                    errorText.textContent = "Invalid email format";
                    errorText.classList.add("active");
                }
                valid = false;
            }
        }

        // PHONE VALIDATION
        if(field.name === "contact" && field.value !== ""){
            let phonePattern = /^09[0-9]{9}$/;
            if(!phonePattern.test(field.value)){
                field.classList.add("input-error");
                if(errorText){
                    errorText.textContent = "Invalid PH number";
                    errorText.classList.add("active");
                }
                valid = false;
            }
        }
    });

    return valid;

}

function nextStep(){
    if(validateStep(currentStep)){  // 🔥 VALIDATE FIRST
        if(currentStep < 2){
            currentStep++;
            showStep(currentStep);
        }
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

function confirmCancel(){
    return confirm("Are you sure to cancel registration?");
}

function openCancelModal(){
    document.getElementById("cancelModal").classList.add("active");
}

function closeCancelModal(){
    document.getElementById("cancelModal").classList.remove("active");
}

function confirmCancel(){
    window.location.href = "index.php";
}

function validateFinalStep(){

    const currentFields = steps[currentStep].querySelectorAll("input, select, textarea");
    let valid = true;

    currentFields.forEach(field => {

        if(field.hasAttribute("readonly")) return;

        if(!field.value.trim()){
            field.style.border = "2px solid red";
            valid = false;
        } else {
            field.style.border = "none";
        }

        if(field.type === "file" && field.files.length === 0){
            field.style.border = "2px solid red";
            valid = false;
        }
    });

    if(!valid){
        alert("Please complete all required fields before submitting.");
        return false;
    }

    return true;
}

showStep(0);
</script>

<!-- 🔥 CUSTOM CANCEL MODAL -->
<div id="cancelModal" class="modal-overlay">
    <div class="modal-box">
        <h3>Cancel Registration</h3>
        <p>Are you sure to cancel registration?</p>

        <div class="modal-actions">
            <button class="btn btn-back" onclick="closeCancelModal()">No</button>
            <button class="btn btn-next" onclick="confirmCancel()">Yes</button>
        </div>
    </div>
</div>


</body>
</html>
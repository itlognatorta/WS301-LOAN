<?php
/**
 * Config & Helper Functions
 */

// Constants
define('MAX_PREMIUM_USERS', 50);
define('MAX_INITIAL_LOAN', 10000);
define('MIN_LOAN', 5000);
define('INTEREST_RATE', 0.03);
define('PENALTY_RATE', 0.02);
define('SAVINGS_MAX', 100000);
define('SAVINGS_MIN_DEPOSIT', 100);
define('SAVINGS_MAX_DEPOSIT', 1000);
define('WITHDRAW_MIN', 500);
define('WITHDRAW_MAX_DAY', 5000);
define('MAX_WITHDRAW_DAILY', 5);
define('BILLING_DAYS_DUE', 28);

// Helper functions
function gen_tx_id($prefix) {
    return $prefix . date('Ymd') . rand(1000, 9999);
}

function validate_ph_phone($phone) {
    return preg_match('/^09[0-9]{9}$/', $phone);
}

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function calculate_age($birthday) {
    $from = new DateTime($birthday);
    $to = new DateTime('today');
    return $to->diff($from)->y;
}

function hash_password($pass) {
    return password_hash($pass, PASSWORD_DEFAULT);
}

function verify_password($pass, $hash) {
    return password_verify($pass, $hash);
}

function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function is_premium_limit_reached($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE account_type = 'premium' AND status = 'active'");
    return $stmt->fetchColumn() >= MAX_PREMIUM_USERS;
}

// Upload helper
function handle_upload($file, $user_id, $type) {
    $upload_dir = 'uploads/';
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
    if (!in_array($file_ext, $allowed)) return false;
    $filename = $user_id . '_' . $type . '_' . time() . '.' . $file_ext;
    $path = $upload_dir . $filename;
    if (move_uploaded_file($file['tmp_name'], $path)) {
        return $path;
    }
    return false;
}
?>


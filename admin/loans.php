<?php
session_start();
if (!isset($_SESSION['admin_id'])) header('Location: login.php');
require_once __DIR__ . '/../db_connect_new.php';
// List loan_requests, approve/reject with reason, generate billing if approve
// Stub
echo 'Admin Loans page - Approve/Reject requests, view per user.';
?>


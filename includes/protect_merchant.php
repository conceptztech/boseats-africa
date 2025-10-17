<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('Location: ../login.php');
    exit();
}

// Redirect if not a merchant
if ($_SESSION['user_type'] !== 'merchant') {
    header('Location: unauthorized.php');
    exit();
}

// Check if merchant is approved
if (isset($_SESSION['merchant_approved']) && !$_SESSION['merchant_approved']) {
    header('Location: pending_approval.php');
    exit();
}

// Merchant is logged in and approved - continue
$merchant_id = $_SESSION['user_id'];
$company_name = $_SESSION['company_name'] ?? 'Merchant';
?>
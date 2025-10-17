<?php
session_start();
include_once "db_connection.php";
// Redirect to login if not logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('Location: ../login.php');
    exit();
}

// Redirect if not an admin
if ($_SESSION['user_type'] !== 'admin') {
    header('Location: unauthorized.php');
    exit();
}

// Check if admin is active
if (isset($_SESSION['is_active']) && !$_SESSION['is_active']) {
    header('Location: ../login.php?error=account_deactivated');
    exit();
}

// Admin is logged in - continue
$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['full_name'] ?? 'Admin';
$admin_email = $_SESSION['email'] ?? '';

// Set last activity time for session timeout
$_SESSION['last_activity'] = time();
?>
<?php
// authentication_check.php - Enhanced authentication for all user types

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session timeout configuration (20 minutes)
$session_timeout = 1200; // 20 minutes in seconds

// Check if session has expired
if (isset($_SESSION['last_activity'])) {
    $session_life = time() - $_SESSION['last_activity'];
    if ($session_life > $session_timeout) {
        // Session expired - destroy and redirect to login
        session_unset();
        session_destroy();
        header('Location: login.php?error=session_expired');
        exit();
    }
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('Location: login.php');
    exit();
}

// Validate user type and check access permissions
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$current_page = basename($_SERVER['PHP_SELF']);

// Define allowed user types and their dashboard paths
$allowed_user_types = ['user', 'merchant', 'admin'];
$dashboard_paths = [
    'user' => '../user/dashboard.php',
    'merchant' => '../merchant/dashboard.php', 
    'admin' => '../admin/dashboard.php'
];

// Validate user type
if (!in_array($user_type, $allowed_user_types)) {
    session_unset();
    session_destroy();
    header('Location: login.php?error=invalid_user_type');
    exit();
}

// Check if user is accessing the correct dashboard
$current_directory = basename(dirname($_SERVER['PHP_SELF']));
$expected_directory = $user_type;

if ($current_directory !== $expected_directory && $current_page === 'dashboard.php') {
    // Redirect to correct dashboard
    header('Location: ' . $dashboard_paths[$user_type]);
    exit();
}

// Additional security checks
if (!isset($_SESSION['ip_address']) || $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
    // IP address changed - possible session hijacking
    session_unset();
    session_destroy();
    header('Location: login.php?error=security_breach');
    exit();
}

// Regenerate session ID periodically to prevent fixation
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} else if (time() - $_SESSION['created'] > 1800) { // 30 minutes
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Get user info based on user type
$user_name = $_SESSION['full_name'] ?? 'User';
$user_email = $_SESSION['email'] ?? '';

// Additional checks for specific user types
switch ($user_type) {
    case 'merchant':
        // Check if merchant is approved and active
        if (!isset($_SESSION['is_approved']) || !$_SESSION['is_approved']) {
            header('Location: merchant_pending.php');
            exit();
        }
        if (!isset($_SESSION['is_active']) || !$_SESSION['is_active']) {
            session_unset();
            session_destroy();
            header('Location: login.php?error=account_deactivated');
            exit();
        }
        break;
        
    case 'admin':
        // Check if admin is active
        if (!isset($_SESSION['is_active']) || !$_SESSION['is_active']) {
            session_unset();
            session_destroy();
            header('Location: login.php?error=admin_deactivated');
            exit();
        }
        break;
        
    case 'user':
        // Add any user-specific checks here
        if (!isset($_SESSION['is_active']) || !$_SESSION['is_active']) {
            session_unset();
            session_destroy();
            header('Location: login.php?error=account_deactivated');
            exit();
        }
        break;
}

// Set security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// Function to check specific user permissions
function checkPermission($required_type, $allowed_types = []) {
    if (!isset($_SESSION['user_type'])) {
        return false;
    }
    
    if ($_SESSION['user_type'] === $required_type) {
        return true;
    }
    
    if (!empty($allowed_types) && in_array($_SESSION['user_type'], $allowed_types)) {
        return true;
    }
    
    return false;
}

// Function to force logout
function forceLogout($reason = '') {
    session_unset();
    session_destroy();
    $redirect = 'login.php';
    if ($reason) {
        $redirect .= '?error=' . urlencode($reason);
    }
    header('Location: ' . $redirect);
    exit();
}

// Function to get user dashboard URL
function getUserDashboardUrl($user_type) {
    $dashboards = [
        'user' => '../user/dashboard.php',
        'merchant' => '../merchant/dashboard.php',
        'admin' => '../admin/dashboard.php'
    ];
    return $dashboards[$user_type] ?? 'login.php';
}
?>
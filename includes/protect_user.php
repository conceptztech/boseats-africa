<?php
// Ensure session settings are configured before starting the session
ini_set('session.cookie_secure', '1'); // Only sent over HTTPS connections
ini_set('session.cookie_httponly', '1'); // Avoid access via JavaScript
ini_set('session.use_only_cookies', '1'); // Ensure cookies are used to store session ID

session_start();

// Set a session timeout in seconds (20 minutes)
$timeout_duration = 20 * 60; // 20 minutes

// Regenerate session ID to prevent session fixation
if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
} else if (time() - $_SESSION['CREATED'] > $timeout_duration) {
    // If session is older than timeout, destroy the session and redirect to login
    session_unset();
    session_destroy();
    header('Location: ../login.php');
    exit();
} else {
    // Regenerate session ID every request to prevent session fixation
    session_regenerate_id(true);
    $_SESSION['CREATED'] = time();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('Location: ../login.php');
    exit();
}

// Redirect if the user is not of type 'user'
if ($_SESSION['user_type'] !== 'user') {
    header('Location: unauthorized.php');
    exit();
}

// Capture user data from session
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];

// Optionally, log the user's IP address and User-Agent for added security
$user_ip = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];

// Store the IP and User-Agent in session for session validation
if (!isset($_SESSION['user_ip'])) {
    $_SESSION['user_ip'] = $user_ip;
    $_SESSION['user_agent'] = $user_agent;
} elseif ($_SESSION['user_ip'] !== $user_ip || $_SESSION['user_agent'] !== $user_agent) {
    // In case the IP or User-Agent changes, log the user out
    session_unset();
    session_destroy();
    header('Location: ../login.php');
    exit();
}

?>

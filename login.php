<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fix the database connection path
require_once "includes/db_connection.php";

// Function to check if it's an AJAX request
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

// Function to send JSON response
function sendJsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// Function to get redirect URL
function getRedirectURL($user_type, $default_redirect = 'user/dashboard.php') {
    // Check if there's a stored redirect URL
    if (isset($_SESSION['redirect_url'])) {
        $redirect_url = $_SESSION['redirect_url'];
        unset($_SESSION['redirect_url']);
        return $redirect_url;
    }
    
    // Default redirect based on user type
    switch ($user_type) {
        case 'admin':
            return 'admin/dashboard.php';
        case 'merchant':
            return 'merchant/dashboard.php';
        case 'user':
        default:
            return $default_redirect;
    }
}

// Handle POST request (login attempt)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];

    try {
        // Validate inputs
        if (empty($_POST['email']) || empty($_POST['password'])) {
            throw new Exception("Email and password are required");
        }

        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Debug: Check if database connection is working
        if (!isset($pdo)) {
            throw new Exception("Database connection failed");
        }

        // Check in all user tables in sequence
        $user = null;
        $user_type = '';
        
        // 1. First check in admins table
        $stmt = $pdo->prepare("SELECT *, 'admin' as user_type FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $user_type = 'admin';
            error_log("Found admin user: " . $email);
        } else {
            // 2. If not admin, check merchants table
            $stmt = $pdo->prepare("SELECT *, 'merchant' as user_type FROM merchants WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $user_type = 'merchant';
                error_log("Found merchant user: " . $email);
            } else {
                // 3. If not merchant, check users table
                $stmt = $pdo->prepare("SELECT *, 'user' as user_type FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    $user_type = 'user';
                    error_log("Found regular user: " . $email);
                } else {
                    error_log("User not found: " . $email);
                }
            }
        }

        if (!$user) {
            throw new Exception("Invalid email or password");
        }

        // Debug: Check what user data was found
        error_log("User type detected: " . $user_type);
        error_log("User email: " . $user['email']);

        // Verify password
        if (!password_verify($password, $user['password'])) {
            error_log("Password verification failed for: " . $email);
            throw new Exception("Invalid email or password");
        }

        error_log("Password verified successfully for: " . $email);

        // Additional checks for merchants
        if ($user_type === 'merchant') {
            if (isset($user['is_active']) && !$user['is_active']) {
                throw new Exception("Your merchant account has been deactivated.");
            }
            if (isset($user['is_approved']) && !$user['is_approved']) {
                throw new Exception("Your merchant account is pending approval. Please contact administrator.");
            }
        }

        // Additional checks for admins
        if ($user_type === 'admin') {
            if (isset($user['is_active']) && !$user['is_active']) {
                throw new Exception("Your admin account has been deactivated.");
            }
        }

        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['user_type'] = $user_type;
        
        // Set appropriate session data based on user type
        switch ($user_type) {
            case 'admin':
                $_SESSION['full_name'] = $user['full_name'] ?? 'Administrator';
                break;
                
            case 'merchant':
                $_SESSION['full_name'] = $user['company_name'] ?? $user['owners_name'] ?? 'Merchant';
                $_SESSION['merchant_id'] = $user['id'];
                $_SESSION['company_name'] = $user['company_name'] ?? '';
                break;
                
            case 'user':
            default:
                $_SESSION['full_name'] = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                $_SESSION['first_name'] = $user['first_name'] ?? '';
                $_SESSION['last_name'] = $user['last_name'] ?? '';
                $_SESSION['country'] = $user['country'] ?? '';
                $_SESSION['state'] = $user['state'] ?? '';
                $_SESSION['phone'] = $user['phone'] ?? '';
                break;
        }

        $response['success'] = true;
        $response['message'] = "Login successful! Redirecting to dashboard...";
        $response['redirect'] = getRedirectURL($user_type, 'index.php');
        $response['user_type'] = $user_type;

    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }

    // Always return JSON for POST requests
    sendJsonResponse($response);
}

// If it's a GET request, show the login form

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page - Boseats Africa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Carousel Message CSS -->
    <style>
        /* Message Carousel Styles */
        .message-carousel {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            max-width: 400px;
            width: 90%;
        }

        .message-slide {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #28a745;
            transform: translateX(100%);
            opacity: 0;
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            position: relative;
            overflow: hidden;
        }

        .message-slide.show {
            transform: translateX(0);
            opacity: 1;
        }

        .message-slide.hide {
            transform: translateX(100%);
            opacity: 0;
        }

        .message-slide.success {
            border-left-color: #28a745;
            background: linear-gradient(135deg, #d4edda 0%, #f8fff9 100%);
        }

        .message-slide.error {
            border-left-color: #dc3545;
            background: linear-gradient(135deg, #f8d7da 0%, #fff5f6 100%);
        }

        .message-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .message-title {
            font-weight: 600;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .message-title.success {
            color: #155724;
        }

        .message-title.error {
            color: #721c24;
        }

        .message-icon {
            font-size: 18px;
        }

        .close-message {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: #6c757d;
            padding: 0;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .close-message:hover {
            background: rgba(0, 0, 0, 0.1);
            color: #000;
        }

        .message-content {
            color: #495057;
            font-size: 14px;
            line-height: 1.5;
        }

        .progress-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: #28a745;
            width: 100%;
            transform-origin: left;
            animation: progress 5s linear forwards;
        }

        .message-slide.error .progress-bar {
            background: #dc3545;
        }

        /* Success checkmark animation */
        .checkmark {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: block;
            stroke-width: 2;
            stroke: #fff;
            stroke-miterlimit: 10;
            animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
        }

        .checkmark__circle {
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            stroke-width: 2;
            stroke-miterlimit: 10;
            stroke: #28a745;
            fill: none;
            animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }

        .checkmark__check {
            transform-origin: 50% 50%;
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
        }

        @keyframes progress {
            from {
                transform: scaleX(1);
            }
            to {
                transform: scaleX(0);
            }
        }

        @keyframes stroke {
            100% {
                stroke-dashoffset: 0;
            }
        }

        @keyframes scale {
            0%, 100% {
                transform: none;
            }
            50% {
                transform: scale3d(1.1, 1.1, 1);
            }
        }

        @keyframes fill {
            100% {
                box-shadow: inset 0px 0px 0px 30px #28a745;
            }
        }

        /* Loading indicator */
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #28a745;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 8px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .message-carousel {
                right: 10px;
                left: 10px;
                max-width: none;
                width: auto;
            }
            
            .message-slide {
                padding: 15px;
            }
        }
    </style>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f7f7f7;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            display: flex;
            width: 80%;
            max-width: 1200px;
            background-color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }

        .left {
            flex: 1;
            background: url('https://leo.it.tab.digital/s/jZYadWTRQXzJysC/preview');
            background-size: cover;
            background-position: center;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 40px;
        }

        .left-content h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        .left-content p {
            font-size: 1.2rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }

        .right {
            flex: 1;
            padding: 50px;
        }

        .right h2 {
            font-size: 2rem;
            margin-bottom: 30px;
            color: #28a745;
            text-align: center;
        }

        .login-form {
            display: flex;
            flex-direction: column;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #28a745;
        }

        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
        }

        .forgot-password {
            text-align: right;
            margin-bottom: 20px;
        }

        .forgot-password a {
            color: #28a745;
            text-decoration: none;
            font-size: 14px;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .login-btn:hover {
            background-color: #218838;
        }

        .login-btn:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }

        .social-login {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }

        .social-login p {
            margin-bottom: 15px;
            color: #666;
        }

        .social-icons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 20px 0;
        }

        .social-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #f1f1f1;
            color: #333;
            text-decoration: none;
            font-size: 20px;
            transition: all 0.3s ease;
        }

        .social-icon:hover {
            background-color: #28a745;
            color: white;
            transform: translateY(-3px);
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        .register-link a {
            color: #28a745;
            text-decoration: none;
            font-weight: 600;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #28a745;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                width: 95%;
            }

            .left {
                min-height: 200px;
                padding: 30px;
            }

            .right {
                padding: 30px 20px;
            }

            .left-content h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Message Carousel Container -->
    <div class="message-carousel"></div>

    <div class="container">
        <div class="left">
            <div class="left-content">
                <h1>Boseats Africa</h1>
               
            </div>
        </div>

        <div class="right">
            <h2>Welcome Back</h2>

            <form id="loginForm" method="POST" class="login-form">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <span class="toggle-password" id="togglePassword">
                            <i class="far fa-eye"></i>
                        </span>
                    </div>
                </div>

                <div class="forgot-password">
                    <a href="forgot-password.php">Forgot Password?</a>
                </div>

                <button type="submit" class="login-btn" id="loginBtn">
                    Login to Account
                </button>
            </form>

            <div class="social-login">
                <p>Don't have an account?</p>
                <div class="register-link">
                    <a href="user/register.php">Create User Account</a> | 
                    <a href="merchant/create.php">Create Merchant Account</a>
                </div>
                
                <p style="margin: 15px 0;">Or continue with</p>
                <div class="social-icons">
                    <a href="#" class="social-icon">
                        <i class="fab fa-google"></i>
                    </a>
                    <a href="#" class="social-icon">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Message Carousel System
        class MessageCarousel {
            constructor() {
                this.carousel = this.createCarousel();
                this.messageCount = 0;
                this.maxMessages = 5;
            }

            createCarousel() {
                let carousel = document.querySelector('.message-carousel');
                if (!carousel) {
                    carousel = document.createElement('div');
                    carousel.className = 'message-carousel';
                    document.body.appendChild(carousel);
                }
                return carousel;
            }

            show(message, type = 'info') {
                if (this.messageCount >= this.maxMessages) {
                    this.removeOldestMessage();
                }

                const messageId = 'msg-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
                const messageSlide = document.createElement('div');
                
                messageSlide.className = `message-slide ${type}`;
                messageSlide.id = messageId;
                
                const icon = this.getIcon(type);
                const title = this.getTitle(type);
                
                messageSlide.innerHTML = `
                    <div class="message-header">
                        <div class="message-title ${type}">
                            ${icon}
                            <span>${title}</span>
                        </div>
                        <button class="close-message" aria-label="Close message">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="message-content">${this.escapeHtml(message)}</div>
                    <div class="progress-bar"></div>
                `;

                this.carousel.appendChild(messageSlide);
                this.messageCount++;

                // Add event listener for close button
                const closeBtn = messageSlide.querySelector('.close-message');
                closeBtn.addEventListener('click', () => this.close(messageId));

                // Animate in
                requestAnimationFrame(() => {
                    messageSlide.classList.add('show');
                });

                // Auto remove
                const autoRemove = setTimeout(() => {
                    this.close(messageId);
                }, 5000);

                messageSlide.dataset.autoRemove = autoRemove;

                return messageId;
            }

            close(messageId) {
                const messageSlide = document.getElementById(messageId);
                if (!messageSlide) return;

                // Clear auto-remove timer
                if (messageSlide.dataset.autoRemove) {
                    clearTimeout(parseInt(messageSlide.dataset.autoRemove));
                }

                // Animate out
                messageSlide.classList.remove('show');
                messageSlide.classList.add('hide');

                // Remove from DOM after animation
                setTimeout(() => {
                    if (messageSlide.parentNode) {
                        messageSlide.parentNode.removeChild(messageSlide);
                        this.messageCount--;
                    }
                }, 500);
            }

            removeOldestMessage() {
                const messages = this.carousel.querySelectorAll('.message-slide');
                if (messages.length > 0) {
                    this.close(messages[0].id);
                }
            }

            getIcon(type) {
                const icons = {
                    success: `<svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52" aria-hidden="true">
                        <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
                        <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                    </svg>`,
                    error: '<i class="fas fa-exclamation-circle message-icon" aria-hidden="true"></i>',
                    info: '<i class="fas fa-info-circle message-icon" aria-hidden="true"></i>',
                    warning: '<i class="fas fa-exclamation-triangle message-icon" aria-hidden="true"></i>'
                };
                return icons[type] || icons.info;
            }

            getTitle(type) {
                const titles = {
                    success: 'Success!',
                    error: 'Error!',
                    info: 'Info',
                    warning: 'Warning!'
                };
                return titles[type] || 'Info';
            }

            escapeHtml(unsafe) {
                return unsafe
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }
        }

        // Initialize message carousel
        const messageCarousel = new MessageCarousel();

        // Enhanced showMessage function
        function showMessage(message, type = 'info') {
            if (typeof message !== 'string') {
                console.warn('showMessage received non-string message:', message);
                message = String(message);
            }
            
            try {
                return messageCarousel.show(message, type);
            } catch (error) {
                console.error('Error showing message:', error);
                // Fallback to alert
                alert(`${type.toUpperCase()}: ${message}`);
            }
        }

        // Password toggle functionality
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });

        // Enhanced form submission with better error handling
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('loginBtn');
            const originalText = submitBtn.innerHTML;
            
            try {
                // Show loading state
                submitBtn.innerHTML = '<span class="loading"></span> Logging in...';
                submitBtn.disabled = true;

                const formData = new FormData(this);
                
                // Add timeout to fetch
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 30000);
                
                const response = await fetch('login.php', {
                    method: 'POST',
                    body: formData,
                    signal: controller.signal,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                clearTimeout(timeoutId);

                if (!response.ok) {
                    throw new Error(`Server error: ${response.status} ${response.statusText}`);
                }

                // Check content type
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Non-JSON response:', text.substring(0, 200));
                    throw new Error('Server returned invalid response format');
                }

                const data = await response.json();
                console.log('Login response:', data);

                if (data.success) {
                    showMessage(data.message, 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                } else {
                    showMessage(data.message, 'error');
                }
                
            } catch (error) {
                console.error('Login error:', error);
                
                let userMessage = 'Login failed. ';
                
                if (error.name === 'AbortError') {
                    userMessage += 'Request timed out. Please check your connection.';
                } else if (error.message.includes('Server error')) {
                    userMessage += 'Server error occurred. Please try again later.';
                } else if (error.message.includes('invalid response')) {
                    userMessage += 'Server returned invalid response. Please contact support.';
                } else {
                    userMessage += error.message;
                }
                
                showMessage(userMessage, 'error');
            } finally {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });

        // Make functions globally available
        window.showMessage = showMessage;
        window.messageCarousel = messageCarousel;

        console.log('Login script loaded successfully');
    </script>
</body>
</html>
<?php
session_start();
include_once '../includes/mobile_footer.php';
// Check if user is logged in as merchant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'merchant') {
    header('Location: ../login.php');
    exit();
}

$merchant_name = $_SESSION['company_name'] ?? $_SESSION['full_name'] ?? 'Merchant';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merchant Dashboard - Boseats Africa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .dashboard-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 50px;
            text-align: center;
            max-width: 600px;
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .dashboard-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #28a745, #17a2b8, #6f42c1);
        }

        .welcome-section {
            margin-bottom: 40px;
        }

        .welcome-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }

        h1 {
            color: #333;
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .merchant-name {
            color: #28a745;
            font-weight: 600;
        }

        .subtitle {
            color: #666;
            font-size: 1.2rem;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .coming-soon-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            border-left: 5px solid #28a745;
        }

        .coming-soon-icon {
            font-size: 3rem;
            color: #ffc107;
            margin-bottom: 20px;
        }

        .coming-soon-section h2 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 15px;
        }

        .coming-soon-section p {
            color: #666;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .features-list {
            text-align: left;
            margin: 25px 0;
        }

        .features-list li {
            margin: 10px 0;
            padding-left: 30px;
            position: relative;
            color: #555;
        }

        .features-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #28a745;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .progress-section {
            margin: 30px 0;
        }

        .progress-bar {
            background: #e9ecef;
            border-radius: 10px;
            height: 10px;
            margin: 20px 0;
            overflow: hidden;
        }

        .progress {
            background: linear-gradient(90deg, #28a745, #20c997);
            height: 100%;
            width: 35%;
            border-radius: 10px;
            animation: progressAnimation 2s ease-in-out;
        }

        @keyframes progressAnimation {
            0% { width: 0%; }
            100% { width: 75%; }
        }

        .progress-text {
            color: #666;
            font-size: 0.9rem;
        }

        .contact-info {
            background: #e8f5e8;
            border-radius: 10px;
            padding: 20px;
            margin: 25px 0;
        }

        .contact-info h3 {
            color: #28a745;
            margin-bottom: 15px;
        }

        .contact-info p {
            color: #555;
            margin: 5px 0;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #28a745;
            color: white;
        }

        .btn-primary:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
            transform: translateY(-2px);
        }

        .btn-logout {
            background: #dc3545;
            color: white;
        }

        .btn-logout:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .notification {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            color: #856404;
        }

        .countdown {
            font-size: 1.5rem;
            font-weight: bold;
            color: #28a745;
            margin: 20px 0;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 30px 20px;
                margin: 20px;
            }

            h1 {
                font-size: 2rem;
            }

            .action-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        .user-info {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 25px;
            font-size: 0.9rem;
            color: #666;
        }

        .badge {
            background: #28a745;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <div class="user-info">
        <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($merchant_name); ?>
        <span class="badge">Merchant</span>
    </div>

    <div class="dashboard-container">
        <div class="welcome-section">
            <div class="welcome-icon">
                <i class="fas fa-store"></i>
            </div>
            <h1>Welcome back, <span class="merchant-name"><?php echo htmlspecialchars($merchant_name); ?></span>!</h1>
            <p class="subtitle">Your merchant dashboard is under development and will be available soon.</p>
        </div>

        <div class="notification">
            <i class="fas fa-info-circle"></i>
            <strong>Important:</strong> We're working hard to bring you the best merchant experience. Thank you for your patience!
        </div>

        <div class="coming-soon-section">
            <div class="coming-soon-icon">
                <i class="fas fa-tools"></i>
            </div>
            <h2>Dashboard Coming Soon</h2>
            <p>We're building powerful tools to help you manage your business efficiently.</p>
            
            <div class="progress-section">
                <div class="progress-bar">
                    <div class="progress"></div>
                </div>
                <div class="progress-text">Development Progress: 35% Complete</div>
            </div>

            <div class="features-list">
                <h3>Features you can expect:</h3>
                <ul>
                    <li>Product Management System</li>
                    <li>Order Tracking & Management</li>
                    <li>Sales Analytics & Reports</li>
                    <li>Customer Management</li>
                    <li>Payment Processing</li>
                    <li>Inventory Management</li>
                    <li>Business Performance Insights</li>
                </ul>
            </div>
        </div>

        <div class="contact-info">
            <h3><i class="fas fa-headset"></i> Need Assistance?</h3>
            <p>Email: info@boseatsafrica.com</p>
            <p>Phone: +234-9033157301</p>
            <p>Hours: Mon-Fri, 9AM-6PM</p>
        </div>

        <div class="countdown">
            <i class="fas fa-clock"></i> Launching in: <span id="countdown-timer">30 days</span>
        </div>

        <div class="action-buttons">
            <a href="profile.php" class="btn btn-primary">
                <i class="fas fa-user-edit"></i> Update Profile
            </a>
            <a href="products.php" class="btn btn-secondary">
                <i class="fas fa-box"></i> View Products
            </a>
            <a href="../logout.php" class="btn btn-logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <script>
        // Simple countdown timer (30 days from now)
        function updateCountdown() {
            const launchDate = new Date();
            launchDate.setDate(launchDate.getDate() + 30);
            
            const now = new Date();
            const diff = launchDate - now;
            
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            
            document.getElementById('countdown-timer').textContent = 
                `${days} days, ${hours} hours`;
        }

        // Update countdown immediately and every hour
        updateCountdown();
        setInterval(updateCountdown, 3600000); // Update every hour

        // Add some interactive animations
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.features-list li');
            elements.forEach((element, index) => {
                element.style.animationDelay = `${index * 0.1}s`;
                element.style.animation = 'fadeInUp 0.5s ease-out forwards';
            });
        });

        // Add CSS for fadeInUp animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            .features-list li {
                opacity: 0;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
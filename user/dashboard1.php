<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: ../login.php');
    exit();
}

$user_name = $_SESSION['full_name'];
$first_name = $_SESSION['first_name'] ?? '';
$last_name = $_SESSION['last_name'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Dashboard - Boseats Africa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #28a745;
        }

        .welcome-section {
            text-align: center;
            margin: 30px 0;
            padding: 30px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            border-left: 5px solid #28a745;
        }

        .welcome-icon {
            font-size: 3rem;
            color: #28a745;
            margin-bottom: 15px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        .welcome-section h1 {
            color: #333;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .user-name {
            color: #28a745;
            font-weight: bold;
        }

        .welcome-section p {
            color: #666;
            font-size: 1.2rem;
            line-height: 1.6;
        }

        .user-info {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            border: 2px solid #e9ecef;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .info-item {
            padding: 15px;
            background: white;
            border-radius: 8px;
            border-left: 4px solid #28a745;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }

        .logout-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }

        .coming-soon-section {
            background: #fff3cd;
            border: 2px solid #ffeaa7;
            border-radius: 10px;
            padding: 30px;
            margin: 30px 0;
            text-align: center;
        }

        .coming-soon-icon {
            font-size: 3rem;
            color: #ffc107;
            margin-bottom: 20px;
        }

        .coming-soon-section h2 {
            color: #856404;
            font-size: 2rem;
            margin-bottom: 15px;
        }

        .coming-soon-section p {
            color: #856404;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .feature-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            border-top: 4px solid #28a745;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 2.5rem;
            color: #28a745;
            margin-bottom: 15px;
        }

        .feature-card h3 {
            color: #333;
            margin-bottom: 10px;
        }

        .feature-card p {
            color: #666;
            font-size: 0.9rem;
        }

        .progress-section {
            margin: 30px 0;
            text-align: center;
        }

        .progress-bar {
            background: #e9ecef;
            border-radius: 10px;
            height: 12px;
            margin: 20px 0;
            overflow: hidden;
        }

        .progress {
            background: linear-gradient(90deg, #28a745, #20c997);
            height: 100%;
            width: 45%;
            border-radius: 10px;
            animation: progressAnimation 2s ease-in-out;
        }

        @keyframes progressAnimation {
            0% { width: 0%; }
            100% { width: 60%; }
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
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
        }

        .btn-secondary {
            background: #17a2b8;
            color: white;
        }

        .btn-secondary:hover {
            background: #138496;
            transform: translateY(-2px);
        }

        .countdown {
            background: #e8f5e8;
            border-radius: 10px;
            padding: 20px;
            margin: 25px 0;
            text-align: center;
        }

        .countdown h3 {
            color: #28a745;
            margin-bottom: 10px;
        }

        #countdown-timer {
            font-size: 1.5rem;
            font-weight: bold;
            color: #28a745;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 20px;
                margin: 10px;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .welcome-section h1 {
                font-size: 2rem;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="header">
            <h1>User Dashboard</h1>
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>

        <div class="welcome-section">
            <div class="welcome-icon">
                <i class="fas fa-user-circle"></i>
            </div>
            <h1>Welcome back, <span class="user-name"><?php echo htmlspecialchars($user_name); ?>!</span></h1>
            <p>We're excited to have you here! Your personalized dashboard is being enhanced with amazing features.</p>
        </div>
        
        <div class="user-info">
            <h2><i class="fas fa-id-card"></i> Your Profile Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <strong><i class="fas fa-user"></i> Name:</strong> <?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>
                </div>
                <div class="info-item">
                    <strong><i class="fas fa-envelope"></i> Email:</strong> <?php echo htmlspecialchars($_SESSION['email']); ?>
                </div>
                <div class="info-item">
                    <strong><i class="fas fa-globe"></i> Country:</strong> <?php echo htmlspecialchars($_SESSION['country'] ?? 'Not set'); ?>
                </div>
                <div class="info-item">
                    <strong><i class="fas fa-map-marker-alt"></i> State:</strong> <?php echo htmlspecialchars($_SESSION['state'] ?? 'Not set'); ?>
                </div>
                <div class="info-item">
                    <strong><i class="fas fa-phone"></i> Phone:</strong> <?php echo htmlspecialchars($_SESSION['phone'] ?? 'Not set'); ?>
                </div>
            </div>
        </div>

        <div class="coming-soon-section">
            <div class="coming-soon-icon">
                <i class="fas fa-rocket"></i>
            </div>
            <h2>🚀 Exciting Features Coming Soon!</h2>
            <p>We're working hard to bring you an enhanced user experience with powerful new features.</p>
            
            <div class="progress-section">
                <div class="progress-bar">
                    <div class="progress"></div>
                </div>
                <p>Development Progress: 45% Complete</p>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3>Smart Shopping</h3>
                    <p>Personalized product recommendations and easy ordering</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Order Tracking</h3>
                    <p>Real-time order status and delivery updates</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3>Wishlist</h3>
                    <p>Save your favorite products for later</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3>Reviews & Ratings</h3>
                    <p>Share your experience with products</p>
                </div>
            </div>
        </div>

        <div class="countdown">
            <h3><i class="fas fa-clock"></i> New Features Launching In:</h3>
            <div id="countdown-timer">30 days</div>
        </div>

        <div class="action-buttons">
            <a href="profile.php" class="btn btn-primary">
                <i class="fas fa-user-edit"></i> Edit Profile
            </a>
            <a href="orders.php" class="btn btn-secondary">
                <i class="fas fa-history"></i> Order History
            </a>
            <a href="settings.php" class="btn btn-primary">
                <i class="fas fa-cog"></i> Settings
            </a>
        </div>
    </div>

    <script>
        // Countdown timer
        function updateCountdown() {
            const launchDate = new Date();
            launchDate.setDate(launchDate.getDate() + 30);
            
            const now = new Date();
            const diff = launchDate - now;
            
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            
            document.getElementById('countdown-timer').textContent = 
                `${days} days and ${hours} hours`;
        }

        // Update countdown immediately and every hour
        updateCountdown();
        setInterval(updateCountdown, 3600000);

        // Add animation to feature cards
        document.addEventListener('DOMContentLoaded', function() {
            const featureCards = document.querySelectorAll('.feature-card');
            featureCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>
<?php
include_once '../includes/db_connection.php';
include_once '../includes/protect_user.php';

// Fetch user data from database
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch user statistics
$orders_count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
$orders_count_stmt->execute([$user_id]);
$orders_count = $orders_count_stmt->fetch(PDO::FETCH_ASSOC)['count'];

$wishlist_count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
$wishlist_count_stmt->execute([$user_id]);
$wishlist_count = $wishlist_count_stmt->fetch(PDO::FETCH_ASSOC)['count'];

$cart_count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
$cart_count_stmt->execute([$user_id]);
$cart_count = $cart_count_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Fetch total amount spent
$total_spent_stmt = $pdo->prepare("SELECT SUM(total_amount) as total FROM orders WHERE user_id = ? AND payment_status = 'completed'");
$total_spent_stmt->execute([$user_id]);
$total_spent_result = $total_spent_stmt->fetch(PDO::FETCH_ASSOC);
$total_spent = $total_spent_result['total'] ? $total_spent_result['total'] : 0;

// Fetch unread notifications count
$unread_count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
$unread_count_stmt->execute([$user_id]);
$unread_count = $unread_count_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Fetch recent orders
$recent_orders_stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
$recent_orders_stmt->execute([$user_id]);
$recent_orders = $recent_orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if user has a merchant account
$merchant_stmt = $pdo->prepare("SELECT * FROM merchants WHERE email = ?");
$merchant_stmt->execute([$user['email']]);
$merchant_account = $merchant_stmt->fetch(PDO::FETCH_ASSOC);

// Get profile picture URL
$profile_picture_url = !empty($user['profile_picture']) 
    ? '../uploads/profile_pictures/' . $user['profile_picture'] . '?v=' . time() 
    : 'https://via.placeholder.com/150/28a745/ffffff?text=' . urlencode(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BoseaAfrica</title>
    <link rel="stylesheet" href="../assets/css/user_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            background-color: #28a745;
            width: 280px;
            padding: 20px;
            color: white;
            min-height: 100vh;
            position: sticky;
            top: 0;
            transition: transform 0.3s ease;
            z-index: 1000;
        }

        .sidebar-header {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin: 15px 0;
        }

        .sidebar-menu a {
            color: white;
            text-decoration: none;
            font-size: 18px;
            display: block;
            padding: 12px;
            border-radius: 5px;
            transition: background-color 0.3s;
            white-space: nowrap;
        }

        .sidebar-menu a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.2);
            font-weight: bold;
        }

        .sidebar-menu i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }

        .sidebar-logo {
            width: 80%;
            max-width: 200px;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        /* Fixed Mobile Menu Toggle Styles */
        .mobile-menu-toggle {
            display: none;
            background: #2c3e50;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1002;
            width: 45px;
            height: 45px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
            align-items: center;
            justify-content: center;
        }

        .mobile-menu-toggle:hover {
            background: #34495e;
            transform: scale(1.05);
        }

        #closeSidebar {
            background: #2c3e50 !important;
            color: white !important;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: none;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1003;
        }

        /* Overlay for mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        .content {
            flex-grow: 1;
            padding: 40px;
            position: relative;
        }

        /* Welcome Section */
        .welcome-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #28a745;
        }

        .welcome-section h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .welcome-section p {
            color: #6c757d;
            font-size: 16px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-top: 4px solid #28a745;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card i {
            font-size: 40px;
            color: #28a745;
            margin-bottom: 15px;
        }

        .stat-card h3 {
            font-size: 32px;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .stat-card p {
            color: #6c757d;
            font-size: 16px;
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        /* Recent Activity */
        .recent-activity {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-header h2 {
            color: #2c3e50;
            font-size: 22px;
        }

        .view-all {
            color: #28a745;
            text-decoration: none;
            font-weight: 600;
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 3px solid #28a745;
            transition: background 0.3s ease;
        }

        .activity-item:hover {
            background: #e9ecef;
        }

        .activity-item i {
            font-size: 20px;
            color: #28a745;
            margin-right: 15px;
        }

        .activity-content h4 {
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .activity-content p {
            color: #6c757d;
            font-size: 14px;
        }

        /* Quick Actions */
        .quick-actions {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .action-btn {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            text-decoration: none;
            color: #495057;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            background: #28a745;
            color: white;
            border-color: #28a745;
        }

        .action-btn i {
            margin-right: 10px;
            font-size: 18px;
        }

        /* Profile Card */
        .profile-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 30px;
        }

        .profile-info {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .profile-pic {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 3px solid #28a745;
        }

        .profile-details h4 {
            color: #2c3e50;
            margin-bottom: 5px;
            font-size: 18px;
        }

        .profile-details p {
            color: #6c757d;
            font-size: 14px;
        }

        .profile-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .profile-stat {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .profile-stat .number {
            display: block;
            font-size: 1.5rem;
            font-weight: 700;
            color: #28a745;
        }

        .profile-stat .label {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 5px;
        }

        /* Merchant Status */
        .merchant-status {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-top: 20px;
        }

        .merchant-status h4 {
            color: #856404;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .merchant-status p {
            color: #856404;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .btn-upgrade {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s ease;
            display: inline-block;
        }

        .btn-upgrade:hover {
            background: #218838;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 48px;
            color: #dee2e6;
            margin-bottom: 15px;
        }

        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: flex;
            }

            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                transform: translateX(-100%);
                width: 280px;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .sidebar-overlay.active {
                display: block;
            }

            .container {
                flex-direction: column;
            }

            .content {
                padding: 70px 20px 20px 20px;
                margin-left: 0;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 480px) {
            .mobile-menu-toggle {
                top: 10px;
                left: 10px;
                width: 40px;
                height: 40px;
                font-size: 18px;
            }

            .content {
                padding: 60px 15px 15px 15px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .profile-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <img src="https://leo.it.tab.digital/s/H5qHAKxTQHzXsyo/preview" alt="BoseaAfrica Logo" class="sidebar-logo">
                </div>
                <button class="mobile-menu-toggle" id="closeSidebar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <ul class="sidebar-menu">
                <li><a href="../index.php"><i class="fa fa-home"></i> Home page</a></li>
                <li><a href="dashboard.php" class="active"><i class="fa fa-user"></i> Dashboard</a></li>
                <li><a href="profile.php"><i class="fa fa-user"></i> Profile</a></li>
                <li><a href="my_orders.php"><i class="fa fa-box"></i> My Orders</a></li>
                <li>
                    <a href="notifications.php">
                        <i class="fa fa-bell"></i> Notifications 
                        <?php if ($unread_count > 0): ?>
                            <span style="background: #dc3545; color: white; border-radius: 50%; width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; margin-left: 5px;">
                                <?php echo $unread_count; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
                <li><a href="discount_offers.php"><i class="fa fa-tags"></i> Discount Offers</a></li>
                <li><a href="payments.php"><i class="fa fa-credit-card"></i> Payment</a></li>
                <li><a href="../logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="content">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1>Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>! 👋</h1>
                <p>Here's what's happening with your account today.</p>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-shopping-bag"></i>
                    <h3><?php echo $orders_count; ?></h3>
                    <p>Total Orders</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-heart"></i>
                    <h3><?php echo $wishlist_count; ?></h3>
                    <p>Wishlist Items</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-shopping-cart"></i>
                    <h3><?php echo $cart_count; ?></h3>
                    <p>Cart Items</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-money-bill-wave"></i>
                    <h3>₦<?php echo number_format($total_spent, 2); ?></h3>
                    <p>Total Spent</p>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Recent Orders -->
                <div class="recent-activity">
                    <div class="section-header">
                        <h2>Recent Orders</h2>
                        <a href="my_orders.php" class="view-all">View All</a>
                    </div>
                    <div class="activity-list">
                        <?php if (count($recent_orders) > 0): ?>
                            <?php foreach ($recent_orders as $order): ?>
                                <div class="activity-item">
                                    <i class="fas fa-shopping-bag"></i>
                                    <div class="activity-content">
                                        <h4>Order #<?php echo htmlspecialchars($order['id']); ?></h4>
                                        <p>₦<?php echo number_format($order['total_amount'], 2); ?> • <?php echo date('M j, Y', strtotime($order['created_at'])); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-shopping-bag"></i>
                                <h4>No orders yet</h4>
                                <p>You haven't placed any orders. Start shopping to see your orders here.</p>
                                <a href="../index.php" class="action-btn" style="margin-top: 15px; display: inline-flex;">
                                    <i class="fas fa-shopping-cart"></i> Start Shopping
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <div class="section-header">
                        <h2>Quick Actions</h2>
                    </div>
                    <div class="action-buttons">
                        <a href="../index.php" class="action-btn">
                            <i class="fas fa-shopping-cart"></i>
                            Continue Shopping
                        </a>
                        <a href="my_orders.php" class="action-btn">
                            <i class="fas fa-box"></i>
                            View Orders
                        </a>
                        <a href="profile.php" class="action-btn">
                            <i class="fas fa-user-edit"></i>
                            Edit Profile
                        </a>
                        <a href="notifications.php" class="action-btn">
                            <i class="fas fa-bell"></i>
                            Notifications
                        </a>
                    </div>
                </div>
            </div>

            <!-- Profile Card -->
            <div class="profile-card">
                <div class="section-header">
                    <h2>Your Profile</h2>
                </div>
                <div class="profile-info">
                    <img src="<?php echo $profile_picture_url; ?>" alt="Profile Picture" class="profile-pic">
                    <div class="profile-details">
                        <h4><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>
                
                <div class="profile-stats">
                    <div class="profile-stat">
                        <span class="number"><?php echo $orders_count; ?></span>
                        <span class="label">Orders</span>
                    </div>
                    <div class="profile-stat">
                        <span class="number"><?php echo $wishlist_count; ?></span>
                        <span class="label">Wishlist</span>
                    </div>
                </div>

                <?php if (!$merchant_account): ?>
                    <div class="merchant-status">
                        <h4><i class="fas fa-crown"></i> Upgrade Account</h4>
                        <p>Become a merchant and start selling on our platform</p>
                        <a href="profile.php" class="btn-upgrade">Upgrade Now</a>
                    </div>
                <?php else: ?>
                    <div class="merchant-status" style="background: linear-gradient(135deg, #d4edda, #c3e6cb);">
                        <h4><i class="fas fa-check-circle"></i> Merchant Account</h4>
                        <p>Your merchant application is <?php echo htmlspecialchars($merchant_account['account_status']); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Mobile sidebar functionality
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const closeSidebar = document.getElementById('closeSidebar');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        function openSidebar() {
            sidebar.classList.add('active');
            sidebarOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebarFunc() {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        mobileMenuToggle.addEventListener('click', openSidebar);
        closeSidebar.addEventListener('click', closeSidebarFunc);
        sidebarOverlay.addEventListener('click', closeSidebarFunc);

        // Close sidebar when clicking on a link (mobile)
        document.querySelectorAll('.sidebar-menu a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    closeSidebarFunc();
                }
            });
        });

        // Close sidebar when pressing Escape key
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && sidebar.classList.contains('active')) {
                closeSidebarFunc();
            }
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                closeSidebarFunc();
            }
        });
    </script>
</body>
</html>
<?php
include_once '../includes/db_connection.php';
include_once '../includes/protect_user.php';

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch user's orders with payment information
$orders_stmt = $pdo->prepare("
    SELECT * FROM orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$orders_stmt->execute([$user_id]);
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate order statistics
$total_orders = count($orders);
$pending_payments = 0;
$completed_payments = 0;
$total_spent = 0;

foreach ($orders as $order) {
    if ($order['payment_status'] === 'completed') {
        $completed_payments++;
        $total_spent += $order['total_amount'];
    } elseif ($order['payment_status'] === 'pending') {
        $pending_payments++;
    }
}

// Handle payment retry
if (isset($_GET['retry_payment']) && is_numeric($_GET['retry_payment'])) {
    $order_id = $_GET['retry_payment'];
    
    // Verify order belongs to user and payment is pending
    $order_stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND payment_status = 'pending'");
    $order_stmt->execute([$order_id, $user_id]);
    $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        // Here you would integrate with your payment gateway
        // For now, we'll simulate payment processing
        $_SESSION['payment_redirect'] = "Redirecting to payment gateway for Order #" . $order_id;
        header("Location: process_payment.php?order_id=" . $order_id);
        exit();
    } else {
        $_SESSION['payment_error'] = "Order not found or already paid!";
        header("Location: payments.php");
        exit();
    }
}

// Handle print receipt
if (isset($_GET['print_receipt']) && is_numeric($_GET['print_receipt'])) {
    $order_id = $_GET['print_receipt'];
    
    // Verify order belongs to user and payment is completed
    $order_stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND payment_status = 'completed'");
    $order_stmt->execute([$order_id, $user_id]);
    $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        // Generate printable receipt
        header("Location: print_receipt.php?order_id=" . $order_id);
        exit();
    } else {
        $_SESSION['payment_error'] = "Receipt not available for this order!";
        header("Location: payments.php");
        exit();
    }
}

// Fetch wishlist and cart counts
$wishlist_stmt = $pdo->prepare("SELECT * FROM wishlist WHERE user_id = ?");
$wishlist_stmt->execute([$user_id]);
$wishlist_items = $wishlist_stmt->fetchAll(PDO::FETCH_ASSOC);
$wishlist_count = count($wishlist_items);

$cart_stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ?");
$cart_stmt->execute([$user_id]);
$cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);
$cart_count = count($cart_items);

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
    <title>Payments - User Dashboard</title>
    <link rel="stylesheet" href="../assets/css/user_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
        }

        .container {
            display: flex;
            flex-direction: row;
            gap: 20px;
        }

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

        #closeSidebar:hover {
            background: #34495e !important;
            transform: scale(1.05);
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

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-header h2 {
            font-size: 28px;
            color: #333;
        }

        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .profile-info {
            display: flex;
            align-items: center;
            position: relative;
        }

        .profile-pic {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            margin-right: 20px;
            object-fit: cover;
            border: 2px solid #28a745;
        }

        .profile-pic-edit {
            position: absolute;
            bottom: 5px;
            left: 50px;
            background: #28a745;
            color: white;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 2px solid white;
            font-size: 12px;
        }

        .profile-details h3 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .profile-details p {
            font-size: 16px;
            color: #777;
        }

        .profile-buttons {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .favorite-button, .cart-button {
            background-color: transparent;
            color: #28a745;
            padding: 10px;
            border: 2px solid #28a745;
            font-size: 20px;
            cursor: pointer;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            position: relative;
        }

        .favorite-button:hover, .cart-button:hover {
            background-color: #28a745;
            color: white;
        }

        .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .edit-button {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .edit-button:hover {
            background-color: #218838;
        }

        /* Payment Stats */
        .payment-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #28a745;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #28a745, #20c997);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 24px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #6c757d;
            font-size: 16px;
        }

        /* Payment Tabs */
        .payment-tabs {
            display: flex;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 5px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .payment-tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 16px;
            font-weight: 600;
            color: #6c757d;
            border-radius: 5px;
            transition: all 0.3s;
            min-width: 150px;
        }

        .payment-tab.active {
            background: #28a745;
            color: white;
        }

        .payment-section {
            display: none;
        }

        .payment-section.active {
            display: block;
        }

        .section-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }

        /* Orders Table */
        .orders-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .table-header {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr 1fr;
            padding: 20px;
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            border-bottom: 1px solid #e9ecef;
        }

        .table-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr 1fr;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            align-items: center;
            transition: background-color 0.3s;
        }

        .table-row:hover {
            background-color: #f8f9fa;
        }

        .table-row:last-child {
            border-bottom: none;
        }

        .order-id {
            font-weight: 600;
            color: #28a745;
        }

        .order-amount {
            font-weight: 600;
            color: #333;
        }

        .payment-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            display: inline-block;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }

        .status-refunded {
            background: #e2e3e5;
            color: #383d41;
        }

        .order-actions {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .action-btn.primary {
            background: #28a745;
            color: white;
        }

        .action-btn.primary:hover {
            background: #218838;
        }

        .action-btn.secondary {
            background: #6c757d;
            color: white;
        }

        .action-btn.secondary:hover {
            background: #5a6268;
        }

        .action-btn:disabled {
            background: #e9ecef;
            color: #6c757d;
            cursor: not-allowed;
        }

        /* Order Details */
        .order-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 15px;
            display: none;
        }

        .order-details.active {
            display: block;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .detail-item {
            background: white;
            padding: 15px;
            border-radius: 5px;
            border-left: 3px solid #28a745;
        }

        .detail-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .detail-value {
            font-size: 14px;
            color: #333;
            font-weight: 500;
        }

        /* Payment Methods */
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .payment-method-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: 2px solid #e9ecef;
            transition: all 0.3s;
            cursor: pointer;
        }

        .payment-method-card:hover {
            border-color: #28a745;
            transform: translateY(-2px);
        }

        .payment-method-card.active {
            border-color: #28a745;
            background: #f8fff9;
        }

        .method-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .method-icon {
            width: 50px;
            height: 50px;
            background: #f8f9fa;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #28a745;
        }

        .method-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        .method-description {
            color: #6c757d;
            line-height: 1.5;
            margin-bottom: 15px;
        }

        .method-features {
            list-style: none;
        }

        .method-features li {
            padding: 5px 0;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .method-features i {
            color: #28a745;
            font-size: 12px;
        }

        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .empty-state i {
            font-size: 64px;
            color: #dee2e6;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #6c757d;
        }

        .empty-state p {
            font-size: 16px;
        }

        /* Alert Messages */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid transparent;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border-color: #bee5eb;
        }

        /* Add this new style for disabled payment methods */
        .payment-method-card.disabled {
            opacity: 0.6;
            cursor: not-allowed;
            border-color: #e9ecef !important;
        }

        .payment-method-card.disabled:hover {
            transform: none;
            border-color: #e9ecef !important;
        }

        .coming-soon-badge {
            background: #ffc107;
            color: #212529;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 10px;
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
                padding: 70px 20px 20px 20px; /* Added top padding for mobile menu */
                margin-left: 0;
            }

            .payment-stats {
                grid-template-columns: 1fr;
            }

            .table-header {
                display: none;
            }

            .table-row {
                grid-template-columns: 1fr;
                gap: 10px;
                padding: 15px;
                border: 1px solid #e9ecef;
                border-radius: 8px;
                margin-bottom: 10px;
            }

            .table-row::before {
                content: attr(data-label);
                font-weight: 600;
                color: #333;
                margin-bottom: 5px;
            }

            .order-actions {
                justify-content: center;
                margin-top: 10px;
            }

            .payment-tabs {
                flex-direction: column;
            }

            .payment-methods {
                grid-template-columns: 1fr;
            }

            .profile-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .profile-buttons {
                width: 100%;
                justify-content: space-between;
            }
        }

        @media (max-width: 480px) {
            .stat-card {
                padding: 20px;
            }

            .action-btn {
                padding: 6px 12px;
                font-size: 12px;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

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
                  <li><a href="dashboard.php" class=""><i class="fa fa-user"></i> Dashboard</a></li>
                <li><a href="profile.php" class=""><i class="fa fa-user"></i> Profile</a></li>
                              <li><a href="my_orders.php"><i class="fa fa-box"></i> My Orders</a></li>
                   <li>
                    <a href="notifications.php">
                        <i class="fa fa-bell"></i> Notifications 
                        <?php 
                        $unread_count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
                        $unread_count_stmt->execute([$user_id]);
                        $unread_count = $unread_count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                        
                        if ($unread_count > 0): ?>
                            <span style="background: #dc3545; color: white; border-radius: 50%; width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; margin-left: 5px;">
                                <?php echo $unread_count; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
                <li><a href="discount_offers.php"><i class="fa fa-tags"></i> Discount Offers</a></li>
                <li><a href="payments.php" class="active"><i class="fa fa-credit-card"></i> Payment</a></li>
                <li><a href="../logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="content">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-info">
                    <img src="<?php echo $profile_picture_url; ?>" 
                         alt="Profile Picture" class="profile-pic" id="profilePicture">
                    <div class="profile-pic-edit" id="openPictureModal">
                        <i class="fas fa-camera"></i>
                    </div>
                    <div class="profile-details">
                        <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>
               
            </div>

            <!-- Page Header -->
            <div class="page-header">
                <h2>Payments & Order History</h2>
            </div>

            <!-- Alert Messages -->
            <?php if (isset($_SESSION['payment_redirect'])): ?>
                <div class="alert alert-info">
                    <?php 
                    echo htmlspecialchars($_SESSION['payment_redirect']);
                    unset($_SESSION['payment_redirect']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['payment_error'])): ?>
                <div class="alert alert-error">
                    <?php 
                    echo htmlspecialchars($_SESSION['payment_error']);
                    unset($_SESSION['payment_error']);
                    ?>
                </div>
            <?php endif; ?>

            <!-- Payment Statistics -->
            <div class="payment-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_orders; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-value"><?php echo $completed_payments; ?></div>
                    <div class="stat-label">Completed Payments</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-value"><?php echo $pending_payments; ?></div>
                    <div class="stat-label">Pending Payments</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-value">₦<?php echo number_format($total_spent, 2); ?></div>
                    <div class="stat-label">Total Spent</div>
                </div>
            </div>

            <!-- Payment Tabs -->
            <div class="payment-tabs">
                <button class="payment-tab active" data-tab="orders">Order History</button>
                <button class="payment-tab" data-tab="payment-methods">Payment Methods</button>
                <button class="payment-tab" data-tab="billing">Billing Information</button>
            </div>

            <!-- Order History Section -->
            <div class="payment-section active" id="orders-section">
                <h3 class="section-title">Order History & Payments</h3>
                
                <?php if (count($orders) > 0): ?>
                    <div class="orders-table">
                        <div class="table-header">
                            <div>Order ID</div>
                            <div>Amount</div>
                            <div>Payment Status</div>
                            <div>Order Status</div>
                            <div>Actions</div>
                        </div>
                        
                        <?php foreach ($orders as $order): 
                            // Safely decode order_data
                            $order_data = [];
                            $order_items = [];
                            
                            if (!empty($order['order_data'])) {
                                $decoded_data = json_decode($order['order_data'], true);
                                
                                // Handle different possible structures of order_data
                                if (is_array($decoded_data)) {
                                    // If it's already an array of items
                                    if (isset($decoded_data[0]) && is_array($decoded_data[0])) {
                                        $order_items = $decoded_data;
                                    } 
                                    // If it's a single item with quantity
                                    elseif (isset($decoded_data['items']) && is_array($decoded_data['items'])) {
                                        $order_items = $decoded_data['items'];
                                    }
                                    // If it's a different structure, try to extract items
                                    else {
                                        // Look for any array that might contain items
                                        foreach ($decoded_data as $key => $value) {
                                            if (is_array($value) && isset($value[0]) && is_array($value[0])) {
                                                $order_items = $value;
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        ?>
                            <div class="table-row" data-label="Order #<?php echo $order['id']; ?>">
                                <div class="order-id" data-label="Order ID">#<?php echo $order['id']; ?></div>
                                <div class="order-amount" data-label="Amount">₦<?php echo number_format($order['total_amount'], 2); ?></div>
                                <div data-label="Payment Status">
                                    <span class="payment-status status-<?php echo $order['payment_status']; ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </div>
                                <div data-label="Order Status">
                                    <span class="payment-status status-<?php echo $order['order_status']; ?>">
                                        <?php echo ucfirst($order['order_status']); ?>
                                    </span>
                                </div>
                                <div class="order-actions" data-label="Actions">
                                    <button class="action-btn secondary view-details" data-order="<?php echo $order['id']; ?>">
                                        <i class="fas fa-eye"></i> Details
                                    </button>
                                    <?php if ($order['payment_status'] === 'pending'): ?>
                                        <a href="?retry_payment=<?php echo $order['id']; ?>" class="action-btn primary">
                                            <i class="fas fa-credit-card"></i> Pay Now
                                        </a>
                                    <?php else: ?>
                                        <a href="?print_receipt=<?php echo $order['id']; ?>" class="action-btn primary" target="_blank">
                                            <i class="fas fa-receipt"></i> Receipt
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Order Details -->
                            <div class="order-details" id="details-<?php echo $order['id']; ?>">
                                <div class="detail-grid">
                                    <div class="detail-item">
                                        <div class="detail-label">Order Date</div>
                                        <div class="detail-value"><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">Payment Reference</div>
                                        <div class="detail-value"><?php echo $order['payment_reference'] ?: 'N/A'; ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">Delivery Location</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($order['delivery_location']); ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">Delivery Address</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($order['delivery_address']); ?></div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($order_items)): ?>
                                    <div style="margin-top: 15px;">
                                        <div class="detail-label" style="margin-bottom: 10px;">Order Items</div>
                                        <?php foreach ($order_items as $item): 
                                            // Skip empty items or items without proper data
                                            if (empty($item) || (!isset($item['name']) && !isset($item['product_name']))) {
                                                continue;
                                            }
                                            
                                            $item_name = $item['name'] ?? $item['product_name'] ?? 'Product';
                                            $item_price = $item['price'] ?? $item['product_price'] ?? 0;
                                            $item_quantity = $item['quantity'] ?? 1;
                                            $item_total = $item_price * $item_quantity;
                                        ?>
                                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: white; border-radius: 5px; margin-bottom: 5px;">
                                                <div style="flex: 1;">
                                                    <strong><?php echo htmlspecialchars($item_name); ?></strong>
                                                    <span style="color: #6c757d; margin-left: 10px;">x<?php echo $item_quantity; ?></span>
                                                </div>
                                                <div style="font-weight: 600;">
                                                    ₦<?php echo number_format($item_total, 2); ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        
                                        <!-- Order Total -->
                                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; background: #28a745; color: white; border-radius: 5px; margin-top: 10px;">
                                            <div style="font-weight: 600; font-size: 16px;">Total Amount</div>
                                            <div style="font-weight: 600; font-size: 16px;">₦<?php echo number_format($order['total_amount'], 2); ?></div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div style="margin-top: 15px; padding: 20px; background: white; border-radius: 5px; text-align: center; color: #6c757d;">
                                        <i class="fas fa-info-circle"></i> No detailed item information available for this order.
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-bag"></i>
                        <h3>No Orders Yet</h3>
                        <p>You haven't placed any orders yet. Start shopping to see your order history here!</p>
                        <a href="../index.php" class="action-btn primary" style="margin-top: 20px; display: inline-block;">
                            <i class="fas fa-shopping-cart"></i> Start Shopping
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Payment Methods Section -->
            <div class="payment-section" id="payment-methods-section">
                <h3 class="section-title">Payment Methods</h3>
                
                <div class="payment-methods">
                    <div class="payment-method-card active">
                        <div class="method-header">
                            <div class="method-icon">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div class="method-title">Credit/Debit Card</div>
                        </div>
                        <p class="method-description">Pay securely with your Visa, MasterCard, or Verve card.</p>
                        <ul class="method-features">
                            <li><i class="fas fa-check"></i> Secure payment processing</li>
                            <li><i class="fas fa-check"></i> Instant confirmation</li>
                            <li><i class="fas fa-check"></i> 3D Secure enabled</li>
                        </ul>
                    </div>
                    
                    <div class="payment-method-card disabled">
                        <div class="method-header">
                            <div class="method-icon">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <div class="method-title">
                                Mobile Money 
                                <span class="coming-soon-badge">COMING SOON</span>
                            </div>
                        </div>
                        <p class="method-description">Pay using your mobile money wallet (MTN, Airtel, etc.).</p>
                        <ul class="method-features">
                            <li><i class="fas fa-clock"></i> Quick and convenient</li>
                            <li><i class="fas fa-clock"></i> No card required</li>
                            <li><i class="fas fa-clock"></i> Instant processing</li>
                        </ul>
                    </div>
                    
                    <div class="payment-method-card">
                        <div class="method-header">
                            <div class="method-icon">
                                <i class="fas fa-university"></i>
                            </div>
                            <div class="method-title">Bank Transfer</div>
                        </div>
                        <p class="method-description">Transfer directly from your bank account.</p>
                        <ul class="method-features">
                            <li><i class="fas fa-check"></i> Direct bank transfer</li>
                            <li><i class="fas fa-check"></i> Secure and reliable</li>
                            <li><i class="fas fa-check"></i> Processing within 24 hours</li>
                        </ul>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Note:</strong> All payments are processed securely. We do not store your payment details.
                    Mobile Money payments will be available soon.
                </div>
            </div>

            <!-- Billing Information Section -->
            <div class="payment-section" id="billing-section">
                <h3 class="section-title">Billing Information</h3>
                
                <div class="empty-state">
                    <i class="fas fa-receipt"></i>
                    <h3>No Billing History</h3>
                    <p>Your billing information and invoices will appear here once you make payments.</p>
                </div>
            </div>
        </main>
    </div>

    <!-- Include your existing modals from dashboard.php -->
    <!-- Edit Profile Modal, Change Picture Modal, Wishlist Modal, Cart Modal -->

    <script>
        // Mobile sidebar functionality
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const closeSidebar = document.getElementById('closeSidebar');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        function openSidebar() {
            sidebar.classList.add('active');
            sidebarOverlay.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        }

        function closeSidebarFunc() {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = ''; // Restore scrolling
        }

        mobileMenuToggle.addEventListener('click', openSidebar);
        closeSidebar.addEventListener('click', closeSidebarFunc);

        // Close sidebar when clicking on overlay
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

        // Tab functionality
        document.querySelectorAll('.payment-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Update active tab
                document.querySelectorAll('.payment-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Show corresponding section
                const tabName = this.getAttribute('data-tab');
                document.querySelectorAll('.payment-section').forEach(section => {
                    section.classList.remove('active');
                });
                document.getElementById(tabName + '-section').classList.add('active');
            });
        });

        // Order details toggle
        document.querySelectorAll('.view-details').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.getAttribute('data-order');
                const detailsDiv = document.getElementById('details-' + orderId);
                
                // Toggle display
                if (detailsDiv.style.display === 'block') {
                    detailsDiv.style.display = 'none';
                    this.innerHTML = '<i class="fas fa-eye"></i> Details';
                } else {
                    detailsDiv.style.display = 'block';
                    this.innerHTML = '<i class="fas fa-eye-slash"></i> Hide';
                }
            });
        });

        // Payment method selection - disable clicking on disabled methods
        document.querySelectorAll('.payment-method-card').forEach(card => {
            card.addEventListener('click', function() {
                if (!this.classList.contains('disabled')) {
                    document.querySelectorAll('.payment-method-card').forEach(c => {
                        c.classList.remove('active');
                    });
                    this.classList.add('active');
                }
            });
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.display = 'none';
            });
        }, 5000);

        // Add your existing modal functionality here
        // (Copy from your dashboard.php file)
    </script>
</body>
</html>
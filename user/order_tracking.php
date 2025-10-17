<?php
include_once '../includes/db_connection.php';
include_once '../includes/protect_user.php';

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: my_orders.php");
    exit();
}

$order_id = intval($_GET['id']);

// Fetch order details
$order_stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$order_stmt->execute([$order_id, $user_id]);
$order = $order_stmt->fetch(PDO::FETCH_ASSOC);

// If order doesn't exist or doesn't belong to user, redirect
if (!$order) {
    header("Location: my_orders.php");
    exit();
}

// Fetch user data
$user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Decode order data
$order_data = [];
$order_note = '';
$delivery_type = 'Pickup';
$order_location = '';

if (!empty($order['order_data'])) {
    $decoded_data = json_decode($order['order_data'], true);
    if (is_array($decoded_data)) {
        $order_data = $decoded_data;
        $order_note = isset($decoded_data['note']) ? $decoded_data['note'] : '';
        $delivery_type = isset($decoded_data['hasHomeDelivery']) && $decoded_data['hasHomeDelivery'] ? 'Home Delivery' : 'Pickup';
        $order_location = isset($decoded_data['location']) ? $decoded_data['location'] : $order['delivery_location'];
    }
}

// Fetch order tracking history
$tracking_stmt = $pdo->prepare("SELECT * FROM order_tracking WHERE order_id = ? ORDER BY created_at ASC");
$tracking_stmt->execute([$order_id]);
$tracking_history = $tracking_stmt->fetchAll(PDO::FETCH_ASSOC);

// If no tracking history exists, create initial tracking entries based on order status
if (empty($tracking_history)) {
    $default_tracking = [
        [
            'status' => 'order_placed',
            'description' => 'Order has been placed',
            'created_at' => $order['created_at']
        ]
    ];
    
    // Add status-specific entries
    if ($order['order_status'] === 'processing') {
        $default_tracking[] = [
            'status' => 'processing',
            'description' => 'Order is being processed',
            'created_at' => date('Y-m-d H:i:s', strtotime($order['created_at']) + 3600) // 1 hour later
        ];
    } elseif ($order['order_status'] === 'shipped') {
        $default_tracking[] = [
            'status' => 'processing',
            'description' => 'Order is being processed',
            'created_at' => date('Y-m-d H:i:s', strtotime($order['created_at']) + 3600)
        ];
        $default_tracking[] = [
            'status' => 'shipped',
            'description' => 'Order has been shipped',
            'created_at' => date('Y-m-d H:i:s', strtotime($order['created_at']) + 7200) // 2 hours later
        ];
    } elseif ($order['order_status'] === 'delivered') {
        $default_tracking[] = [
            'status' => 'processing',
            'description' => 'Order is being processed',
            'created_at' => date('Y-m-d H:i:s', strtotime($order['created_at']) + 3600)
        ];
        $default_tracking[] = [
            'status' => 'shipped',
            'description' => 'Order has been shipped',
            'created_at' => date('Y-m-d H:i:s', strtotime($order['created_at']) + 7200)
        ];
        $default_tracking[] = [
            'status' => 'delivered',
            'description' => 'Order has been delivered',
            'created_at' => date('Y-m-d H:i:s', strtotime($order['created_at']) + 10800) // 3 hours later
        ];
    }
    
    $tracking_history = $default_tracking;
}

// Fetch user's wishlist and cart counts
$wishlist_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
$wishlist_stmt->execute([$user_id]);
$wishlist_count = $wishlist_stmt->fetch(PDO::FETCH_ASSOC)['count'];

$cart_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
$cart_stmt->execute([$user_id]);
$cart_count = $cart_stmt->fetch(PDO::FETCH_ASSOC)['count'];

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
    <title>Order Tracking - BoseaAfrica</title>
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

        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
        }

        .content {
            flex-grow: 1;
            padding: 40px;
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
            margin-bottom: 10px;
        }

        .page-header p {
            font-size: 16px;
            color: #555;
        }

        .back-button {
            background-color: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.3s;
        }

        .back-button:hover {
            background-color: #5a6268;
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

        /* Order Tracking Section */
        .tracking-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }

        .order-summary {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid #28a745;
        }

        .order-summary-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .order-info h3 {
            font-size: 22px;
            margin-bottom: 5px;
            color: #333;
        }

        .order-info p {
            color: #666;
            font-size: 14px;
        }

        .order-status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-processing {
            background-color: #cce7ff;
            color: #004085;
        }

        .status-shipped {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .status-delivered {
            background-color: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .order-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 12px;
            color: #777;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .detail-value {
            font-size: 14px;
            font-weight: 500;
        }

        /* Tracking Timeline */
        .tracking-timeline {
            position: relative;
            padding: 20px 0;
        }

        .timeline-progress {
            position: absolute;
            left: 30px;
            top: 0;
            bottom: 0;
            width: 3px;
            background-color: #e9ecef;
            z-index: 1;
        }

        .timeline-progress-bar {
            position: absolute;
            left: 30px;
            top: 0;
            width: 3px;
            background-color: #28a745;
            z-index: 2;
            transition: height 0.3s ease;
        }

        .timeline-items {
            position: relative;
            z-index: 3;
        }

        .timeline-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 30px;
            position: relative;
        }

        .timeline-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: white;
            border: 3px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            flex-shrink: 0;
            position: relative;
            z-index: 4;
            transition: all 0.3s ease;
        }

        .timeline-item.active .timeline-icon {
            border-color: #28a745;
            background-color: #28a745;
            color: white;
        }

        .timeline-item.completed .timeline-icon {
            border-color: #28a745;
            background-color: #28a745;
            color: white;
        }

        .timeline-content {
            flex: 1;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid #e9ecef;
        }

        .timeline-item.active .timeline-content {
            border-left-color: #28a745;
            background-color: #e8f5e8;
        }

        .timeline-item.completed .timeline-content {
            border-left-color: #28a745;
        }

        .timeline-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }

        .timeline-description {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }

        .timeline-time {
            font-size: 12px;
            color: #888;
        }

        /* Order Items Section */
        .order-items-section {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 20px;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #28a745;
            padding-bottom: 10px;
        }

        .items-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .item-row:hover {
            border-color: #28a745;
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.1);
        }

        .item-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }

        .item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }

        .item-description {
            font-size: 13px;
            color: #666;
            margin-bottom: 5px;
        }

        .item-quantity {
            font-size: 13px;
            color: #888;
        }

        .item-price {
            color: #28a745;
            font-weight: bold;
            font-size: 16px;
            text-align: right;
            min-width: 100px;
        }

        /* Delivery Information */
        .delivery-info {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .info-card {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #28a745;
        }

        .info-card h4 {
            font-size: 16px;
            margin-bottom: 10px;
            color: #333;
        }

        .info-card p {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 30px;
        }

        .action-btn {
            padding: 12px 24px;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
        }

        .btn-primary {
            background-color: #28a745;
            color: white;
        }

        .btn-primary:hover {
            background-color: #218838;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .btn-outline {
            background-color: transparent;
            color: #28a745;
            border: 2px solid #28a745;
        }

        .btn-outline:hover {
            background-color: #28a745;
            color: white;
        }

        /* Order Tracking Section in Sidebar */
        .tracking-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }

        .tracking-section h4 {
            font-size: 16px;
            margin-bottom: 15px;
            color: white;
        }

        .tracking-list {
            list-style: none;
            max-height: 200px;
            overflow-y: auto;
        }

        .tracking-item {
            padding: 8px 12px;
            margin-bottom: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            font-size: 14px;
        }

        .tracking-item:last-child {
            margin-bottom: 0;
        }

        .tracking-order-id {
            font-weight: bold;
            display: block;
        }

        .tracking-status {
            font-size: 12px;
            display: block;
            margin-top: 2px;
        }

        .tracking-view-all {
            display: block;
            text-align: center;
            margin-top: 10px;
            color: white;
            text-decoration: underline;
            font-size: 14px;
        }

        .tracking-view-all:hover {
            color: #e0e0e0;
        }

        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }

            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                z-index: 1000;
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .container {
                flex-direction: column;
            }

            .content {
                padding: 20px;
                margin-left: 0;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .profile-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .profile-buttons {
                width: 100%;
                justify-content: space-between;
            }

            .order-summary-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .order-details-grid {
                grid-template-columns: 1fr;
            }

            .timeline-item {
                flex-direction: column;
            }

            .timeline-icon {
                margin-right: 0;
                margin-bottom: 15px;
            }

            .timeline-progress, .timeline-progress-bar {
                left: 30px;
            }

            .item-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .item-price {
                text-align: left;
            }

            .action-buttons {
                flex-direction: column;
            }

            .tracking-section {
                display: none;
            }
        }
    </style>
</head>
<body>
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
                <button class="mobile-menu-toggle" id="closeSidebar" style="display: none;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <ul class="sidebar-menu">
                <li><a href="../index.php"><i class="fa fa-home"></i> Home page</a></li>
                <li><a href="profile.php"><i class="fa fa-user"></i> Profile</a></li>
                <li><a href="my_orders.php"><i class="fa fa-box"></i> My Orders</a></li>
                <li><a href="#"><i class="fa fa-bell"></i> Notifications</a></li>
                <li><a href="#"><i class="fa fa-tags"></i> Discount Offers</a></li>
                <li><a href="#"><i class="fa fa-credit-card"></i> Payment</a></li>
                <li><a href="../logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
            </ul>

            <!-- Order Tracking Section -->
            <div class="tracking-section">
                <h4><i class="fas fa-shipping-fast"></i> Order Tracking</h4>
                <ul class="tracking-list">
                    <?php
                    // Get recent orders for tracking (limit to 3)
                    $tracking_stmt = $pdo->prepare("SELECT id, order_status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
                    $tracking_stmt->execute([$user_id]);
                    $tracking_orders = $tracking_stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($tracking_orders) > 0): 
                        foreach ($tracking_orders as $track_order): 
                    ?>
                        <li class="tracking-item">
                            <span class="tracking-order-id">Order #<?php echo htmlspecialchars($track_order['id']); ?></span>
                            <span class="tracking-status status-<?php echo htmlspecialchars($track_order['order_status']); ?>">
                                <?php echo ucfirst(htmlspecialchars($track_order['order_status'])); ?>
                            </span>
                        </li>
                    <?php 
                        endforeach; 
                    else: 
                    ?>
                        <li class="tracking-item">
                            <span>No recent orders</span>
                        </li>
                    <?php endif; ?>
                </ul>
                <a href="my_orders.php" class="tracking-view-all">View All Orders</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="content">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h2>Order Tracking</h2>
                    <p>Track your order #<?php echo htmlspecialchars($order['id']); ?> in real-time</p>
                </div>
                <a href="my_orders.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> Back to Orders
                </a>
            </div>

            <!-- Profile Section -->
            <div class="profile-header">
                <div class="profile-info">
                    <img src="<?php echo $profile_picture_url; ?>" 
                         alt="Profile Picture" class="profile-pic">
                    <div class="profile-details">
                        <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>
                <div class="profile-buttons">
                    <button class="favorite-button" id="openWishlistModal">
                        <i class="fa fa-heart"></i>
                        <?php if ($wishlist_count > 0): ?>
                            <span class="badge"><?php echo $wishlist_count; ?></span>
                        <?php endif; ?>
                    </button>
                    <button class="cart-button" id="openCartModal">
                        <i class="fa fa-shopping-cart"></i>
                        <?php if ($cart_count > 0): ?>
                            <span class="badge"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </button>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="tracking-container">
                <div class="order-summary">
                    <div class="order-summary-header">
                        <div class="order-info">
                            <h3>Order #<?php echo htmlspecialchars($order['id']); ?></h3>
                            <p>Placed on <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?></p>
                        </div>
                        <div class="order-status-badge status-<?php echo htmlspecialchars($order['order_status']); ?>">
                            <?php echo ucfirst(htmlspecialchars($order['order_status'])); ?>
                        </div>
                    </div>

                    <div class="order-details-grid">
                        <div class="detail-item">
                            <span class="detail-label">Total Amount</span>
                            <span class="detail-value">₦<?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Payment Status</span>
                            <span class="detail-value"><?php echo ucfirst(htmlspecialchars($order['payment_status'])); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Delivery Type</span>
                            <span class="detail-value"><?php echo htmlspecialchars($delivery_type); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Location</span>
                            <span class="detail-value"><?php echo htmlspecialchars($order_location); ?></span>
                        </div>
                        <?php if (!empty($order['payment_reference'])): ?>
                            <div class="detail-item">
                                <span class="detail-label">Payment Reference</span>
                                <span class="detail-value"><?php echo htmlspecialchars($order['payment_reference']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tracking Timeline -->
                <h3 class="section-title">Order Tracking Timeline</h3>
                <div class="tracking-timeline">
                    <div class="timeline-progress"></div>
                    <div class="timeline-progress-bar" id="progressBar"></div>
                    
                    <div class="timeline-items">
                        <?php
                        $status_icons = [
                            'order_placed' => 'fas fa-shopping-cart',
                            'processing' => 'fas fa-cog',
                            'shipped' => 'fas fa-shipping-fast',
                            'delivered' => 'fas fa-check-circle',
                            'cancelled' => 'fas fa-times-circle'
                        ];
                        
                        $status_titles = [
                            'order_placed' => 'Order Placed',
                            'processing' => 'Processing',
                            'shipped' => 'Shipped',
                            'delivered' => 'Delivered',
                            'cancelled' => 'Cancelled'
                        ];
                        
                        foreach ($tracking_history as $index => $tracking):
                            $is_completed = $index < count($tracking_history) - 1;
                            $is_active = $index === count($tracking_history) - 1;
                            $status_class = $is_active ? 'active' : ($is_completed ? 'completed' : '');
                        ?>
                            <div class="timeline-item <?php echo $status_class; ?>">
                                <div class="timeline-icon">
                                    <i class="<?php echo $status_icons[$tracking['status']] ?? 'fas fa-info-circle'; ?>"></i>
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-title">
                                        <?php echo $status_titles[$tracking['status']] ?? ucfirst(str_replace('_', ' ', $tracking['status'])); ?>
                                    </div>
                                    <div class="timeline-description">
                                        <?php echo htmlspecialchars($tracking['description']); ?>
                                    </div>
                                    <div class="timeline-time">
                                        <?php echo date('F j, Y \a\t g:i A', strtotime($tracking['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <?php if ($order['order_status'] === 'pending'): ?>
                        <button class="action-btn btn-primary" onclick="cancelOrder(<?php echo $order['id']; ?>)">
                            <i class="fas fa-times"></i> Cancel Order
                        </button>
                    <?php endif; ?>
                    
                    <button class="action-btn btn-secondary" onclick="window.location.href='my_orders.php'">
                        <i class="fas fa-list"></i> View All Orders
                    </button>
                    
                    <?php if ($order['order_status'] === 'delivered'): ?>
                        <button class="action-btn btn-primary" onclick="reorderItems(<?php echo $order['id']; ?>)">
                            <i class="fas fa-redo"></i> Reorder
                        </button>
                    <?php endif; ?>
                    
                    <button class="action-btn btn-outline" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Receipt
                    </button>
                </div>
            </div>

            <!-- Order Items -->
            <?php if (isset($order_data['items']) && !empty($order_data['items'])): ?>
            <div class="order-items-section">
                <h3 class="section-title">Order Items</h3>
                <div class="items-list">
                    <?php foreach ($order_data['items'] as $item): 
                        $item_name = isset($item['name']) ? $item['name'] : 'Product';
                        $item_price = isset($item['price']) ? $item['price'] : 0;
                        $item_quantity = isset($item['quantity']) ? $item['quantity'] : 1;
                        $item_image = isset($item['imageUrl']) ? $item['imageUrl'] : 'https://via.placeholder.com/60/28a745/ffffff?text=Prod';
                        $item_description = isset($item['description']) ? $item['description'] : '';
                    ?>
                        <div class="item-row">
                            <div class="item-info">
                                <img src="<?php echo htmlspecialchars($item_image); ?>" 
                                     alt="<?php echo htmlspecialchars($item_name); ?>" 
                                     class="item-image"
                                     onerror="this.src='https://via.placeholder.com/60/28a745/ffffff?text=Prod'">
                                <div class="item-details">
                                    <div class="item-name"><?php echo htmlspecialchars($item_name); ?></div>
                                    <?php if (!empty($item_description)): ?>
                                        <div class="item-description"><?php echo htmlspecialchars($item_description); ?></div>
                                    <?php endif; ?>
                                    <div class="item-quantity">Quantity: <?php echo htmlspecialchars($item_quantity); ?></div>
                                </div>
                            </div>
                            <div class="item-price">
                                ₦<?php echo number_format($item_price * $item_quantity, 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Delivery Information -->
            <div class="delivery-info">
                <h3 class="section-title">Delivery Information</h3>
                <div class="info-grid">
                    <div class="info-card">
                        <h4><i class="fas fa-map-marker-alt"></i> Delivery Address</h4>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($order_location); ?></p>
                        <p><strong>Type:</strong> <?php echo htmlspecialchars($delivery_type); ?></p>
                        <?php if (!empty($order_note)): ?>
                            <p><strong>Note:</strong> <?php echo htmlspecialchars($order_note); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="info-card">
                        <h4><i class="fas fa-credit-card"></i> Payment Information</h4>
                        <p><strong>Status:</strong> <?php echo ucfirst(htmlspecialchars($order['payment_status'])); ?></p>
                        <p><strong>Amount:</strong> ₦<?php echo number_format($order['total_amount'], 2); ?></p>
                        <?php if (!empty($order['payment_reference'])): ?>
                            <p><strong>Reference:</strong> <?php echo htmlspecialchars($order['payment_reference']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="info-card">
                        <h4><i class="fas fa-user"></i> Customer Information</h4>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Mobile sidebar functionality
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const closeSidebar = document.getElementById('closeSidebar');
        const sidebar = document.getElementById('sidebar');

        mobileMenuToggle.addEventListener('click', () => {
            sidebar.classList.add('active');
            closeSidebar.style.display = 'block';
        });

        closeSidebar.addEventListener('click', () => {
            sidebar.classList.remove('active');
            closeSidebar.style.display = 'none';
        });

        // Close sidebar when clicking on a link (mobile)
        document.querySelectorAll('.sidebar-menu a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('active');
                    closeSidebar.style.display = 'none';
                }
            });
        });

        // Update progress bar
        function updateProgressBar() {
            const progressBar = document.getElementById('progressBar');
            const timelineItems = document.querySelectorAll('.timeline-item');
            const completedItems = document.querySelectorAll('.timeline-item.completed, .timeline-item.active');
            
            if (timelineItems.length > 0) {
                const progress = (completedItems.length / timelineItems.length) * 100;
                progressBar.style.height = progress + '%';
            }
        }

        // Initialize progress bar
        document.addEventListener('DOMContentLoaded', updateProgressBar);

        // Order actions
        function cancelOrder(orderId) {
            if (confirm('Are you sure you want to cancel this order?')) {
                fetch('cancel_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'order_id=' + orderId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Order cancelled successfully!');
                        location.reload();
                    } else {
                        alert('Error cancelling order: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error cancelling order.');
                });
            }
        }

        function reorderItems(orderId) {
            fetch('reorder.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'order_id=' + orderId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Items added to cart successfully!');
                    window.location.href = 'cart.php';
                } else {
                    alert('Error adding items to cart: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding items to cart.');
            });
        }

        // Auto-refresh tracking every 30 seconds for pending/processing orders
        const orderStatus = '<?php echo $order['order_status']; ?>';
        if (orderStatus === 'pending' || orderStatus === 'processing') {
            setInterval(() => {
                location.reload();
            }, 30000); // 30 seconds
        }
    </script>
</body>
</html>
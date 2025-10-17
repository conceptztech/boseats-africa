<?php
include_once '../includes/db_connection.php';
include_once '../includes/protect_user.php';

// Fetch user data from database
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch user's orders from database
$orders_stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$orders_stmt->execute([$user_id]);
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get profile picture URL
$profile_picture_url = !empty($user['profile_picture']) 
    ? '../uploads/profile_pictures/' . $user['profile_picture'] . '?v=' . time() 
    : 'https://via.placeholder.com/150/28a745/ffffff?text=' . urlencode(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));

// Function to get correct image URL
function getCorrectImageUrl($imagePath, $defaultImage = 'https://via.placeholder.com/50/28a745/ffffff?text=Prod') {
    if (empty($imagePath)) {
        return $defaultImage;
    }
    
    // If it's already a full URL, return as is
    if (strpos($imagePath, 'http') === 0) {
        return $imagePath;
    }
    
    // Handle relative paths
    if (strpos($imagePath, 'uploads/products/') !== false) {
        return '../' . $imagePath;
    } elseif (strpos($imagePath, '/uploads/products/') !== false) {
        return '..' . $imagePath;
    } elseif (strpos($imagePath, '/') !== false) {
        return '../' . str_replace('/', '', $imagePath);
    } else {
        // Default path construction
        return '../uploads/products/' . basename($imagePath);
    }
}

// NEW: Fetch enhanced order data with actual image paths
$enhanced_orders = [];
if (!empty($orders)) {
    foreach ($orders as $order) {
        $enhanced_order = $order;
        
        // Safely decode order_data with proper structure handling
        $order_items = [];
        $order_note = '';
        $delivery_type = 'Pickup';
        
        if (!empty($order['order_data'])) {
            $decoded_data = json_decode($order['order_data'], true);
            if (is_array($decoded_data) && isset($decoded_data['items'])) {
                $order_items = $decoded_data['items'];
                $order_note = isset($decoded_data['note']) ? $decoded_data['note'] : '';
                $delivery_type = isset($decoded_data['hasHomeDelivery']) && $decoded_data['hasHomeDelivery'] ? 'Home Delivery' : 'Pickup';
                
                // Get food item IDs to fetch actual image paths
                $food_ids = [];
                foreach ($order_items as $item) {
                    if (isset($item['id'])) {
                        $food_ids[] = intval($item['id']);
                    }
                }
                
                // Fetch actual image paths from database if we have food IDs
                if (!empty($food_ids)) {
                    $placeholders = str_repeat('?,', count($food_ids) - 1) . '?';
                    $image_stmt = $pdo->prepare("
                        SELECT id, image_url 
                        FROM food_items 
                        WHERE id IN ($placeholders)
                    ");
                    $image_stmt->execute($food_ids);
                    $image_data = $image_stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Create mapping of food_id to image_url
                    $image_map = [];
                    foreach ($image_data as $img) {
                        $image_map[$img['id']] = $img['image_url'];
                    }
                    
                    // Enhance order items with actual image paths
                    foreach ($order_items as &$item) {
                        $food_id = isset($item['id']) ? $item['id'] : null;
                        if ($food_id && isset($image_map[$food_id])) {
                            $item['actual_image'] = $image_map[$food_id];
                        } else {
                            $item['actual_image'] = $item['imageUrl'] ?? '';
                        }
                    }
                } else {
                    // If no food IDs, use original image URLs
                    foreach ($order_items as &$item) {
                        $item['actual_image'] = $item['imageUrl'] ?? '';
                    }
                }
            }
        }
        
        $enhanced_order['order_items'] = $order_items;
        $enhanced_order['order_note'] = $order_note;
        $enhanced_order['delivery_type'] = $delivery_type;
        
        $enhanced_orders[] = $enhanced_order;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - My Orders</title>
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
            margin-bottom: 30px;
        }

        .page-header h2 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .page-header p {
            font-size: 16px;
            color: #555;
        }

        /* Profile Header - Simplified without buttons */
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
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

        /* Orders Section */
        .orders-section {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }

        .orders-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .orders-header h3 {
            font-size: 22px;
            color: #333;
        }

        .orders-filter {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-btn {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-btn:hover, .filter-btn.active {
            background-color: #28a745;
            color: white;
            border-color: #28a745;
        }

        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .order-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            transition: all 0.3s;
        }

        .order-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-color: #28a745;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .order-info h4 {
            font-size: 18px;
            margin-bottom: 5px;
        }

        .order-info p {
            color: #666;
            font-size: 14px;
        }

        .order-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
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

        .order-details {
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
        }

        .detail-value {
            font-size: 14px;
            font-weight: 500;
        }

        .order-items {
            margin: 15px 0;
        }

        .items-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .item-info {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }

        .item-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
            border: 1px solid #ddd;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 500;
            margin-bottom: 3px;
        }

        .item-description {
            font-size: 12px;
            color: #666;
            margin-bottom: 3px;
        }

        .item-quantity {
            font-size: 12px;
            color: #888;
        }

        .item-price {
            color: #28a745;
            font-weight: bold;
            text-align: right;
            min-width: 80px;
        }

        .order-notes {
            background-color: #f8f9fa;
            padding: 10px 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 3px solid #28a745;
        }

        .order-notes h6 {
            font-size: 14px;
            margin-bottom: 5px;
            color: #333;
        }

        .order-notes p {
            font-size: 13px;
            color: #666;
            margin: 0;
        }

        .order-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 15px;
        }

        .action-btn {
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background-color: #28a745;
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background-color: #218838;
        }

        .btn-secondary {
            background-color: #f8f9fa;
            color: #333;
            border: 1px solid #ddd;
        }

        .btn-secondary:hover {
            background-color: #e9ecef;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .empty-state i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
        }

        .empty-state h3 {
            margin-bottom: 10px;
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

            .orders-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .orders-filter {
                width: 100%;
                justify-content: space-between;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .order-details {
                grid-template-columns: 1fr;
            }

            .order-actions {
                justify-content: flex-start;
                flex-wrap: wrap;
            }

            .tracking-section {
                display: none;
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

            .filter-btn {
                flex: 1;
                text-align: center;
                padding: 6px 10px;
                font-size: 12px;
            }

            .order-card {
                padding: 15px;
            }

            .order-actions {
                flex-direction: column;
                gap: 8px;
            }

            .action-btn {
                width: 100%;
                text-align: center;
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
                <li><a href="my_orders.php" class="active"><i class="fa fa-box"></i> My Orders</a></li>
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
                <li><a href="payments.php"><i class="fa fa-credit-card"></i> Payment</a></li>
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
            <div class="page-header">
                <h2>My Orders</h2>
                <p>View and manage your order history</p>
            </div>

            <!-- Simplified Profile Header without buttons -->
            <div class="profile-header">
                <div class="profile-info">
                    <img src="<?php echo $profile_picture_url; ?>" 
                         alt="Profile Picture" class="profile-pic">
                    <div class="profile-details">
                        <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Orders Section -->
            <div class="orders-section">
                <div class="orders-header">
                    <h3>Order History</h3>
                    <div class="orders-filter">
                        <button class="filter-btn active" data-filter="all">All Orders</button>
                        <button class="filter-btn" data-filter="pending">Pending</button>
                        <button class="filter-btn" data-filter="processing">Processing</button>
                        <button class="filter-btn" data-filter="shipped">Shipped</button>
                        <button class="filter-btn" data-filter="delivered">Delivered</button>
                        <button class="filter-btn" data-filter="cancelled">Cancelled</button>
                    </div>
                </div>

                <div class="orders-list" id="ordersList">
                    <?php if (count($enhanced_orders) > 0): ?>
                        <?php foreach ($enhanced_orders as $order): ?>
                            <div class="order-card" data-status="<?php echo htmlspecialchars($order['order_status']); ?>">
                                <div class="order-header">
                                    <div class="order-info">
                                        <h4>Order #<?php echo htmlspecialchars($order['id']); ?></h4>
                                        <p>Placed on <?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
                                    </div>
                                    <div class="order-status status-<?php echo htmlspecialchars($order['order_status']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($order['order_status'])); ?>
                                    </div>
                                </div>

                                <div class="order-details">
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
                                        <span class="detail-value"><?php echo htmlspecialchars($order['delivery_type']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Location</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($order['delivery_location']); ?></span>
                                    </div>
                                    <?php if (!empty($order['payment_reference'])): ?>
                                        <div class="detail-item">
                                            <span class="detail-label">Payment Reference</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($order['payment_reference']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($order['order_note'])): ?>
                                    <div class="order-notes">
                                        <h6>Order Note:</h6>
                                        <p><?php echo htmlspecialchars($order['order_note']); ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($order['order_items'])): ?>
                                    <div class="order-items">
                                        <h5>Items in this order (<?php echo count($order['order_items']); ?>):</h5>
                                        <div class="items-list">
                                            <?php foreach ($order['order_items'] as $item): 
                                                // Safely access array elements with proper fallbacks
                                                $item_name = isset($item['name']) ? $item['name'] : 'Product';
                                                $item_price = isset($item['price']) ? $item['price'] : 0;
                                                $item_quantity = isset($item['quantity']) ? $item['quantity'] : 1;
                                                $item_image = getCorrectImageUrl($item['actual_image'] ?? $item['imageUrl'] ?? '');
                                                $item_description = isset($item['description']) ? $item['description'] : '';
                                            ?>
                                                <div class="item-row">
                                                    <div class="item-info">
                                                        <img src="<?php echo htmlspecialchars($item_image); ?>" 
                                                             alt="<?php echo htmlspecialchars($item_name); ?>" 
                                                             class="item-image"
                                                             onerror="this.src='https://via.placeholder.com/50/28a745/ffffff?text=Prod'">
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

                                <div class="order-actions">
                                    <?php if ($order['order_status'] === 'pending'): ?>
                                        <button class="action-btn btn-primary" onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                            Cancel Order
                                        </button>
                                    <?php endif; ?>
                                    <button class="action-btn btn-secondary" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                        View Details
                                    </button>
                                    <?php if ($order['order_status'] === 'delivered'): ?>
                                        <button class="action-btn btn-primary" onclick="reorderItems(<?php echo $order['id']; ?>)">
                                            Reorder
                                        </button>
                                    <?php endif; ?>
                                    <button class="action-btn btn-secondary" onclick="trackOrder(<?php echo $order['id']; ?>)">
                                        Track Order
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-box-open"></i>
                            <h3>No orders yet</h3>
                            <p>You haven't placed any orders. Start shopping to see your orders here.</p>
                            <button class="action-btn btn-primary" style="margin-top: 15px;" onclick="window.location.href='../index.php'">
                                Start Shopping
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
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

        // Order filtering functionality
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                document.querySelectorAll('.filter-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Add active class to clicked button
                this.classList.add('active');
                
                const filter = this.getAttribute('data-filter');
                filterOrders(filter);
            });
        });

        function filterOrders(status) {
            const orders = document.querySelectorAll('.order-card');
            
            orders.forEach(order => {
                if (status === 'all' || order.getAttribute('data-status') === status) {
                    order.style.display = 'block';
                } else {
                    order.style.display = 'none';
                }
            });
        }

        // Order actions
        function cancelOrder(orderId) {
            if (confirm('Are you sure you want to cancel this order?')) {
                // AJAX call to cancel order
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

        function viewOrderDetails(orderId) {
            // Redirect to order details page or show modal with details
            window.location.href = 'order_details.php?id=' + orderId;
        }

        function trackOrder(orderId) {
            // Redirect to order tracking page
            window.location.href = 'order_tracking.php?id=' + orderId;
        }

        function reorderItems(orderId) {
            // AJAX call to reorder items
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
                    // Optionally redirect to cart page
                    // window.location.href = 'cart.php';
                } else {
                    alert('Error adding items to cart: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding items to cart.');
            });
        }
    </script>
</body>
</html>
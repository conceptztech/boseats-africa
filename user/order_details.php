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
$order_items = [];

if (!empty($order['order_data'])) {
    $decoded_data = json_decode($order['order_data'], true);
    if (is_array($decoded_data)) {
        $order_data = $decoded_data;
        $order_note = isset($decoded_data['note']) ? $decoded_data['note'] : '';
        $delivery_type = isset($decoded_data['hasHomeDelivery']) && $decoded_data['hasHomeDelivery'] ? 'Home Delivery' : 'Pickup';
        $order_location = isset($decoded_data['location']) ? $decoded_data['location'] : $order['delivery_location'];
        $order_items = isset($decoded_data['items']) ? $decoded_data['items'] : [];
    }
}

// Calculate order totals
$subtotal = 0;
$tax_rate = 0.075; // 7.5% tax
$shipping_fee = $delivery_type === 'Home Delivery' ? 5.00 : 0.00;

foreach ($order_items as $item) {
    $item_price = isset($item['price']) ? $item['price'] : 0;
    $item_quantity = isset($item['quantity']) ? $item['quantity'] : 1;
    $subtotal += $item_price * $item_quantity;
}

$tax_amount = $subtotal * $tax_rate;
$total_amount = $subtotal + $tax_amount + $shipping_fee;

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

// NEW: Fetch company information and actual image paths for order items
$companies_in_order = [];
$enhanced_order_items = [];

if (!empty($order_items)) {
    // Get unique food item IDs from the order
    $food_ids = [];
    foreach ($order_items as $item) {
        if (isset($item['id'])) {
            $food_ids[] = intval($item['id']);
        }
    }
    
    if (!empty($food_ids)) {
        $placeholders = str_repeat('?,', count($food_ids) - 1) . '?';
        
        // Fetch food items with their merchant information and actual image paths
        $enhanced_stmt = $pdo->prepare("
            SELECT 
                fi.id as food_id, 
                fi.name as food_name, 
                fi.image_url as food_image,
                fi.description as food_description,
                fi.price as food_price,
                m.company_name, 
                m.company_address, 
                m.phone, 
                m.email
            FROM food_items fi 
            LEFT JOIN merchants m ON fi.merchant_id = m.id 
            WHERE fi.id IN ($placeholders)
        ");
        $enhanced_stmt->execute($food_ids);
        $enhanced_data = $enhanced_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Create a mapping of food_id to enhanced data
        $food_data_map = [];
        foreach ($enhanced_data as $data) {
            $food_data_map[$data['food_id']] = $data;
        }
        
        // Enhance order items with actual data from database
        foreach ($order_items as $item) {
            $food_id = isset($item['id']) ? $item['id'] : null;
            $enhanced_item = $item;
            
            if ($food_id && isset($food_data_map[$food_id])) {
                $db_data = $food_data_map[$food_id];
                
                // Use actual image URL from database
                $enhanced_item['actual_image'] = $db_data['food_image'];
                $enhanced_item['actual_description'] = $db_data['food_description'];
                $enhanced_item['actual_price'] = $db_data['food_price'];
                $enhanced_item['company_name'] = $db_data['company_name'];
                
                // Store company information
                $company_name = $db_data['company_name'] ?? 'Unknown Company';
                if (!isset($companies_in_order[$company_name])) {
                    $companies_in_order[$company_name] = [
                        'company_name' => $company_name,
                        'company_address' => $db_data['company_address'] ?? 'Address not available',
                        'phone' => $db_data['phone'] ?? 'Phone not available',
                        'email' => $db_data['email'] ?? 'Email not available',
                        'items' => []
                    ];
                }
                
                // Add food item to this company
                $companies_in_order[$company_name]['items'][] = [
                    'food_id' => $db_data['food_id'],
                    'food_name' => $db_data['food_name'],
                    'food_image' => $db_data['food_image']
                ];
            } else {
                // Fallback to original item data
                $enhanced_item['actual_image'] = $item['imageUrl'] ?? '';
                $enhanced_item['actual_description'] = $item['description'] ?? '';
                $enhanced_item['actual_price'] = $item['price'] ?? 0;
                $enhanced_item['company_name'] = 'Unknown Company';
            }
            
            $enhanced_order_items[] = $enhanced_item;
        }
    } else {
        // If no food IDs, use original items
        $enhanced_order_items = $order_items;
        foreach ($enhanced_order_items as &$item) {
            $item['actual_image'] = $item['imageUrl'] ?? '';
            $item['actual_description'] = $item['description'] ?? '';
            $item['actual_price'] = $item['price'] ?? 0;
            $item['company_name'] = 'Unknown Company';
        }
    }
} else {
    $enhanced_order_items = $order_items;
}

// Function to get correct image URL
function getCorrectImageUrl($imagePath, $defaultImage = 'https://via.placeholder.com/60/28a745/ffffff?text=Prod') {
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
    } elseif (strpos($imagePath, 'boseatsafrica/') !== false) {
        return '../' . str_replace('boseatsafrica/', '', $imagePath);
    } else {
        // Default path construction
        return '../uploads/products/' . basename($imagePath);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - BoseatsAfrica</title>
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

        /* Order Details Section */
        .order-details-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }

        .order-info h3 {
            font-size: 24px;
            margin-bottom: 5px;
            color: #333;
        }

        .order-info p {
            color: #666;
            font-size: 14px;
        }

        .order-status-badge {
            padding: 10px 20px;
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

        /* Order Summary Cards */
        .order-summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #28a745;
        }

        .summary-card h4 {
            font-size: 16px;
            margin-bottom: 10px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .summary-card p {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }

        .summary-card .value {
            font-weight: bold;
            color: #28a745;
        }

        /* Order Items Section */
        .order-items-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 20px;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #28a745;
            padding-bottom: 10px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .items-table th {
            background-color: #28a745;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        .items-table td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .items-table tr:hover {
            background-color: #f8f9fa;
        }

        .item-info-cell {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
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
        }

        .item-price, .item-quantity, .item-total {
            font-weight: 500;
        }

        .item-total {
            color: #28a745;
            font-weight: bold;
        }

        /* Order Totals */
        .order-totals {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 25px;
            margin-top: 30px;
        }

        .totals-table {
            width: 100%;
            max-width: 400px;
            margin-left: auto;
        }

        .totals-table tr {
            border-bottom: 1px solid #dee2e6;
        }

        .totals-table td {
            padding: 10px 15px;
        }

        .totals-table .label {
            color: #666;
            text-align: left;
        }

        .totals-table .amount {
            text-align: right;
            font-weight: 500;
        }

        .totals-table .total-row {
            border-top: 2px solid #28a745;
            font-weight: bold;
            font-size: 16px;
        }

        .totals-table .total-row .amount {
            color: #28a745;
            font-size: 18px;
        }

        /* Delivery Information */
        .delivery-info-section {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .info-card {
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            border-left: 4px solid #28a745;
        }

        .info-card h4 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-card p {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
            line-height: 1.5;
        }

        .info-card .highlight {
            background-color: #e8f5e8;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            border-left: 3px solid #28a745;
        }

        /* NEW: Company Information Styles */
        .company-info {
            background-color: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .company-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 5px;
        }

        .company-contact {
            font-size: 14px;
            color: #666;
        }

        .company-items {
            margin-top: 15px;
        }

        .company-item-list {
            list-style: none;
            padding-left: 0;
        }

        .company-item {
            padding: 8px 0;
            border-bottom: 1px solid #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .company-item:last-child {
            border-bottom: none;
        }

        .item-name {
            flex: 1;
        }

        .delivery-instructions {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
            font-size: 14px;
        }

        /* Order Notes */
        .order-notes {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .order-notes h5 {
            font-size: 16px;
            margin-bottom: 10px;
            color: #856404;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .order-notes p {
            margin: 0;
            font-size: 14px;
            line-height: 1.5;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
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

        .btn-danger {
            background-color: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        /* Print Styles */
        @media print {
            .sidebar, .profile-header, .action-buttons, .back-button {
                display: none !important;
            }
            
            .content {
                padding: 20px;
                margin: 0;
            }
            
            .order-details-container, .delivery-info-section {
                box-shadow: none;
                border: 1px solid #ddd;
            }
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

            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .order-summary-cards {
                grid-template-columns: 1fr;
            }

            .items-table {
                display: block;
                overflow-x: auto;
            }

            .item-info-cell {
                flex-direction: column;
                align-items: flex-start;
                text-align: left;
            }

            .item-image {
                margin-bottom: 10px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .totals-table {
                max-width: 100%;
            }

            .action-buttons {
                flex-direction: column;
            }

            .tracking-section {
                display: none;
            }

            .company-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 480px) {
            .sidebar-menu li {
                flex: 1 0 100%;
            }

            .items-table th, .items-table td {
                padding: 10px 8px;
                font-size: 14px;
            }

            .summary-card, .info-card {
                padding: 15px;
            }

            .company-info {
                padding: 15px;
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
                    <img src="https://leo.it.tab.digital/s/H5qHAKxTQHzXsyo/preview" alt="BoseatsAfrica Logo" class="sidebar-logo">
                </div>
                <button class="mobile-menu-toggle" id="closeSidebar" style="display: none;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <ul class="sidebar-menu">
             <li><a href="../index.php"><i class="fa fa-home"></i> Home page</a></li>
                <li><a href="dashboard.php" class="active"><i class="fa fa-user"></i> Dashboard</a></li>
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
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h2>Order Details</h2>
                    <p>Detailed information for order #<?php echo htmlspecialchars($order['id']); ?></p>
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

            <!-- Order Details Container -->
            <div class="order-details-container">
                <!-- Order Header -->
                <div class="order-header">
                    <div class="order-info">
                        <h3>Order #<?php echo htmlspecialchars($order['id']); ?></h3>
                        <p>Placed on <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?></p>
                        <p>Order Status: <span class="order-status-badge status-<?php echo htmlspecialchars($order['order_status']); ?>">
                            <?php echo ucfirst(htmlspecialchars($order['order_status'])); ?>
                        </span></p>
                    </div>
                </div>

                <!-- Order Summary Cards -->
                <div class="order-summary-cards">
                    <div class="summary-card">
                        <h4><i class="fas fa-money-bill-wave"></i> Payment Information</h4>
                        <p><strong>Status:</strong> <span class="value"><?php echo ucfirst(htmlspecialchars($order['payment_status'])); ?></span></p>
                        <p><strong>Total Amount:</strong> <span class="value">₦<?php echo number_format($order['total_amount'], 2); ?></span></p>
                        <?php if (!empty($order['payment_reference'])): ?>
                            <p><strong>Reference:</strong> <span class="value"><?php echo htmlspecialchars($order['payment_reference']); ?></span></p>
                        <?php endif; ?>
                    </div>

                    <div class="summary-card">
                        <h4><i class="fas fa-shipping-fast"></i> Delivery Information</h4>
                        <p><strong>Type:</strong> <span class="value"><?php echo htmlspecialchars($delivery_type); ?></span></p>
                        <p><strong>Location:</strong> <span class="value"><?php echo htmlspecialchars($order_location); ?></span></p>
                        <p><strong>Status:</strong> <span class="value"><?php echo ucfirst(htmlspecialchars($order['order_status'])); ?></span></p>
                    </div>

                    <div class="summary-card">
                        <h4><i class="fas fa-user"></i> Customer Information</h4>
                        <p><strong>Name:</strong> <span class="value"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span></p>
                        <p><strong>Email:</strong> <span class="value"><?php echo htmlspecialchars($user['email']); ?></span></p>
                        <p><strong>Phone:</strong> <span class="value"><?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></span></p>
                    </div>
                </div>

                <!-- Order Notes -->
                <?php if (!empty($order_note)): ?>
                <div class="order-notes">
                    <h5><i class="fas fa-sticky-note"></i> Order Note</h5>
                    <p><?php echo htmlspecialchars($order_note); ?></p>
                </div>
                <?php endif; ?>

                <!-- Order Items -->
                <div class="order-items-section">
                    <h3 class="section-title">Order Items</h3>
                    <?php if (!empty($enhanced_order_items)): ?>
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($enhanced_order_items as $item): 
                                    $item_name = isset($item['name']) ? $item['name'] : 'Product';
                                    $item_price = isset($item['price']) ? $item['price'] : 0;
                                    $item_quantity = isset($item['quantity']) ? $item['quantity'] : 1;
                                    $item_image = getCorrectImageUrl($item['actual_image'] ?? $item['imageUrl'] ?? '');
                                    $item_description = $item['actual_description'] ?? $item['description'] ?? '';
                                    $item_total = $item_price * $item_quantity;
                                ?>
                                    <tr>
                                        <td>
                                            <div class="item-info-cell">
                                                <img src="<?php echo htmlspecialchars($item_image); ?>" 
                                                     alt="<?php echo htmlspecialchars($item_name); ?>" 
                                                     class="item-image"
                                                     onerror="this.src='https://via.placeholder.com/60/28a745/ffffff?text=Prod'">
                                                <div class="item-details">
                                                    <div class="item-name"><?php echo htmlspecialchars($item_name); ?></div>
                                                    <?php if (!empty($item_description)): ?>
                                                        <div class="item-description"><?php echo htmlspecialchars($item_description); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="item-price">₦<?php echo number_format($item_price, 2); ?></td>
                                        <td class="item-quantity"><?php echo htmlspecialchars($item_quantity); ?></td>
                                        <td class="item-total">₦<?php echo number_format($item_total, 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- Order Totals -->
                        <div class="order-totals">
                            <table class="totals-table">
                                <tr>
                                    <td class="label">Subtotal:</td>
                                    <td class="amount">₦<?php echo number_format($subtotal, 2); ?></td>
                                </tr>
                                <tr>
                                    <td class="label">Tax (7.5%):</td>
                                    <td class="amount">₦<?php echo number_format($tax_amount, 2); ?></td>
                                </tr>
                                <tr>
                                    <td class="label">Shipping Fee:</td>
                                    <td class="amount">₦<?php echo number_format($shipping_fee, 2); ?></td>
                                </tr>
                                <tr class="total-row">
                                    <td class="label">Total:</td>
                                    <td class="amount">₦<?php echo number_format($total_amount, 2); ?></td>
                                </tr>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No items found in this order.</p>
                    <?php endif; ?>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <?php if ($order['order_status'] === 'pending'): ?>
                        <button class="action-btn btn-danger" onclick="cancelOrder(<?php echo $order['id']; ?>)">
                            <i class="fas fa-times"></i> Cancel Order
                        </button>
                    <?php endif; ?>
                    
                    <button class="action-btn btn-outline" onclick="trackOrder(<?php echo $order['id']; ?>)">
                        <i class="fas fa-shipping-fast"></i> Track Order
                    </button>
                    
                    <?php if ($order['order_status'] === 'delivered'): ?>
                        <button class="action-btn btn-primary" onclick="reorderItems(<?php echo $order['id']; ?>)">
                            <i class="fas fa-redo"></i> Reorder
                        </button>
                    <?php endif; ?>
                    
                    <button class="action-btn btn-secondary" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Receipt
                    </button>
                    
                    <button class="action-btn btn-outline" onclick="window.location.href='my_orders.php'">
                        <i class="fas fa-list"></i> Back to Orders
                    </button>
                </div>
            </div>

            <!-- Delivery Information -->
            <div class="delivery-info-section">
                <h3 class="section-title">Delivery & Contact Information</h3>
                <div class="info-grid">
                    <div class="info-card">
                        <h4><i class="fas fa-map-marker-alt"></i> Delivery Address</h4>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($order_location); ?></p>
                        <p><strong>Delivery Type:</strong> <?php echo htmlspecialchars($delivery_type); ?></p>
                        <?php if ($delivery_type === 'Home Delivery'): ?>
                            <div class="highlight">
                                <p><strong>Home Delivery:</strong> Your order will be delivered to your specified address.</p>
                            </div>
                        <?php else: ?>
                            <div class="highlight">
                                <p><strong>Pickup:</strong> Please collect your order from the restaurant location.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- NEW: Company Information Section -->
                    <?php if (!empty($companies_in_order)): ?>
                    <div class="info-card">
                        <h4><i class="fas fa-store"></i> Restaurant Information</h4>
                        <?php foreach ($companies_in_order as $company): ?>
                            <div class="company-info">
                                <div class="company-header">
                                    <div>
                                        <div class="company-name"><?php echo htmlspecialchars($company['company_name']); ?></div>
                                        <div class="company-contact">
                                            <?php if (!empty($company['phone']) && $company['phone'] !== 'Phone not available'): ?>
                                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($company['phone']); ?>
                                            <?php endif; ?>
                                            <?php if (!empty($company['email']) && $company['email'] !== 'Email not available'): ?>
                                                <br><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($company['email']); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($company['company_address']) && $company['company_address'] !== 'Address not available'): ?>
                                    <p><strong>Address:</strong> <?php echo htmlspecialchars($company['company_address']); ?></p>
                                <?php endif; ?>
                                
                                <?php if (!empty($company['items'])): ?>
                                    <div class="company-items">
                                        <p><strong>Items from this restaurant:</strong></p>
                                        <ul class="company-item-list">
                                            <?php foreach ($company['items'] as $company_item): ?>
                                                <li class="company-item">
                                                    <span class="item-name"><?php echo htmlspecialchars($company_item['food_name']); ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($delivery_type === 'Pickup'): ?>
                                    <div class="delivery-instructions">
                                        <i class="fas fa-info-circle"></i>
                                        <strong>Pickup Instructions:</strong> Please collect your order from this restaurant location.
                                    </div>
                                <?php else: ?>
                                    <div class="delivery-instructions">
                                        <i class="fas fa-truck"></i>
                                        <strong>Delivery:</strong> This restaurant will deliver your items to your address.
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="info-card">
                        <h4><i class="fas fa-phone"></i> Contact Information</h4>
                        <p><strong>Customer Service:</strong> +234-9033157301</p>
                        <p><strong>Email:</strong> info@boseatsafrica.com</p>
                        <p><strong>Hours:</strong> Mon-Sat, 8:00 AM - 7:00 PM</p>
                        <div class="highlight">
                            <p>Need help with your order? Contact our support team for assistance.</p>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <h4><i class="fas fa-info-circle"></i> Order Timeline</h4>
                        <p><strong>Order Placed:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?></p>
                        <?php if ($order['order_status'] === 'delivered'): ?>
                            <p><strong>Delivered On:</strong> <?php echo date('F j, Y', strtotime($order['created_at']) + 3*3600); ?></p>
                        <?php elseif ($order['order_status'] === 'shipped'): ?>
                            <p><strong>Shipped On:</strong> <?php echo date('F j, Y', strtotime($order['created_at']) + 2*3600); ?></p>
                        <?php endif; ?>
                        <div class="highlight">
                            <p>Estimated delivery: 30-45 minutes for pickup, 45-60 minutes for home delivery.</p>
                        </div>
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

        // Order actions
        function cancelOrder(orderId) {
            if (confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
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

        function trackOrder(orderId) {
            window.location.href = 'order_tracking.php?id=' + orderId;
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

        // Print functionality
        function printOrder() {
            window.print();
        }
    </script>
</body>
</html>
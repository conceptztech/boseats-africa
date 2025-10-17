<?php
include_once '../includes/db_connection.php';
include_once '../includes/protect_user.php';

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch active discount offers
$discounts_stmt = $pdo->prepare("
    SELECT * FROM discount_offers 
    WHERE is_active = 1 
    AND start_date <= NOW() 
    AND end_date >= NOW()
    AND (usage_limit IS NULL OR used_count < usage_limit)
    ORDER BY created_at DESC
");
$discounts_stmt->execute();
$discount_offers = $discounts_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch user's claimed coupons
$user_coupons_stmt = $pdo->prepare("
    SELECT uc.*, do.title, do.description, do.discount_type, do.discount_value, 
           do.min_order_amount, do.max_discount_amount, do.end_date
    FROM user_coupons uc
    JOIN discount_offers do ON uc.discount_offer_id = do.id
    WHERE uc.user_id = ?
    ORDER BY uc.created_at DESC
");
$user_coupons_stmt->execute([$user_id]);
$user_coupons = $user_coupons_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle coupon claim
if (isset($_GET['claim_coupon']) && is_numeric($_GET['claim_coupon'])) {
    $offer_id = $_GET['claim_coupon'];
    
    // Check if offer exists and is valid
    $offer_stmt = $pdo->prepare("
        SELECT * FROM discount_offers 
        WHERE id = ? 
        AND is_active = 1 
        AND start_date <= NOW() 
        AND end_date >= NOW()
        AND (usage_limit IS NULL OR used_count < usage_limit)
    ");
    $offer_stmt->execute([$offer_id]);
    $offer = $offer_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($offer) {
        // Check if user already claimed this coupon
        $existing_claim_stmt = $pdo->prepare("SELECT * FROM user_coupons WHERE user_id = ? AND discount_offer_id = ?");
        $existing_claim_stmt->execute([$user_id, $offer_id]);
        $existing_claim = $existing_claim_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existing_claim) {
            // Claim the coupon
            $claim_stmt = $pdo->prepare("INSERT INTO user_coupons (user_id, coupon_code, discount_offer_id) VALUES (?, ?, ?)");
            $claim_stmt->execute([$user_id, $offer['coupon_code'], $offer_id]);
            
            // Update used count
            $update_count_stmt = $pdo->prepare("UPDATE discount_offers SET used_count = used_count + 1 WHERE id = ?");
            $update_count_stmt->execute([$offer_id]);
            
            $_SESSION['coupon_claimed'] = "Coupon claimed successfully! Your code: " . $offer['coupon_code'];
            header("Location: discount_offers.php");
            exit();
        } else {
            $_SESSION['coupon_error'] = "You have already claimed this coupon!";
            header("Location: discount_offers.php");
            exit();
        }
    } else {
        $_SESSION['coupon_error'] = "This offer is no longer available!";
        header("Location: discount_offers.php");
        exit();
    }
}

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
    <title>Discount Offers - User Dashboard</title>
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

        /* Discount Offers Styles */
        .offers-tabs {
            display: flex;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 5px;
            margin-bottom: 30px;
        }

        .offer-tab {
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
        }

        .offer-tab.active {
            background: #28a745;
            color: white;
        }

        .offers-section {
            display: none;
        }

        .offers-section.active {
            display: block;
        }

        .section-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }

        /* Discount Cards */
        .offers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .discount-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid #e9ecef;
        }

        .discount-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .discount-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 20px;
            position: relative;
        }

        .discount-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255,255,255,0.2);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .discount-value {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .discount-type {
            font-size: 14px;
            opacity: 0.9;
        }

        .discount-body {
            padding: 20px;
        }

        .discount-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }

        .discount-description {
            color: #6c757d;
            line-height: 1.5;
            margin-bottom: 15px;
        }

        .discount-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .detail-label {
            color: #6c757d;
        }

        .detail-value {
            color: #333;
            font-weight: 500;
        }

        .discount-footer {
            padding: 15px 20px 20px;
            border-top: 1px solid #e9ecef;
        }

        .coupon-code {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .code-display {
            flex: 1;
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 5px;
            border: 1px dashed #28a745;
            font-family: monospace;
            font-weight: 600;
            color: #28a745;
            text-align: center;
        }

        .copy-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .copy-btn:hover {
            background: #218838;
        }

        .claim-btn {
            width: 100%;
            background: #28a745;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .claim-btn:hover {
            background: #218838;
        }

        .claim-btn.claimed {
            background: #6c757d;
            cursor: not-allowed;
        }

        .claim-btn.expired {
            background: #dc3545;
            cursor: not-allowed;
        }

        /* My Coupons Styles */
        .coupons-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .coupon-item {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #28a745;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .coupon-info {
            flex: 1;
        }

        .coupon-code-badge {
            background: #28a745;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-family: monospace;
            font-weight: 600;
            font-size: 14px;
        }

        .coupon-title {
            font-size: 18px;
            font-weight: 600;
            margin: 10px 0 5px;
            color: #333;
        }

        .coupon-description {
            color: #6c757d;
            margin-bottom: 10px;
        }

        .coupon-meta {
            display: flex;
            gap: 20px;
            font-size: 14px;
            color: #6c757d;
        }

        .coupon-status {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-available {
            background: #d4edda;
            color: #155724;
        }

        .status-used {
            background: #e2e3e5;
            color: #383d41;
        }

        .status-expired {
            background: #f8d7da;
            color: #721c24;
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

        /* Progress Bar */
        .usage-progress {
            margin-top: 10px;
        }

        .progress-bar {
            width: 100%;
            height: 6px;
            background: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: #28a745;
            border-radius: 3px;
            transition: width 0.3s;
        }

        .progress-text {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
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

            .offers-grid {
                grid-template-columns: 1fr;
            }

            .coupon-item {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .coupon-status {
                align-self: flex-end;
            }

            .profile-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 480px) {
            .offers-tabs {
                flex-direction: column;
            }

            .discount-card {
                margin: 0 10px;
            }

            .coupon-meta {
                flex-direction: column;
                gap: 5px;
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
                <li><a href="dashboard.php" class="active"><i class="fa fa-user"></i> Dashboard</a></li>
                <li><a href="profile.php"><i class="fa fa-user"></i> Profile</a></li>
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
                <li><a href="discount_offers.php" class="active"><i class="fa fa-tags"></i> Discount Offers</a></li>
                <li><a href="payments.php"><i class="fa fa-credit-card"></i> Payment</a></li>
                <li><a href="../logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="content">
            <!-- Simplified Profile Header without buttons -->
            <div class="profile-header">
                <div class="profile-info">
                    <img src="<?php echo $profile_picture_url; ?>" 
                         alt="Profile Picture" class="profile-pic" id="profilePicture">
                    <div class="profile-details">
                        <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Page Header -->
            <div class="page-header">
                <h2>Discount Offers & Coupons</h2>
            </div>

            <!-- Alert Messages -->
            <?php if (isset($_SESSION['coupon_claimed'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo htmlspecialchars($_SESSION['coupon_claimed']);
                    unset($_SESSION['coupon_claimed']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['coupon_error'])): ?>
                <div class="alert alert-error">
                    <?php 
                    echo htmlspecialchars($_SESSION['coupon_error']);
                    unset($_SESSION['coupon_error']);
                    ?>
                </div>
            <?php endif; ?>

            <!-- Offers Tabs -->
            <div class="offers-tabs">
                <button class="offer-tab active" data-tab="available">Available Offers</button>
                <button class="offer-tab" data-tab="my-coupons">My Coupons</button>
            </div>

            <!-- Available Offers Section -->
            <div class="offers-section active" id="available-offers">
                <h3 class="section-title">Available Discount Offers</h3>
                
                <?php if (count($discount_offers) > 0): ?>
                    <div class="offers-grid">
                        <?php foreach ($discount_offers as $offer): 
                            $is_claimed = false;
                            foreach ($user_coupons as $coupon) {
                                if ($coupon['discount_offer_id'] == $offer['id']) {
                                    $is_claimed = true;
                                    break;
                                }
                            }
                        ?>
                            <div class="discount-card">
                                <div class="discount-header">
                                    <div class="discount-badge">
                                        <?php echo strtoupper($offer['discount_type']); ?>
                                    </div>
                                    <div class="discount-value">
                                        <?php if ($offer['discount_type'] == 'percentage'): ?>
                                            <?php echo $offer['discount_value']; ?>%
                                        <?php elseif ($offer['discount_type'] == 'fixed'): ?>
                                            ₦<?php echo number_format($offer['discount_value']); ?>
                                        <?php else: ?>
                                            FREE
                                        <?php endif; ?>
                                    </div>
                                    <div class="discount-type">
                                        <?php 
                                        $type_labels = [
                                            'percentage' => 'Percentage Off',
                                            'fixed' => 'Fixed Amount Off',
                                            'free_shipping' => 'Free Shipping'
                                        ];
                                        echo $type_labels[$offer['discount_type']];
                                        ?>
                                    </div>
                                </div>
                                
                                <div class="discount-body">
                                    <h4 class="discount-title"><?php echo htmlspecialchars($offer['title']); ?></h4>
                                    <p class="discount-description"><?php echo htmlspecialchars($offer['description']); ?></p>
                                    
                                    <div class="discount-details">
                                        <?php if ($offer['min_order_amount'] > 0): ?>
                                            <div class="detail-item">
                                                <span class="detail-label">Min. Order:</span>
                                                <span class="detail-value">₦<?php echo number_format($offer['min_order_amount']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($offer['max_discount_amount']): ?>
                                            <div class="detail-item">
                                                <span class="detail-label">Max. Discount:</span>
                                                <span class="detail-value">₦<?php echo number_format($offer['max_discount_amount']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="detail-item">
                                            <span class="detail-label">Valid Until:</span>
                                            <span class="detail-value"><?php echo date('M j, Y', strtotime($offer['end_date'])); ?></span>
                                        </div>
                                        
                                        <?php if ($offer['usage_limit']): ?>
                                            <div class="usage-progress">
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: <?php echo ($offer['used_count'] / $offer['usage_limit']) * 100; ?>%"></div>
                                                </div>
                                                <div class="progress-text">
                                                    <?php echo $offer['used_count']; ?> of <?php echo $offer['usage_limit']; ?> claimed
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="discount-footer">
                                    <div class="coupon-code">
                                        <div class="code-display"><?php echo $offer['coupon_code']; ?></div>
                                        <button class="copy-btn" onclick="copyToClipboard('<?php echo $offer['coupon_code']; ?>')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                    
                                    <?php if ($is_claimed): ?>
                                        <button class="claim-btn claimed" disabled>
                                            <i class="fas fa-check"></i> Already Claimed
                                        </button>
                                    <?php else: ?>
                                        <a href="?claim_coupon=<?php echo $offer['id']; ?>" class="claim-btn">
                                            <i class="fas fa-gift"></i> Claim This Offer
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-tags"></i>
                        <h3>No Active Offers</h3>
                        <p>Check back later for new discount offers and promotions!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- My Coupons Section -->
            <div class="offers-section" id="my-coupons">
                <h3 class="section-title">My Claimed Coupons</h3>
                
                <?php if (count($user_coupons) > 0): ?>
                    <div class="coupons-list">
                        <?php foreach ($user_coupons as $coupon): 
                            $is_expired = strtotime($coupon['end_date']) < time();
                            $is_used = !is_null($coupon['used_at']);
                            $status_class = $is_used ? 'status-used' : ($is_expired ? 'status-expired' : 'status-available');
                            $status_text = $is_used ? 'Used' : ($is_expired ? 'Expired' : 'Available');
                        ?>
                            <div class="coupon-item">
                                <div class="coupon-info">
                                    <div class="coupon-code-badge"><?php echo $coupon['coupon_code']; ?></div>
                                    <h4 class="coupon-title"><?php echo htmlspecialchars($coupon['title']); ?></h4>
                                    <p class="coupon-description"><?php echo htmlspecialchars($coupon['description']); ?></p>
                                    <div class="coupon-meta">
                                        <span><i class="far fa-calendar"></i> Expires: <?php echo date('M j, Y', strtotime($coupon['end_date'])); ?></span>
                                        <?php if ($coupon['min_order_amount'] > 0): ?>
                                            <span><i class="fas fa-shopping-cart"></i> Min. order: ₦<?php echo number_format($coupon['min_order_amount']); ?></span>
                                        <?php endif; ?>
                                        <?php if ($coupon['used_at']): ?>
                                            <span><i class="far fa-clock"></i> Used on: <?php echo date('M j, Y', strtotime($coupon['used_at'])); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="coupon-status">
                                    <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                    <?php if (!$is_used && !$is_expired): ?>
                                        <button class="copy-btn" onclick="copyToClipboard('<?php echo $coupon['coupon_code']; ?>')">
                                            <i class="fas fa-copy"></i> Copy Code
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-ticket-alt"></i>
                        <h3>No Coupons Claimed</h3>
                        <p>Claim some discount offers to see them here!</p>
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
        document.querySelectorAll('.offer-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Update active tab
                document.querySelectorAll('.offer-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Show corresponding section
                const tabName = this.getAttribute('data-tab');
                document.querySelectorAll('.offers-section').forEach(section => {
                    section.classList.remove('active');
                });
                document.getElementById(tabName === 'available' ? 'available-offers' : 'my-coupons').classList.add('active');
            });
        });

        // Copy to clipboard function
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show success message
                const originalText = event.target.innerHTML;
                event.target.innerHTML = '<i class="fas fa-check"></i> Copied!';
                event.target.style.background = '#28a745';
                
                setTimeout(() => {
                    event.target.innerHTML = originalText;
                    event.target.style.background = '';
                }, 2000);
            }).catch(function(err) {
                console.error('Failed to copy: ', err);
                alert('Failed to copy coupon code. Please copy manually.');
            });
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html>
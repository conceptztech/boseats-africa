<?php
session_start();
require_once '../includes/db_connection.php';
require_once '../includes/merchant_auth.php';

// Check if user is merchant and logged in
if (!is_merchant_logged_in()) {
    header('Location: ../login.php');
    exit;
}

$merchant_id = $_SESSION['merchant_id'];

// Get merchant data
$stmt = $pdo->prepare("SELECT * FROM merchants WHERE id = ?");
$stmt->execute([$merchant_id]);
$merchant = $stmt->fetch();

// Helper function to get correct profile picture path
function getProfilePicturePath($profile_picture) {
    if (empty($profile_picture)) {
        return '';
    }
    
    // Define the base upload directory - using forward slashes for web compatibility
    $base_upload_dir = 'boseatsafrica/uploads/profile_pictures/';
    
    // If it's already a full path starting with uploads/
    if (strpos($profile_picture, 'uploads/profile_pictures/') !== false) {
        $full_path = '../' . $profile_picture;
    } 
    // If it's just a filename
    else {
        $full_path = '../' . $base_upload_dir . $profile_picture;
    }
    
    // Check if file exists
    if (file_exists($full_path)) {
        return $full_path;
    } else {
        return ''; // File doesn't exist
    }
}

// Get customers function
function get_merchant_customers($merchant_id, $search_filter = '', $sort_by = 'total_orders', $sort_order = 'desc') {
    global $pdo;
    
    $search_condition = '';
    $params = [$merchant_id];
    
    if (!empty($search_filter)) {
        $search_condition = " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
        $search_term = "%$search_filter%";
        $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
    }
    
    $order_by = '';
    switch ($sort_by) {
        case 'total_spent':
            $order_by = "total_spent";
            break;
        case 'last_order':
            $order_by = "last_order_date";
            break;
        case 'name':
            $order_by = "u.first_name, u.last_name";
            break;
        default:
            $order_by = "total_orders";
            break;
    }
    
    $order_direction = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';
    
    $sql = "SELECT 
                u.id as user_id,
                u.first_name,
                u.last_name,
                u.email,
                u.phone,
                u.profile_picture,
                u.gender,
                u.created_at as joined_date,
                COUNT(DISTINCT o.id) as total_orders,
                COALESCE(SUM(o.total_amount), 0) as total_spent,
                MAX(o.created_at) as last_order_date,
                MIN(o.created_at) as first_order_date,
                COALESCE(AVG(o.total_amount), 0) as average_order,
                GROUP_CONCAT(DISTINCT o.delivery_location) as locations
            FROM users u
            INNER JOIN orders o ON u.id = o.user_id
            WHERE o.merchant_id = ? $search_condition
            GROUP BY u.id, u.first_name, u.last_name, u.email, u.phone, u.profile_picture, u.gender, u.created_at
            ORDER BY $order_by $order_direction";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $customers;
}

// Get filters
$search_filter = $_GET['search'] ?? '';
$sort_by = $_GET['sort'] ?? 'total_orders';
$sort_order = $_GET['order'] ?? 'desc';

// Get customers
$customers = get_merchant_customers($merchant_id, $search_filter, $sort_by, $sort_order);

// Get business type for dynamic content
$business_type = strtolower($merchant['business_type'] ?? 'food');
$business_icons = [
    'food' => '🍕',
    'event' => '🎪',
    'hotel' => '🏨',
    'car hiring' => '🚗',
    'flight' => '✈️',
    'restaurant' => '🍽️',
    'cafe' => '☕'
];
$business_icon = $business_icons[$business_type] ?? '🏢';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customers - <?php echo htmlspecialchars($merchant['company_name']); ?></title>
    <style>
        :root {
            --primary: #28a745;
            --primary-dark: #218838;
            --primary-light: #34ce57;
            --secondary: #FFFFFF;
            --accent: #DDDDDD;
            --text: #1F2937;
            --text-light: #6B7280;
            --border: #E5E7EB;
            --success: #D1FAE5;
            --warning: #FEF3C7;
            --error: #FEE2E2;
            --info: #DBEAFE;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #F9FAFB;
            color: var(--text);
            overflow-x: hidden;
        }
        
        /* Dashboard Layout */
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: var(--secondary); border-right: 1px solid var(--border); position: fixed; height: 100vh; overflow-y: auto; z-index: 1000; transition: transform 0.3s ease; }
        .logo { padding: 20px; border-bottom: 1px solid var(--border); text-align: center; }
        .logo h1 { color: var(--primary); font-size: 24px; }
        .business-badge { background: var(--primary); color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; margin-top: 5px; display: inline-block; }
        .nav-menu { padding: 20px 0; }
        .nav-item { padding: 12px 20px; display: flex; align-items: center; color: var(--text); text-decoration: none; transition: all 0.3s; border-left: 3px solid transparent; }
        .nav-item:hover, .nav-item.active { background-color: rgba(40, 167, 69, 0.1); color: var(--primary); border-left-color: var(--primary); }
        .nav-item i { margin-right: 10px; width: 20px; font-size: 18px; }
        .mobile-menu-toggle { display: none; position: fixed; top: 20px; left: 20px; background: var(--primary); color: white; border: none; border-radius: 5px; padding: 10px; z-index: 1001; cursor: pointer; }
        .main-content { flex: 1; margin-left: 260px; padding: 20px; transition: margin-left 0.3s ease; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid var(--border); flex-wrap: wrap; gap: 15px; }
        .welcome h2 { color: var(--text); margin-bottom: 5px; font-size: clamp(1.5rem, 2.5vw, 2rem); }
        .welcome p { color: var(--text-light); font-size: clamp(0.875rem, 1.5vw, 1rem); }
        .business-type { background: var(--primary); color: white; padding: 4px 12px; border-radius: 15px; font-size: 12px; margin-left: 10px; }
        .user-menu { display: flex; align-items: center; gap: 15px; }
        .user-info { text-align: right; }
        .user-name { font-weight: 600; font-size: clamp(0.875rem, 1.5vw, 1rem); }
        .user-role { color: var(--text-light); font-size: 14px; }
        .user-avatar img { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary); }
        .avatar-placeholder { width: 45px; height: 45px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px; border: 2px solid var(--primary); }
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; }
        .sidebar-overlay.active { display: block; }
        
        /* Customers Specific Styles */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .page-title {
            font-size: 28px;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .btn-primary {
            background: var(--primary);
            color: var(--secondary);
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
        }
        
        .btn-secondary {
            background: var(--secondary);
            color: var(--text);
            border: 1px solid var(--border);
        }
        
        .btn-secondary:hover {
            background: #f8f9fa;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        /* Filters Section */
        .filters-section {
            background: var(--secondary);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--text);
        }
        
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }
        
        /* Customers Grid - COMPLETELY REVISED */
        .customers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .customer-card {
            background: var(--secondary);
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05), 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            min-height: 420px;
        }
        
        .customer-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.15);
            border-color: var(--primary-light);
        }
        
        .customer-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 18px;
            position: relative;
            min-height: 120px;
        }
        
        .customer-avatar-container {
            position: relative;
            flex-shrink: 0;
        }
        
        .customer-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255,255,255,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: bold;
            border: 4px solid rgba(255,255,255,0.3);
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .customer-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            display: block;
        }
        
        .profile-picture-placeholder {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0.7) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-dark);
            font-weight: bold;
            font-size: 24px;
        }
        
        .customer-basic-info {
            flex: 1;
            min-width: 0;
            padding-right: 10px;
        }
        
        .customer-name {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .customer-contact {
            font-size: 14px;
            opacity: 0.95;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .customer-contact:last-child {
            margin-bottom: 0;
        }
        
        .customer-body {
            padding: 25px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .customer-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .stat-item {
            text-align: center;
            padding: 18px 12px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            border: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .stat-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .stat-value {
            font-size: 26px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 6px;
            line-height: 1;
        }
        
        .stat-label {
            font-size: 12px;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 600;
        }
        
        .customer-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            font-size: 13px;
            color: var(--text-light);
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 0;
        }
        
        .meta-item span:first-child {
            font-size: 14px;
        }
        
        .customer-actions {
            padding: 20px 25px;
            border-top: 1px solid var(--border);
            display: flex;
            gap: 12px;
            background: #fafbfc;
        }
        
        .customer-actions .btn {
            flex: 1;
            justify-content: center;
            padding: 10px 16px;
            font-weight: 600;
        }
        
        /* Customer Details Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1100;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: var(--secondary);
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--text);
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-light);
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 20px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        /* Orders Table in Modal */
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .orders-table th,
        .orders-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        
        .orders-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: var(--text);
        }
        
        .status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .status-pending { background: var(--warning); color: #92400E; }
        .status-preparing { background: var(--info); color: #1E40AF; }
        .status-completed { background: var(--success); color: #065F46; }
        .status-delivered { background: var(--success); color: #065F46; }
        .status-cancelled { background: var(--error); color: #DC2626; }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-light);
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        /* Message Styles */
        .messages-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1200;
            max-width: 400px;
        }
        
        .message {
            padding: 15px 20px;
            margin-bottom: 10px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease-out;
            transform: translateX(100%);
            animation-fill-mode: forwards;
        }
        
        @keyframes slideIn {
            to { transform: translateX(0); }
        }
        
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        .message.success {
            background: var(--success);
            color: #065F46;
            border-left: 4px solid #059669;
        }
        
        .message.error {
            background: var(--error);
            color: #DC2626;
            border-left: 4px solid #DC2626;
        }
        
        .message-icon {
            font-size: 20px;
        }
        
        .message-content {
            flex: 1;
        }
        
        .message-close {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            opacity: 0.7;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .message-close:hover {
            opacity: 1;
        }
        
        /* Mobile Styles */
        @media (max-width: 768px) {
            .mobile-menu-toggle { display: block; }
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 70px 15px 15px; }
            .header { flex-direction: column; text-align: center; }
            .user-menu { justify-content: center; }
            .user-info { text-align: center; }
            .page-header { flex-direction: column; align-items: flex-start; }
            .filters-grid { grid-template-columns: 1fr; }
            .customers-grid { grid-template-columns: 1fr; }
            .customer-stats { grid-template-columns: 1fr; }
            .customer-meta { grid-template-columns: 1fr; }
            .customer-actions { flex-direction: column; }
            .customer-header { 
                flex-direction: column; 
                text-align: center; 
                padding: 20px;
            }
            .customer-avatar { 
                margin: 0 auto 15px;
                width: 70px;
                height: 70px;
            }
            .customer-basic-info {
                padding-right: 0;
            }
        }
        
        @media (max-width: 480px) {
            .modal-content { width: 95%; margin: 10px; }
            .customer-card {
                min-height: 380px;
            }
            .customer-avatar {
                width: 60px;
                height: 60px;
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle">☰</button>
    
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Messages Container -->
    <div class="messages-container" id="messagesContainer">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="message success">
                <span class="message-icon">✅</span>
                <div class="message-content">
                    <strong>Success!</strong>
                    <div><?php echo htmlspecialchars($_SESSION['success_message']); ?></div>
                </div>
                <button class="message-close" onclick="this.parentElement.remove()">×</button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="message error">
                <span class="message-icon">❌</span>
                <div class="message-content">
                    <strong>Error!</strong>
                    <div><?php echo htmlspecialchars($_SESSION['error_message']); ?></div>
                </div>
                <button class="message-close" onclick="this.parentElement.remove()">×</button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
    </div>
    
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <h1>BoseatsAfrica</h1>
                <div class="business-badge">Merchant Portal</div>
            </div>
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-item">
                    <i>📊</i> Dashboard
                </a>
                <a href="products.php" class="nav-item">
                    <i><?php echo $business_icon; ?></i> Products/Services
                </a>
                <a href="orders.php" class="nav-item">
                    <i>📦</i> Orders/Bookings
                </a>
                <a href="customers.php" class="nav-item active">
                    <i>👥</i> Customers
                </a>
                <a href="analytics.php" class="nav-item">
                    <i>📈</i> Analytics
                </a>
                <a href="inventory.php" class="nav-item">
                    <i>📦</i> Inventory
                </a>
                <a href="coupons.php" class="nav-item">
                    <i>🎫</i> Coupons & Deals
                </a>
                <a href="settings.php" class="nav-item">
                    <i>⚙️</i> Settings
                </a>
                <a href="../logout.php" class="nav-item">
                    <i>🚪</i> Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="welcome">
                    <h2>
                        Manage Customers 
                        <span class="business-type"><?php echo ucfirst($merchant['business_type'] ?? 'Business'); ?></span>
                    </h2>
                    <p>View and manage your customer relationships and order history</p>
                </div>
                <div class="user-menu">
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($merchant['company_name']); ?></div>
                        <div class="user-role"><?php echo ucfirst($merchant['business_type'] ?? 'Merchant'); ?></div>
                    </div>
                    <div class="user-avatar">
                        <?php 
                        $picture_path = $merchant['picture_path'] ?? '';
                        if (!empty($picture_path) && file_exists('../' . $picture_path)): 
                        ?>
                            <img src="../<?php echo htmlspecialchars($picture_path); ?>" alt="Profile">
                        <?php else: ?>
                            <div class="avatar-placeholder">
                                <?php echo strtoupper(substr($merchant['company_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <span>👥</span>
                    Customers (<?php echo count($customers); ?>)
                </h1>
                <div class="header-actions">
                    <a href="?export=csv" class="btn btn-secondary">
                        <span>📊</span> Export CSV
                    </a>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="filters-section">
                <form method="GET" class="filters-grid">
                    <div class="form-group">
                        <label for="search">Search Customers</label>
                        <input type="text" id="search" name="search" class="form-control" 
                               placeholder="Search by name, email, or phone..." value="<?php echo htmlspecialchars($search_filter); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="sort">Sort By</label>
                        <select id="sort" name="sort" class="form-control">
                            <option value="total_orders" <?php echo $sort_by === 'total_orders' ? 'selected' : ''; ?>>Total Orders</option>
                            <option value="total_spent" <?php echo $sort_by === 'total_spent' ? 'selected' : ''; ?>>Total Spent</option>
                            <option value="last_order" <?php echo $sort_by === 'last_order' ? 'selected' : ''; ?>>Last Order Date</option>
                            <option value="name" <?php echo $sort_by === 'name' ? 'selected' : ''; ?>>Customer Name</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="order">Sort Order</label>
                        <select id="order" name="order" class="form-control">
                            <option value="desc" <?php echo $sort_order === 'desc' ? 'selected' : ''; ?>>Descending</option>
                            <option value="asc" <?php echo $sort_order === 'asc' ? 'selected' : ''; ?>>Ascending</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <span>🔍</span> Apply Filters
                        </button>
                    </div>
                </form>
            </div>

            <!-- Customers Grid -->
            <div class="customers-container">
                <?php if (!empty($customers)): ?>
                    <div class="customers-grid">
                        <?php foreach ($customers as $customer): ?>
                            <div class="customer-card">
                                <div class="customer-header">
                                    <div class="customer-avatar">
                                        <?php 
                                        $profile_picture = $customer['profile_picture'] ?? '';
                                        $picture_path = getProfilePicturePath($profile_picture);
                                        
                                        if (!empty($picture_path) && file_exists($picture_path)):
                                        ?>
                                            <img src="<?php echo htmlspecialchars($picture_path); ?>" 
                                                 alt="<?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>"
                                                 onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\"profile-picture-placeholder\"><?php echo strtoupper(substr($customer["first_name"], 0, 1) . substr($customer["last_name"], 0, 1)); ?></div>';">
                                        <?php else: ?>
                                            <div class="profile-picture-placeholder">
                                                <?php echo strtoupper(substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="customer-basic-info">
                                        <div class="customer-name">
                                            <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
                                        </div>
                                        <div class="customer-contact">
                                            <span>✉️</span>
                                            <span><?php echo htmlspecialchars($customer['email']); ?></span>
                                        </div>
                                        <?php if (!empty($customer['phone'])): ?>
                                            <div class="customer-contact">
                                                <span>📞</span>
                                                <span><?php echo htmlspecialchars($customer['phone']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="customer-body">
                                    <div class="customer-stats">
                                        <div class="stat-item">
                                            <div class="stat-value"><?php echo $customer['total_orders']; ?></div>
                                            <div class="stat-label">Total Orders</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-value">₦<?php echo number_format($customer['total_spent'], 2); ?></div>
                                            <div class="stat-label">Total Spent</div>
                                        </div>
                                    </div>
                                    
                                    <div class="customer-meta">
                                        <div class="meta-item">
                                            <span>📍</span>
                                            <span>
                                                <?php 
                                                $locations = $customer['locations'] ?? '';
                                                if (!empty($locations)) {
                                                    $location_array = explode(',', $locations);
                                                    echo htmlspecialchars($location_array[0] . (count($location_array) > 1 ? ' +' . (count($location_array) - 1) . ' more' : ''));
                                                } else {
                                                    echo 'Unknown';
                                                }
                                                ?>
                                            </span>
                                        </div>
                                        <div class="meta-item">
                                            <span>👤</span>
                                            <span><?php echo ucfirst($customer['gender'] ?? 'Not specified'); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <span>📅</span>
                                            <span>Joined <?php echo date('M Y', strtotime($customer['joined_date'])); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <span>🕒</span>
                                            <span>Last order: <?php echo $customer['last_order_date'] ? date('M j, Y', strtotime($customer['last_order_date'])) : 'Never'; ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="customer-actions">
                                    <button class="btn btn-primary btn-sm" 
                                            onclick="viewCustomerDetails(<?php echo $customer['user_id']; ?>)">
                                        <span>👁️</span> View Details
                                    </button>
                                    <button class="btn btn-secondary btn-sm" 
                                            onclick="viewCustomerOrders(<?php echo $customer['user_id']; ?>)">
                                        <span>📦</span> View Orders
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i>👥</i>
                        <h3>No Customers Found</h3>
                        <p>No customers match your current filters or you haven't received any orders yet.</p>
                        <a href="customers.php" class="btn btn-primary" style="margin-top: 15px;">
                            <span>🔄</span> Clear Filters
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Customer Details Modal -->
    <div class="modal" id="customerDetailsModal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3 class="modal-title">Customer Details</h3>
                <button class="modal-close" onclick="closeCustomerDetailsModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="customerDetailsContent">
                    <!-- Customer details will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCustomerDetailsModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- Customer Orders Modal -->
    <div class="modal" id="customerOrdersModal">
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                <h3 class="modal-title" id="customerOrdersTitle">Customer Orders</h3>
                <button class="modal-close" onclick="closeCustomerOrdersModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="customerOrdersContent">
                    <!-- Customer orders will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCustomerOrdersModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Mobile Menu Toggle
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        function toggleSidebar() {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
        }
        
        mobileMenuToggle.addEventListener('click', toggleSidebar);
        sidebarOverlay.addEventListener('click', toggleSidebar);
        
        // Customer Details Modal Functions
        function viewCustomerDetails(userId) {
            // Show loading state
            document.getElementById('customerDetailsContent').innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <div style="font-size: 48px; margin-bottom: 20px;">⏳</div>
                    <div>Loading customer details...</div>
                </div>
            `;
            
            document.getElementById('customerDetailsModal').classList.add('active');
            
            // For demo purposes, we'll create mock data
            // In a real application, you would fetch this from get_customer_details.php
            setTimeout(() => {
                const mockCustomer = {
                    first_name: 'Customer',
                    last_name: 'User',
                    email: 'customer@example.com',
                    phone: '+1234567890',
                    gender: 'male',
                    joined_date: '2023-01-15',
                    first_order_date: '2023-01-20',
                    last_order_date: '2024-01-10',
                    total_orders: 5,
                    total_spent: 125000,
                    average_order: 25000,
                    profile_picture: ''
                };
                
                displayCustomerDetails(mockCustomer);
            }, 1000);
        }
        
        function displayCustomerDetails(customer) {
            const customerDetailsHtml = `
                <div style="max-height: 60vh; overflow-y: auto;">
                    <!-- Customer Header -->
                    <div style="text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid var(--border);">
                        <div style="width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; color: white; font-size: 30px; font-weight: bold; border: 3px solid var(--primary);">
                            ${customer.first_name.charAt(0)}${customer.last_name.charAt(0)}
                        </div>
                        <h3 style="margin-bottom: 5px; color: var(--text);">${customer.first_name} ${customer.last_name}</h3>
                        <p style="color: var(--text-light); margin-bottom: 10px;">${customer.email}</p>
                        ${customer.phone ? `<p style="color: var(--text-light);">📞 ${customer.phone}</p>` : ''}
                    </div>
                    
                    <!-- Customer Stats -->
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 25px;">
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 24px; font-weight: bold; color: var(--primary);">${customer.total_orders}</div>
                            <div style="font-size: 12px; color: var(--text-light); text-transform: uppercase;">Total Orders</div>
                        </div>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 24px; font-weight: bold; color: var(--primary);">₦${parseFloat(customer.total_spent).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                            <div style="font-size: 12px; color: var(--text-light); text-transform: uppercase;">Total Spent</div>
                        </div>
                    </div>
                    
                    <!-- Customer Information -->
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                        <h4 style="margin-bottom: 15px; color: var(--text);">Customer Information</h4>
                        <div style="display: grid; gap: 10px;">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-light);">Gender:</span>
                                <span style="font-weight: 500;">${customer.gender ? customer.gender.charAt(0).toUpperCase() + customer.gender.slice(1) : 'Not specified'}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-light);">Member Since:</span>
                                <span style="font-weight: 500;">${new Date(customer.joined_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-light);">First Order:</span>
                                <span style="font-weight: 500;">${customer.first_order_date ? new Date(customer.first_order_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'No orders yet'}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-light);">Last Order:</span>
                                <span style="font-weight: 500;">${customer.last_order_date ? new Date(customer.last_order_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'No recent orders'}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-light);">Average Order:</span>
                                <span style="font-weight: 500;">₦${parseFloat(customer.average_order).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('customerDetailsContent').innerHTML = customerDetailsHtml;
        }
        
        function closeCustomerDetailsModal() {
            document.getElementById('customerDetailsModal').classList.remove('active');
        }
        
        function viewCustomerOrders(userId) {
            // Show loading state
            document.getElementById('customerOrdersContent').innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <div style="font-size: 48px; margin-bottom: 20px;">⏳</div>
                    <div>Loading customer orders...</div>
                </div>
            `;
            
            document.getElementById('customerOrdersTitle').textContent = 'Customer Orders';
            document.getElementById('customerOrdersModal').classList.add('active');
            
            // For demo purposes - in real app, fetch from get_customer_orders.php
            setTimeout(() => {
                document.getElementById('customerOrdersContent').innerHTML = `
                    <div style="text-align: center; padding: 40px;">
                        <div style="font-size: 48px; margin-bottom: 20px;">📦</div>
                        <h3>Order History</h3>
                        <p style="color: var(--text-light); margin-top: 10px;">
                            Order details would be loaded here for customer ID: ${userId}
                        </p>
                        <p style="color: var(--text-light); font-size: 14px; margin-top: 20px;">
                            This feature requires the get_customer_orders.php endpoint to be implemented.
                        </p>
                    </div>
                `;
            }, 1000);
        }
        
        function closeCustomerOrdersModal() {
            document.getElementById('customerOrdersModal').classList.remove('active');
        }
        
        // Auto-hide messages after 5 seconds
        setTimeout(() => {
            const messages = document.querySelectorAll('.message');
            messages.forEach(message => {
                message.style.animation = 'slideOut 0.3s ease-in forwards';
                setTimeout(() => message.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>
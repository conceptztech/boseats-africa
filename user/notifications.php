<?php
include_once '../includes/db_connection.php';
include_once '../includes/protect_user.php';
include_once '../includes/notification_functions.php'; // Include the new functions

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch notifications with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$notifications_stmt = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
");
$notifications_stmt->bindValue(1, $user_id, PDO::PARAM_INT);
$notifications_stmt->bindValue(2, $per_page, PDO::PARAM_INT);
$notifications_stmt->bindValue(3, $offset, PDO::PARAM_INT);
$notifications_stmt->execute();
$notifications = $notifications_stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total notifications for pagination
$total_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ?");
$total_stmt->execute([$user_id]);
$total_notifications = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_notifications / $per_page);

// Count unread notifications
$unread_count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
$unread_count_stmt->execute([$user_id]);
$unread_count = $unread_count_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Handle mark as read action
if (isset($_GET['mark_as_read']) && is_numeric($_GET['mark_as_read'])) {
    $mark_read_stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $mark_read_stmt->execute([$_GET['mark_as_read'], $user_id]);
    header("Location: notifications.php");
    exit();
}

// Handle mark all as read
if (isset($_GET['mark_all_read'])) {
    $mark_all_stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $mark_all_stmt->execute([$user_id]);
    header("Location: notifications.php");
    exit();
}

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $delete_stmt->execute([$_GET['delete'], $user_id]);
    header("Location: notifications.php");
    exit();
}

// Handle clear all
if (isset($_GET['clear_all'])) {
    $clear_stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
    $clear_stmt->execute([$user_id]);
    header("Location: notifications.php");
    exit();
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
    <title>Notifications - User Dashboard</title>
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

        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .notifications-header h2 {
            font-size: 28px;
            color: #333;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .action-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.3s;
            text-decoration: none;
        }

        .action-btn:hover {
            background-color: #218838;
        }

        .action-btn.secondary {
            background-color: #6c757d;
        }

        .action-btn.secondary:hover {
            background-color: #5a6268;
        }

        .action-btn.danger {
            background-color: #dc3545;
        }

        .action-btn.danger:hover {
            background-color: #c82333;
        }

        .notifications-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .notification-tabs {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .tab {
            padding: 15px 25px;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 16px;
            color: #6c757d;
            transition: all 0.3s;
            position: relative;
        }

        .tab.active {
            color: #28a745;
            font-weight: 600;
        }

        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 3px;
            background: #28a745;
        }

        .tab-badge {
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: 8px;
        }

        .notifications-list {
            max-height: 600px;
            overflow-y: auto;
        }

        .notification-item {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: flex-start;
            gap: 15px;
            transition: background-color 0.3s;
        }

        .notification-item:hover {
            background-color: #f8f9fa;
        }

        .notification-item.unread {
            background-color: #f0f9ff;
            border-left: 4px solid #28a745;
        }

        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            flex-shrink: 0;
        }

        .notification-icon.order {
            background: #007bff;
        }

        .notification-icon.system {
            background: #6c757d;
        }

        .notification-icon.promotion {
            background: #ffc107;
            color: #000;
        }

        .notification-icon.security {
            background: #dc3545;
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 5px;
            color: #333;
        }

        .notification-message {
            color: #6c757d;
            line-height: 1.5;
            margin-bottom: 8px;
        }

        .notification-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 12px;
            color: #999;
        }

        .notification-time {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .notification-actions {
            display: flex;
            gap: 10px;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .notification-item:hover .notification-actions {
            opacity: 1;
        }

        .notification-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
            border-radius: 3px;
            transition: background-color 0.3s;
            color: #6c757d;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .notification-btn:hover {
            background-color: #e9ecef;
            color: #333;
        }

        .notification-btn.read {
            color: #28a745;
        }

        .notification-btn.delete {
            color: #dc3545;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
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

            .notifications-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .header-actions {
                width: 100%;
                justify-content: space-between;
            }

            .notification-tabs {
                flex-wrap: wrap;
            }

            .tab {
                flex: 1;
                min-width: 120px;
                text-align: center;
            }

            .notification-item {
                flex-direction: column;
                gap: 10px;
            }

            .notification-actions {
                opacity: 1;
                justify-content: flex-end;
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

            .tab {
                padding: 12px 15px;
                font-size: 14px;
            }

            .action-btn {
                padding: 8px 15px;
                font-size: 12px;
            }

            .notification-item {
                padding: 15px;
            }
        }
        
        /* Add pagination styles */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 20px 0;
            gap: 10px;
        }

        .pagination a, .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }

        .pagination a:hover {
            background-color: #28a745;
            color: white;
            border-color: #28a745;
        }

        .pagination .current {
            background-color: #28a745;
            color: white;
            border-color: #28a745;
        }

        .pagination .disabled {
            color: #999;
            cursor: not-allowed;
        }

        .notification-order-link {
            color: #28a745;
            text-decoration: none;
            font-weight: 600;
        }

        .notification-order-link:hover {
            text-decoration: underline;
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
                    <img src="https://leo.it.tab.digital/s/H5qHAKxTQHzXsyo/preview" alt="BoseatsAfrica Logo" class="sidebar-logo">
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
                <li><a href="notifications.php" class="active"><i class="fa fa-bell"></i> Notifications 
                    <?php if ($unread_count > 0): ?>
                        <span style="background: #dc3545; color: white; border-radius: 50%; width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; margin-left: 5px;">
                            <?php echo $unread_count; ?>
                        </span>
                    <?php endif; ?>
                </a></li>
                <li><a href="discount_offers.php"><i class="fa fa-tags"></i> Discount Offers</a></li>
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

            <!-- Notifications Header -->
            <div class="notifications-header">
                <h2>Notifications (<?php echo $total_notifications; ?>)</h2>
                <div class="header-actions">
                    <?php if ($unread_count > 0): ?>
                        <a href="?mark_all_read=1" class="action-btn">
                            <i class="fas fa-check-double"></i> Mark All as Read
                        </a>
                    <?php endif; ?>
                    <?php if ($total_notifications > 0): ?>
                        <a href="?clear_all=1" class="action-btn danger" onclick="return confirm('Are you sure you want to clear all notifications?')">
                            <i class="fas fa-trash"></i> Clear All
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Notifications Container -->
            <div class="notifications-container">
                <!-- Tabs -->
                <div class="notification-tabs">
                    <button class="tab active" data-tab="all">All Notifications</button>
                    <button class="tab" data-tab="unread">Unread 
                        <?php if ($unread_count > 0): ?>
                            <span class="tab-badge"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </button>
                    <button class="tab" data-tab="order">Order Updates</button>
                </div>

                <!-- Notifications List -->
                <div class="notifications-list">
                    <?php if (count($notifications) > 0): ?>
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>" 
                                 data-type="<?php echo $notification['type']; ?>">
                                <div class="notification-icon <?php echo $notification['type']; ?>">
                                    <?php 
                                    $icons = [
                                        'order_update' => 'fa-shopping-bag',
                                        'system' => 'fa-cog',
                                        'promotion' => 'fa-tags',
                                        'security' => 'fa-shield-alt'
                                    ];
                                    echo '<i class="fas ' . ($icons[$notification['type']] ?? 'fa-bell') . '"></i>';
                                    ?>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-title">
                                        <?php echo htmlspecialchars($notification['title']); ?>
                                    </div>
                                    <div class="notification-message">
                                        <?php 
                                        // Parse order ID from message for order updates
                                        $message = htmlspecialchars($notification['message']);
                                        if ($notification['type'] === 'order_update') {
                                            // Extract order number from message (looking for patterns like #123)
                                            if (preg_match('/#(\d+)/', $message, $matches)) {
                                                $order_id = $matches[1];
                                                $message = preg_replace(
                                                    '/#(\d+)/', 
                                                    '<a href="order_details.php?id=' . $order_id . '" class="notification-order-link">#$1</a>', 
                                                    $message
                                                );
                                            }
                                        }
                                        echo $message;
                                        ?>
                                    </div>
                                    <div class="notification-meta">
                                        <span class="notification-time">
                                            <i class="far fa-clock"></i>
                                            <?php 
                                            $time_ago = time() - strtotime($notification['created_at']);
                                            if ($time_ago < 3600) {
                                                echo floor($time_ago / 60) . ' minutes ago';
                                            } elseif ($time_ago < 86400) {
                                                echo floor($time_ago / 3600) . ' hours ago';
                                            } else {
                                                echo date('M j, Y g:i A', strtotime($notification['created_at']));
                                            }
                                            ?>
                                        </span>
                                        <span class="notification-type">
                                            <?php echo ucfirst(str_replace('_', ' ', $notification['type'])); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="notification-actions">
                                    <?php if (!$notification['is_read']): ?>
                                        <a href="?mark_as_read=<?php echo $notification['id']; ?>" 
                                           class="notification-btn read" 
                                           title="Mark as read">
                                            <i class="fas fa-check"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="?delete=<?php echo $notification['id']; ?>" 
                                       class="notification-btn delete" 
                                       title="Delete notification"
                                       onclick="return confirm('Are you sure you want to delete this notification?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=1">&laquo; First</a>
                                    <a href="?page=<?php echo $page - 1; ?>">&lsaquo; Prev</a>
                                <?php else: ?>
                                    <span class="disabled">&laquo; First</span>
                                    <span class="disabled">&lsaquo; Prev</span>
                                <?php endif; ?>

                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                for ($i = $start_page; $i <= $end_page; $i++):
                                ?>
                                    <?php if ($i == $page): ?>
                                        <span class="current"><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>">Next &rsaquo;</a>
                                    <a href="?page=<?php echo $total_pages; ?>">Last &raquo;</a>
                                <?php else: ?>
                                    <span class="disabled">Next &rsaquo;</span>
                                    <span class="disabled">Last &raquo;</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="far fa-bell"></i>
                            <h3>No notifications yet</h3>
                            <p>We'll notify you when something important happens.</p>
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

        // Tab functionality
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Update active tab
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                const tabType = this.getAttribute('data-tab');
                const notifications = document.querySelectorAll('.notification-item');
                
                notifications.forEach(notification => {
                    const notificationType = notification.getAttribute('data-type');
                    
                    if (tabType === 'all') {
                        notification.style.display = 'flex';
                    } else if (tabType === 'unread') {
                        if (notification.classList.contains('unread')) {
                            notification.style.display = 'flex';
                        } else {
                            notification.style.display = 'none';
                        }
                    } else if (tabType === 'order') {
                        if (notificationType === 'order_update') {
                            notification.style.display = 'flex';
                        } else {
                            notification.style.display = 'none';
                        }
                    }
                });
            });
        });

        // Auto-hide notifications after marking as read
        document.querySelectorAll('.notification-btn.read').forEach(btn => {
            btn.addEventListener('click', function(e) {
                const notificationItem = this.closest('.notification-item');
                setTimeout(() => {
                    notificationItem.classList.remove('unread');
                    // Update unread count in sidebar
                    const unreadBadge = document.querySelector('.sidebar-menu .active .badge');
                    if (unreadBadge) {
                        const currentCount = parseInt(unreadBadge.textContent);
                        if (currentCount > 1) {
                            unreadBadge.textContent = currentCount - 1;
                        } else {
                            unreadBadge.remove();
                        }
                    }
                }, 300);
            });
        });
    </script>
</body>
</html>
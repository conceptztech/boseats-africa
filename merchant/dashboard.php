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

// Get dashboard stats
$stats = get_merchant_stats($merchant_id);

// Get revenue data for the chart (last 7 days)
$revenue_data = get_revenue_chart_data($merchant_id);

// Get business type for dynamic icons
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
    <title>Merchant Dashboard - <?php echo htmlspecialchars($merchant['company_name']); ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 260px;
            background: var(--secondary);
            border-right: 1px solid var(--border);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s ease;
        }
        
        .logo {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            text-align: center;
        }
        
        .logo h1 {
            color: var(--primary);
            font-size: 24px;
        }
        
        .business-badge {
            background: var(--primary);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            margin-top: 5px;
            display: inline-block;
        }
        
        .nav-menu {
            padding: 20px 0;
        }
        
        .nav-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            color: var(--text);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .nav-item:hover, .nav-item.active {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--primary);
            border-left-color: var(--primary);
        }
        
        .nav-item i {
            margin-right: 10px;
            width: 20px;
            font-size: 18px;
        }
        
        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px;
            z-index: 1001;
            cursor: pointer;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .welcome h2 {
            color: var(--text);
            margin-bottom: 5px;
            font-size: clamp(1.5rem, 2.5vw, 2rem);
        }
        
        .welcome p {
            color: var(--text-light);
            font-size: clamp(0.875rem, 1.5vw, 1rem);
        }
        
        .business-type {
            background: var(--primary);
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            margin-left: 10px;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-info {
            text-align: right;
        }
        
        .user-name {
            font-weight: 600;
            font-size: clamp(0.875rem, 1.5vw, 1rem);
        }
        
        .user-role {
            color: var(--text-light);
            font-size: 14px;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--secondary);
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .stat-value {
            font-size: clamp(1.5rem, 3vw, 2rem);
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--text-light);
            font-size: 14px;
        }
        
        .stat-icon {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        /* Charts and Tables */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        
        .chart-container, .recent-orders {
            background: var(--secondary);
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .chart-wrapper {
            height: 300px;
            position: relative;
        }
        
        .orders-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .order-item {
            padding: 15px;
            border-bottom: 1px solid var(--border);
            transition: background-color 0.3s;
        }
        
        .order-item:hover {
            background-color: #f8f9fa;
        }
        
        .order-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .order-details {
            flex: 1;
            min-width: 200px;
        }
        
        .order-number {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .order-meta {
            color: var(--text-light);
            font-size: 14px;
        }
        
        .status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }
        
        .status.completed { background: #D1FAE5; color: #065F46; }
        .status.pending { background: #FEF3C7; color: #92400E; }
        .status.preparing { background: #DBEAFE; color: #1E40AF; }
        .status.delivered { background: #D1FAE5; color: #065F46; }
        .status.cancelled { background: #FEE2E2; color: #DC2626; }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: var(--primary);
            color: var(--secondary);
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
        }
        
        .user-avatar img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
        }
        
        .avatar-placeholder {
            width: 45px;
            height: 45px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            border: 2px solid var(--primary);
        }
        
        /* Mobile Styles */
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .chart-wrapper {
                height: 250px;
            }
        }
        
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }
            
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 70px 15px 15px;
            }
            
            .header {
                flex-direction: column;
                text-align: center;
            }
            
            .user-menu {
                justify-content: center;
            }
            
            .user-info {
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
            }
            
            .stat-card {
                padding: 20px;
            }
            
            .chart-container, .recent-orders {
                padding: 20px;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .order-info {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .order-details {
                min-width: auto;
            }
        }
        
        /* Overlay for mobile menu */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        
        .sidebar-overlay.active {
            display: block;
        }
    </style>
</head>
<body>
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

    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle">☰</button>
    
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <h1>BoseatsAfrica</h1>
                <div class="business-badge">Merchant Portal</div>
            </div>
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-item active">
                    <i>📊</i> Dashboard
                </a>
                <a href="products.php" class="nav-item">
                    <i><?php echo $business_icon; ?></i> Products/Services
                </a>
                <a href="orders.php" class="nav-item">
                    <i>📦</i> Orders/Bookings
                </a>
                <a href="customers.php" class="nav-item">
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
                        Welcome back, <?php echo htmlspecialchars($merchant['owners_name']); ?>! 👋 
                        <span class="business-type"><?php echo ucfirst($merchant['business_type'] ?? 'Business'); ?></span>
                    </h2>
                    <p>Here's what's happening with your <?php echo $merchant['business_type'] ?? 'business'; ?> today</p>
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

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-value">₦<?php echo number_format($stats['total_revenue'], 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
                    <div class="stat-label">Total Orders/Bookings</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><?php echo $business_icon; ?></div>
                    <div class="stat-value"><?php echo $stats['active_products']; ?></div>
                    <div class="stat-label">Active Products/Services</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-value"><?php echo $stats['pending_orders']; ?></div>
                    <div class="stat-label">Pending Orders</div>
                </div>
            </div>

            <!-- Charts and Recent Orders -->
            <div class="dashboard-grid">
                <div class="chart-container">
                    <h3 class="section-title">
                        <span>📈</span> Revenue Overview (Last 7 Days)
                    </h3>
                    <div class="chart-wrapper">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
                
                <div class="recent-orders">
                    <h3 class="section-title">
                        <span>🆕</span> Recent Orders
                    </h3>
                    <div class="orders-list">
                        <?php if (!empty($stats['recent_orders'])): ?>
                            <?php foreach ($stats['recent_orders'] as $order): ?>
                                <div class="order-item">
                                    <div class="order-info">
                                        <div class="order-details">
                                            <div class="order-number">Order #<?php echo $order['id']; ?></div>
                                            <div class="order-meta">
                                                ₦<?php echo number_format($order['total_amount'], 2); ?> • <?php echo time_ago($order['created_at']); ?>
                                            </div>
                                        </div>
                                        <span class="status <?php echo $order['order_status']; ?>">
                                            <?php echo ucfirst($order['order_status']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="text-align: center; color: var(--text-light); padding: 20px;">No recent orders</p>
                        <?php endif; ?>
                    </div>
                    <a href="orders.php" class="btn btn-primary" style="display: block; text-align: center; margin-top: 15px;">
                        View All Orders
                    </a>
                </div>
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
        
        // Close sidebar when clicking on a nav item on mobile
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    toggleSidebar();
                }
            });
        });
        
        // Real Revenue Chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('revenueChart').getContext('2d');
            
            // Chart data from PHP
            const chartData = <?php echo json_encode($revenue_data); ?>;
            
            const revenueChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'Daily Revenue (₦)',
                        data: chartData.data,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#28a745',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `₦${context.parsed.y.toLocaleString()}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₦' + value.toLocaleString();
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });
            
            // Handle window resize
            window.addEventListener('resize', function() {
                revenueChart.resize();
            });
        });
    </script>
</body>
</html>
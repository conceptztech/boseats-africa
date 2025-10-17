<?php 
include '../includes/protect_admin.php';

// Fetch statistics
try {
    // Total Merchants
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM merchants WHERE is_active = 1");
    $total_merchants = $stmt->fetch()['total'];

    // Total Users
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $total_users = $stmt->fetch()['total'];

    // Total Orders
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
    $total_orders = $stmt->fetch()['total'];

    // Total Revenue
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE payment_status = 'completed'");
    $total_revenue = $stmt->fetch()['revenue'];

    // Pending Merchant Approvals
    $stmt = $pdo->query("SELECT COUNT(*) as pending FROM merchants WHERE is_approved = 0 AND is_active = 1");
    $pending_approvals = $stmt->fetch()['pending'];

    // Recent Merchants
    $stmt = $pdo->query("SELECT company_name, owners_name, email, account_status, created_at 
                         FROM merchants 
                         ORDER BY created_at DESC 
                         LIMIT 5");
    $recent_merchants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent Orders
    $stmt = $pdo->query("SELECT o.id, o.reference, u.first_name, u.last_name, o.total_amount, 
                         o.order_status, o.payment_status, o.created_at 
                         FROM orders o 
                         LEFT JOIN users u ON o.user_id = u.id 
                         ORDER BY o.created_at DESC 
                         LIMIT 5");
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent Users
    $stmt = $pdo->query("SELECT first_name, last_name, email, country, created_at 
                         FROM users 
                         ORDER BY created_at DESC 
                         LIMIT 5");
    $recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Dashboard data error: " . $e->getMessage());
    // Initialize empty arrays to prevent errors
    $recent_merchants = [];
    $recent_orders = [];
    $recent_users = [];
    $total_merchants = $total_users = $total_orders = $total_revenue = $pending_approvals = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Food Ordering System</title>
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
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: var(--text);
            line-height: 1.6;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: var(--secondary);
            border-right: 1px solid var(--border);
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .logo {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 20px;
        }

        .logo h2 {
            color: var(--primary);
            font-size: 1.5rem;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 5px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--text);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .nav-link:hover, .nav-link.active {
            background-color: var(--primary);
            color: var(--secondary);
            border-left-color: var(--primary-dark);
        }

        .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }

        .header {
            background: var(--secondary);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--secondary);
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid var(--primary);
        }

        .stat-card.warning {
            border-left-color: #ffc107;
        }

        .stat-card h3 {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .stat-card.warning .stat-number {
            color: #ffc107;
        }

        /* Tables */
        .content-section {
            background: var(--secondary);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .section-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        th {
            background-color: var(--accent);
            font-weight: 600;
        }

        .status {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status.active, .status.completed, .status.approved {
            background-color: var(--success);
            color: #065f46;
        }

        .status.pending {
            background-color: var(--warning);
            color: #92400e;
        }

        .status.inactive, .status.cancelled {
            background-color: var(--error);
            color: #991b1b;
        }

        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: var(--primary);
            color: var(--secondary);
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
            }
            .admin-container {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <h2>Admin Panel</h2>
            </div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="admin.php" class="nav-link active"><i>📊</i> Dashboard</a></li>
                <li class="nav-item"><a href="merchants.php" class="nav-link"><i>🏪</i> Merchants</a></li>
                <li class="nav-item"><a href="users.php" class="nav-link"><i>👥</i> Users</a></li>
                <li class="nav-item"><a href="food_items.php" class="nav-link"><i>🍕</i> Food Items</a></li>
                <li class="nav-item"><a href="orders.php" class="nav-link"><i>📦</i> Orders</a></li>
                <li class="nav-item"><a href="discounts.php" class="nav-link"><i>🎫</i> Discounts</a></li>
                <li class="nav-item"><a href="notifications.php" class="nav-link"><i>🔔</i> Notifications</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <h1>Admin Dashboard</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($admin_name); ?></span>
                    <a href="../logout.php" class="btn btn-primary">Logout</a>
                </div>
            </header>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Merchants</h3>
                    <div class="stat-number"><?php echo $total_merchants; ?></div>
                    <small>Active merchants</small>
                </div>
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <div class="stat-number"><?php echo $total_users; ?></div>
                    <small>Registered users</small>
                </div>
                <div class="stat-card">
                    <h3>Total Orders</h3>
                    <div class="stat-number"><?php echo $total_orders; ?></div>
                    <small>All-time orders</small>
                </div>
                <div class="stat-card">
                    <h3>Total Revenue</h3>
                    <div class="stat-number">$<?php echo number_format($total_revenue, 2); ?></div>
                    <small>Completed orders</small>
                </div>
                <div class="stat-card warning">
                    <h3>Pending Approvals</h3>
                    <div class="stat-number"><?php echo $pending_approvals; ?></div>
                    <small>Merchants waiting</small>
                </div>
            </div>

            <!-- Recent Merchants -->
            <section class="content-section">
                <div class="section-header">
                    <h2>Recent Merchants</h2>
                    <a href="merchants.php" class="btn btn-primary">View All</a>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Company Name</th>
                                <th>Owner</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_merchants)): ?>
                                <?php foreach ($recent_merchants as $merchant): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($merchant['company_name']); ?></td>
                                    <td><?php echo htmlspecialchars($merchant['owners_name']); ?></td>
                                    <td><?php echo htmlspecialchars($merchant['email']); ?></td>
                                    <td><span class="status <?php echo $merchant['account_status']; ?>"><?php echo ucfirst($merchant['account_status']); ?></span></td>
                                    <td><?php echo date('M j, Y', strtotime($merchant['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">No merchants found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Recent Orders -->
            <section class="content-section">
                <div class="section-header">
                    <h2>Recent Orders</h2>
                    <a href="orders.php" class="btn btn-primary">View All</a>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_orders)): ?>
                                <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['reference']); ?></td>
                                    <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td><span class="status <?php echo $order['order_status']; ?>"><?php echo ucfirst($order['order_status']); ?></span></td>
                                    <td><span class="status <?php echo $order['payment_status']; ?>"><?php echo ucfirst($order['payment_status']); ?></span></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center;">No orders found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Recent Users -->
            <section class="content-section">
                <div class="section-header">
                    <h2>Recent Users</h2>
                    <a href="users.php" class="btn btn-primary">View All</a>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Country</th>
                                <th>Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_users)): ?>
                                <?php foreach ($recent_users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['country']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center;">No users found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
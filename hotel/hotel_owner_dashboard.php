<?php
session_start();
include_once '../includes/db_connection.php'; 

// Check if user is logged in and is hotel owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'hotel_owner') {
    header('Location: ../login.php');
    exit;
}

$ownerId = $_SESSION['user_id'];

try {
    // Get dashboard statistics
    $statsQuery = "SELECT 
        COUNT(DISTINCT h.id) as total_hotels,
        COUNT(DISTINCT hb.id) as total_bookings,
        SUM(CASE WHEN hb.booking_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
        SUM(CASE WHEN hb.payment_status = 'paid' THEN hb.total_price ELSE 0 END) as total_revenue
    FROM hotels h
    LEFT JOIN hotel_bookings hb ON h.id = hb.hotel_id
    WHERE h.owner_id = ?";

    $stmt = $pdo->prepare($statsQuery);
    $stmt->execute([$ownerId]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get recent bookings
    $recentBookingsQuery = "SELECT hb.*, h.name as hotel_name, u.full_name as customer_name
    FROM hotel_bookings hb
    JOIN hotels h ON hb.hotel_id = h.id
    LEFT JOIN users u ON hb.user_id = u.id
    WHERE h.owner_id = ?
    ORDER BY hb.created_at DESC
    LIMIT 10";

    $stmt = $pdo->prepare($recentBookingsQuery);
    $stmt->execute([$ownerId]);
    $recentBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get owner's hotels
    $hotelsQuery = "SELECT h.*, 
        (SELECT COUNT(*) FROM hotel_bookings WHERE hotel_id = h.id) as total_bookings,
        (SELECT image_url FROM hotel_images WHERE hotel_id = h.id AND is_primary = 1 LIMIT 1) as main_image
    FROM hotels h
    WHERE h.owner_id = ?
    ORDER BY h.created_at DESC";

    $stmt = $pdo->prepare($hotelsQuery);
    $stmt->execute([$ownerId]);
    $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    // Initialize variables to prevent errors on the page
    $stats = ['total_hotels' => 0, 'total_bookings' => 0, 'confirmed_bookings' => 0, 'total_revenue' => 0];
    $recentBookings = [];
    $hotels = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Owner Dashboard - BoseatsAfrica</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Styles remain the same */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f7fa;
        }

        .dashboard-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            background: linear-gradient(135deg, #72A458 0%, #5a8e43 100%);
            color: white;
            padding: 20px;
        }

        .sidebar-header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 20px;
        }

        .sidebar-header h2 {
            font-size: 24px;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 10px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255,255,255,0.2);
        }

        /* Main Content */
        .main-content {
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 32px;
            color: #333;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-icon.blue {
            background: rgba(33, 150, 243, 0.1);
            color: #2196f3;
        }

        .stat-icon.green {
            background: rgba(76, 175, 80, 0.1);
            color: #4caf50;
        }

        .stat-icon.orange {
            background: rgba(255, 152, 0, 0.1);
            color: #ff9800;
        }

        .stat-icon.purple {
            background: rgba(156, 39, 176, 0.1);
            color: #9c27b0;
        }

        .stat-details h3 {
            font-size: 28px;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-details p {
            color: #666;
            font-size: 14px;
        }

        /* Section */
        .section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 20px;
            color: #333;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #72A458;
            color: white;
        }

        .btn-primary:hover {
            background: #5a8e43;
        }

        /* Hotels Grid */
        .hotels-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .hotel-card {
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s;
        }

        .hotel-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .hotel-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .hotel-info {
            padding: 20px;
        }

        .hotel-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }

        .hotel-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            color: #666;
            font-size: 14px;
        }

        .hotel-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .btn-small {
            padding: 8px 12px;
            font-size: 13px;
        }

        .btn-edit {
            background: #2196f3;
            color: white;
        }

        .btn-view {
            background: #4caf50;
            color: white;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background: #f5f7fa;
            font-weight: 600;
            color: #333;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .badge-warning {
            background: #fff3e0;
            color: #ef6c00;
        }

        .badge-info {
            background: #e3f2fd;
            color: #1565c0;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }

            .sidebar {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Hotel Owner</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="my_hotels.php"><i class="fas fa-hotel"></i> My Hotels</a></li>
                <li><a href="add_hotel.php"><i class="fas fa-plus-circle"></i> Add New Hotel</a></li>
                <li><a href="bookings.php"><i class="fas fa-calendar-check"></i> Bookings</a></li>
                <li><a href="reviews.php"><i class="fas fa-star"></i> Reviews</a></li>
                <li><a href="earnings.php"><i class="fas fa-dollar-sign"></i> Earnings</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div class="header">
                <h1>Dashboard</h1>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Hotel Owner'); ?></span>
                    <img src="<?php echo $_SESSION['profile_image'] ?? '../assets/images/default-avatar.png'; ?>" 
                         alt="Profile" class="user-avatar">
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-hotel"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $stats['total_hotels']; ?></h3>
                        <p>Total Hotels</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $stats['total_bookings']; ?></h3>
                        <p>Total Bookings</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $stats['confirmed_bookings']; ?></h3>
                        <p>Confirmed Bookings</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-details">
                        <h3>$<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
            </div>

            <!-- My Hotels -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">My Hotels</h2>
                    <a href="add_hotel.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Hotel
                    </a>
                </div>

                <div class="hotels-grid">
                    <?php foreach ($hotels as $hotel): ?>
                    <div class="hotel-card">
                        <img src="<?php echo $hotel['main_image'] ?? '../assets/images/default-hotel.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($hotel['name']); ?>" 
                             class="hotel-image"
                             onerror="this.src='../assets/images/default-hotel.jpg'">
                        <div class="hotel-info">
                            <h3 class="hotel-name"><?php echo htmlspecialchars($hotel['name']); ?></h3>
                            <div class="hotel-meta">
                                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($hotel['city']); ?></span>
                                <span><i class="fas fa-calendar"></i> <?php echo $hotel['total_bookings']; ?> bookings</span>
                            </div>
                            <div class="hotel-meta">
                                <span><strong>$<?php echo number_format($hotel['price_per_night'], 2); ?></strong>/night</span>
                                <span class="badge <?php echo $hotel['status'] === 'active' ? 'badge-success' : 'badge-warning'; ?>">
                                    <?php echo ucfirst($hotel['status']); ?>
                                </span>
                            </div>
                            <div class="hotel-actions">
                                <a href="edit_hotel.php?id=<?php echo $hotel['id']; ?>" class="btn btn-edit btn-small">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="../hotel/hotel_detail.php?id=<?php echo $hotel['id']; ?>" class="btn btn-view btn-small" target="_blank">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <?php if (empty($hotels)): ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;">
                        <i class="fas fa-hotel" style="font-size: 48px; margin-bottom: 20px; color: #ddd;"></i>
                        <h3>No Hotels Yet</h3>
                        <p>Add your first hotel to get started</p>
                        <a href="add_hotel.php" class="btn btn-primary" style="margin-top: 20px;">
                            <i class="fas fa-plus"></i> Add Hotel
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Bookings -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">Recent Bookings</h2>
                    <a href="bookings.php" class="btn btn-primary">View All</a>
                </div>

                <?php if (!empty($recentBookings)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Booking Ref</th>
                            <th>Hotel</th>
                            <th>Customer</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentBookings as $booking): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($booking['booking_reference']); ?></strong></td>
                            <td><?php echo htmlspecialchars($booking['hotel_name']); ?></td>
                            <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($booking['checkin_date'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($booking['checkout_date'])); ?></td>
                            <td><strong>$<?php echo number_format($booking['total_price'], 2); ?></strong></td>
                            <td>
                                <span class="badge <?php 
                                    echo $booking['booking_status'] === 'confirmed' ? 'badge-success' : 
                                        ($booking['booking_status'] === 'cancelled' ? 'badge-warning' : 'badge-info'); 
                                ?>">
                                    <?php echo ucfirst($booking['booking_status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="booking_detail.php?id=<?php echo $booking['id']; ?>" class="btn btn-small" style="background: #2196f3; color: white;">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-calendar-times" style="font-size: 48px; margin-bottom: 20px; color: #ddd;"></i>
                    <h3>No Bookings Yet</h3>
                    <p>Your bookings will appear here once customers start booking</p>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
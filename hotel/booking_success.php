<?php
session_start();
include_once '../includes/db_connection.php'; 

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Check if booking was completed and stored in session
if (!isset($_SESSION['completed_booking_id'])) {
    header('Location: index.php');
    exit;
}

$bookingId = $_SESSION['completed_booking_id'];
$userId = $_SESSION['user_id'];

try {
    // Get booking details from the database
    $query = "SELECT hb.*, h.name as hotel_name, h.location, rt.name as room_type_name 
              FROM hotel_bookings hb
              JOIN hotels h ON hb.hotel_id = h.id
              JOIN room_types rt ON hb.room_type_id = rt.id
              WHERE hb.id = ? AND hb.user_id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$bookingId, $userId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        // If booking not found for the user, redirect
        unset($_SESSION['completed_booking_id']);
        header('Location: index.php');
        exit;
    }
    
    // Use the booking reference from the database record
    $bookingReference = $booking['booking_reference'];

} catch (PDOException $e) {
    error_log("Booking success page error: " . $e->getMessage());
    // Redirect to a safe page if there's a database error
    header('Location: index.php');
    exit;
}

// Clear session variables after fetching data
unset($_SESSION['completed_booking_id']);
unset($_SESSION['booking_reference']); // This is no longer needed from session
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Successful - BoseatsAfrica</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .success-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #72A458 0%, #5a8e43 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .success-container {
            max-width: 600px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-header {
            background: linear-gradient(135deg, #72A458 0%, #5a8e43 100%);
            padding: 40px 20px;
            text-align: center;
            color: white;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            animation: scaleIn 0.5s ease-out 0.3s both;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        .success-icon i {
            font-size: 40px;
            color: #72A458;
        }

        .success-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .success-header p {
            opacity: 0.9;
            font-size: 16px;
        }

        .booking-details-section {
            padding: 30px;
        }

        .reference-box {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 30px;
            border: 2px dashed #72A458;
        }

        .reference-box label {
            display: block;
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .reference-number {
            font-size: 24px;
            font-weight: 700;
            color: #72A458;
            letter-spacing: 2px;
        }

        .details-grid {
            display: grid;
            gap: 15px;
            margin-bottom: 30px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #666;
            font-size: 14px;
        }

        .detail-value {
            color: #333;
            font-weight: 600;
            text-align: right;
        }

        .total-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 20px;
            font-weight: 700;
        }

        .total-label {
            color: #333;
        }

        .total-amount {
            color: #72A458;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .btn-action {
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: #72A458;
            color: white;
        }

        .btn-primary:hover {
            background: #5a8e43;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #333;
            border: 2px solid #e0e0e0;
        }

        .btn-secondary:hover {
            background: #e9ecef;
        }

        .info-message {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .info-message i {
            color: #2196f3;
            margin-right: 10px;
        }

        .info-message p {
            margin: 0;
            color: #1565c0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="success-page">
        <div class="success-container">
            <!-- Success Header -->
            <div class="success-header">
                <div class="success-icon">
                    <i class="fas fa-check"></i>
                </div>
                <h1>Booking Successful!</h1>
                <p>Your hotel reservation has been confirmed</p>
            </div>

            <!-- Booking Details -->
            <div class="booking-details-section">
                <!-- Booking Reference -->
                <div class="reference-box">
                    <label>Booking Reference</label>
                    <div class="reference-number"><?php echo htmlspecialchars($bookingReference); ?></div>
                </div>

                <!-- Info Message -->
                <div class="info-message">
                    <i class="fas fa-info-circle"></i>
                    <p>Please save your booking reference. You'll need it for check-in.</p>
                </div>

                <!-- Booking Details -->
                <div class="details-grid">
                    <div class="detail-row">
                        <span class="detail-label">Hotel</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['hotel_name']); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Location</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['location']); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Guest Name</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['customer_name']); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Phone</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['phone']); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Check-in</span>
                        <span class="detail-value"><?php echo date('M d, Y', strtotime($booking['checkin_date'])); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Check-out</span>
                        <span class="detail-value"><?php echo date('M d, Y', strtotime($booking['checkout_date'])); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Room Type</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['room_type_name']); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Nights</span>
                        <span class="detail-value"><?php echo $booking['nights']; ?> Night<?php echo $booking['nights'] > 1 ? 's' : ''; ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Guests</span>
                        <span class="detail-value"><?php echo $booking['adults']; ?> Adult<?php echo $booking['adults'] > 1 ? 's' : ''; ?>, <?php echo $booking['children']; ?> Child<?php echo $booking['children'] != 1 ? 'ren' : ''; ?></span>
                    </div>
                </div>

                <!-- Total -->
                <div class="total-section">
                    <div class="total-row">
                        <span class="total-label">Total Paid</span>
                        <span class="total-amount">$ <?php echo number_format($booking['total_price'], 2); ?></span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="../user/bookings.php" class="btn-action btn-primary">
                        <i class="fas fa-calendar"></i> My Bookings
                    </a>
                    <a href="index.php" class="btn-action btn-secondary">
                        <i class="fas fa-home"></i> Back to Hotels
                    </a>
                </div>

                <!-- Print/Download -->
                <button onclick="window.print()" class="btn-action btn-secondary" style="grid-column: 1 / -1;">
                    <i class="fas fa-print"></i> Print Confirmation
                </button>
            </div>
        </div>
    </div>

    <script>
        // Prevent back navigation to payment page
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>
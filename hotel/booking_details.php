<?php
session_start();
include_once '../includes/db_connection.php'; 

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$userId = $_SESSION['user_id'];
$userEmail = $_SESSION['email'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

try {
    // Get and validate form data
    $hotelId = intval($_POST['hotel_id']);
    $customerName = trim($_POST['customer_name']);
    $phone = trim($_POST['phone']);
    $checkin = $_POST['checkin'];
    $checkout = $_POST['checkout'];
    $children = intval($_POST['children']);
    $adults = intval($_POST['adults']);
    
    // Parse selected rooms JSON
    $selectedRoomsJson = $_POST['selected_rooms'] ?? '{}';
    $selectedRooms = json_decode($selectedRoomsJson, true);
    
    if (empty($selectedRooms) || !is_array($selectedRooms)) {
        throw new Exception("Please select at least one room");
    }
    
    // Validate required fields
    if (empty($customerName) || empty($phone) || empty($checkin) || empty($checkout)) {
        throw new Exception("All required fields must be filled");
    }
    
    if ($hotelId <= 0) {
        throw new Exception("Invalid hotel selection");
    }
    
    if ($adults <= 0) {
        throw new Exception("At least one adult is required");
    }
    
    // Validate dates
    $checkinDate = new DateTime($checkin);
    $checkoutDate = new DateTime($checkout);
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    
    if ($checkinDate < $today) {
        throw new Exception("Check-in date cannot be in the past");
    }
    
    if ($checkoutDate <= $checkinDate) {
        throw new Exception("Check-out date must be after check-in date");
    }

    // Get hotel details
    $hotelQuery = "SELECT * FROM hotels WHERE id = ? AND status = 'active'";
    $stmt = $pdo->prepare($hotelQuery);
    $stmt->execute([$hotelId]);
    $hotel = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$hotel) {
        throw new Exception("Hotel not found or not available");
    }

    // Calculate number of nights
    $nights = $checkinDate->diff($checkoutDate)->days;
    if ($nights <= 0) {
        $nights = 1;
    }
    
    // Check minimum nights requirement
    $minNights = intval($hotel['nights']);
    if ($nights < $minNights) {
        throw new Exception("Minimum stay is $minNights night(s) for this hotel");
    }
    
    // Validate and calculate total price for all selected rooms
    $totalPrice = 0;
    $roomDetails = [];
    
    foreach ($selectedRooms as $roomId => $roomData) {
        $roomCount = intval($roomData['count'] ?? 0);
        if ($roomCount <= 0) continue;
        
        // Get room type details
        $roomQuery = "SELECT * FROM room_types WHERE id = ? AND hotel_id = ? AND available = 1";
        $stmt = $pdo->prepare($roomQuery);
        $stmt->execute([intval($roomId), $hotelId]);
        $roomType = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$roomType) {
            throw new Exception("One or more selected rooms are no longer available");
        }
        
        // Calculate price for this room type
        $roomPrice = floatval($roomType['price']) * $nights * $roomCount;
        $totalPrice += $roomPrice;
        
        $roomDetails[] = [
            'id' => intval($roomId),
            'name' => $roomType['name'],
            'count' => $roomCount,
            'price_per_night' => floatval($roomType['price']),
            'total_price' => $roomPrice
        ];
    }
    
    if (empty($roomDetails)) {
        throw new Exception("No valid rooms selected");
    }

    // Store booking in session for payment
    $_SESSION['pending_booking'] = [
        'hotel_id' => $hotelId,
        'hotel_name' => $hotel['name'],
        'customer_name' => $customerName,
        'phone' => $phone,
        'checkin' => $checkin,
        'checkout' => $checkout,
        'children' => $children,
        'adults' => $adults,
        'nights' => $nights,
        'total_price' => $totalPrice,
        'company' => $hotel['company_name'] ?? 'BoseatsAfrica',
        'room_details' => $roomDetails
    ];

} catch (Exception $e) {
    error_log("Booking details error: " . $e->getMessage());
    $_SESSION['error_message'] = $e->getMessage();
    
    if (isset($hotelId) && $hotelId > 0) {
        header('Location: hotel_detail.php?id=' . $hotelId);
    } else {
        header('Location: index.php');
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - BoseatsAfrica</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <style>
        .booking-details-page {
            min-height: 100vh;
            background: #f5f7fa;
            padding: 40px 20px;
        }

        .booking-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 40px;
        }

        .booking-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }

        .booking-header h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
        }

        .booking-info-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .details-section {
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }

        .section-header h2 {
            font-size: 22px;
            color: #333;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .detail-label {
            color: #666;
            font-size: 14px;
        }

        .detail-value {
            color: #333;
            font-weight: 600;
            font-size: 16px;
        }

        .rooms-breakdown {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .room-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .room-item:last-child {
            border-bottom: none;
        }

        .room-item-details {
            flex: 1;
        }

        .room-item-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .room-item-info {
            font-size: 13px;
            color: #666;
        }

        .room-item-price {
            text-align: right;
            font-weight: 600;
            color: #28a745;
        }

        .summary-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            margin-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding-top: 15px;
            border-top: 2px solid #28a745;
        }

        .total-label {
            font-size: 20px;
            font-weight: 700;
            color: #333;
        }

        .total-amount {
            font-size: 24px;
            font-weight: 700;
            color: #28a745;
        }

        .btn-pay-now {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #28a745 0%, #5a8e43 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-pay-now:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #28a745;
            text-decoration: none;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .booking-container {
                padding: 20px;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="booking-details-page">
        <div class="booking-container">
            <div class="booking-header">
                <h1><i class="fas fa-receipt"></i> Booking Details</h1>
                <p style="color: #666;">Review your booking information before payment</p>
            </div>

            <div class="booking-info-section">
                <div class="info-row">
                    <span class="info-label">Date</span>
                    <span class="info-value"><?php echo date('d F Y'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Time</span>
                    <span class="info-value"><?php echo date('h:i A'); ?></span>
                </div>
            </div>

            <div class="details-section">
                <div class="section-header">
                    <h2>Booking Information</h2>
                </div>

                <div class="details-grid">
                    <div class="detail-item">
                        <span class="detail-label">Guest Name</span>
                        <span class="detail-value"><?php echo htmlspecialchars($customerName); ?></span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Phone Number</span>
                        <span class="detail-value"><?php echo htmlspecialchars($phone); ?></span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Check-in Date</span>
                        <span class="detail-value"><?php echo date('M d, Y', strtotime($checkin)); ?></span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Check-out Date</span>
                        <span class="detail-value"><?php echo date('M d, Y', strtotime($checkout)); ?></span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Number of Nights</span>
                        <span class="detail-value"><?php echo $nights; ?> Night<?php echo $nights > 1 ? 's' : ''; ?></span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Guests</span>
                        <span class="detail-value"><?php echo $adults; ?> Adult<?php echo $adults > 1 ? 's' : ''; ?>, <?php echo $children; ?> Child<?php echo $children != 1 ? 'ren' : ''; ?></span>
                    </div>
                </div>
            </div>

            <div class="details-section">
                <div class="section-header">
                    <h2>Selected Rooms</h2>
                </div>

                <div class="rooms-breakdown">
                    <?php foreach ($roomDetails as $room): ?>
                    <div class="room-item">
                        <div class="room-item-details">
                            <div class="room-item-name"><?php echo htmlspecialchars($room['name']); ?></div>
                            <div class="room-item-info">
                                Quantity: <?php echo $room['count']; ?> × $<?php echo number_format($room['price_per_night'], 2); ?>/night × <?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?>
                            </div>
                        </div>
                        <div class="room-item-price">
                            $<?php echo number_format($room['total_price'], 2); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="summary-section">
                <div class="summary-row">
                    <span class="summary-label">Hotel</span>
                    <span class="summary-value"><?php echo htmlspecialchars($hotel['name']); ?></span>
                </div>

                <div class="summary-row">
                    <span class="summary-label">Total Rooms</span>
                    <span class="summary-value"><?php echo array_sum(array_column($roomDetails, 'count')); ?></span>
                </div>

                <div class="summary-row">
                    <span class="summary-label">Duration</span>
                    <span class="summary-value"><?php echo $nights; ?> Night<?php echo $nights > 1 ? 's' : ''; ?></span>
                </div>

                <div class="total-row">
                    <span class="total-label">Total Amount</span>
                    <span class="total-amount">$ <?php echo number_format($totalPrice, 2); ?></span>
                </div>
            </div>

            <button class="btn-pay-now" onclick="processPayment()">
                <i class="fas fa-lock"></i> Pay Now - $ <?php echo number_format($totalPrice, 2); ?>
            </button>

            <div class="back-link">
                <a href="hotel_detail.php?id=<?php echo $hotelId; ?>">
                    <i class="fas fa-arrow-left"></i> Back to hotel details
                </a>
            </div>
        </div>
    </div>

    <script>
        function processPayment() {
            const totalAmount = <?php echo $totalPrice; ?>;
            const totalInKobo = Math.round(totalAmount * 100);
            
            const handler = PaystackPop.setup({
                key: 'pk_test_1b251229f5da6778289c78b9f73075dcd30003a9',
                email: '<?php echo htmlspecialchars($userEmail); ?>',
                amount: totalInKobo,
                currency: 'USD',
                ref: 'HOTEL_' + Date.now() + '_' + Math.floor((Math.random() * 1000000)),
                metadata: {
                    custom_fields: [
                        { 
                            display_name: "Hotel Name", 
                            variable_name: "hotel_name", 
                            value: "<?php echo htmlspecialchars(str_replace('"', '\\"', $hotel['name'])); ?>" 
                        },
                        { 
                            display_name: "Customer Name", 
                            variable_name: "customer_name", 
                            value: "<?php echo htmlspecialchars(str_replace('"', '\\"', $customerName)); ?>" 
                        },
                        { 
                            display_name: "Check-in", 
                            variable_name: "checkin", 
                            value: "<?php echo htmlspecialchars($checkin); ?>" 
                        },
                        { 
                            display_name: "Check-out", 
                            variable_name: "checkout", 
                            value: "<?php echo htmlspecialchars($checkout); ?>" 
                        },
                        { 
                            display_name: "Total Rooms", 
                            variable_name: "total_rooms", 
                            value: "<?php echo array_sum(array_column($roomDetails, 'count')); ?>" 
                        }
                    ]
                },
                callback: function(response) {
                    document.querySelector('.btn-pay-now').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying payment...';
                    document.querySelector('.btn-pay-now').disabled = true;
                    
                    window.location.href = 'verify_hotel_payment.php?reference=' + encodeURIComponent(response.reference);
                },
                onClose: function() {
                    console.log('Payment window closed.');
                }
            });
            
            handler.openIframe();
        }

        window.addEventListener('beforeunload', function(e) {
            if (document.querySelector('.btn-pay-now').disabled !== true) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>
</body>
</html>
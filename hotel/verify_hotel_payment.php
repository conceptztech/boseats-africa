<?php
session_start();
include_once '../includes/db_connection.php'; 

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$reference = isset($_GET['reference']) ? trim($_GET['reference']) : '';

if (empty($reference)) {
    $_SESSION['error_message'] = "Invalid payment reference.";
    header('Location: index.php');
    exit;
}

if (!isset($_SESSION['pending_booking'])) {
    $_SESSION['error_message'] = "No pending booking found. Please try again.";
    header('Location: index.php');
    exit;
}

$booking = $_SESSION['pending_booking'];

if (empty($booking['hotel_id']) || empty($booking['room_details']) || empty($booking['total_price'])) {
    $_SESSION['error_message'] = "Invalid booking data. Please try again.";
    unset($_SESSION['pending_booking']);
    header('Location: index.php');
    exit;
}

$paystackSecretKey = 'sk_test_your_actual_paystack_secret_key_here';

if ($paystackSecretKey === 'sk_test_your_actual_paystack_secret_key_here') {
    error_log("CRITICAL: Paystack secret key not configured");
    $_SESSION['error_message'] = "Payment system not configured. Please contact support.";
    header('Location: booking_details.php');
    exit;
}

// Verify payment with Paystack
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer " . $paystackSecretKey,
        "Cache-Control: no-cache",
        "Content-Type: application/json"
    ],
));

$response = curl_exec($curl);
$err = curl_error($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($err) {
    error_log("Paystack verification cURL error: " . $err);
    $_SESSION['error_message'] = "Payment verification failed. Please contact support with reference: " . $reference;
    header('Location: booking_details.php');
    exit;
}

$result = json_decode($response, true);
error_log("Paystack verification response (HTTP $httpCode): " . $response);

if (!$result || !isset($result['status'])) {
    error_log("Invalid Paystack response: " . $response);
    $_SESSION['error_message'] = "Payment verification failed. Please contact support with reference: " . $reference;
    header('Location: booking_details.php');
    exit;
}

if ($result['status'] && isset($result['data']['status']) && $result['data']['status'] === 'success') {
    $paidAmount = $result['data']['amount'] / 100;
    $expectedAmount = floatval($booking['total_price']);
    
    if (abs($paidAmount - $expectedAmount) > ($expectedAmount * 0.01)) {
        error_log("Amount mismatch: Expected $expectedAmount, Got $paidAmount for reference $reference");
        $_SESSION['error_message'] = "Payment amount mismatch. Please contact support.";
        header('Location: booking_details.php');
        exit;
    }
    
    $pdo->beginTransaction();
    
    try {
        // Check if payment reference already used
        $checkQuery = "SELECT id FROM hotel_bookings WHERE payment_reference = ?";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->execute([$reference]);
        
        if ($checkStmt->fetch()) {
            $pdo->rollBack();
            $_SESSION['error_message'] = "This payment has already been processed.";
            header('Location: index.php');
            exit;
        }
        
        // Generate unique booking reference
        $bookingReference = 'HOTEL' . date('Ymd') . strtoupper(substr(uniqid(), -6));
        
        $refCheckQuery = "SELECT id FROM hotel_bookings WHERE booking_reference = ?";
        $refCheckStmt = $pdo->prepare($refCheckQuery);
        $refCheckStmt->execute([$bookingReference]);
        
        while ($refCheckStmt->fetch()) {
            $bookingReference = 'HOTEL' . date('Ymd') . strtoupper(substr(uniqid(), -6));
            $refCheckStmt->execute([$bookingReference]);
        }
        
        // Insert booking for each room type
        $insertQuery = "INSERT INTO hotel_bookings (
            user_id, hotel_id, customer_name, phone, checkin_date, checkout_date, 
            room_type_id, children, adults, room_number, nights, total_price, 
            payment_reference, payment_status, booking_status, booking_reference, 
            special_requests, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'paid', 'confirmed', ?, ?, NOW())";
        
        $stmt = $pdo->prepare($insertQuery);
        $bookingIds = [];
        
        foreach ($booking['room_details'] as $room) {
            for ($i = 0; $i < $room['count']; $i++) {
                // Each room gets a separate booking record
                $roomNumber = 'R' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
                
                $stmt->execute([
                    $userId,
                    intval($booking['hotel_id']),
                    $booking['customer_name'],
                    $booking['phone'],
                    $booking['checkin'],
                    $booking['checkout'],
                    intval($room['id']),
                    intval($booking['children']),
                    intval($booking['adults']),
                    $roomNumber,
                    intval($booking['nights']),
                    floatval($room['price_per_night']) * intval($booking['nights']),
                    $reference,
                    $bookingReference,
                    null
                ]);
                
                $bookingIds[] = $pdo->lastInsertId();
            }
        }
        
        // Send confirmation email
        try {
            $to = $_SESSION['email'];
            $subject = "Hotel Booking Confirmation - " . $bookingReference;
            
            $roomsList = '';
            foreach ($booking['room_details'] as $room) {
                $roomsList .= "<p><strong>" . $room['count'] . "x " . htmlspecialchars($room['name']) . "</strong> - $" . number_format($room['total_price'], 2) . "</p>";
            }
            
            $message = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: #28a745; color: white; padding: 20px; text-align: center; }
                        .content { padding: 20px; background: #f9f9f9; }
                        .booking-ref { font-size: 24px; font-weight: bold; color: #28a745; text-align: center; padding: 15px; background: white; margin: 20px 0; }
                        .details { background: white; padding: 15px; margin: 10px 0; }
                        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>Booking Confirmed!</h1>
                        </div>
                        <div class='content'>
                            <p>Dear " . htmlspecialchars($booking['customer_name']) . ",</p>
                            <p>Your hotel booking has been confirmed! Here are your booking details:</p>
                            
                            <div class='booking-ref'>
                                Booking Reference: " . htmlspecialchars($bookingReference) . "
                            </div>
                            
                            <div class='details'>
                                <p><strong>Hotel:</strong> " . htmlspecialchars($booking['hotel_name']) . "</p>
                                <p><strong>Check-in:</strong> " . date('M d, Y', strtotime($booking['checkin'])) . "</p>
                                <p><strong>Check-out:</strong> " . date('M d, Y', strtotime($booking['checkout'])) . "</p>
                                <p><strong>Nights:</strong> " . $booking['nights'] . "</p>
                                <p><strong>Guests:</strong> " . intval($booking['adults']) . " Adult(s), " . intval($booking['children']) . " Child(ren)</p>
                                
                                <h3>Rooms Booked:</h3>
                                " . $roomsList . "
                                
                                <p><strong>Total Paid:</strong> $" . number_format($booking['total_price'], 2) . "</p>
                            </div>
                            
                            <p>Please keep this booking reference safe. You will need it during check-in.</p>
                            <p>Thank you for booking with BoseatsAfrica!</p>
                        </div>
                        <div class='footer'>
                            <p>This is an automated email. Please do not reply.</p>
                            <p>&copy; " . date('Y') . " BoseatsAfrica. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: BoseatsAfrica <noreply@boseatsafrica.com>" . "\r\n";
            
            @mail($to, $subject, $message, $headers);
        } catch (Exception $e) {
            error_log("Failed to send confirmation email: " . $e->getMessage());
        }
        
        $pdo->commit();
        
        unset($_SESSION['pending_booking']);
        
        // Store the first booking ID and reference for success page
        $_SESSION['completed_booking_id'] = $bookingIds[0];
        $_SESSION['booking_reference'] = $bookingReference;
        $_SESSION['total_rooms_booked'] = array_sum(array_column($booking['room_details'], 'count'));
        
        header('Location: booking_success.php');
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Booking database error: " . $e->getMessage());
        $_SESSION['error_message'] = "Failed to complete booking. Please contact support with reference: " . $reference;
        header('Location: booking_details.php');
        exit;
    }
} else {
    $errorMessage = "Payment verification failed.";
    
    if (isset($result['message'])) {
        $errorMessage .= " " . $result['message'];
    }
    
    if (isset($result['data']['gateway_response'])) {
        error_log("Paystack gateway response: " . $result['data']['gateway_response']);
    }
    
    $_SESSION['error_message'] = $errorMessage;
    header('Location: booking_details.php');
    exit;
}
?>
<?php
session_start();
include_once 'db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['valid' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$couponCode = $input['coupon_code'] ?? '';
$cartTotal = $input['cart_total'] ?? 0;
$userId = $input['user_id'] ?? 0;

if (empty($couponCode)) {
    echo json_encode(['valid' => false, 'message' => 'Coupon code is required']);
    exit;
}

try {
    // Check if coupon exists and is valid
    $sql = "SELECT * FROM discount_offers 
            WHERE coupon_code = ? 
            AND is_active = 1 
            AND start_date <= NOW() 
            AND (end_date IS NULL OR end_date >= NOW())
            AND (usage_limit IS NULL OR used_count < usage_limit)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$couponCode]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$coupon) {
        echo json_encode(['valid' => false, 'message' => 'Invalid or expired coupon code']);
        exit;
    }
    
    // Check minimum order amount
    if ($cartTotal < $coupon['min_order_amount']) {
        echo json_encode(['valid' => false, 'message' => 'Minimum order amount not reached']);
        exit;
    }
    
    // Return valid coupon data
    echo json_encode([
        'valid' => true,
        'message' => 'Coupon applied successfully!',
        'discount_data' => [
            'coupon_code' => $coupon['coupon_code'],
            'discount_type' => $coupon['discount_type'],
            'discount_value' => $coupon['discount_value'],
            'max_discount_amount' => $coupon['max_discount_amount']
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Coupon validation error: " . $e->getMessage());
    echo json_encode(['valid' => false, 'message' => 'Error validating coupon']);
}
?>
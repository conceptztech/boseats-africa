<?php
session_start();
include_once '../includes/db_connection.php';
include_once '../includes/notification_functions.php'; // Include notification functions at the top

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);
$reference = $input['reference'] ?? '';
$orderData = $input['order_data'] ?? [];

// Validate input
if (empty($reference)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing payment reference']);
    exit;
}

if (empty($orderData)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing order data']);
    exit;
}

try {
    // Verify with Paystack
    $secretKey = 'sk_test_1bf1faf459d072c627db80a2a7061562e13030b7';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.paystack.co/transaction/verify/" . rawurlencode($reference));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $secretKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        throw new Exception("cURL Error: " . $curlError);
    }
    
    $result = json_decode($response, true);
    
    if ($httpCode === 200 && $result['status'] === true && $result['data']['status'] === 'success') {
        // Payment verified successfully
        $totalAmount = $orderData['total'] ?? 0;
        $userId = $orderData['user_id'] ?? null;
        $location = $orderData['location'] ?? '';
        $address = $orderData['note'] ?? '';
        $couponCode = $orderData['coupon_code'] ?? null;
        
        // DEBUG: Log the order data to see what's being sent
        error_log("=== PAYMENT VERIFICATION DEBUG ===");
        error_log("Order Data: " . print_r($orderData, true));
        error_log("User ID: " . $userId);
        
        if (!$userId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
            exit;
        }
        
        // Extract merchant_id from order data
        $merchant_id = null;
        $companyName = null;
        
        // Method 1: Check if items array has companyName
        if (isset($orderData['items']) && is_array($orderData['items']) && !empty($orderData['items'])) {
            $firstItem = $orderData['items'][0];
            if (isset($firstItem['companyName'])) {
                $companyName = $firstItem['companyName'];
                error_log("Found companyName in items: " . $companyName);
                
                // Find merchant by company name
                $stmt = $pdo->prepare("SELECT id FROM merchants WHERE company_name = ? LIMIT 1");
                $stmt->execute([$companyName]);
                $merchant = $stmt->fetch();
                
                if ($merchant) {
                    $merchant_id = $merchant['id'];
                    error_log("Found merchant_id by company name: " . $merchant_id);
                } else {
                    error_log("No merchant found with company name: " . $companyName);
                }
            }
        }
        
        // Method 2: Check if there's a companyName directly in orderData
        if (!$merchant_id && isset($orderData['companyName'])) {
            $companyName = $orderData['companyName'];
            error_log("Found companyName in orderData: " . $companyName);
            
            $stmt = $pdo->prepare("SELECT id FROM merchants WHERE company_name = ? LIMIT 1");
            $stmt->execute([$companyName]);
            $merchant = $stmt->fetch();
            
            if ($merchant) {
                $merchant_id = $merchant['id'];
                error_log("Found merchant_id by company name: " . $merchant_id);
            }
        }
        
        // Method 3: Try to get merchant_id from food_items table using product ID
        if (!$merchant_id && isset($orderData['items']) && is_array($orderData['items']) && !empty($orderData['items'])) {
            $firstItem = $orderData['items'][0];
            if (isset($firstItem['id'])) {
                $product_id = $firstItem['id'];
                error_log("Trying to find merchant_id from product ID: " . $product_id);
                
                $stmt = $pdo->prepare("SELECT merchant_id FROM food_items WHERE id = ? LIMIT 1");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch();
                
                if ($product && $product['merchant_id']) {
                    $merchant_id = $product['merchant_id'];
                    error_log("Found merchant_id from product: " . $merchant_id);
                }
            }
        }
        
        error_log("Final merchant_id to save: " . ($merchant_id ?? 'NULL'));
        
        // Insert into orders table
        $sql = "INSERT INTO orders (
                    user_id, 
                    merchant_id,
                    payment_reference, 
                    order_data,
                    total_amount, 
                    delivery_location, 
                    delivery_address, 
                    payment_status,
                    order_status,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'completed', 'pending', NOW())";
        
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([
            $userId, 
            $merchant_id,
            $reference,
            json_encode($orderData),
            $totalAmount,
            $location, 
            $address
        ]);
        
        if ($success) {
            $orderId = $pdo->lastInsertId();
            
            // Update coupon usage if used
            if ($couponCode) {
                $updateSql = "UPDATE discount_offers SET used_count = used_count + 1 WHERE coupon_code = ?";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([$couponCode]);
            }
            
            // Clear cart or session data if needed
            if (isset($_SESSION['cart'])) {
                unset($_SESSION['cart']);
            }
            
            // UPDATE ORDER STATUS TO CONFIRMED AFTER SUCCESSFUL PAYMENT
            $update_order_sql = "UPDATE orders SET payment_status = 'paid', order_status = 'confirmed' WHERE id = ?";
            $update_order_stmt = $pdo->prepare($update_order_sql);
            $update_order_stmt->execute([$orderId]);
            
            // CREATE NOTIFICATIONS FOR THE USER
            createPaymentNotification($userId, $orderId, 'successful');
            createOrderNotification($userId, $orderId, 'confirmed', $orderData);
            
            error_log("Order saved successfully! Order ID: " . $orderId . ", Merchant ID: " . ($merchant_id ?? 'NULL'));
            
            echo json_encode([
                'success' => true, 
                'message' => 'Payment verified and order saved',
                'order_id' => $orderId,
                'reference' => $reference
            ]);
            
        } else {
            throw new Exception("Failed to save order to database");
        }
        
    } else {
        $errorMessage = $result['message'] ?? 'Payment verification failed';
        error_log("Paystack verification failed: " . $errorMessage);
        
        // If we have an order ID from a previous attempt, update it to failed status
        if (isset($orderId)) {
            $update_order_sql = "UPDATE orders SET payment_status = 'failed' WHERE id = ?";
            $update_order_stmt = $pdo->prepare($update_order_sql);
            $update_order_stmt->execute([$orderId]);
            
            // Create notification for failed payment
            createPaymentNotification($userId, $orderId, 'failed');
        }
        
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => $errorMessage
        ]);
    }
    
} catch (Exception $e) {
    error_log("Payment verification error: " . $e->getMessage());
    
    // If we have an order ID from a previous attempt, update it to failed status
    if (isset($orderId) && isset($userId)) {
        $update_order_sql = "UPDATE orders SET payment_status = 'failed' WHERE id = ?";
        $update_order_stmt = $pdo->prepare($update_order_sql);
        $update_order_stmt->execute([$orderId]);
        
        // Create notification for failed payment
        createPaymentNotification($userId, $orderId, 'failed');
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server error during payment verification: ' . $e->getMessage()
    ]);
}
?>
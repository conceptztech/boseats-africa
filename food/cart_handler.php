<?php
session_start();
include_once '../includes/db_connection.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = $_POST['user_id'] ?? null;
    
    switch ($action) {
        case 'save_cart':
            saveUserCart($pdo, $userId, $_POST['cart_data']);
            break;
            
        case 'save_order':
            saveUserOrder($pdo, $userId, $_POST);
            break;
            
        case 'clear_cart':
            clearUserCart($pdo, $userId);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function saveUserCart($pdo, $userId, $cartData) {
    try {
        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'User ID required']);
            return;
        }

        // Check if user already has a cart
        $checkSql = "SELECT id FROM user_carts WHERE user_id = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$userId]);
        $existingCart = $checkStmt->fetch();
        
        if ($existingCart) {
            // Update existing cart
            $sql = "UPDATE user_carts SET food_items = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$cartData, $userId]);
        } else {
            // Create new cart
            $sql = "INSERT INTO user_carts (user_id, food_items) VALUES (?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId, $cartData]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Cart saved successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error saving cart: ' . $e->getMessage()]);
    }
}

function saveUserOrder($pdo, $userId, $data) {
    try {
        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'User ID required']);
            return;
        }

        // Prepare order data for the orders table
        $orderData = json_decode($data['order_data'], true);
        $reference = $data['payment_reference']; // Reference returned from Paystack

        // Verify the payment with Paystack
        if ($this->verifyPayment($reference)) {
            $sql = "INSERT INTO orders (
                user_id, 
                order_data, 
                total_amount, 
                payment_status, 
                payment_reference, 
                delivery_location, 
                delivery_address, 
                order_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $userId,
                $data['order_data'],
                $data['total_amount'],
                'paid',  // Set payment status to 'paid'
                $data['payment_reference'],
                $data['delivery_location'],
                $data['delivery_address'],
                'pending' // Default order status
            ]);

            $orderId = $pdo->lastInsertId();

            // Clear user's cart after successful order
            $clearSql = "DELETE FROM user_carts WHERE user_id = ?";
            $clearStmt = $pdo->prepare($clearSql);
            $clearStmt->execute([$userId]);

            echo json_encode([
                'success' => true, 
                'message' => 'Order saved successfully', 
                'order_id' => $orderId
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Payment verification failed']);
        }
    } catch (PDOException $e) {
        error_log("Order save error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error saving order: ' . $e->getMessage()]);
    }
}

function verifyPayment($reference) {
    $secretKey = "sk_test_your_secret_key";  // Use your secret API key here
    $url = "https://api.paystack.co/transaction/verify/$reference";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $secretKey"
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    // Check if the payment was successful
    if ($data['status'] == 'success') {
        // Payment was successful
        return true;
    } else {
        // Payment failed
        return false;
    }
}

function clearUserCart($pdo, $userId) {
    try {
        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'User ID required']);
            return;
        }

        $sql = "DELETE FROM user_carts WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        
        echo json_encode(['success' => true, 'message' => 'Cart cleared successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error clearing cart: ' . $e->getMessage()]);
    }
}
?>
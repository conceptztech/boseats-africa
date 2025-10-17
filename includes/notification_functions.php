<?php
include_once 'db_connection.php';

/**
 * Create a notification for a user
 * @param int $user_id The user ID
 * @param string $title Notification title
 * @param string $message Notification message
 * @param string $type Notification type (order_update, system, promotion, security)
 * @return bool Success status
 */
function createNotification($user_id, $title, $message, $type = 'order_update') {
    global $pdo;
    
    try {
        $sql = "INSERT INTO notifications (user_id, title, message, type, is_read, created_at) 
                VALUES (?, ?, ?, ?, 0, NOW())";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$user_id, $title, $message, $type]);
    } catch (PDOException $e) {
        error_log("Notification creation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Create order-related notifications
 * @param int $user_id The user ID
 * @param int $order_id The order ID
 * @param string $status Order status
 * @param array $order_data Order data
 * @return bool Success status
 */
function createOrderNotification($user_id, $order_id, $status, $order_data = []) {
    $notifications = [];
    
    switch ($status) {
        case 'pending':
            $notifications[] = [
                'title' => 'Order Placed Successfully',
                'message' => "Your order #{$order_id} has been received and is being processed. You'll be notified when it's confirmed.",
                'type' => 'order_update'
            ];
            break;
            
        case 'confirmed':
            $notifications[] = [
                'title' => 'Order Confirmed',
                'message' => "Your order #{$order_id} has been confirmed and is being prepared.",
                'type' => 'order_update'
            ];
            break;
            
        case 'processing':
            $notifications[] = [
                'title' => 'Order Processing',
                'message' => "Your order #{$order_id} is now being processed. We're preparing your items.",
                'type' => 'order_update'
            ];
            break;
            
        case 'shipped':
            $delivery_type = isset($order_data['hasHomeDelivery']) && $order_data['hasHomeDelivery'] ? 'home delivery' : 'pickup';
            $notifications[] = [
                'title' => 'Order Shipped',
                'message' => "Your order #{$order_id} has been shipped. Get ready for {$delivery_type}!
                ",
                'type' => 'order_update'
            ];
            break;
            
        case 'delivered':
            $notifications[] = [
                'title' => 'Order Delivered',
                'message' => "Your order #{$order_id} has been delivered. Enjoy your meal!",
                'type' => 'order_update'
            ];
            break;
            
        case 'cancelled':
            $notifications[] = [
                'title' => 'Order Cancelled',
                'message' => "Your order #{$order_id} has been cancelled.",
                'type' => 'order_update'
            ];
            break;
            
        default:
            $notifications[] = [
                'title' => 'Order Status Updated',
                'message' => "Your order #{$order_id} status has been updated to: " . ucfirst($status),
                'type' => 'order_update'
            ];
            break;
    }
    
    // Send all notifications
    $success = true;
    foreach ($notifications as $notification) {
        if (!createNotification($user_id, $notification['title'], $notification['message'], $notification['type'])) {
            $success = false;
        }
    }
    
    return $success;
}

/**
 * Create payment-related notifications
 * @param int $user_id The user ID
 * @param int $order_id The order ID
 * @param string $status Payment status
 * @return bool Success status
 */
function createPaymentNotification($user_id, $order_id, $status) {
    switch ($status) {
        case 'successful':
            return createNotification(
                $user_id,
                'Payment Successful',
                "Your payment for order #{$order_id} was successful. Your order is being processed.",
                'order_update'
            );
            
        case 'failed':
            return createNotification(
                $user_id,
                'Payment Failed',
                "Your payment for order #{$order_id} failed. Please try again or contact support.",
                'order_update'
            );
            
        case 'pending':
            return createNotification(
                $user_id,
                'Payment Pending',
                "Your payment for order #{$order_id} is pending. We'll notify you when it's confirmed.",
                'order_update'
            );
            
        default:
            return createNotification(
                $user_id,
                'Payment Status Updated',
                "Payment status for order #{$order_id} has been updated.",
                'order_update'
            );
    }
}
?>
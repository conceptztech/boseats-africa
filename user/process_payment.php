<?php
include_once '../includes/db_connection.php';
include_once '../includes/protect_user.php';

if (isset($_GET['order_id'])) {
    $order_id = $_GET['order_id'];
    
    // Verify order belongs to user
    $order_stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $order_stmt->execute([$order_id, $user_id]);
    $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        // Here you would integrate with your actual payment gateway
        // For demonstration, we'll simulate payment success
        
        // Generate payment reference
        $payment_reference = 'PAY_' . time() . '_' . $order_id;
        
        // Update order payment status
        $update_stmt = $pdo->prepare("UPDATE orders SET payment_status = 'completed', payment_reference = ? WHERE id = ?");
        $update_stmt->execute([$payment_reference, $order_id]);
        
        $_SESSION['payment_success'] = "Payment completed successfully for Order #" . $order_id;
        header("Location: payments.php");
        exit();
    } else {
        $_SESSION['payment_error'] = "Order not found!";
        header("Location: payments.php");
        exit();
    }
} else {
    $_SESSION['payment_error'] = "Invalid request!";
    header("Location: payments.php");
    exit();
}
?>
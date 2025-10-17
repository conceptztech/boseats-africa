<?php
session_start();
require_once '../includes/db_connection.php';
require_once '../includes/merchant_auth.php';

header('Content-Type: application/json');

// Check if user is merchant and logged in
if (!is_merchant_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

$merchant_id = $_SESSION['merchant_id'];
$user_id = $_GET['user_id'] ?? '';

if (empty($user_id)) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

try {
    // Get customer details
    $stmt = $pdo->prepare("
        SELECT 
            u.*,
            CONCAT(u.country, ', ', u.state) as location,
            u.created_at as joined_date
        FROM users u 
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $customer = $stmt->fetch();
    
    if (!$customer) {
        echo json_encode(['success' => false, 'message' => 'Customer not found']);
        exit;
    }
    
    // Check if merchant_id column exists
    $column_check = $pdo->query("SHOW COLUMNS FROM orders LIKE 'merchant_id'")->fetch();
    
    // Get customer order statistics for this merchant
    if ($column_check) {
        $stats_stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_orders,
                COALESCE(SUM(total_amount), 0) as total_spent,
                COALESCE(AVG(total_amount), 0) as average_order,
                MAX(created_at) as last_order,
                MIN(created_at) as first_order
            FROM orders 
            WHERE user_id = ? AND merchant_id = ?
        ");
        $stats_stmt->execute([$user_id, $merchant_id]);
    } else {
        $stats_stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_orders,
                COALESCE(SUM(total_amount), 0) as total_spent,
                COALESCE(AVG(total_amount), 0) as average_order,
                MAX(created_at) as last_order,
                MIN(created_at) as first_order
            FROM orders 
            WHERE user_id = ? AND JSON_EXTRACT(order_data, '$.merchant_id') = ?
        ");
        $stats_stmt->execute([$user_id, $merchant_id]);
    }
    
    $stats = $stats_stmt->fetch();
    
    // Ensure all stats have values
    $stats = array_map(function($value) {
        return $value === null ? 0 : $value;
    }, $stats);
    
    // Merge customer data with stats
    $customer = array_merge($customer, $stats);
    
    echo json_encode([
        'success' => true,
        'customer' => $customer
    ]);
    
} catch (Exception $e) {
    error_log("Error getting customer details: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error loading customer details: ' . $e->getMessage()]);
}
?>
<?php
include_once '../includes/db_connection.php';

echo "Starting order merchant_id update using companyName...\n";

$orders_stmt = $pdo->query("SELECT id, order_data FROM orders WHERE merchant_id IS NULL");
$orders = $orders_stmt->fetchAll();

$updated_count = 0;

foreach ($orders as $order) {
    $order_data = json_decode($order['order_data'], true);
    $merchant_id = null;
    $companyName = null;
    
    // Try to extract companyName from order_data
    if (isset($order_data['items']) && is_array($order_data['items']) && !empty($order_data['items'])) {
        $firstItem = $order_data['items'][0];
        if (isset($firstItem['companyName'])) {
            $companyName = $firstItem['companyName'];
            
            // Find merchant by company name
            $stmt = $pdo->prepare("SELECT id FROM merchants WHERE company_name = ? LIMIT 1");
            $stmt->execute([$companyName]);
            $merchant = $stmt->fetch();
            
            if ($merchant) {
                $merchant_id = $merchant['id'];
            }
        }
    }
    
    // Alternative: Try direct companyName in order_data
    if (!$merchant_id && isset($order_data['companyName'])) {
        $companyName = $order_data['companyName'];
        
        $stmt = $pdo->prepare("SELECT id FROM merchants WHERE company_name = ? LIMIT 1");
        $stmt->execute([$companyName]);
        $merchant = $stmt->fetch();
        
        if ($merchant) {
            $merchant_id = $merchant['id'];
        }
    }
    
    // Alternative: Try to get from food_items table using product ID
    if (!$merchant_id && isset($order_data['items']) && is_array($order_data['items']) && !empty($order_data['items'])) {
        $firstItem = $order_data['items'][0];
        if (isset($firstItem['id'])) {
            $product_id = $firstItem['id'];
            
            $stmt = $pdo->prepare("SELECT merchant_id FROM food_items WHERE id = ? LIMIT 1");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            
            if ($product && $product['merchant_id']) {
                $merchant_id = $product['merchant_id'];
            }
        }
    }
    
    if ($merchant_id) {
        // Update the order with merchant_id
        $update_stmt = $pdo->prepare("UPDATE orders SET merchant_id = ? WHERE id = ?");
        $update_stmt->execute([$merchant_id, $order['id']]);
        $updated_count++;
        
        echo "Updated order ID: " . $order['id'] . " with merchant_id: " . $merchant_id . " (Company: " . ($companyName ?? 'Unknown') . ")\n";
    } else {
        echo "Could not find merchant_id for order ID: " . $order['id'] . " (Company: " . ($companyName ?? 'Not found') . ")\n";
    }
}

echo "Completed! Updated " . $updated_count . " orders with merchant_id.\n";

// Show summary
$summary_stmt = $pdo->query("SELECT COUNT(*) as total, COUNT(merchant_id) as with_merchant FROM orders");
$summary = $summary_stmt->fetch();
echo "Summary - Total orders: " . $summary['total'] . ", With merchant_id: " . $summary['with_merchant'] . "\n";
?>
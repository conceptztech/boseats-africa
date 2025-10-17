<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_merchant_logged_in() {
    return isset($_SESSION['merchant_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'merchant';
}

function get_merchant_stats($merchant_id) {
    global $pdo;
    
    // Check if merchant_id column exists in orders table
    $column_check = $pdo->query("SHOW COLUMNS FROM orders LIKE 'merchant_id'")->fetch();
    
    if ($column_check) {
        // Use merchant_id column directly
        // Total Revenue
        $revenue_stmt = $pdo->prepare("
            SELECT COALESCE(SUM(total_amount), 0) as total_revenue 
            FROM orders 
            WHERE payment_status = 'completed' 
            AND merchant_id = ?
        ");
        $revenue_stmt->execute([$merchant_id]);
        $total_revenue = $revenue_stmt->fetchColumn();
        
        // Total Orders
        $orders_stmt = $pdo->prepare("
            SELECT COUNT(*) as total_orders 
            FROM orders 
            WHERE merchant_id = ?
        ");
        $orders_stmt->execute([$merchant_id]);
        $total_orders = $orders_stmt->fetchColumn();
        
        // Pending Orders
        $pending_stmt = $pdo->prepare("
            SELECT COUNT(*) as pending_orders 
            FROM orders 
            WHERE order_status = 'pending' 
            AND merchant_id = ?
        ");
        $pending_stmt->execute([$merchant_id]);
        $pending_orders = $pending_stmt->fetchColumn();
        
        // Recent Orders
        $recent_orders_stmt = $pdo->prepare("
            SELECT id, total_amount, order_status, created_at 
            FROM orders 
            WHERE merchant_id = ?
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $recent_orders_stmt->execute([$merchant_id]);
        $recent_orders = $recent_orders_stmt->fetchAll();
        
    } else {
        // Fallback to JSON extraction
        // Total Revenue
        $revenue_stmt = $pdo->prepare("
            SELECT COALESCE(SUM(total_amount), 0) as total_revenue 
            FROM orders 
            WHERE payment_status = 'completed' 
            AND JSON_EXTRACT(order_data, '$.merchant_id') = ?
        ");
        $revenue_stmt->execute([$merchant_id]);
        $total_revenue = $revenue_stmt->fetchColumn();
        
        // Total Orders
        $orders_stmt = $pdo->prepare("
            SELECT COUNT(*) as total_orders 
            FROM orders 
            WHERE JSON_EXTRACT(order_data, '$.merchant_id') = ?
        ");
        $orders_stmt->execute([$merchant_id]);
        $total_orders = $orders_stmt->fetchColumn();
        
        // Pending Orders
        $pending_stmt = $pdo->prepare("
            SELECT COUNT(*) as pending_orders 
            FROM orders 
            WHERE order_status = 'pending' 
            AND JSON_EXTRACT(order_data, '$.merchant_id') = ?
        ");
        $pending_stmt->execute([$merchant_id]);
        $pending_orders = $pending_stmt->fetchColumn();
        
        // Recent Orders
        $recent_orders_stmt = $pdo->prepare("
            SELECT id, total_amount, order_status, created_at 
            FROM orders 
            WHERE JSON_EXTRACT(order_data, '$.merchant_id') = ?
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $recent_orders_stmt->execute([$merchant_id]);
        $recent_orders = $recent_orders_stmt->fetchAll();
    }
    
    // Active Products (this doesn't change)
    $products_stmt = $pdo->prepare("
        SELECT COUNT(*) as active_products 
        FROM food_items 
        WHERE merchant_id = ? AND active = 1
    ");
    $products_stmt->execute([$merchant_id]);
    $active_products = $products_stmt->fetchColumn();
    
    return [
        'total_revenue' => $total_revenue,
        'total_orders' => $total_orders,
        'active_products' => $active_products,
        'pending_orders' => $pending_orders,
        'recent_orders' => $recent_orders
    ];
}

function get_revenue_chart_data($merchant_id) {
    global $pdo;
    
    // Check if merchant_id column exists
    $column_check = $pdo->query("SHOW COLUMNS FROM orders LIKE 'merchant_id'")->fetch();
    
    if ($column_check) {
        // Use merchant_id column directly
        $revenue_stmt = $pdo->prepare("
            SELECT 
                DATE(created_at) as order_date,
                COALESCE(SUM(total_amount), 0) as daily_revenue
            FROM orders 
            WHERE payment_status = 'completed' 
            AND merchant_id = ?
            AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY order_date
        ");
    } else {
        // Fallback to JSON extraction
        $revenue_stmt = $pdo->prepare("
            SELECT 
                DATE(created_at) as order_date,
                COALESCE(SUM(total_amount), 0) as daily_revenue
            FROM orders 
            WHERE payment_status = 'completed' 
            AND JSON_EXTRACT(order_data, '$.merchant_id') = ?
            AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY order_date
        ");
    }
    
    $revenue_stmt->execute([$merchant_id]);
    $revenue_data = $revenue_stmt->fetchAll();
    
    // Create an array for the last 7 days
    $labels = [];
    $data = [];
    
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $labels[] = date('M j', strtotime($date));
        
        // Find revenue for this date
        $revenue = 0;
        foreach ($revenue_data as $row) {
            if ($row['order_date'] == $date) {
                $revenue = $row['daily_revenue'];
                break;
            }
        }
        $data[] = $revenue;
    }
    
    return [
        'labels' => $labels,
        'data' => $data
    ];
}

function get_item_label($business_type) {
    $labels = [
        'food' => 'food item',
        'event' => 'event',
        'hotel' => 'room',
        'car hiring' => 'vehicle',
        'flight' => 'flight',
        'restaurant' => 'menu item',
        'cafe' => 'beverage'
    ];
    return $labels[$business_type] ?? 'product';
}

function get_order_label($business_type) {
    $labels = [
        'food' => 'orders',
        'event' => 'bookings', 
        'hotel' => 'reservations',
        'car hiring' => 'rentals',
        'flight' => 'bookings',
        'restaurant' => 'orders',
        'cafe' => 'orders'
    ];
    return $labels[$business_type] ?? 'orders';
}

function get_merchant_products($merchant_id, $search = '', $category = '', $status = '') {
    global $pdo;
    
    $sql = "SELECT * FROM food_items WHERE merchant_id = ?";
    $params = [$merchant_id];
    
    if (!empty($search)) {
        $sql .= " AND (name LIKE ? OR description LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    if (!empty($category)) {
        $sql .= " AND category = ?";
        $params[] = $category;
    }
    
    if ($status === 'active') {
        $sql .= " AND active = 1";
    } elseif ($status === 'inactive') {
        $sql .= " AND active = 0";
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_product_categories($merchant_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT DISTINCT category 
        FROM food_items 
        WHERE merchant_id = ? AND category IS NOT NULL AND category != ''
        ORDER BY category
    ");
    $stmt->execute([$merchant_id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function get_merchant_orders($merchant_id, $status = '', $date = '', $search = '') {
    global $pdo;
    
    // Check if merchant_id column exists
    $column_check = $pdo->query("SHOW COLUMNS FROM orders LIKE 'merchant_id'")->fetch();
    
    if ($column_check) {
        // Use merchant_id column directly (recommended)
        $sql = "SELECT o.*, u.first_name, u.last_name, u.email, u.phone 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                WHERE o.merchant_id = ?";
        $params = [$merchant_id];
    } else {
        // Fallback to JSON extraction
        $sql = "SELECT o.*, u.first_name, u.last_name, u.email, u.phone 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                WHERE JSON_EXTRACT(o.order_data, '$.merchant_id') = ?";
        $params = [$merchant_id];
    }
    
    // Add filters
    if (!empty($status)) {
        $sql .= " AND o.order_status = ?";
        $params[] = $status;
    }
    
    if (!empty($date)) {
        $sql .= " AND DATE(o.created_at) = ?";
        $params[] = $date;
    }
    
    if (!empty($search)) {
        $sql .= " AND (o.id LIKE ? OR o.payment_reference LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $sql .= " ORDER BY o.created_at DESC";
    
    error_log("Merchant Orders Query: " . $sql);
    error_log("Query Parameters: " . print_r($params, true));
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        
        error_log("Found " . count($results) . " orders for merchant ID: " . $merchant_id);
        
        return $results;
        
    } catch (Exception $e) {
        error_log("Error in get_merchant_orders: " . $e->getMessage());
        return [];
    }
}

function get_customer_info($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT first_name, last_name, email, phone FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $customer = $stmt->fetch();
    
    if (!$customer) {
        return [
            'first_name' => 'Unknown', 
            'last_name' => 'Customer', 
            'email' => 'N/A', 
            'phone' => 'N/A'
        ];
    }
    
    return $customer;
}

function update_order_status($merchant_id, $order_id, $new_status) {
    global $pdo;
    
    try {
        // Check if merchant_id column exists
        $column_check = $pdo->query("SHOW COLUMNS FROM orders LIKE 'merchant_id'")->fetch();
        
        if ($column_check) {
            // Verify order belongs to merchant using merchant_id column
            $verify_stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND merchant_id = ?");
            $verify_stmt->execute([$order_id, $merchant_id]);
        } else {
            // Verify order belongs to merchant using JSON extraction
            $verify_stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND JSON_EXTRACT(order_data, '$.merchant_id') = ?");
            $verify_stmt->execute([$order_id, $merchant_id]);
        }
        
        if (!$verify_stmt->fetch()) {
            $_SESSION['error_message'] = "Order not found or access denied";
            header("Location: orders.php");
            exit;
        }
        
        $stmt = $pdo->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);
        
        $_SESSION['success_message'] = "Order status updated to " . ucfirst($new_status) . " successfully!";
        header("Location: orders.php");
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error updating order status: " . $e->getMessage();
        header("Location: orders.php");
        exit;
    }
}

function add_product($merchant_id, $data, $files) {
    global $pdo;
    
    try {
        $image_url = upload_product_image($files['image']);
        
        $sql = "INSERT INTO food_items (
            name, description, location, price, image_url, 
            category, company, active, delivery_options, merchant_id, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['description'],
            $data['location'],
            $data['price'],
            $image_url,
            $data['category'],
            get_merchant_company_name($merchant_id),
            $data['active'] ?? 0,
            $data['delivery_options'],
            $merchant_id
        ]);
        
        $_SESSION['success_message'] = "Product added successfully!";
        header("Location: products.php");
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error adding product: " . $e->getMessage();
        header("Location: products.php");
        exit;
    }
}

// Your update_product function with delivery fee added
function update_product($merchant_id, $data, $files) {
    global $pdo;
    
    try {
        // Verify product belongs to merchant
        $verify_stmt = $pdo->prepare("SELECT id FROM food_items WHERE id = ? AND merchant_id = ?");
        $verify_stmt->execute([$data['product_id'], $merchant_id]);
        
        if (!$verify_stmt->fetch()) {
            $_SESSION['error_message'] = "Product not found or access denied";
            header("Location: products.php");
            exit;
        }
        
        $image_url = null;
        if (!empty($files['image']['name'])) {
            $image_url = upload_product_image($files['image']);
        }
        
        // Handle delivery fee
        $delivery_fee = isset($data['delivery_fee']) ? floatval($data['delivery_fee']) : 0.00;
        
        $sql = "UPDATE food_items SET 
                name = ?, description = ?, location = ?, price = ?, 
                category = ?, active = ?, delivery_options = ?, delivery_fee = ?, updated_at = NOW()";
        
        $params = [
            $data['name'],
            $data['description'],
            $data['location'],
            $data['price'],
            $data['category'],
            $data['active'] ?? 0,
            $data['delivery_options'],
            $delivery_fee
        ];
        
        if ($image_url) {
            $sql .= ", image_url = ?";
            $params[] = $image_url;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $data['product_id'];
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $_SESSION['success_message'] = "Product updated successfully!";
        header("Location: products.php");
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error updating product: " . $e->getMessage();
        header("Location: products.php");
        exit;
    }
}

function delete_product($merchant_id, $product_id) {
    global $pdo;
    
    try {
        // Verify product belongs to merchant
        $verify_stmt = $pdo->prepare("SELECT id FROM food_items WHERE id = ? AND merchant_id = ?");
        $verify_stmt->execute([$product_id, $merchant_id]);
        
        if (!$verify_stmt->fetch()) {
            $_SESSION['error_message'] = "Product not found or access denied";
            header("Location: products.php");
            exit;
        }
        
        $stmt = $pdo->prepare("DELETE FROM food_items WHERE id = ?");
        $stmt->execute([$product_id]);
        
        $_SESSION['success_message'] = "Product deleted successfully!";
        header("Location: products.php");
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error deleting product: " . $e->getMessage();
        header("Location: products.php");
        exit;
    }
}

function toggle_product_status($merchant_id, $product_id) {
    global $pdo;
    
    try {
        // Verify product belongs to merchant
        $verify_stmt = $pdo->prepare("SELECT id, active FROM food_items WHERE id = ? AND merchant_id = ?");
        $verify_stmt->execute([$product_id, $merchant_id]);
        $product = $verify_stmt->fetch();
        
        if (!$product) {
            $_SESSION['error_message'] = "Product not found or access denied";
            header("Location: products.php");
            exit;
        }
        
        $new_status = $product['active'] ? 0 : 1;
        $status_text = $new_status ? 'activated' : 'deactivated';
        
        $stmt = $pdo->prepare("UPDATE food_items SET active = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$new_status, $product_id]);
        
        $_SESSION['success_message'] = "Product {$status_text} successfully!";
        header("Location: products.php");
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error updating product status: " . $e->getMessage();
        header("Location: products.php");
        exit;
    }
}

function upload_product_image($file) {
    if (empty($file['name'])) {
        return null;
    }
    
    $upload_dir = '../uploads/products/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($file_extension, $allowed_extensions)) {
        throw new Exception("Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.");
    }
    
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
        throw new Exception("File size too large. Maximum size is 5MB.");
    }
    
    $filename = uniqid() . '_' . time() . '.' . $file_extension;
    $filepath = $upload_dir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception("Failed to upload file.");
    }
    
    return 'uploads/products/' . $filename;
}

function get_merchant_company_name($merchant_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT company_name FROM merchants WHERE id = ?");
    $stmt->execute([$merchant_id]);
    return $stmt->fetchColumn();
}

function time_ago($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' mins ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 2592000) return floor($diff / 86400) . ' days ago';
    return date('M j, Y', $time);
}
?>


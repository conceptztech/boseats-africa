<?php
session_start();
include_once '../includes/db_connection.php';

// Check if user is logged in and is a merchant
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] != 'merchant' && $_SESSION['user_type'] != 'admin')) {
    header('Location: ../login.php');
    exit();
}

$merchant_id = $_SESSION['user_id'];

// Get merchant details
$merchant_sql = "SELECT * FROM merchants WHERE id = ?";
$merchant_stmt = $pdo->prepare($merchant_sql);
$merchant_stmt->execute([$merchant_id]);
$merchant = $merchant_stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? '';
    $location = $_POST['location'] ?? '';
    $category = $_POST['category'] ?? '';
    $delivery_options = $_POST['delivery_options'] ?? '';
    
    // Handle image upload
    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = '../food/images/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_name = time() . '_' . basename($_FILES['image']['name']);
        $upload_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_file)) {
            $image_url = 'food/images/' . $file_name;
        }
    }
    
    // Insert food item - using existing columns
    try {
        // First check if merchant_id column exists
        $check_column_sql = "SHOW COLUMNS FROM food_items LIKE 'merchant_id'";
        $check_stmt = $pdo->query($check_column_sql);
        $merchant_id_exists = $check_stmt->fetch();
        
        if ($merchant_id_exists) {
            $insert_sql = "INSERT INTO food_items (name, description, price, location, image_url, category, delivery_options, company, company_logo, active, merchant_id) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)";
            $insert_stmt = $pdo->prepare($insert_sql);
            $insert_stmt->execute([
                $name, 
                $description, 
                $price, 
                $location, 
                $image_url, 
                $category, 
                $delivery_options, 
                $merchant['company_name'],
                $merchant['picture_path'] ?? '',
                $merchant_id
            ]);
        } else {
            // Fallback without merchant_id
            $insert_sql = "INSERT INTO food_items (name, description, price, location, image_url, category, delivery_options, company, company_logo, active) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
            $insert_stmt = $pdo->prepare($insert_sql);
            $insert_stmt->execute([
                $name, 
                $description, 
                $price, 
                $location, 
                $image_url, 
                $category, 
                $delivery_options, 
                $merchant['company_name'],
                $merchant['picture_path'] ?? ''
            ]);
        }
        $success = "Food item added successfully!";
    } catch (PDOException $e) {
        $error = "Error adding food item: " . $e->getMessage();
        error_log("Food item insert error: " . $e->getMessage());
    }
}

// Get merchant's food items - FIXED: Remove created_at from order by
try {
    // Check if merchant_id column exists
    $check_column_sql = "SHOW COLUMNS FROM food_items LIKE 'merchant_id'";
    $check_stmt = $pdo->query($check_column_sql);
    $merchant_id_exists = $check_stmt->fetch();
    
    if ($merchant_id_exists) {
        $food_sql = "SELECT * FROM food_items WHERE merchant_id = ? ORDER BY id DESC";
        $food_stmt = $pdo->prepare($food_sql);
        $food_stmt->execute([$merchant_id]);
    } else {
        // Fallback: get by company name
        $food_sql = "SELECT * FROM food_items WHERE company = ? ORDER BY id DESC";
        $food_stmt = $pdo->prepare($food_sql);
        $food_stmt->execute([$merchant['company_name']]);
    }
    $food_items = $food_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching food items: " . $e->getMessage());
    $food_items = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merchant Dashboard - BosEats Africa</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f8f9fa;
            color: #333;
        }
        
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .header h1 {
            color: #28a745;
            margin-bottom: 10px;
        }
        
        .merchant-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .merchant-logo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #28a745;
        }
        
        .dashboard-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .dashboard-content {
                grid-template-columns: 1fr;
            }
        }
        
        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .card h2 {
            color: #28a745;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
        }
        
        .form-control:focus {
            border-color: #28a745;
            outline: none;
        }
        
        .btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: #1c3a23;
            transform: translateY(-2px);
        }
        
        .btn-block {
            width: 100%;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .food-items {
            max-height: 500px;
            overflow-y: auto;
        }
        
        .food-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 10px;
            background: #fafafa;
        }
        
        .food-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .food-details {
            flex: 1;
        }
        
        .food-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .food-price {
            color: #28a745;
            font-weight: 600;
        }
        
        .food-meta {
            font-size: 12px;
            color: #666;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ddd;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 12px;
            opacity: 0.9;
        }
        
        .nav-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .nav-tab {
            padding: 10px 20px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .nav-tab.active {
            background: #28a745;
            color: white;
            border-color: #28a745;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="header">
            <h1>Merchant Dashboard</h1>
            <div class="merchant-info">
                <?php if (!empty($merchant['picture_path'])): ?>
                    <img src="<?php echo $merchant['picture_path']; ?>" alt="Company Logo" class="merchant-logo">
                <?php else: ?>
                    <div class="merchant-logo" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-store" style="font-size: 24px; color: #666;"></i>
                    </div>
                <?php endif; ?>
                <div>
                    <h3><?php echo htmlspecialchars($merchant['company_name']); ?></h3>
                    <p><?php echo htmlspecialchars($merchant['company_address']); ?></p>
                    <p style="color: #666; font-size: 14px; margin-top: 5px;">
                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($merchant['phone'] ?? 'N/A'); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($food_items); ?></div>
                <div class="stat-label">Total Items</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($food_items, function($item) { return ($item['active'] ?? 1) == 1; })); ?></div>
                <div class="stat-label">Active Items</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">₦<?php echo number_format(array_sum(array_column($food_items, 'price'))); ?></div>
                <div class="stat-label">Total Value</div>
            </div>
        </div>
        
        <div class="dashboard-content">
            <div class="card">
                <h2>Add New Food Item</h2>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name">Food Name *</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3" placeholder="Describe your food item..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price (₦) *</label>
                        <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" required placeholder="0.00">
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location *</label>
                        <input type="text" id="location" name="location" class="form-control" required value="<?php echo htmlspecialchars($merchant['state'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category *</label>
                        <select id="category" name="category" class="form-control" required>
                            <option value="">Select Category</option>
                            <option value="local">Local Food</option>
                            <option value="continental">Continental</option>
                            <option value="fast_food">Fast Food</option>
                            <option value="drinks">Drinks</option>
                            <option value="desserts">Desserts</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="delivery_options">Delivery Options *</label>
                        <select id="delivery_options" name="delivery_options" class="form-control" required>
                            <option value="">Select Option</option>
                            <option value="Home Delivery">Home Delivery Only</option>
                            <option value="Pickup Only">Pickup Only</option>
                            <option value="Pickup & Home Delivery">Both Pickup & Home Delivery</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Food Image</label>
                        <input type="file" id="image" name="image" class="form-control" accept="image/*">
                        <small style="color: #666;">Recommended: Square image, max 2MB</small>
                    </div>
                    
                    <button type="submit" class="btn btn-block">
                        <i class="fas fa-plus"></i> Add Food Item
                    </button>
                </form>
            </div>
            
            <div class="card">
                <h2>Your Food Items (<?php echo count($food_items); ?>)</h2>
                
                <div class="food-items">
                    <?php if (empty($food_items)): ?>
                        <div class="empty-state">
                            <i class="fas fa-utensils"></i>
                            <h3>No Food Items Yet</h3>
                            <p>Add your first food item to get started!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($food_items as $item): ?>
                            <div class="food-item">
                                <?php if (!empty($item['image_url'])): ?>
                                    <img src="../<?php echo $item['image_url']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div style="width: 60px; height: 60px; background: #f0f0f0; border-radius: 8px; display: none; align-items: center; justify-content: center;">
                                        <i class="fas fa-utensils" style="color: #666;"></i>
                                    </div>
                                <?php else: ?>
                                    <div style="width: 60px; height: 60px; background: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-utensils" style="color: #666;"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="food-details">
                                    <div class="food-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="food-price">₦<?php echo number_format($item['price'], 2); ?></div>
                                    <div class="food-meta">
                                        <?php echo htmlspecialchars($item['category'] ?? 'Uncategorized'); ?> • 
                                        <?php echo htmlspecialchars($item['delivery_options'] ?? 'Not specified'); ?>
                                        <?php if (isset($item['location'])): ?>
                                            • <?php echo htmlspecialchars($item['location']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div style="color: <?php echo ($item['active'] ?? 1) ? '#28a745' : '#dc3545'; ?>;" title="<?php echo ($item['active'] ?? 1) ? 'Active' : 'Inactive'; ?>">
                                    <i class="fas fa-circle" style="font-size: 8px;"></i>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Simple form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const priceInput = document.getElementById('price');
            
            form.addEventListener('submit', function(e) {
                const price = parseFloat(priceInput.value);
                if (price <= 0) {
                    alert('Please enter a valid price greater than 0.');
                    e.preventDefault();
                    return;
                }
            });
            
            // Image preview (optional enhancement)
            const imageInput = document.getElementById('image');
            imageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    if (file.size > 2 * 1024 * 1024) {
                        alert('Image size must be less than 2MB.');
                        e.target.value = '';
                        return;
                    }
                }
            });
        });
    </script>
</body>
</html>
<?php
session_start();
include_once '../includes/db_connection.php';

// Check if reference is provided
$reference = $_GET['reference'] ?? '';

if (empty($reference)) {
    header('Location: food.php');
    exit;
}

// Fetch order details
$order = [];
if (!empty($reference)) {
    try {
        $sql = "SELECT * FROM orders WHERE reference = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$reference]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching order: " . $e->getMessage());
    }
}

include_once "../includes/e_header.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - BoseatsAfrica</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        
        .success-container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .success-icon {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .success-title {
            color: #28a745;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .success-message {
            color: #666;
            font-size: 1.2em;
            margin-bottom: 30px;
        }
        
        .order-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: left;
        }
        
        .order-details h3 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detail-label {
            font-weight: 600;
            color: #555;
        }
        
        .detail-value {
            color: #333;
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #28a745;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        @media (max-width: 768px) {
            .success-container {
                margin: 20px;
                padding: 20px;
            }
            
            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include_once "../includes/e_header.php"; ?>
    
    <div class="success-container">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        
        <h1 class="success-title">Payment Successful!</h1>
        <p class="success-message">Thank you for your order. Your payment has been processed successfully.</p>
        
        <?php if (!empty($order)): ?>
            <div class="order-details">
                <h3>Order Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Reference Number:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($order['reference']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Total Amount:</span>
                    <span class="detail-value">₦<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Delivery Location:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($order['delivery_location']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value" style="color: #28a745; font-weight: 600;">
                        <?php echo htmlspecialchars(ucfirst($order['status'])); ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Order Date:</span>
                    <span class="detail-value"><?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></span>
                </div>
            </div>
        <?php else: ?>
            <div class="order-details">
                <p>Reference: <strong><?php echo htmlspecialchars($reference); ?></strong></p>
                <p>We're processing your order details. You'll receive a confirmation email shortly.</p>
            </div>
        <?php endif; ?>
        
        <div class="btn-group">
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-utensils"></i> Continue Shopping
            </a>
            <a href="../user/my_orders.php" class="btn btn-secondary">
                <i class="fas fa-history"></i> View Order History
            </a>
        </div>
        
        <p style="margin-top: 20px; color: #666; font-size: 0.9em;">
            If you have any questions, please contact our support team.
        </p>
    </div>
    
    <?php include_once "../includes/footer.php"; ?>
</body>
</html>
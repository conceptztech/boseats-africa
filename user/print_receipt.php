<?php
include_once '../includes/db_connection.php';
include_once '../includes/protect_user.php';

if (isset($_GET['order_id'])) {
    $order_id = $_GET['order_id'];
    
    // Verify order belongs to user and payment is completed
    $order_stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND payment_status = 'completed'");
    $order_stmt->execute([$order_id, $user_id]);
    $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        die("Receipt not available for this order!");
    }
    
    // Fetch user data for receipt
    $user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Decode order data
    $order_data = json_decode($order['order_data'], true);
    $order_items = [];
    
    if (is_array($order_data)) {
        if (isset($order_data[0]) && is_array($order_data[0])) {
            $order_items = $order_data;
        } elseif (isset($order_data['items']) && is_array($order_data['items'])) {
            $order_items = $order_data['items'];
        }
    }
} else {
    die("Invalid order ID!");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - Order #<?php echo $order_id; ?> - BoseaAfrica</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        .receipt-container {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            box-shadow: 0 0 30px rgba(0,0,0,0.1);
            border-radius: 15px;
            overflow: hidden;
        }

        .receipt-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .logo-container {
            margin-bottom: 20px;
        }

        .logo {
            max-width: 200px;
            height: auto;
            filter: brightness(0) invert(1);
        }

        .receipt-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }

        .receipt-subtitle {
            font-size: 16px;
            opacity: 0.9;
            font-weight: 400;
        }

        .receipt-body {
            padding: 40px;
        }

        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #28a745;
        }

        .info-group {
            margin-bottom: 15px;
        }

        .info-label {
            font-size: 12px;
            text-transform: uppercase;
            color: #6c757d;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .items-table th {
            background: #28a745;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 12px;
        }

        .items-table td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .items-table tr:hover {
            background: #f8f9fa;
        }

        .item-name {
            font-weight: 600;
            color: #333;
        }

        .item-quantity {
            color: #6c757d;
            text-align: center;
        }

        .item-price {
            text-align: right;
            font-weight: 600;
        }

        .total-section {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 25px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }

        .total-row:last-child {
            border-bottom: none;
            font-size: 20px;
            font-weight: 700;
            color: #28a745;
        }

        .total-label {
            font-weight: 600;
            color: #333;
        }

        .total-value {
            font-weight: 600;
            color: #333;
        }

        .receipt-footer {
            background: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }

        .thank-you {
            font-size: 18px;
            font-weight: 600;
            color: #28a745;
            margin-bottom: 15px;
        }

        .contact-info {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .support-email {
            color: #28a745;
            text-decoration: none;
            font-weight: 600;
        }

        .print-button {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
        }

        .print-button:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px;
            color: rgba(40, 167, 69, 0.03);
            font-weight: 900;
            z-index: -1;
            pointer-events: none;
            white-space: nowrap;
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
                margin: 0;
                padding: 0;
            }

            .receipt-container {
                box-shadow: none;
                border-radius: 0;
                margin: 0;
                max-width: none;
            }

            .print-button {
                display: none;
            }

            .watermark {
                display: block;
            }
        }

        @media screen and (max-width: 768px) {
            .receipt-container {
                margin: 10px;
                border-radius: 10px;
            }

            .receipt-body {
                padding: 20px;
            }

            .order-info {
                grid-template-columns: 1fr;
                padding: 20px;
            }

            .items-table {
                font-size: 14px;
            }

            .items-table th,
            .items-table td {
                padding: 10px;
            }

            .watermark {
                font-size: 80px;
            }
        }

        @media screen and (max-width: 480px) {
            .receipt-header {
                padding: 20px;
            }

            .logo {
                max-width: 150px;
            }

            .receipt-title {
                font-size: 24px;
            }

            .items-table {
                display: block;
                overflow-x: auto;
            }

            .watermark {
                font-size: 60px;
            }
        }
    </style>
</head>
<body>
    <div class="watermark">BoseaAfrica</div>
    
    <div class="receipt-container">
        <!-- Header -->
        <div class="receipt-header">
            <div class="logo-container">
                <img src="https://leo.it.tab.digital/s/ayC7xSHyzWfyKZA/preview" alt="BoseaAfrica Logo" class="logo">
            </div>
            <h1 class="receipt-title">PAYMENT RECEIPT</h1>
            <p class="receipt-subtitle">Thank you for your purchase</p>
        </div>

        <!-- Body -->
        <div class="receipt-body">
            <!-- Order Information -->
            <div class="order-info">
                <div>
                    <div class="info-group">
                        <div class="info-label">Order Number</div>
                        <div class="info-value">#<?php echo $order_id; ?></div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Order Date</div>
                        <div class="info-value"><?php echo date('F j, Y', strtotime($order['created_at'])); ?></div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Order Time</div>
                        <div class="info-value"><?php echo date('g:i A', strtotime($order['created_at'])); ?></div>
                    </div>
                </div>
                <div>
                    <div class="info-group">
                        <div class="info-label">Payment Reference</div>
                        <div class="info-value"><?php echo $order['payment_reference']; ?></div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Payment Status</div>
                        <div class="info-value" style="color: #28a745;"><?php echo ucfirst($order['payment_status']); ?></div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Order Status</div>
                        <div class="info-value"><?php echo ucfirst($order['order_status']); ?></div>
                    </div>
                </div>
                <div>
                    <div class="info-group">
                        <div class="info-label">Customer Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Delivery Location</div>
                        <div class="info-value"><?php echo htmlspecialchars($order['delivery_location']); ?></div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Delivery Address</div>
                        <div class="info-value"><?php echo htmlspecialchars($order['delivery_address']); ?></div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <h3 style="margin-bottom: 20px; color: #333; font-size: 18px;">Order Items</h3>
            
            <?php if (!empty($order_items)): ?>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th style="text-align: center;">Quantity</th>
                            <th style="text-align: right;">Price</th>
                            <th style="text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $subtotal = 0;
                        foreach ($order_items as $item): 
                            // Skip empty items
                            if (empty($item) || (!isset($item['name']) && !isset($item['product_name']))) {
                                continue;
                            }
                            
                            $item_name = $item['name'] ?? $item['product_name'] ?? 'Product';
                            $item_price = $item['price'] ?? $item['product_price'] ?? 0;
                            $item_quantity = $item['quantity'] ?? 1;
                            $item_total = $item_price * $item_quantity;
                            $subtotal += $item_total;
                        ?>
                            <tr>
                                <td class="item-name"><?php echo htmlspecialchars($item_name); ?></td>
                                <td class="item-quantity"><?php echo $item_quantity; ?></td>
                                <td class="item-price">₦<?php echo number_format($item_price, 2); ?></td>
                                <td class="item-price">₦<?php echo number_format($item_total, 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 10px; color: #6c757d;">
                    <i class="fas fa-info-circle" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                    <p>No detailed item information available for this order.</p>
                </div>
            <?php endif; ?>

            <!-- Total Section -->
            <div class="total-section">
                <div class="total-row">
                    <span class="total-label">Subtotal</span>
                    <span class="total-value">₦<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="total-row">
                    <span class="total-label">Delivery Fee</span>
                    <span class="total-value">₦<?php echo number_format($order['total_amount'] - $subtotal, 2); ?></span>
                </div>
                <div class="total-row">
                    <span class="total-label">Total Amount</span>
                    <span class="total-value">₦<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="receipt-footer">
            <div class="thank-you">Thank you for choosing BoseaAfrica!</div>
            <div class="contact-info">
                For any questions, contact us at 
                <a href="mailto:support@boseaafrica.com" class="support-email">support@boseaafrica.com</a>
            </div>
            <p style="color: #6c757d; font-size: 12px; margin-top: 10px;">
                This is an computer-generated receipt. No signature required.
            </p>
            
            <button class="print-button" onclick="window.print()">
                <i class="fas fa-print"></i> Print Receipt
            </button>
        </div>
    </div>

    <script>
        // Auto-print when page loads
        window.onload = function() {
            // Small delay to ensure everything is loaded
            setTimeout(function() {
                window.print();
            }, 500);
        };

        // Add Font Awesome for icons
        const faLink = document.createElement('link');
        faLink.rel = 'stylesheet';
        faLink.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css';
        document.head.appendChild(faLink);
    </script>
</body>
</html>
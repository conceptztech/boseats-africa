<?php
session_start();
include_once '../includes/db_connection.php';
include_once "../includes/e_header.php";

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$reference = $_GET['reference'] ?? 'Unknown';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Successful - BosEats Africa</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .success-container {
            text-align: center;
            padding: 50px 20px;
            max-width: 600px;
            margin: 0 auto;
        }
        .success-icon {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
        }
        .success-message {
            font-size: 24px;
            margin-bottom: 20px;
            color: #28a745;
        }
        .reference-number {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="success-message">
            Payment Successful!
        </div>
        <p>Your order has been placed successfully.</p>
        <div class="reference-number">
            Reference: <?php echo htmlspecialchars($reference); ?>
        </div>
        <p>You will receive a confirmation email shortly.</p>
        <button onclick="window.location.href='index.php'" style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Continue Shopping
        </button>
    </div>
</body>
</html>
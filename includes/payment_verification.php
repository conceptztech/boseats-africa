<?php
session_start();
include_once '../includes/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reference = $_POST['reference'] ?? '';
    $orderData = $_POST['order_data'] ?? '';

    if (empty($reference)) {
        echo json_encode(['success' => false, 'message' => 'Payment reference is required']);
        exit;
    }

    // Call the verifyPayment function to confirm the payment status
    $result = verifyPayment($pdo, $reference, $orderData);

    echo json_encode($result);
}

function verifyPayment($pdo, $reference, $orderData) {
    // Paystack secret key
    $secretKey = 'sk_test_01a2d9f450e83c27f77868f0d70b9b36d6e2e40c'; // Replace with your secret key

    // Set Paystack API URL for verification
    $url = "https://api.paystack.co/transaction/verify/$reference";

    // Initialize cURL
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $secretKey",
        "Content-Type: application/json"
    ]);

    // Execute cURL request
    $response = curl_exec($ch);

    // Check for errors
    if (curl_errno($ch)) {
        // Handle cURL error
        return [
            'success' => false,
            'message' => 'cURL Error: ' . curl_error($ch)
        ];
    }

    // Decode the response from Paystack
    $data = json_decode($response, true);

    // Check if Paystack returned a successful response
    if ($data['status'] === 'success') {
        // Transaction is successful, proceed with saving the order and updating the order status
        $paymentStatus = $data['data']['status'];  // 'success' or 'failed'
        $paymentAmount = $data['data']['amount'] / 100;  // Convert from kobo to Naira
        $transactionReference = $data['data']['reference'];

        // Save the order to the database (update the payment status, etc.)
        return saveOrder($pdo, $orderData, $paymentStatus, $transactionReference, $paymentAmount);
    }

    return [
        'success' => false,
        'message' => 'Payment verification failed: ' . $data['message']
    ];
}

function saveOrder($pdo, $orderData, $paymentStatus, $transactionReference, $paymentAmount) {
    // Prepare order data for the orders table
    $orderDataDecoded = json_decode($orderData, true);
    
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
        $orderDataDecoded['user_id'],
        json_encode($orderDataDecoded['items']),
        $paymentAmount,
        $paymentStatus,
        $transactionReference,
        $orderDataDecoded['location'],
        $orderDataDecoded['note'],
        'pending' // Default order status
    ]);

    // Optionally, clear the user's cart or perform other actions

    return [
        'success' => true,
        'message' => 'Payment verified and order saved successfully!'
    ];
}
?>

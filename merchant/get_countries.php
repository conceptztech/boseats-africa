<?php
// Clear any previous output
ob_clean();
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

require_once "../includes/db_connection.php";

$response = ['success' => false, 'data' => []];

try {
    // Get countries
    $stmt = $pdo->query("SELECT code, name, phone_code FROM countries ORDER BY name");
    $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get states
    $stmt = $pdo->query("SELECT country_code, name FROM states ORDER BY name");
    $states = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['success'] = true;
    $response['data'] = [
        'countries' => $countries,
        'states' => $states
    ];
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Ensure no other output
ob_clean();
echo json_encode($response);
exit;
?>
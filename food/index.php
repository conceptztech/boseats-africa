<?php
session_start();
include_once '../includes/db_connection.php';
include_once '../includes/mobile_footer.php';

// ==================== SINGLE ITEM VIEW ====================
$singleItemView = false;
$singleFoodItem = null;

// Check if we're viewing a single item
if (isset($_GET['item_id'])) {
    $itemId = intval($_GET['item_id']);
    
    try {
        $singleSql = "SELECT fi.*, m.company_name, m.company_address, m.state 
                     FROM food_items fi 
                     LEFT JOIN merchants m ON fi.merchant_id = m.id 
                     WHERE fi.id = ? AND fi.active = 1";
        
        $singleStmt = $pdo->prepare($singleSql);
        $singleStmt->execute([$itemId]);
        $singleFoodItem = $singleStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($singleFoodItem) {
            $singleItemView = true;
        } else {
            // Item not found or inactive, redirect to main page
            header("Location: index.php");
            exit();
        }
    } catch (PDOException $e) {
        error_log("Single item fetch error: " . $e->getMessage());
        header("Location: index.php");
        exit();
    }
}

// Store the current URL for redirect back after login
if (!isset($_SESSION['redirect_url'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $isLoggedIn = false;
    $userId = null;
    $userEmail = '';
} else {
    $isLoggedIn = true;
    $userId = $_SESSION['user_id'];

    // Fetch user email from database
    $userEmail = '';
    $userSql = "SELECT email FROM users WHERE id = ?";
    $userStmt = $pdo->prepare($userSql);
    try {
        $userStmt->execute([$userId]);
        $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
        if ($userData) {
            $userEmail = $userData['email'];
        }
    } catch (PDOException $e) {
        error_log("User email fetch error: " . $e->getMessage());
    }
}

// ==================== PAGINATION SETUP ====================
$itemsPerPage = 16;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// ==================== FIXED CURRENCY SYSTEM ====================
$apiKey = '602c87836436dcad1ce55ea7';

// Function to get user's country
function getUserCountry() {
    if (isset($_SESSION['user_country'])) {
        return $_SESSION['user_country'];
    }
    return 'NG';
}

// Function to get currency code based on country
function getCurrencyCode($countryCode) {
    $countryToCurrency = [
        'NG' => 'NGN', 'GH' => 'GHS', 'KE' => 'KES', 'ZA' => 'ZAR',
        'UG' => 'UGX', 'TZ' => 'TZS', 'RW' => 'RWF', 'CM' => 'XAF',
        'SN' => 'XOF', 'CI' => 'XOF', 'BF' => 'XOF', 'ML' => 'XOF'
    ];
    return $countryToCurrency[$countryCode] ?? 'NGN';
}

// Function to get currency symbol
function getCurrencySymbol($currencyCode) {
    $symbols = [
        'NGN' => '₦', 'GHS' => 'GH₵', 'KES' => 'KSh', 'ZAR' => 'R',
        'UGX' => 'USh', 'TZS' => 'TSh', 'RWF' => 'FRw', 'XAF' => 'FCFA',
        'XOF' => 'CFA', 'USD' => '$'
    ];
    return $symbols[$currencyCode] ?? $currencyCode;
}

// FIXED: Fetch exchange rates with NGN as base
function fetchExchangeRatesFromNGN($apiKey) {
    $apiUrl = "https://v6.exchangerate-api.com/v6/{$apiKey}/latest/NGN";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        if ($data && $data['result'] === 'success') {
            return $data['conversion_rates'];
        }
    }
    return null;
}

// Main currency logic
$userCountry = getUserCountry();
$userCurrency = getCurrencyCode($userCountry);
$currencySymbol = getCurrencySymbol($userCurrency);

// Default conversion rate (1 NGN = 1 NGN)
$conversionRate = 1.0;

// If user is not in Nigeria, convert from NGN to their currency
if ($userCurrency !== 'NGN') {
    $exchangeRates = fetchExchangeRatesFromNGN($apiKey);
    
    if ($exchangeRates && isset($exchangeRates[$userCurrency])) {
        $conversionRate = $exchangeRates[$userCurrency];
        
        // Store in session
        $_SESSION['exchange_rates'] = $exchangeRates;
        $_SESSION['user_currency'] = $userCurrency;
        $_SESSION['conversion_rate'] = $conversionRate;
        $_SESSION['rates_timestamp'] = time();
    } else {
        // Use cached rates if available
        if (isset($_SESSION['exchange_rates']) && 
            isset($_SESSION['rates_timestamp']) && 
            (time() - $_SESSION['rates_timestamp']) < 3600) {
            $conversionRate = $_SESSION['conversion_rate'] ?? 1.0;
        } else {
            // Fallback rates (approximate)
            $fallbackRates = [
                'GHS' => 0.012, 'KES' => 0.12, 'ZAR' => 0.012,
                'UGX' => 2.8, 'TZS' => 1.67, 'RWF' => 0.87, 'XAF' => 0.4
            ];
            $conversionRate = $fallbackRates[$userCurrency] ?? 1.0;
        }
    }
}

// FIXED: Function to convert price FROM Naira to user's currency
function convertPriceFromNGN($priceNGN, $conversionRate, $format = true) {
    $converted = $priceNGN * $conversionRate;
    if ($format) {
        return number_format($converted, 2);
    }
    return $converted;
}

// ==================== FOOD DATA FETCHING ====================
$foodData = [];
$totalItems = 0;
$totalPages = 0;

// Only fetch multiple items if we're not in single item view
if (!$singleItemView) {
    try {
        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total FROM food_items fi 
                    LEFT JOIN merchants m ON fi.merchant_id = m.id 
                    WHERE fi.active = 1";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute();
        $totalItems = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        $totalPages = ceil($totalItems / $itemsPerPage);

        // First try to get food items with merchant information
        $sql = "SELECT fi.*, m.company_name, m.company_address, m.state 
                FROM food_items fi 
                LEFT JOIN merchants m ON fi.merchant_id = m.id 
                WHERE fi.active = 1 
                ORDER BY fi.name
                LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $foodData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If no results or merchant_id doesn't exist, try alternative queries
        if (empty($foodData)) {
            // Try without merchant join
            $simpleSql = "SELECT * FROM food_items WHERE active = 1 ORDER BY name LIMIT :limit OFFSET :offset";
            $simpleStmt = $pdo->prepare($simpleSql);
            $simpleStmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
            $simpleStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $simpleStmt->execute();
            $foodData = $simpleStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Error fetching food items: " . $e->getMessage());
        // Last resort - simple query
        try {
            $simpleSql = "SELECT * FROM food_items WHERE active = 1 ORDER BY name LIMIT :limit OFFSET :offset";
            $simpleStmt = $pdo->prepare($simpleSql);
            $simpleStmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
            $simpleStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $simpleStmt->execute();
            $foodData = $simpleStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e2) {
            error_log("Backup query also failed: " . $e2->getMessage());
        }
    }
}

// FIX 4: FIXED Company Fetch - Get all active merchants
$companies = [];
try {
    $companySql = "SELECT id, company_name, picture_path as company_logo FROM merchants WHERE is_active = 1 AND is_approved = 1 ORDER BY company_name";
    $companyStmt = $pdo->prepare($companySql);
    $companyStmt->execute();
    $companies = $companyStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching companies: " . $e->getMessage());
}

// Fetch user's saved cart if logged in
$userCart = [];
if ($isLoggedIn) {
    $cartSql = "SELECT food_items FROM user_carts WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1";
    $cartStmt = $pdo->prepare($cartSql);
    try {
        $cartStmt->execute([$userId]);
        $cartData = $cartStmt->fetch(PDO::FETCH_ASSOC);
        if ($cartData && !empty($cartData['food_items'])) {
            $userCart = json_decode($cartData['food_items'], true);
        }
    } catch (PDOException $e) {
        error_log("Cart fetch error: " . $e->getMessage());
    }
}

// Fetch unique locations
$locations = [];
try {
    $locationSql = "SELECT DISTINCT location FROM food_items WHERE active = 1 AND location IS NOT NULL AND location != '' ORDER BY location";
    $locationStmt = $pdo->prepare($locationSql);
    $locationStmt->execute();
    $locations = $locationStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $locations = ['Abuja', 'Lagos', 'Lugbe'];
}

// Now include the header after all PHP processing
include_once "../includes/e_header.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $singleItemView ? htmlspecialchars($singleFoodItem['name']) . ' - ' : ''; ?>Food Page - BoseatsAfrica</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <link rel="stylesheet" href="../assets/css/index_food.css">
    <style>
    /* Fix overflow issues */
    body {
        overflow-x: hidden;
        width: 100%;
    }

    .container {
        max-width: 100%;
        overflow-x: hidden;
    }

    /* Ensure all elements stay within viewport */
    * {
        box-sizing: border-box;
    }

    /* Hide login prompt by default */
    .login-prompt {
        display: none !important;
    }

    /* Single Item View Styles */
    .single-item-view {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .back-button {
        margin-bottom: 20px;
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: #6c757d;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-back:hover {
        background: #5a6268;
        transform: translateY(-2px);
    }

    .single-food-card {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .single-item-image img {
        width: 100%;
        height: 400px;
        object-fit: cover;
        border-radius: 10px;
        cursor: zoom-in;
        transition: transform 0.3s ease;
    }

    .single-item-image img:hover {
        transform: scale(1.02);
    }

    .single-item-details h1 {
        font-size: 2.5em;
        margin-bottom: 20px;
        color: #333;
    }

    .item-company, .item-location, .item-delivery-options {
        margin-bottom: 15px;
        font-size: 1.1em;
    }

    .item-company i, .item-location i, .item-delivery-options i {
        margin-right: 10px;
        color: #28a745;
        width: 20px;
    }

    .item-description {
        margin: 25px 0;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #28a745;
    }

    .item-description p {
        font-size: 1.1em;
        line-height: 1.6;
        color: #555;
    }

    .item-price-section {
        margin: 25px 0;
        padding: 20px;
        background: linear-gradient(135deg, #28a745, #20c997);
        border-radius: 10px;
        color: white;
    }

    .item-price-section .price {
        font-size: 2.5em;
        font-weight: bold;
        margin-bottom: 10px;
        color: white; /* FIX: Force white color */
    }

    .delivery-fee {
        font-size: 1.1em;
        opacity: 0.9;
        color: white; /* FIX: Force white color */
    }

    .single-item-actions {
        display: flex;
        gap: 15px;
        align-items: center;
        margin-top: 30px;
    }

    .btn-add-to-cart {
        flex: 1;
        padding: 15px 25px;
        background: #ff6b6b;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 1.2em;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .btn-add-to-cart:hover {
        background: #ff5252;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
    }

    .single-item-actions .favorite {
        padding: 15px 25px;
        background: #6c757d;
        color: white;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .single-item-actions .favorite:hover {
        background: #5a6268;
        transform: translateY(-2px);
    }

    .single-item-actions .favorite.favorite-active {
        background: #dc3545;
    }

    /* Mobile Responsive for Single Item */
    @media (max-width: 768px) {
        .single-food-card {
            grid-template-columns: 1fr;
            gap: 20px;
            padding: 20px;
        }
        
        .single-item-details h1 {
            font-size: 2em;
        }
        
        .single-item-actions {
            flex-direction: column;
        }
        
        .btn-add-to-cart,
        .single-item-actions .favorite {
            width: 100%;
            justify-content: center;
        }
    }

    /* Your existing styles remain the same below */
    .pagination { display: flex; justify-content: center; align-items: center; margin: 20px 0; gap: 5px; }
    .pagination a, .pagination span { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #333; font-size: 14px; }
    .pagination a:hover { background-color: #28a745; color: white; border-color: #28a745; }
    .pagination .current { background-color: #28a745; color: white; border-color: #28a745; }
    .pagination .disabled { color: #999; cursor: not-allowed; }
    .pagination-info { text-align: center; color: #666; margin: 10px 0; font-size: 14px; }
    .locked-address { background-color: #f5f5f5 !important; color: #666 !important; cursor: not-allowed !important; }
    .address-field-container { position: relative; }
    .lock-icon { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #666; z-index: 2; }
    .cheapest-banner { position: absolute; top: 10px; left: 10px; background: linear-gradient(45deg, #ff6b6b, #ee5a24); color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8em; font-weight: bold; z-index: 2; }
    .payment-processing { display: none; text-align: center; padding: 20px; }
    .payment-processing .spinner { border: 4px solid #f3f3f3; border-top: 4px solid #28a745; border-radius: 50%; width: 40px; height: 40px; animation: spin 2s linear infinite; margin: 0 auto 15px; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .price { color: #28a745; font-weight: bold; font-size: 1.2em; margin: 10px 0; }
    .original-price { font-size: 0.8em; color: #666; margin-left: 5px; }
    .delivery-badge { display: inline-block; padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: 600; margin-left: 8px; }
    .delivery-available { background-color: #e8f5e8; color: #2e7d32; border: 1px solid #4caf50; }
    .delivery-not-available { background-color: #fff3e0; color: #ef6c00; border: 1px solid #ff9800; }
    .pickup-available { background-color: #e3f2fd; color: #1565c0; border: 1px solid #2196f3; }
    .disabled-field { background-color: #f5f5f5 !important; color: #666 !important; cursor: not-allowed !important; }
    .delivery-warning { background-color: #fff3e0; border: 1px solid #ff9800; color: #ef6c00; padding: 10px; border-radius: 5px; margin: 10px 0; font-size: 0.9em; }
    .cart-delivery-available { color: #2e7d32; font-size: 0.8em; font-weight: 600; }
    .cart-delivery-unavailable { color: #ef6c00; font-size: 0.8em; font-weight: 600; }
    .image-zoom-modal { display: none; position: fixed; z-index: 1002; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.9); animation: fadeIn 0.3s; }
    .zoom-modal-content { position: relative; margin: auto; display: block; width: 80%; max-width: 700px; top: 50%; transform: translateY(-50%); animation: zoomIn 0.3s; }
    .zoom-modal-content img { width: 100%; height: auto; border-radius: 10px; }
    .close-zoom { position: absolute; top: 15px; right: 35px; color: #f1f1f1; font-size: 40px; font-weight: bold; cursor: pointer; transition: 0.3s; }
    .card img { cursor: zoom-in; transition: transform 0.3s ease; }
    .card img:hover { transform: scale(1.02); }
    #cart-modal { display: none; position: fixed; top: 0; right: 0; width: 450px; height: 100vh; background-color: white; box-shadow: -4px 0 6px rgba(0, 0, 0, 0.1); padding: 20px; overflow-y: auto; z-index: 1000; border-left: 3px solid #28a745; border-radius: 10px 0 0 10px; }
    .cart-items { margin-top: 20px; }
    .cart-item { padding: 15px 0; border-bottom: 1px solid #ddd; }
    .cart-item-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
    .cart-item img { width: 60px; height: 60px; object-fit: cover; border-radius: 5px; }
    .cart-item-details { flex-grow: 1; margin-left: 10px; }
    .cart-item-name { font-size: 16px; font-weight: 500; color: #333; margin-bottom: 5px; }
    .cart-item-desc { font-size: 12px; color: #666; margin-bottom: 10px; }
    .cart-item-price { font-weight: bold; color: #28a745; }
    .quantity-controls { display: flex; align-items: center; gap: 10px; margin: 10px 0; }
    .quantity-btn { width: 30px; height: 30px; border: 1px solid #ddd; background: #f9f9f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 16px; font-weight: bold; }
    .quantity-input { width: 50px; text-align: center; font-size: 16px; padding: 5px; border-radius: 5px; border: 1px solid #ccc; }
    .delete-btn { background-color: #dc3545; color: white; border: none; padding: 5px 10px; cursor: pointer; font-size: 12px; border-radius: 3px; display: flex; align-items: center; gap: 5px; }
    .location-section { margin-top: 20px; }
    .location-section select { width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc; font-size: 16px; background-color: #f9f9f9; }
    .note-section { margin-top: 15px; }
    .note-section textarea { width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc; font-size: 14px; font-family: 'Poppins', sans-serif; resize: vertical; min-height: 80px; }
    .cart-total { margin-top: 20px; padding-top: 20px; border-top: 2px solid #eee; }
    .total-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 16px; }
    .total-label { font-weight: 600; color: #333; }
    .total-value { font-weight: bold; color: #28a745; }
    .total-final { font-size: 18px; margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd; }
    .cart-total button { padding: 15px; background-color: #28a745; color: white; border: none; width: 100%; font-size: 18px; cursor: pointer; border-radius: 5px; margin-top: 20px; font-weight: 600; }
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 999; }
    @media (max-width: 768px) { #cart-modal { width: 100%; max-width: 400px; } }
    @media (max-width: 480px) { #cart-modal { width: 100%; max-width: 100%; } }

    /* Search Section - Mobile Fixes */
    .search-section {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        margin-bottom: 20px;
    }

    #search-input {
        flex: 1;
        min-width: 200px;
        padding: 10px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 14px;
    }

    #search-btn {
        padding: 10px 20px;
        background: #28a745;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
    }

    .company-filter-wrapper {
        position: relative;
        flex: 1;
        min-width: 200px;
    }

    .company-search-input {
        width: 100%;
        padding: 10px 35px 10px 12px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 14px;
        background-color: #fff;
    }

    .company-search-container {
        position: relative;
        width: 500px;
        z-index: 1;
    }

    .company-search-icon {
        position: absolute;
        left: 270px;
        top: 50%;
        transform: translateY(-50%);
        color: #666;
        z-index: 2;
    }

    .company-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .location-search{
        width: 97%;
    }
    
    .company-option {
        display: flex;
        align-items: center;
        padding: 10px;
        cursor: pointer;
        border-bottom: 1px solid #f0f0f0;
    }

    .company-option:hover {
        background-color: #f8f9fa;
    }

    .company-logo {
        width: 25px;
        height: 25px;
        border-radius: 50%;
        object-fit: cover;
        margin-right: 10px;
        border: 1px solid #ddd;
    }

    /* Mobile Responsive Fixes */
    @media (max-width: 768px) {
        .search-section {
            flex-direction: row !important;
            flex-wrap: nowrap;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }
        
        #search-input {
            flex: 1;
            min-width: auto;
            margin-bottom: 0;
        }
        
        #search-btn {
            flex-shrink: 0;
            padding: 10px 15px;
            margin-bottom: 0;
        }
        
        .company-filter-wrapper {
            display: none; /* Hide company filter on mobile */
        }
        
        .cart {
            margin-left: auto; /* Push cart to right */
            order: 3;
        }
        
        .company-search-container {
            width: 100% !important;
        }
        
        .company-search-icon {
            left: auto !important;
            right: 10px !important;
        }
        
        .location-search {
            width: 100%;
        }
        
        /* Hide login prompt on mobile */
        .login-prompt {
            display: none !important;
        }
    }

    @media (max-width: 480px) {
        .search-section {
            gap: 8px;
        }
        
        #search-input {
            font-size: 12px;
            padding: 8px 12px;
        }
        
        #search-btn {
            font-size: 12px;
            padding: 8px 12px;
        }
        
        .cart {
            font-size: 14px;
        }
    }

    /* Your existing coupon section styles remain unchanged */
    /* Coupon Section - Fixed Horizontal Layout for Mobile */
.coupon-section {
    margin: 20px 0;
    padding: 15px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 10px;
    border: 1px solid #dee2e6;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    width: 100%;
    box-sizing: border-box;
}

.coupon-section label {
    display: block;
    margin-bottom: 10px;
    font-weight: 600;
    color: #495057;
    font-size: 16px;
}

.coupon-input-group {
    display: flex;
    gap: 8px;
    margin-bottom: 10px;
    width: 100%;
    align-items: stretch;
}

.coupon-input-group input {
    flex: 1;
    padding: 12px 15px;
    border: 2px solid #28a745;
    border-radius: 8px;
    font-size: 14px;
    font-family: 'Poppins', sans-serif;
    background: white;
    transition: all 0.3s ease;
    min-width: 0; /* Important for flexbox */
    width: 100%;
}

.coupon-input-group input:focus {
    outline: none;
    border-color: #1c3a23;
    box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
}

.coupon-input-group button {
    background: #28a745;
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
    white-space: nowrap;
    flex-shrink: 0;
    min-width: 80px; /* Ensure button has minimum width */
}

.coupon-input-group button:hover {
    background: #1c3a23;
    transform: translateY(-1px);
}

.coupon-message {
    margin-top: 8px;
    font-size: 14px;
    min-height: 20px;
    padding: 8px 12px;
    border-radius: 5px;
    text-align: center;
    width: 100%;
}

.coupon-message span {
    font-weight: 500;
}

/* Mobile Responsive for Coupon Section - Horizontal Layout */
@media (max-width: 768px) {
    .coupon-section {
        padding: 12px;
        margin: 15px 0;
    }
    
    .coupon-input-group {
        gap: 8px;
    }
    
    .coupon-input-group input {
        padding: 10px 12px;
        font-size: 14px;
        flex: 2; /* Give input more space */
    }
    
    .coupon-input-group button {
        padding: 10px 15px;
        font-size: 14px;
        flex: 1; /* Give button less space */
        min-width: 70px;
    }
    
    .coupon-section label {
        font-size: 14px;
    }
}

@media (max-width: 480px) {
    .coupon-section {
        padding: 10px;
    }
    
    .coupon-input-group {
        gap: 6px;
    }
    
    .coupon-input-group input {
        padding: 8px 10px;
        font-size: 13px;
    }
    
    .coupon-input-group button {
        padding: 8px 12px;
        font-size: 13px;
        min-width: 60px;
    }
}

    /* Your existing delivery options styles remain unchanged */
    .delivery-options-info {
        background: #e3f2fd;
        border: 1px solid #2196f3;
        color: #1565c0;
        padding: 12px;
        border-radius: 8px;
        margin: 10px 0;
        font-size: 0.9em;
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }

    .delivery-options-info i {
        margin-top: 2px;
    }

    .address-hint {
        margin-top: 8px;
        color: #6c757d;
        font-style: italic;
    }

    .address-hint i {
        color: #ffc107;
        margin-right: 5px;
    }

    /* Your existing phone update prompt styles remain unchanged */
    .phone-update-prompt {
        margin: 15px 0;
    }

    .phone-alert {
        background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
        border: 1px solid #ffc107;
        border-radius: 8px;
        padding: 15px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .phone-alert i {
        color: #856404;
        font-size: 18px;
        margin-top: 2px;
    }

    .phone-alert strong {
        color: #856404;
        display: block;
        margin-bottom: 5px;
    }

    .phone-alert p {
        color: #856404;
        margin: 0 0 10px 0;
        font-size: 0.9em;
    }

    .btn-update-phone {
        background: #856404;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 0.85em;
        transition: all 0.3s ease;
    }

    .btn-update-phone:hover {
        background: #5a4503;
        transform: translateY(-1px);
    }

    /* Your existing empty cart styles remain unchanged */
    .empty-cart {
        text-align: center;
        padding: 40px 20px;
        color: #6c757d;
        font-style: italic;
    }

    .empty-cart::before {
        content: "🛒";
        font-size: 48px;
        display: block;
        margin-bottom: 15px;
        opacity: 0.5;
    }

    /* Your existing login prompt modal styles remain unchanged */
    .login-prompt-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.8);
        z-index: 2000;
        justify-content: center;
        align-items: center;
    }

    .login-prompt-content {
        background: white;
        padding: 30px;
        border-radius: 15px;
        text-align: center;
        max-width: 400px;
        width: 90%;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
        from { transform: translateY(-50px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    .login-prompt-content h3 {
        color: #333;
        margin-bottom: 10px;
        font-size: 1.5em;
    }

    .login-prompt-content p {
        color: #666;
        margin-bottom: 20px;
    }

    .login-prompt-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
    }

    .login-prompt-buttons button {
        padding: 12px 25px;
        border: none;
        border-radius: 25px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .login-prompt-buttons button:first-child {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .login-prompt-buttons button:last-child {
        background: #28a745;
        color: white;
    }

    .login-prompt-buttons button:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }

    /* NEW STYLES FOR ENHANCED DELIVERY OPTIONS */
    .delivery-type-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.75em;
        font-weight: 600;
        margin-left: 5px;
    }
    
    .delivery-home {
        background-color: #e8f5e8;
        color: #2e7d32;
        border: 1px solid #4caf50;
    }
    
    .delivery-pickup {
        background-color: #e3f2fd;
        color: #1565c0;
        border: 1px solid #2196f3;
    }
    
    .company-address-display {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        padding: 10px;
        margin: 10px 0;
        font-size: 0.9em;
        color: #495057;
    }
    
    .company-address-display strong {
        color: #333;
    }
</style>
</head>
<body>

<!-- Login Prompt Modal -->
<div class="login-prompt-modal" id="login-prompt-modal">
    <div class="login-prompt-content">
        <h3>Login Required</h3>
        <p>You need to be logged in to proceed with payment and save your cart.</p>
        <div class="login-prompt-buttons">
            <button onclick="redirectToLogin()">Login Now</button>
            <button onclick="redirectToRegister()">Create Account</button>
        </div>
        <button onclick="closeLoginPrompt()" style="margin-top: 15px; background: none; border: none; color: #666; cursor: pointer; text-decoration: underline;">
            Maybe Later
        </button>
    </div>
</div>

<div class="modal-overlay" id="modal-overlay" onclick="closeCart()"></div>

<!-- Image Zoom Modal -->
<div id="imageZoomModal" class="image-zoom-modal">
    <span class="close-zoom" onclick="closeImageZoom()">&times;</span>
    <div class="zoom-modal-content">
        <img id="zoomedImage" src="" alt="Zoomed Food Image">
    </div>
</div>

<div class="container">
    <?php if ($singleItemView && $singleFoodItem): ?>
        <!-- SINGLE ITEM VIEW -->
        <div class="single-item-view">
            <div class="back-button">
                <a href="index.php" class="btn-back">&larr; Back to All Items</a>
            </div>
            
            <div class="single-food-card">
                <?php 
                $food = $singleFoodItem;
                $deliveryOptions = isset($food['delivery_options']) ? $food['delivery_options'] : 'Home Delivery';
                $hasHomeDelivery = stripos($deliveryOptions, 'Home Delivery') !== false;
                $hasPickup = stripos($deliveryOptions, 'Pickup') !== false;
                $hasBoth = $hasHomeDelivery && $hasPickup;
                $isPickupOnly = $hasPickup && !$hasHomeDelivery;
                
                $company = isset($food['company_name']) ? htmlspecialchars($food['company_name']) : 
                          (isset($food['company']) ? htmlspecialchars($food['company']) : 'Restaurant');
                
                $companyAddress = isset($food['company_address']) ? htmlspecialchars($food['company_address']) : 
                                'Address not available';
               
                $location = isset($food['location']) ? htmlspecialchars($food['location']) : 'Location not specified';
                $description = isset($food['description']) ? htmlspecialchars($food['description']) : 'No description available';
               // Fix image URL path
$imageUrl = '';
if (isset($food['image_url']) && !empty($food['image_url'])) {
    // Check if it's already a full URL
    if (strpos($food['image_url'], 'http') === 0) {
        $imageUrl = $food['image_url'];
    } else {
        // Handle relative paths
        $imagePath = $food['image_url'];
        
        // Fix common path issues
        if (strpos($imagePath, 'uploads/products/') !== false) {
            $imageUrl = '../' . $imagePath;
        } elseif (strpos($imagePath, '/uploads/products/') !== false) {
            $imageUrl = '..' . $imagePath;
        } elseif (strpos($imagePath, 'boseatsafrica/') !== false) {
            $imageUrl = '../' . str_replace('boseatsafrica/', '', $imagePath);
        } else {
            // Default path construction
            $imageUrl = '../uploads/products/' . basename($imagePath);
        }
    }
} else {
    $imageUrl = '../food/images/default.png';
}
                $category = isset($food['category']) ? $food['category'] : 'other';
                
                $convertedPrice = convertPriceFromNGN($food['price'], $conversionRate, false);
                $displayPrice = convertPriceFromNGN($food['price'], $conversionRate, true);
                
                $deliveryFee = isset($food['delivery_fee']) ? floatval($food['delivery_fee']) : 0.00;
                ?>
                
                <div class="single-item-image">
                    <img src="<?php echo $imageUrl; ?>" 
                         alt="<?php echo htmlspecialchars($food['name']); ?>" 
                         loading="lazy" 
                         onerror="this.src='../food/images/default.png'; this.onerror=null;"
                         onclick="openImageZoom('<?php echo $imageUrl; ?>', '<?php echo addslashes($food['name']); ?>')">
                </div>
                
                <div class="single-item-details">
                    <h1><?php echo htmlspecialchars($food['name']); ?></h1>
                    
                    <div class="item-company">
                        <i class="fas fa-store"></i>
                        <strong>Company:</strong> <?php echo $company; ?>
                    </div>
                    
                    <div class="item-description">
                        <p><?php echo nl2br(htmlspecialchars($description)); ?></p>
                    </div>
                    
                    <div class="item-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <strong>Location:</strong> <?php echo $location; ?>
                    </div>
                    
                    <div class="item-delivery-options">
                        <i class="fas fa-shipping-fast"></i>
                        <strong>Delivery Options:</strong>
                        <?php if ($hasBoth): ?>
                            <span class="delivery-type-badge delivery-home">
                                <i class="fas fa-truck"></i> Home Delivery
                            </span>
                            <span class="delivery-type-badge delivery-pickup">
                                <i class="fas fa-store"></i> Pickup
                            </span>
                        <?php elseif ($hasHomeDelivery): ?>
                            <span class="delivery-type-badge delivery-home">
                                <i class="fas fa-truck"></i> Home Delivery
                            </span>
                        <?php elseif ($hasPickup): ?>
                            <span class="delivery-type-badge delivery-pickup">
                                <i class="fas fa-store"></i> Pickup Only
                            </span>
                        <?php else: ?>
                            <span class="delivery-badge delivery-not-available">
                                <i class="fas fa-times"></i> Pickup Only
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="item-price-section">
                        <div class="price">
                            <?php echo $currencySymbol . $displayPrice; ?>
                        </div>
                        <?php if ($deliveryFee > 0): ?>
                            <div class="delivery-fee">
                                <small>Delivery Fee: <?php echo $currencySymbol . convertPriceFromNGN($deliveryFee, $conversionRate, true); ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="single-item-actions">
                        <button class="btn-add-to-cart" 
                                onclick="addToCart(<?php echo $food['id']; ?>, '<?php echo addslashes($food['name']); ?>', <?php echo $food['price']; ?>, '<?php echo $imageUrl; ?>', '<?php echo addslashes($description); ?>', <?php echo $hasHomeDelivery ? 1 : 0; ?>, <?php echo $hasPickup ? 1 : 0; ?>, '<?php echo addslashes($location); ?>', '<?php echo addslashes($companyAddress); ?>', '<?php echo addslashes($company); ?>', <?php echo $deliveryFee; ?>)">
                            <i class="fas fa-cart-plus"></i> Add to Cart
                        </button>
                        
                        <span class="favorite" onclick="toggleFavorite(<?php echo $food['id']; ?>, this)">
                            <i class="fas fa-heart"></i> Add to Favorites
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- MULTIPLE ITEMS VIEW (Original Listing) -->
        
        <!-- Original Login Prompt -->
        <div class="login-prompt" id="login-prompt">
            <h3>Please Login to Continue</h3>
            <p>You need to be logged in to proceed with payment and save your cart.</p>
            <button onclick="redirectToLogin()">Login</button>
            <button onclick="redirectToRegister()">Register</button>
        </div>

        <!-- Search Section -->
        <div class="search-section">
            <input type="text" id="search-input" placeholder="Search your food">
            <button id="search-btn"><i class="fas fa-search"></i> Search</button>

            <!-- Company Dropdown -->
            <div class="company-filter-wrapper">
                <div class="company-search-container">
                    <input type="text" id="company-search" class="company-search-input" placeholder="Search companies...">
                    <i class="fas fa-search company-search-icon"></i>
                </div>
                <div class="company-dropdown" id="company-dropdown">
                    <div class="company-option" data-company="all">
                        <i class="fas fa-building company-logo" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #666;"></i>
                        All Companies
                    </div>
                    <?php foreach($companies as $company): 
                        $companyName = $company['company_name'] ?? '';
                        $companyLogo = $company['company_logo'] ?? '';
                        if (!empty($companyName)):
                    ?>
                        <div class="company-option" data-company="<?php echo htmlspecialchars($companyName); ?>">
                            <?php if(!empty($companyLogo)): ?>
                                <img src="<?php echo $companyLogo; ?>" 
                                     alt="<?php echo htmlspecialchars($companyName); ?>" 
                                     class="company-logo"
                                     onerror="this.src='../food/images/default-company.png'">
                            <?php else: ?>
                                <i class="fas fa-store company-logo" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #666;"></i>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($companyName); ?>
                        </div>
                    <?php endif; endforeach; ?>
                </div>
            </div>

            <div class="cart" onclick="toggleCart()">
                <i class="fas fa-shopping-cart"></i>
                <span id="cart-count">0</span>
            </div>
        </div>

        <!-- Location Search -->
        <div class="location-search">
            <input type="text" id="location-input" placeholder="Enter your location (e.g., Lugbe, Abuja)">
            <button id="location-search-btn"><i class="fas fa-map-marker-alt"></i> Find Nearby</button>
            <button id="clear-location-btn" style="background-color: #6c757d;"><i class="fas fa-times"></i> Clear</button>
        </div>

        <!-- Sorting Tabs -->
        <div class="tabs">
            <div class="active" data-filter="all">All</div>
            <div data-filter="cheapest">Cheapest</div>
            <div data-filter="local">Local food</div>
            <div data-filter="nearest">Nearest</div>
            <div data-filter="other">Other sort</div>
        </div>

        <!-- Food Cards Container -->
        <div class="card-container" id="food-cards-container">
            <?php if (empty($foodData)): ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-utensils" style="font-size: 48px; margin-bottom: 20px; color: #ddd;"></i>
                    <h3>No Food Items Available</h3>
                    <p>We're currently updating our menu. Please check back later.</p>
                    <p style="font-size: 12px; color: #999; margin-top: 20px;">
                        <a href="merchant_dashboard.php" style="color: #28a745; text-decoration: none;">
                            <i class="fas fa-plus"></i> Add Food Items (Merchant)
                        </a>
                    </p>
                </div>
            <?php else: ?>
                <?php 
                // Find cheapest items for highlighting
                $cheapestPrice = !empty($foodData) ? min(array_column($foodData, 'price')) : 0;
                foreach($foodData as $food): 
                    $deliveryOptions = isset($food['delivery_options']) ? $food['delivery_options'] : 'Home Delivery';
                    $hasHomeDelivery = stripos($deliveryOptions, 'Home Delivery') !== false;
                    $hasPickup = stripos($deliveryOptions, 'Pickup') !== false;
                    $hasBoth = $hasHomeDelivery && $hasPickup;
                    $isPickupOnly = $hasPickup && !$hasHomeDelivery;
                    
                    $company = isset($food['company_name']) ? htmlspecialchars($food['company_name']) : 
                              (isset($food['company']) ? htmlspecialchars($food['company']) : 'Restaurant');
                    
                    $companyAddress = isset($food['company_address']) ? htmlspecialchars($food['company_address']) : 
                                    'Address not available';
                   
                    $location = isset($food['location']) ? htmlspecialchars($food['location']) : 'Location not specified';
                    $description = isset($food['description']) ? htmlspecialchars($food['description']) : 'No description available';
                   // Fix image URL path
$imageUrl = '';
if (isset($food['image_url']) && !empty($food['image_url'])) {
    // Check if it's already a full URL
    if (strpos($food['image_url'], 'http') === 0) {
        $imageUrl = $food['image_url'];
    } else {
        // Handle relative paths
        $imagePath = $food['image_url'];
        
        // Fix common path issues
        if (strpos($imagePath, 'uploads/products/') !== false) {
            $imageUrl = '../' . $imagePath;
        } elseif (strpos($imagePath, '/uploads/products/') !== false) {
            $imageUrl = '..' . $imagePath;
        } elseif (strpos($imagePath, 'boseatsafrica/') !== false) {
            $imageUrl = '../' . str_replace('boseatsafrica/', '', $imagePath);
        } else {
            // Default path construction
            $imageUrl = '../uploads/products/' . basename($imagePath);
        }
    }
} else {
    $imageUrl = '../food/images/default.png';
}
                    $category = isset($food['category']) ? $food['category'] : 'other';
                    $isCheapest = !empty($foodData) && $food['price'] == $cheapestPrice;
                    
                    $convertedPrice = convertPriceFromNGN($food['price'], $conversionRate, false);
                    $displayPrice = convertPriceFromNGN($food['price'], $conversionRate, true);
                    
                    $deliveryFee = isset($food['delivery_fee']) ? floatval($food['delivery_fee']) : 0.00;
                ?>
                    <div class="card <?php echo $isCheapest ? 'cheapest-item' : ''; ?>" 
                         data-id="<?php echo $food['id']; ?>" 
                         data-category="<?php echo $category; ?>" 
                         data-location="<?php echo $location; ?>" 
                         data-home-delivery="<?php echo $hasHomeDelivery ? 1 : 0; ?>"
                         data-pickup-available="<?php echo $hasPickup ? 1 : 0; ?>"
                         data-company-address="<?php echo $companyAddress; ?>"
                         data-price="<?php echo $food['price']; ?>"
                         data-delivery-fee="<?php echo $deliveryFee; ?>"
                         data-company="<?php echo htmlspecialchars($company); ?>">
                        <?php if ($isCheapest): ?>
                            <div class="cheapest-banner">Cheapest!</div>
                        <?php endif; ?>
                        <img src="<?php echo $imageUrl; ?>" 
                             alt="<?php echo htmlspecialchars($food['name']); ?>" 
                             loading="lazy" 
                             onerror="this.src='../food/images/default.png'; this.onerror=null;"
                             onclick="openImageZoom('<?php echo $imageUrl; ?>', '<?php echo addslashes($food['name']); ?>')">
                        <h3><?php echo htmlspecialchars($food['name']); ?></h3>
                        <p>
                            <b>Company</b> - 
                            <?php echo $company; ?>
                        </p>
                        <p><?php echo $description; ?></p>
                        <p>
                            <label><b>Location:</b></label> <?php echo $location; ?>
                            <?php if ($hasBoth): ?>
                                <span class="delivery-type-badge delivery-home">
                                    <i class="fas fa-truck"></i> Home Delivery
                                </span>
                                <span class="delivery-type-badge delivery-pickup">
                                    <i class="fas fa-store"></i> Pickup
                                </span>
                            <?php elseif ($hasHomeDelivery): ?>
                                <span class="delivery-type-badge delivery-home">
                                    <i class="fas fa-truck"></i> Home Delivery
                                </span>
                            <?php elseif ($hasPickup): ?>
                                <span class="delivery-type-badge delivery-pickup">
                                    <i class="fas fa-store"></i> Pickup Only
                                </span>
                            <?php else: ?>
                                <span class="delivery-badge delivery-not-available">
                                    <i class="fas fa-times"></i> Pickup Only
                                </span>
                            <?php endif; ?>
                        </p>
                       <div class="price">
                            <?php echo $currencySymbol . $displayPrice; ?>
                        </div>
                        <div class="card-actions">
                            <button onclick="addToCart(<?php echo $food['id']; ?>, '<?php echo addslashes($food['name']); ?>', <?php echo $food['price']; ?>, '<?php echo $imageUrl; ?>', '<?php echo addslashes($description); ?>', <?php echo $hasHomeDelivery ? 1 : 0; ?>, <?php echo $hasPickup ? 1 : 0; ?>, '<?php echo addslashes($location); ?>', '<?php echo addslashes($companyAddress); ?>', '<?php echo addslashes($company); ?>', <?php echo $deliveryFee; ?>)">Add to cart</button>
                            <span class="favorite" onclick="toggleFavorite(<?php echo $food['id']; ?>, this)">
                                <i class="fas fa-heart"></i>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination Controls -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=1">&laquo; First</a>
                    <a href="?page=<?php echo $currentPage - 1; ?>">&lsaquo; Prev</a>
                <?php else: ?>
                    <span class="disabled">&laquo; First</span>
                    <span class="disabled">&lsaquo; Prev</span>
                <?php endif; ?>

                <?php
                // Show page numbers
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);
                
                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                    <?php if ($i == $currentPage): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?php echo $currentPage + 1; ?>">Next &rsaquo;</a>
                    <a href="?page=<?php echo $totalPages; ?>">Last &raquo;</a>
                <?php else: ?>
                    <span class="disabled">Next &rsaquo;</span>
                    <span class="disabled">Last &raquo;</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Cart Modal -->
<div id="cart-modal">
    <span class="close" onclick="closeCart()">&times;</span>
    <h2>Your Cart</h2>
    
    <!-- Payment Processing -->
    <div class="payment-processing" id="payment-processing">
        <div class="spinner"></div>
        <p>Processing your payment...</p>
    </div>
    
    <div class="cart-items" id="cart-items"></div>

    <div class="delivery-section" id="delivery-section">
        <!-- Company Address Display for Pickup Items -->
        <div class="company-address-display" id="company-address-display" style="display: none;">
            <strong><i class="fas fa-store"></i> Pickup Address:</strong>
            <span id="company-address-text"></span>
        </div>

        <div class="delivery-warning" id="delivery-warning" style="display: none;">
            <i class="fas fa-exclamation-triangle"></i>
            <span id="warning-text">Some items require pickup. Company address is shown above.</span>
        </div>

        <div class="delivery-options-info" id="delivery-options-info" style="display: none;">
            <i class="fas fa-info-circle"></i>
            <span id="delivery-info-text">Mixed delivery options in cart. You can choose delivery address for home delivery items.</span>
        </div>

        <div class="location-section">
            <label for="location">Delivery/Pickup Location</label>
            <select id="location" name="location">
                <option value="">Select a location</option>
                <?php foreach($locations as $location): ?>
                    <option value="<?php echo htmlspecialchars($location); ?>"><?php echo htmlspecialchars($location); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="note-section">
            <label for="order-note" id="address-label">Delivery Address</label>
            <div class="address-field-container">
                <textarea id="order-note" placeholder="Input your delivery address..."></textarea>
            </div>
            <div class="address-hint" id="address-hint" style="display: none;">
                <small><i class="fas fa-lightbulb"></i> Company address shown for pickup items. Enter your address for home delivery items.</small>
            </div>
        </div>

        <!-- Phone Number Update Prompt -->
        <div class="phone-update-prompt" id="phone-update-prompt" style="display: none;">
            <div class="phone-alert">
                <i class="fas fa-phone"></i>
                <div>
                    <strong>Update Your Phone Number</strong>
                    <p>Ensure your phone number is up to date for delivery updates.</p>
                    <button onclick="redirectToProfile()" class="btn-update-phone">
                        <i class="fas fa-user-edit"></i> Update Profile
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Coupon Code Section -->
    <div class="coupon-section">
        <label for="coupon-code">Coupon Code (Optional)</label>
        <div class="coupon-input-group">
            <input type="text" id="coupon-code" placeholder="Enter coupon code">
            <button onclick="applyCoupon()">Apply</button>
        </div>
        <div id="coupon-message" class="coupon-message"></div>
    </div>
    
    <div class="cart-total">
        <div class="total-row">
            <span class="total-label">Sub Total:</span>
            <span class="total-value">
                <span id="currency-symbol"><?php echo $currencySymbol; ?></span>
                <span id="sub-total">0.00</span>
            </span>
        </div>
        <div class="total-row" id="discount-row" style="display: none;">
            <span class="total-label">Discount:</span>
            <span class="total-value" style="color: #dc3545;">
                -<span id="currency-symbol-discount"><?php echo $currencySymbol; ?></span>
                <span id="discount-amount">0.00</span>
            </span>
        </div>
        <div class="total-row">
            <span class="total-label">Delivery Fee:</span>
            <span class="total-value" id="delivery-fee">Free</span>
        </div>
        <div class="total-row total-final">
            <span class="total-label">Total:</span>
            <span class="total-value">
                <span id="total-currency-symbol"><?php echo $currencySymbol; ?></span>
                <span id="cart-total">0.00</span>
            </span>
        </div>
        <button onclick="processPayment()">Proceed to Payment</button>
    </div>
</div>

<script>
    // Application state
    let cart = JSON.parse(localStorage.getItem('foodCart')) || <?php echo !empty($userCart) ? json_encode($userCart) : '[]'; ?>;
    let favorites = JSON.parse(localStorage.getItem('foodFavorites')) || [];
    let userLocation = localStorage.getItem('userLocation') || '';
    let isLocationFilterActive = false;
    let companies = <?php echo json_encode($companies); ?>;
    let isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
    let userId = <?php echo $userId ?: 'null'; ?>;
    let userEmail = '<?php echo $userEmail; ?>';
    let appliedCoupon = null;
    
    // Currency configuration
    let currencySymbol = '<?php echo $currencySymbol; ?>';
    let conversionRate = <?php echo $conversionRate; ?>;

    // Define clearCart function
    function clearCart() {
        cart = [];
        localStorage.removeItem('foodCart');
        updateCartCount();
        updateCart();
        
        if (isLoggedIn) {
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('action', 'clear_cart');
            
            fetch('../includes/cart_handler.php', {
                method: 'POST',
                body: formData
            });
        }
    }

    // Login Prompt Modal Functions
    function showLoginPrompt() {
        document.getElementById('login-prompt-modal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeLoginPrompt() {
        document.getElementById('login-prompt-modal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // Initialize the page
    document.addEventListener('DOMContentLoaded', function() {
        updateCartCount();
        initializeFavorites();
        setupEventListeners();
        
        if (userLocation) {
            document.getElementById('location-input').value = userLocation;
            isLocationFilterActive = true;
        }
        
        <?php if (!$singleItemView): ?>
            filterFoodCards();
        <?php endif; ?>
        
        console.log('Food items loaded:', <?php echo count($foodData); ?>);
        console.log('Companies loaded:', companies);
    });

    function setupEventListeners() {
        <?php if (!$singleItemView): ?>
            // Search functionality (only for listing view)
            document.getElementById('search-btn').addEventListener('click', handleSearch);
            document.getElementById('search-input').addEventListener('input', handleSearch);
            document.getElementById('location-search-btn').addEventListener('click', handleLocationSearch);
            document.getElementById('clear-location-btn').addEventListener('click', clearLocationSearch);
            document.getElementById('location-input').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') handleLocationSearch();
            });
            
            // Company search functionality
            document.getElementById('company-search').addEventListener('input', handleCompanySearch);
            document.getElementById('company-search').addEventListener('focus', showCompanyDropdown);
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.company-filter-wrapper')) {
                    hideCompanyDropdown();
                }
            });

            // Company option selection
            document.querySelectorAll('.company-option').forEach(option => {
                option.addEventListener('click', function() {
                    const company = this.getAttribute('data-company');
                    document.getElementById('company-search').value = company === 'all' ? '' : this.textContent.trim();
                    hideCompanyDropdown();
                    filterFoodCards();
                });
            });
            
            // Tabs
            document.querySelectorAll('.tabs div').forEach(tab => {
                tab.addEventListener('click', function() {
                    document.querySelectorAll('.tabs div').forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    filterFoodCards();
                });
            });
        <?php endif; ?>

        // Close modals when clicking outside
        document.getElementById('imageZoomModal').addEventListener('click', function(e) {
            if (e.target === this) closeImageZoom();
        });

        document.getElementById('login-prompt-modal').addEventListener('click', function(e) {
            if (e.target === this) closeLoginPrompt();
        });

        // Close modals with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeImageZoom();
                closeLoginPrompt();
            }
        });
    }

    <?php if (!$singleItemView): ?>
        // Company Search Functions (only for listing view)
        function handleCompanySearch() {
            const searchTerm = document.getElementById('company-search').value.toLowerCase().trim();
            const dropdown = document.getElementById('company-dropdown');
            
            if (searchTerm === '') {
                document.querySelectorAll('.company-option').forEach(option => {
                    option.style.display = 'flex';
                });
                return;
            }

            document.querySelectorAll('.company-option').forEach(option => {
                const companyName = option.textContent.toLowerCase();
                if (companyName.includes(searchTerm)) {
                    option.style.display = 'flex';
                } else {
                    option.style.display = 'none';
                }
            });
        }

        function showCompanyDropdown() {
            document.getElementById('company-dropdown').style.display = 'block';
        }

        function hideCompanyDropdown() {
            document.getElementById('company-dropdown').style.display = 'none';
        }

        // Filter Functions (only for listing view)
        function filterFoodCards() {
            const filter = document.querySelector('.tabs .active').getAttribute('data-filter');
            const searchTerm = document.getElementById('search-input').value.toLowerCase();
            const companySearch = document.getElementById('company-search').value.toLowerCase();
            
            document.querySelectorAll('.card').forEach(card => {
                const name = card.querySelector('h3').textContent.toLowerCase();
                const description = card.querySelector('p:nth-of-type(2)').textContent.toLowerCase();
                const company = card.getAttribute('data-company').toLowerCase();
                const category = card.getAttribute('data-category');
                const location = card.getAttribute('data-location').toLowerCase();
                
                const matchesSearch = !searchTerm || name.includes(searchTerm) || description.includes(searchTerm);
                const matchesCompany = !companySearch || company.includes(companySearch);
                const matchesCategory = filter === 'all' || 
                                      (filter === 'cheapest' && card.classList.contains('cheapest-item')) ||
                                      category === filter;
                const matchesLocation = !isLocationFilterActive || !userLocation || location.includes(userLocation.toLowerCase());
                
                card.style.display = matchesSearch && matchesCompany && matchesCategory && matchesLocation ? 'block' : 'none';
            });
        }

        function handleSearch() {
            filterFoodCards();
        }

        function handleLocationSearch() {
            const location = document.getElementById('location-input').value.trim();
            if (location) {
                userLocation = location;
                localStorage.setItem('userLocation', userLocation);
                isLocationFilterActive = true;
                filterFoodCards();
                showNotification(`Searching for restaurants near ${location}`);
            }
        }

        function clearLocationSearch() {
            userLocation = '';
            localStorage.removeItem('userLocation');
            document.getElementById('location-input').value = '';
            isLocationFilterActive = false;
            filterFoodCards();
            showNotification('Location filter cleared');
        }
    <?php endif; ?>

    // Cart Functions (works for both views)
    function toggleCart() {
        const cartModal = document.getElementById('cart-modal');
        const overlay = document.getElementById('modal-overlay');
        
        if (cartModal.style.display === 'block') {
            closeCart();
        } else {
            cartModal.style.display = 'block';
            overlay.style.display = 'block';
            updateCart();
        }
    }

    function closeCart() {
        document.getElementById('cart-modal').style.display = 'none';
        document.getElementById('modal-overlay').style.display = 'none';
    }

    function updateCartCount() {
        const count = cart.reduce((total, item) => total + item.quantity, 0);
        document.getElementById('cart-count').textContent = count;
    }

    function updateCart() {
        const cartItemsContainer = document.getElementById('cart-items');
        const locationSelect = document.getElementById('location');
        const addressTextarea = document.getElementById('order-note');
        const addressLabel = document.getElementById('address-label');
        const deliveryWarning = document.getElementById('delivery-warning');
        const deliveryOptionsInfo = document.getElementById('delivery-options-info');
        const addressHint = document.getElementById('address-hint');
        const phoneUpdatePrompt = document.getElementById('phone-update-prompt');
        const companyAddressDisplay = document.getElementById('company-address-display');
        const companyAddressText = document.getElementById('company-address-text');
        
        let total = 0;
        let totalDeliveryFee = 0;

        // Check delivery options in cart
        const hasHomeDeliveryItems = cart.some(item => item.homeDelivery == 1);
        const hasPickupItems = cart.some(item => item.pickupAvailable == 1);
        const allPickupOnly = cart.every(item => item.homeDelivery == 0 && item.pickupAvailable == 1);
        const mixedDelivery = hasHomeDeliveryItems && hasPickupItems;

        // Get company address from first pickup item
        const firstPickupItem = cart.find(item => item.pickupAvailable == 1);
        const companyAddress = firstPickupItem ? firstPickupItem.companyAddress : '';
        const companyName = firstPickupItem ? firstPickupItem.companyName : '';

        // Update UI based on delivery options
        if (cart.length > 0) {
            if (allPickupOnly) {
                // All items are pickup only - SHOW COMPANY ADDRESS AND LOCK DELIVERY FIELD
                companyAddressDisplay.style.display = 'block';
                companyAddressText.textContent = companyAddress || 'Address will be provided after order confirmation';
                
                addressTextarea.disabled = true;
                addressTextarea.classList.add('locked-address');
                addressLabel.textContent = 'Delivery Address (Not Required for Pickup)';
                addressTextarea.value = '';
                addressTextarea.placeholder = 'Pickup address shown above';
                
                // Add lock icon
                let lockIcon = document.querySelector('.lock-icon');
                if (!lockIcon) {
                    lockIcon = document.createElement('i');
                    lockIcon.className = 'fas fa-lock lock-icon';
                    addressTextarea.parentNode.appendChild(lockIcon);
                }
                
                deliveryWarning.style.display = 'block';
                deliveryOptionsInfo.style.display = 'none';
                addressHint.style.display = 'block';
                
            } else if (mixedDelivery) {
                // Mixed delivery options - SHOW COMPANY ADDRESS FOR PICKUP ITEMS
                companyAddressDisplay.style.display = 'block';
                companyAddressText.textContent = companyAddress || 'Address will be provided after order confirmation';
                
                addressTextarea.disabled = false;
                addressTextarea.classList.remove('locked-address');
                addressLabel.textContent = 'Delivery Address (For Home Delivery Items)';
                addressTextarea.placeholder = 'Enter your delivery address for home delivery items';
                addressTextarea.value = '';
                
                // Remove lock icon
                const lockIcon = document.querySelector('.lock-icon');
                if (lockIcon) lockIcon.remove();
                
                deliveryWarning.style.display = 'none';
                deliveryOptionsInfo.style.display = 'block';
                addressHint.style.display = 'block';
                
            } else {
                // All items have home delivery - HIDE COMPANY ADDRESS
                companyAddressDisplay.style.display = 'none';
                
                addressTextarea.disabled = false;
                addressTextarea.classList.remove('locked-address');
                addressLabel.textContent = 'Delivery Address';
                addressTextarea.placeholder = 'Input your delivery address...';
                addressTextarea.value = '';
                
                // Remove lock icon
                const lockIcon = document.querySelector('.lock-icon');
                if (lockIcon) lockIcon.remove();
                
                deliveryWarning.style.display = 'none';
                deliveryOptionsInfo.style.display = 'none';
                addressHint.style.display = 'none';
            }

            // Show phone update prompt for delivery items
            phoneUpdatePrompt.style.display = hasHomeDeliveryItems ? 'block' : 'none';

        } else {
            // Empty cart - reset everything
            companyAddressDisplay.style.display = 'none';
            
            addressTextarea.disabled = false;
            addressTextarea.classList.remove('locked-address');
            addressLabel.textContent = 'Delivery Address';
            addressTextarea.placeholder = 'Input your delivery address...';
            addressTextarea.value = '';
            
            // Remove lock icon
            const lockIcon = document.querySelector('.lock-icon');
            if (lockIcon) lockIcon.remove();
            
            deliveryWarning.style.display = 'none';
            deliveryOptionsInfo.style.display = 'none';
            addressHint.style.display = 'none';
            phoneUpdatePrompt.style.display = 'none';
        }

        // Update cart items display and calculate totals
        if (cart.length === 0) {
            cartItemsContainer.innerHTML = '<p class="empty-cart">Your cart is empty</p>';
        } else {
            cartItemsContainer.innerHTML = '';
            cart.forEach((item, index) => {
                const displayPrice = (item.price * conversionRate).toFixed(2);
                total += item.price * item.quantity;
                
                // Calculate delivery fee from database
                const itemDeliveryFee = item.deliveryFee || 0;
                totalDeliveryFee += itemDeliveryFee;
                
                // Enhanced delivery info display
                let deliveryInfo = '';
                if (item.homeDelivery == 1 && item.pickupAvailable == 1) {
                    deliveryInfo = '<span class="cart-delivery-available"><i class="fas fa-truck"></i> Home Delivery</span> <span class="cart-delivery-unavailable"><i class="fas fa-store"></i> Pickup Available</span>';
                } else if (item.homeDelivery == 1) {
                    deliveryInfo = '<span class="cart-delivery-available"><i class="fas fa-truck"></i> Home Delivery</span>';
                } else if (item.pickupAvailable == 1) {
                    deliveryInfo = '<span class="cart-delivery-unavailable"><i class="fas fa-store"></i> Pickup Only</span>';
                }
                
                const cartItemHTML = `
                    <div class="cart-item">
                        <div class="cart-item-header">
                            <img src="${item.imageUrl}" alt="${item.name}" loading="lazy" onerror="this.src='../food/images/default.png'; this.onerror=null;">
                            <div class="cart-item-details">
                                <div class="cart-item-name">${item.name}</div>
                                <div class="cart-item-desc">${item.description}</div>
                                <div class="cart-item-price">${currencySymbol}${displayPrice} each</div>
                                ${deliveryInfo}
                            </div>
                            <button class="delete-btn" onclick="removeFromCart(${index})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <div class="quantity-controls">
                            <button class="quantity-btn" onclick="updateQuantity(${index}, -1)">-</button>
                            <input type="number" class="quantity-input" value="${item.quantity}" min="1" onchange="updateQuantity(${index}, 0, this.value)" readonly>
                            <button class="quantity-btn" onclick="updateQuantity(${index}, 1)">+</button>
                        </div>
                    </div>
                `;
                cartItemsContainer.innerHTML += cartItemHTML;
            });
        }

        // Apply coupon discount if any
        let finalTotal = total + totalDeliveryFee;
        let discountAmount = 0;
        
        if (appliedCoupon) {
            if (appliedCoupon.discount_type === 'percentage') {
                discountAmount = (finalTotal * appliedCoupon.discount_value) / 100;
                if (appliedCoupon.max_discount_amount && discountAmount > appliedCoupon.max_discount_amount) {
                    discountAmount = appliedCoupon.max_discount_amount;
                }
            } else if (appliedCoupon.discount_type === 'fixed') {
                discountAmount = appliedCoupon.discount_value;
            }
            finalTotal = Math.max(0, finalTotal - discountAmount);
        }

        // Convert totals for display
        const displaySubTotal = (total * conversionRate).toFixed(2);
        const displayDeliveryFee = (totalDeliveryFee * conversionRate).toFixed(2);
        const displayDiscount = (discountAmount * conversionRate).toFixed(2);
        const displayFinalTotal = (finalTotal * conversionRate).toFixed(2);

        document.getElementById('sub-total').textContent = displaySubTotal;
        document.getElementById('cart-total').textContent = displayFinalTotal;

        // Update delivery fee display from database
        const deliveryFeeElement = document.getElementById('delivery-fee');
        if (totalDeliveryFee > 0) {
            deliveryFeeElement.innerHTML = `${currencySymbol}${displayDeliveryFee}`;
        } else {
            deliveryFeeElement.innerHTML = 'Free';
        }

        // Show/hide discount row
        const discountRow = document.getElementById('discount-row');
        if (discountAmount > 0) {
            discountRow.style.display = 'flex';
            document.getElementById('discount-amount').textContent = displayDiscount;
        } else {
            discountRow.style.display = 'none';
        }
    }

    // Enhanced addToCart function to include both delivery options
    function addToCart(foodId, foodName, price, imageUrl, description, homeDelivery, pickupAvailable, location, companyAddress = '', companyName = '', deliveryFee = 0) {
        const existingItemIndex = cart.findIndex(item => item.id === foodId);
        
        if (existingItemIndex > -1) {
            cart[existingItemIndex].quantity += 1;
        } else {
            cart.push({
                id: foodId,
                name: foodName,
                price: price,
                imageUrl: imageUrl,
                description: description,
                homeDelivery: homeDelivery,
                pickupAvailable: pickupAvailable,
                location: location,
                companyAddress: companyAddress,
                companyName: companyName,
                deliveryFee: deliveryFee,
                quantity: 1
            });
        }
        
        localStorage.setItem('foodCart', JSON.stringify(cart));
        updateCartCount();
        updateCart();
        
        if (isLoggedIn) {
            saveCartToDatabase();
        }
        
        // FIX: Show notification after cart is updated
        setTimeout(() => {
            showNotification(`${foodName} added to cart!`);
        }, 100);
    }

    function updateQuantity(index, change, directValue = null) {
        if (directValue !== null) {
            cart[index].quantity = parseInt(directValue) || 1;
        } else {
            cart[index].quantity += change;
        }
        
        if (cart[index].quantity < 1) cart[index].quantity = 1;
        
        localStorage.setItem('foodCart', JSON.stringify(cart));
        updateCartCount();
        updateCart();
        
        if (isLoggedIn) {
            saveCartToDatabase();
        }
    }

    function removeFromCart(index) {
        const itemName = cart[index].name;
        cart.splice(index, 1);
        localStorage.setItem('foodCart', JSON.stringify(cart));
        updateCartCount();
        updateCart();
        
        if (isLoggedIn) {
            saveCartToDatabase();
        }
        
        showNotification(`${itemName} removed from cart`);
    }

    function saveCartToDatabase() {
        if (!isLoggedIn) return;
        
        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('cart_data', JSON.stringify(cart));
        formData.append('action', 'save_cart');
        
        fetch('../includes/cart_handler.php', {
            method: 'POST',
            body: formData
        }).catch(error => console.error('Error saving cart:', error));
    }

    // Coupon Code Functions
    function applyCoupon() {
        const couponCode = document.getElementById('coupon-code').value.trim();
        const messageElement = document.getElementById('coupon-message');
        
        if (!couponCode) {
            messageElement.innerHTML = '<span style="color: #dc3545;">Please enter a coupon code</span>';
            return;
        }
        
        fetch('../includes/validate_coupon.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                coupon_code: couponCode,
                cart_total: cart.reduce((sum, item) => sum + (item.price * item.quantity), 0),
                user_id: userId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.valid) {
                messageElement.innerHTML = `<span style="color: #28a745;">${data.message}</span>`;
                appliedCoupon = data.discount_data;
                updateCart();
            } else {
                messageElement.innerHTML = `<span style="color: #dc3545;">${data.message}</span>`;
                appliedCoupon = null;
                updateCart();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            messageElement.innerHTML = '<span style="color: #dc3545;">Error validating coupon</span>';
            appliedCoupon = null;
        });
    }

    // Image Zoom Functions
    function openImageZoom(imageSrc, altText) {
        const modal = document.getElementById('imageZoomModal');
        const zoomedImg = document.getElementById('zoomedImage');
        
        zoomedImg.src = imageSrc;
        zoomedImg.alt = altText;
        modal.style.display = 'block';
        
        document.body.style.overflow = 'hidden';
    }

    function closeImageZoom() {
        const modal = document.getElementById('imageZoomModal');
        modal.style.display = 'none';
        
        document.body.style.overflow = 'auto';
    }

    // Payment Process
    function processPayment() {
        if (cart.length === 0) {
            alert('Your cart is empty!');
            return;
        }

        // Check if user is logged in and show popup if not
        if (!isLoggedIn) {
            showLoginPrompt();
            return;
        }

        const location = document.getElementById('location').value;
        const note = document.getElementById('order-note').value;
        
        const totalInNaira = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const deliveryFeeInNaira = cart.reduce((sum, item) => sum + (item.deliveryFee * item.quantity), 0);
        const discountAmount = appliedCoupon ? calculateDiscount(totalInNaira + deliveryFeeInNaira, appliedCoupon) : 0;
        const finalTotalInNaira = Math.max(0, (totalInNaira + deliveryFeeInNaira) - discountAmount);
        
        if (!location) {
            alert('Please select a delivery/pickup location.');
            return;
        }
        
        const hasHomeDeliveryItems = cart.some(item => item.homeDelivery == 1);
        if (hasHomeDeliveryItems && !note.trim()) {
            alert('Please enter your delivery address for home delivery items.');
            return;
        }
        
        const orderData = {
            items: cart,
            location: location,
            note: note,
            total: finalTotalInNaira,
            original_total: totalInNaira + deliveryFeeInNaira,
            discount_amount: discountAmount,
            delivery_fee: deliveryFeeInNaira,
            coupon_code: appliedCoupon ? appliedCoupon.coupon_code : null,
            currency: 'NGN',
            hasHomeDelivery: hasHomeDeliveryItems,
            hasPickupItems: cart.some(item => item.pickupAvailable == 1),
            timestamp: new Date().toISOString(),
            user_id: userId,
            user_email: userEmail
        };

        initiatePayment(orderData);
    }

    function calculateDiscount(total, coupon) {
        if (coupon.discount_type === 'percentage') {
            let discount = (total * coupon.discount_value) / 100;
            if (coupon.max_discount_amount && discount > coupon.max_discount_amount) {
                return coupon.max_discount_amount;
            }
            return discount;
        } else if (coupon.discount_type === 'fixed') {
            return coupon.discount_value;
        }
        return 0;
    }

    function initiatePayment(orderData) {
        document.getElementById('payment-processing').style.display = 'block';
        
        const totalInKobo = Math.round(orderData.total * 100);
        
        const paymentData = {
            email: orderData.user_email,
            amount: totalInKobo,
            currency: 'NGN',
            ref: 'BOSEATS_' + Math.floor((Math.random() * 1000000000) + 1),
            metadata: {
                custom_fields: [
                    {
                        display_name: "Order Items",
                        variable_name: "order_items",
                        value: JSON.stringify(orderData.items)
                    },
                    {
                        display_name: "User ID", 
                        variable_name: "user_id",
                        value: orderData.user_id
                    }
                ]
            },
            callback: function(response) {
                verifyPayment(response.reference, orderData);
            },
            onClose: function() {
                document.getElementById('payment-processing').style.display = 'none';
            }
        };

        var handler = PaystackPop.setup({
            key: 'pk_test_1b251229f5da6778289c78b9f73075dcd30003a9',
            ...paymentData
        });
        
        handler.openIframe();
    }

    function verifyPayment(reference, orderData) {
        fetch('../includes/verify_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                reference: reference,
                order_data: orderData
            })
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('payment-processing').style.display = 'none';
            
            if (data.success) {
                clearCart();
                window.location.href = 'payment_success.php?reference=' + reference;
            } else {
                alert('Payment verification failed: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('payment-processing').style.display = 'none';
            alert('Payment verification error. Please contact support.');
        });
    }

    // Redirect functions for login
    function redirectToLogin() {
        window.location.href = '../login.php?redirect=' + encodeURIComponent(window.location.href);
    }

    function redirectToRegister() {
        window.location.href = '../user/register.php?redirect=' + encodeURIComponent(window.location.href);
    }

    // Favorite Functions
    function toggleFavorite(foodId, element) {
        const index = favorites.indexOf(foodId);
        if (index > -1) {
            favorites.splice(index, 1);
            element.classList.remove('favorite-active');
            showNotification('Removed from favorites');
        } else {
            favorites.push(foodId);
            element.classList.add('favorite-active');
            showNotification('Added to favorites!');
        }
        localStorage.setItem('foodFavorites', JSON.stringify(favorites));
    }

    function initializeFavorites() {
        favorites.forEach(foodId => {
            const favoriteIcon = document.querySelector(`.card[data-id="${foodId}"] .favorite`);
            if (favoriteIcon) {
                favoriteIcon.classList.add('favorite-active');
            }
        });
    }

    // Utility Functions
    function showNotification(message) {
        const notification = document.createElement('div');
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #28a745;
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            z-index: 1001;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        `;
        document.body.appendChild(notification);
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    // Redirect to profile for phone update
    function redirectToProfile() {
        if (isLoggedIn) {
            window.location.href = '../user/profile.php';
        } else {
            showLoginPrompt();
        }
    }
</script>

</body>
</html>

<?php 
include_once "../includes/footer.php"; 
?>
<?php
include_once '../includes/db_connection.php';
include_once '../includes/protect_user.php';

// Fetch user data from database
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch countries and states from database
$countries_stmt = $pdo->prepare("SELECT * FROM countries ORDER BY name");
$countries_stmt->execute();
$countries = $countries_stmt->fetchAll(PDO::FETCH_ASSOC);

$states_stmt = $pdo->prepare("SELECT * FROM states ORDER BY name");
$states_stmt->execute();
$states = $states_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch user's wishlist items
$wishlist_stmt = $pdo->prepare("SELECT * FROM wishlist WHERE user_id = ?");
$wishlist_stmt->execute([$user_id]);
$wishlist_items = $wishlist_stmt->fetchAll(PDO::FETCH_ASSOC);
$wishlist_count = count($wishlist_items);

// Fetch user's cart items
$cart_stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ?");
$cart_stmt->execute([$user_id]);
$cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);
$cart_count = count($cart_items);

// Handle form submission for profile update
$update_success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $gender = $_POST['gender'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $state = $_POST['state'];
    $country = $_POST['country'];
    
    $update_stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, gender = ?, phone = ?, email = ?, state = ?, country = ?, updated_at = NOW() WHERE id = ?");
    if ($update_stmt->execute([$first_name, $last_name, $gender, $phone, $email, $state, $country, $user_id])) {
        $update_success = true;
        
        // Update session data
        $_SESSION['full_name'] = $first_name . ' ' . $last_name;
        $_SESSION['email'] = $email;
        
        // Refresh user data
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $target_dir = __DIR__ . "/../uploads/profile_pictures/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Get file info
    $file_name = $_FILES["profile_picture"]["name"];
    $file_tmp = $_FILES["profile_picture"]["tmp_name"];
    $file_size = $_FILES["profile_picture"]["size"];
    $file_error = $_FILES["profile_picture"]["error"];
    
    // Check if file is uploaded successfully
    if ($file_error === UPLOAD_ERR_OK) {
        // Check if file is an actual image
        $check = getimagesize($file_tmp);
        if ($check !== false) {
            // Get file extension
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
            
            if (in_array($file_extension, $allowed_extensions)) {
                // Check file size (2MB max)
                if ($file_size <= 2 * 1024 * 1024) {
                    // Generate unique filename
                    $new_filename = "user_" . $user_id . "_" . time() . "." . $file_extension;
                    $target_file = $target_dir . $new_filename;
                    
                    // Delete old profile picture if exists
                    if (!empty($user['profile_picture']) && file_exists($target_dir . $user['profile_picture'])) {
                        unlink($target_dir . $user['profile_picture']);
                    }
                    
                    if (move_uploaded_file($file_tmp, $target_file)) {
                        // Update database with just the filename
                        $picture_update_stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                        if ($picture_update_stmt->execute([$new_filename, $user_id])) {
                            // Refresh user data
                            $stmt->execute([$user_id]);
                            $user = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            // Force refresh by redirecting
                            header("Location: " . $_SERVER['PHP_SELF'] . "?picture_updated=1");
                            exit();
                        } else {
                            $picture_error = "Database update failed.";
                        }
                    } else {
                        $picture_error = "Sorry, there was an error uploading your file.";
                    }
                } else {
                    $picture_error = "File size must be less than 2MB.";
                }
            } else {
                $picture_error = "Only JPG, JPEG, PNG & GIF files are allowed.";
            }
        } else {
            $picture_error = "File is not a valid image.";
        }
    } else {
        $picture_error = "File upload error: " . $file_error;
    }
}

// Handle business account creation
// Handle business account creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_business'])) {
    // Check if user already has a merchant account
    $check_stmt = $pdo->prepare("SELECT * FROM merchants WHERE email = ?");
    $check_stmt->execute([$user['email']]);
    $existing_merchant = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existing_merchant) {
        // Check if required merchant fields are provided
        if (!empty($_POST['company_name']) && !empty($_POST['company_address']) && !empty($_POST['business_type']) && !empty($_POST['nin_passport']) && !empty($_POST['services']) && !empty($_POST['selfie_picture'])) {
            // Handle selfie picture upload
            $selfie_data = $_POST['selfie_picture'];
            $selfie_filename = "merchant_" . $user_id . "_" . time() . ".jpg";
            $selfie_target_file = "../uploads/merchant_pictures/" . $selfie_filename;
            
            // Create directory if it doesn't exist
            if (!file_exists("../uploads/merchant_pictures/")) {
                mkdir("../uploads/merchant_pictures/", 0777, true);
            }
            
            // Convert base64 to image file
            if (strpos($selfie_data, 'data:image') === 0) {
                list($type, $selfie_data) = explode(';', $selfie_data);
                list(, $selfie_data) = explode(',', $selfie_data);
                $selfie_data = base64_decode($selfie_data);
                
                if (file_put_contents($selfie_target_file, $selfie_data)) {
                    // Get phone code from country
                    $phone_code_stmt = $pdo->prepare("SELECT phone_code FROM countries WHERE code = ?");
                    $phone_code_stmt->execute([$user['country']]);
                    $phone_code_result = $phone_code_stmt->fetch(PDO::FETCH_ASSOC);
                    $phone_code = $phone_code_result ? $phone_code_result['phone_code'] : '+234';
                    
                    // Convert services array to string
                    $services_string = is_array($_POST['services']) ? implode(', ', $_POST['services']) : $_POST['services'];
                    
                    // Create merchant account but don't migrate yet - wait for admin approval
                    $migrate_stmt = $pdo->prepare("INSERT INTO merchants (company_name, company_address, business_type, owners_name, email, country, state, phone_code, phone, nin_passport, services, password, picture_path, user_type, is_active, is_approved, account_status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'merchant', 0, 0, 'pending', NOW(), NOW())");
                    
                    if ($migrate_stmt->execute([
                        $_POST['company_name'],
                        $_POST['company_address'],
                        $_POST['business_type'],
                        $user['first_name'] . ' ' . $user['last_name'],
                        $user['email'],
                        $user['country'],
                        $user['state'],
                        $phone_code,
                        $user['phone'],
                        $_POST['nin_passport'],
                        $services_string,
                        $user['password'],
                        $selfie_filename
                    ])) {
                        // Set success message but don't log them in as merchant
                        $_SESSION['migration_success'] = "Your business account application has been submitted successfully! Please wait for admin approval. You will receive an email once your account is approved.";
                        
                        // Redirect back to profile page with success message
                        header("Location: " . $_SERVER['PHP_SELF'] . "?migration_success=1");
                        exit();
                    } else {
                        $business_error = "Failed to create business account. Please try again.";
                    }
                } else {
                    $business_error = "Failed to save selfie picture. Please try again.";
                }
            } else {
                $business_error = "Invalid selfie picture format.";
            }
        } else {
            $business_error = "Please fill all required business information including company address, business type, and selfie picture.";
        }
    } else {
        $business_error = "You already have a business account with this email.";
    }
}
// Get profile picture URL with cache busting
$profile_picture_url = !empty($user['profile_picture']) 
    ? '../uploads/profile_pictures/' . $user['profile_picture'] . '?v=' . time() 
    : 'https://via.placeholder.com/150/28a745/ffffff?text=' . urlencode(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Profile</title>
    <link rel="stylesheet" href="../assets/css/user_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
        }

        .container {
            display: flex;
            flex-direction: row;
            gap: 20px;
        }

        .sidebar {
            background-color: #28a745;
            width: 280px;
            padding: 20px;
            color: white;
            min-height: 100vh;
            position: sticky;
            top: 0;
            transition: transform 0.3s ease;
            z-index: 1000;
        }

        .sidebar-header {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin: 15px 0;
        }

        .sidebar-menu a {
            color: white;
            text-decoration: none;
            font-size: 18px;
            display: block;
            padding: 12px;
            border-radius: 5px;
            transition: background-color 0.3s;
            white-space: nowrap;
        }

        .sidebar-menu a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.2);
            font-weight: bold;
        }

        .sidebar-menu i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }

        .sidebar-logo {
            width: 80%;
            max-width: 200px;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        /* Fixed Mobile Menu Toggle Styles */
        .mobile-menu-toggle {
            display: none;
            background: #2c3e50;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1002;
            width: 45px;
            height: 45px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
            align-items: center;
            justify-content: center;
        }

        .mobile-menu-toggle:hover {
            background: #34495e;
            transform: scale(1.05);
        }

        #closeSidebar {
            background: #2c3e50 !important;
            color: white !important;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: none;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1003;
        }

        #closeSidebar:hover {
            background: #34495e !important;
            transform: scale(1.05);
        }

        /* Overlay for mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        .content {
            flex-grow: 1;
            padding: 40px;
            position: relative;
        }

        .personal-data {
            background-color: transparent;
            padding: 30px;
            border-radius: 8px;
            border: 1px solid rgb(114, 111, 111);
            margin-bottom: 30px;
        }

        .personal-data h2 {
            font-size: 28px;
            margin-bottom: 20px;
        }

        .personal-data p {
            font-size: 16px;
            color: #555;
            margin-bottom: 30px;
        }

        .form-row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            flex: 1;
        }

        form label {
            display: block;
            margin-bottom: 8px;
            font-size: 16px;
            font-weight: bold;
        }

        form input, form select, form textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 16px;
        }

        .submit-button {
            background-color: #28a745;
            color: white;
            padding: 12px 20px;
            border: none;
            font-size: 18px;
            cursor: pointer;
            width: 100%;
            border-radius: 5px;
            transition: background-color 0.3s;
            margin-top: 10px;
        }

        .submit-button:hover {
            background-color: #218838;
        }

        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .profile-info {
            display: flex;
            align-items: center;
            position: relative;
        }

        .profile-pic {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            margin-right: 20px;
            object-fit: cover;
            border: 2px solid #28a745;
        }

        .profile-pic-edit {
            position: absolute;
            bottom: 5px;
            left: 50px;
            background: #28a745;
            color: white;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 2px solid white;
            font-size: 12px;
        }

        .profile-details h3 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .profile-details p {
            font-size: 16px;
            color: #777;
        }

        .profile-buttons {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .favorite-button, .cart-button {
            background-color: transparent;
            color: #28a745;
            padding: 10px;
            border: 2px solid #28a745;
            font-size: 20px;
            cursor: pointer;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            position: relative;
        }

        .favorite-button:hover, .cart-button:hover {
            background-color: #28a745;
            color: white;
        }

        .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .edit-button {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .edit-button:hover {
            background-color: #218838;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 800px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }

        .close-button {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #777;
        }

        .close-button:hover {
            color: #333;
        }

        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .cancel-button {
            background-color: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .cancel-button:hover {
            background-color: #5a6268;
        }

        .save-button {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .save-button:hover {
            background-color: #218838;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .picture-upload-form {
            text-align: center;
            margin-bottom: 20px;
        }

        .picture-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 20px;
            display: block;
            border: 3px solid #28a745;
        }

        /* Professional Business Account Styles */
        .business-account-section {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 16px;
            padding: 40px;
            margin: 40px 0;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            border: 1px solid #e8f5e8;
            position: relative;
            overflow: hidden;
        }

        .business-account-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #28a745, #20c997, #28a745);
        }

        .business-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .business-header h3 {
            color: #2c3e50;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .business-header p {
            color: #6c757d;
            font-size: 18px;
            font-weight: 400;
        }

        /* Step Indicator */
        .business-steps {
            position: relative;
        }

        .step-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 50px;
            position: relative;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        .step-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #e9ecef;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            border: 3px solid #e9ecef;
            transition: all 0.3s ease;
            margin-bottom: 12px;
        }

        .step.active .step-circle {
            background: #28a745;
            color: white;
            border-color: #28a745;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }

        .step-label {
            font-size: 14px;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .step.active .step-label {
            color: #28a745;
        }

        .step-connector {
            width: 120px;
            height: 3px;
            background: #e9ecef;
            margin: 0 20px;
            position: relative;
            top: -25px;
        }

        /* Form Steps */
        .form-step {
            display: none;
            animation: fadeInUp 0.5s ease;
        }

        .form-step.active {
            display: block;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .step-content {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
        }

        .step-content h4 {
            color: #2c3e50;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .step-description {
            color: #6c757d;
            font-size: 16px;
            margin-bottom: 30px;
        }

        /* Form Styles - FIXED Company Name Width */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }

        .form-group label.required::after {
            content: ' *';
            color: #e74c3c;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: white;
        }

        /* Fix for company name input width */
        #company_name {
            width: 100% !important;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-group small {
            display: block;
            margin-top: 6px;
            color: #6c757d;
            font-size: 12px;
        }

        /* Services Grid */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 30px 0;
        }

        .service-card {
            position: relative;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            background: white;
        }

        .service-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }

        .service-checkbox:checked + .service-label {
            border-color: #28a745;
            background: linear-gradient(135deg, #f8fff9 0%, #e8f5e9 100%);
        }

        .service-checkbox {
            display: none;
        }

        .service-label {
            display: flex;
            flex-direction: column;
            padding: 24px;
            cursor: pointer;
            height: 100%;
            transition: all 0.3s ease;
        }

        .service-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #28a745, #20c997);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            color: white;
            font-size: 24px;
        }

        .service-content h5 {
            color: #2c3e50;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .service-content p {
            color: #6c757d;
            font-size: 13px;
            line-height: 1.4;
            margin: 0;
        }

        /* Camera Section */
        .selfie-section {
            margin: 30px 0;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 12px;
            border: 1px solid #e9ecef;
        }

        .selfie-section h5 {
            color: #2c3e50;
            font-size: 18px;
            margin-bottom: 8px;
        }

        .selfie-section > p {
            color: #6c757d;
            margin-bottom: 20px;
        }

        .camera-setup {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            align-items: start;
        }

        .camera-container {
            background: transparent;
            border-radius: 12px;
            padding: 24px;
            border: 2px dashed #dee2e6;
            margin-left: -25px;
        }

        .camera-view {
            position: relative;
            width: 100%;
            height: 300px;
            background: #000;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .camera-view video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .camera-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .face-guide {
            width: 200px;
            height: 200px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
        }

        .camera-controls {
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .camera-btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .camera-btn.primary {
            background: #007bff;
            color: white;
        }

        .camera-btn.success {
            background: #28a745;
            color: white;
        }

        .camera-btn.warning {
            background: #ffc107;
            color: #212529;
        }

        .camera-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .camera-btn:not(:disabled):hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        /* Preview Section */
        .preview-section {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .photo-preview {
            background: transparent;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            border: 2px solid #e9ecef;
            margin-left:-25px;
        }

        .preview-header h6 {
            margin: 0 0 16px 0;
            color: #2c3e50;
            font-size: 16px;
            font-weight: 600;
        }

        .photo-preview img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            border: 2px solid #28a745;
        }

        .preview-text {
            margin: 12px 0 0 0;
            color: #6c757d;
            font-size: 14px;
        }

        .instructions {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e9ecef;
        }

        .instructions h6 {
            margin: 0 0 12px 0;
            color: #2c3e50;
            font-size: 14px;
            font-weight: 600;
        }

        .instructions ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .instructions li {
            padding: 6px 0;
            color: #495057;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }

        .instructions i {
            color: #28a745;
            font-size: 12px;
        }

        /* Terms Section */
        .terms-section {
            margin-top: 30px;
        }

        .terms-agreement {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .terms-agreement input[type="checkbox"] {
            margin-top: 2px;
        }

        .terms-agreement label {
            margin: 0;
            font-size: 14px;
            line-height: 1.5;
            color: #495057;
        }

        .terms-agreement a {
            color: #28a745;
            text-decoration: none;
            font-weight: 600;
        }

        .terms-agreement a:hover {
            text-decoration: underline;
        }

        /* Step Actions */
        .step-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #e9ecef;
        }

        .btn-prev, .btn-next, .btn-submit {
            padding: 14px 32px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
        }

        .btn-prev {
            background: #6c757d;
            color: white;
        }

        .btn-next, .btn-submit {
            background: #28a745;
            color: white;
        }

        .btn-prev:hover, .btn-next:hover, .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }

        .btn-submit {
            background: linear-gradient(135deg, #28a745, #20c997);
        }

        /* Migration Notice */
        .migration-notice {
            background: linear-gradient(135deg, #e3f2fd, #e8f5e8);
            border: 1px solid #bbdefb;
            border-radius: 12px;
            padding: 24px;
            margin-top: 30px;
        }

        .notice-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .notice-header i {
            color: #1976d2;
            font-size: 20px;
        }

        .notice-header h5 {
            margin: 0;
            color: #1976d2;
            font-size: 16px;
        }

        .notice-content p {
            margin: 0 0 12px 0;
            color: #2c3e50;
            font-weight: 600;
        }

        .notice-content ul {
            margin: 0;
            padding-left: 20px;
            color: #495057;
        }

        .notice-content li {
            margin-bottom: 6px;
            line-height: 1.4;
        }

        /* Wishlist and Cart Modal Styles */
        .items-container {
            max-height: 400px;
            overflow-y: auto;
            margin: 20px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
        }

        .item-card {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 5px;
            margin-bottom: 10px;
            background: #f9f9f9;
            transition: all 0.3s;
        }

        .item-card:hover {
            background: #f0f0f0;
            border-color: #28a745;
        }

        .item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 15px;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }

        .item-price {
            color: #28a745;
            font-weight: bold;
        }

        .item-quantity {
            color: #666;
            font-size: 14px;
        }

        .remove-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }

        .remove-btn:hover {
            background: #c82333;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .empty-state i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
        }
/* Ensure company address and business description are on same row */
@media (min-width: 769px) {
    .form-grid .form-group:first-child:nth-last-child(2),
    .form-grid .form-group:first-child:nth-last-child(2) ~ .form-group {
        flex: 1;
    }
}
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: flex;
            }

            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                transform: translateX(-100%);
                width: 280px;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .sidebar-overlay.active {
                display: block;
            }

            .container {
                flex-direction: column;
            }

            .content {
                padding: 70px 20px 20px 20px; /* Added top padding for mobile menu */
                margin-left: 0;
            }

            .form-row {
                flex-direction: column;
            }

            .profile-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .profile-buttons {
                width: 100%;
                justify-content: space-between;
            }

            .modal-content {
                width: 95%;
                padding: 20px;
            }

            .services-grid {
                grid-template-columns: 1fr;
            }

            .camera-setup {
                grid-template-columns: 1fr;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .step-indicator {
                flex-direction: column;
                gap: 20px;
            }

            .step-connector {
                width: 3px;
                height: 40px;
            }
        }

        @media (max-width: 480px) {
            .mobile-menu-toggle {
                top: 10px;
                left: 10px;
                width: 40px;
                height: 40px;
                font-size: 18px;
            }

            .content {
                padding: 60px 15px 15px 15px;
            }

            .sidebar-menu li {
                flex: 1 0 100%;
            }

            .profile-info {
                flex-direction: column;
                text-align: center;
            }

            .profile-pic {
                margin-right: 0;
                margin-bottom: 10px;
            }

            .profile-buttons {
                flex-direction: row;
                width: 100%;
            }

            .item-card {
                flex-direction: column;
                text-align: center;
            }

            .item-image {
                margin-right: 0;
                margin-bottom: 10px;
            }

            .business-account-section {
                padding: 20px;
            }

            .step-content {
                padding: 20px;
            }
        }

        @media (max-width: 768px) {
    .profile-info {
        flex-direction: column;
        text-align: center;
    }
    
    .profile-pic {
        margin-right: 0;
        margin-bottom: 15px;
    }
    
    .profile-pic-edit {
        left: 50%;
        transform: translateX(-50%);
        bottom: -5px;
        top: 60px;
    }
    
    .profile-details {
        width: 100%;
    }
}
    </style>
</head>
<body>
    <!-- Mobile Menu Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <img src="https://leo.it.tab.digital/s/H5qHAKxTQHzXsyo/preview" alt="BoseaAfrica Logo" class="sidebar-logo">
                </div>
                <button class="mobile-menu-toggle" id="closeSidebar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <ul class="sidebar-menu">
                <li><a href="../index.php"><i class="fa fa-home"></i> Home page</a></li>
                <li><a href="dashboard.php" class="active"><i class="fa fa-user"></i> Dashboard</a></li>
                <li><a href="profile.php" class=""><i class="fa fa-user"></i> Profile</a></li>
                <li><a href="my_orders.php"><i class="fa fa-box"></i> My Orders</a></li>
                <li>
                    <a href="notifications.php">
                        <i class="fa fa-bell"></i> Notifications 
                        <?php 
                        $unread_count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
                        $unread_count_stmt->execute([$user_id]);
                        $unread_count = $unread_count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                        
                        if ($unread_count > 0): ?>
                            <span style="background: #dc3545; color: white; border-radius: 50%; width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; margin-left: 5px;">
                                <?php echo $unread_count; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
                <li><a href="discount_offers.php"><i class="fa fa-tags"></i> Discount Offers</a></li>
                <li><a href="payments.php"><i class="fa fa-credit-card"></i> Payment</a></li>
                <li><a href="../logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="content">
            <h2>Personal Data</h2>
            <p>Enter your personal data so that you do not have to fill it in manually when placing an order.</p>

            <!-- Success/Error Messages -->
            <?php if (isset($update_success) && $update_success): ?>
                <div class="alert alert-success">Profile updated successfully!</div>
            <?php endif; ?>
            
            <?php if (isset($_GET['picture_updated'])): ?>
                <div class="alert alert-success">Profile picture updated successfully!</div>
            <?php endif; ?>
            
            <?php if (isset($_GET['migration_success']) || isset($_SESSION['migration_success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    $message = $_SESSION['migration_success'] ?? "Business account application submitted successfully! Waiting for admin approval.";
                    echo htmlspecialchars($message);
                    unset($_SESSION['migration_success']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($picture_error)): ?>
                <div class="alert alert-error"><?php echo $picture_error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($business_error)): ?>
                <div class="alert alert-error"><?php echo $business_error; ?></div>
            <?php endif; ?>

            <!-- Profile Section with buttons, after the Personal Data -->
            <div class="profile-header">
                <div class="profile-info">
                    <img src="<?php echo $profile_picture_url; ?>" 
                         alt="Profile Picture" class="profile-pic" id="profilePicture">
                    <div class="profile-pic-edit" id="openPictureModal">
                        <i class="fas fa-camera"></i>
                    </div>
                    <div class="profile-details">
                        <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>
                <div class="profile-buttons">
                    <button class="favorite-button" id="openWishlistModal">
                        <i class="fa fa-heart"></i>
                        <?php if ($wishlist_count > 0): ?>
                            <span class="badge"><?php echo $wishlist_count; ?></span>
                        <?php endif; ?>
                    </button>
                    <button class="cart-button" id="openCartModal">
                        <i class="fa fa-shopping-cart"></i>
                        <?php if ($cart_count > 0): ?>
                            <span class="badge"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </button>
                    <button class="edit-button" id="openEditModal">Edit</button>
                </div>
            </div>

            <!-- Personal Data Section -->
            <div class="personal-data">
                <form action="#" method="POST" id="profileForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first-name">First Name</label>
                            <input type="text" id="first-name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="last-name">Last Name</label>
                            <input type="text" id="last-name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender" disabled>
                                <option value="male" <?php echo (isset($user['gender']) && $user['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo (isset($user['gender']) && $user['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo (isset($user['gender']) && $user['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="state">State</label>
                            <input type="text" id="state" name="state" value="<?php echo htmlspecialchars($user['state']); ?>" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="country">Country of Residence</label>
                        <select id="country" name="country" disabled>
                            <?php foreach ($countries as $country): ?>
                                <option value="<?php echo htmlspecialchars($country['code']); ?>" 
                                    <?php echo (isset($user['country']) && $user['country'] == $country['code']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($country['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Professional Business Account Section -->
                    <div class="business-account-section">
                        <div class="business-header">
                            <h3><i class="fas fa-building"></i> Upgrade to Business Account</h3>
                            <p>Expand your reach and start selling on our platform</p>
                        </div>

                        <div class="business-steps">
                            <!-- Step Indicator -->
                            <div class="step-indicator">
                                <div class="step active" data-step="1">
                                    <div class="step-circle">1</div>
                                    <span class="step-label">Business Info</span>
                                </div>
                                <div class="step-connector"></div>
                                <div class="step" data-step="2">
                                    <div class="step-circle">2</div>
                                    <span class="step-label">Services</span>
                                </div>
                                <div class="step-connector"></div>
                                <div class="step" data-step="3">
                                    <div class="step-circle">3</div>
                                    <span class="step-label">Verification</span>
                                </div>
                            </div>

                            <form action="#" method="POST" id="businessAccountForm">
                                <!-- Step 1: Business Information -->
                                <!-- Step 1: Business Information -->
<div class="form-step active" id="step-1">
    <div class="step-content">
        <h4>Business Information</h4>
        <p class="step-description">Tell us about your company</p>
        
        <div class="form-grid">
            <div class="form-group">
                <label for="company_name" class="required">Company Name</label>
                <input type="text" id="company_name" name="company_name" 
                       placeholder="Enter your company or brand name" required>
                <small>This name will be visible to customers</small>
            </div>
            
            <div class="form-group">
                <label for="business_type" class="required">Business Type</label>
                <select id="business_type" name="business_type" required>
                    <option value="">Select business type</option>
                    <option value="individual">Individual / Sole Proprietor</option>
                    <option value="partnership">Partnership</option>
                    <option value="corporation">Corporation</option>
                    <option value="llc">Limited Liability Company (LLC)</option>
                </select>
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="company_address" class="required">Company Address</label>
                <textarea id="company_address" name="company_address" 
                          placeholder="Enter your company's physical address..."
                          rows="3" required></textarea>
                <small>Your business location address</small>
            </div>
            
            <div class="form-group">
                <label for="business_description">Business Description</label>
                <textarea id="business_description" name="business_description" 
                          placeholder="Briefly describe your business, products, or services..."
                          rows="3"></textarea>
                <small>Help customers understand what you offer (optional)</small>
            </div>
        </div>
    </div>

    <div class="step-actions">
        <button type="button" class="btn-next" onclick="nextStep(2)">
            Continue to Services <i class="fas fa-arrow-right"></i>
        </button>
    </div>
</div>
                                <!-- Step 2: Services Selection -->
                                <div class="form-step" id="step-2">
                                    <div class="step-content">
                                        <h4>Services & Offerings</h4>
                                        <p class="step-description">Select the services you want to provide</p>

                                        <div class="services-grid">
                                            <div class="service-card" data-service="food">
                                                <input type="checkbox" id="service_food" name="services[]" value="Food" class="service-checkbox">
                                                <label for="service_food" class="service-label">
                                                    <div class="service-icon">
                                                        <i class="fas fa-utensils"></i>
                                                    </div>
                                                    <div class="service-content">
                                                        <h5>Food & Restaurant</h5>
                                                        <p>Food delivery, restaurant bookings, catering services</p>
                                                    </div>
                                                </label>
                                            </div>

                                            <div class="service-card" data-service="hotel">
                                                <input type="checkbox" id="service_hotel" name="services[]" value="Hotel" class="service-checkbox">
                                                <label for="service_hotel" class="service-label">
                                                    <div class="service-icon">
                                                        <i class="fas fa-hotel"></i>
                                                    </div>
                                                    <div class="service-content">
                                                        <h5>Hotel & Accommodation</h5>
                                                        <p>Hotel bookings, vacation rentals, hostels</p>
                                                    </div>
                                                </label>
                                            </div>

                                            <div class="service-card" data-service="flight">
                                                <input type="checkbox" id="service_flight" name="services[]" value="Flight" class="service-checkbox">
                                                <label for="service_flight" class="service-label">
                                                    <div class="service-icon">
                                                        <i class="fas fa-plane"></i>
                                                    </div>
                                                    <div class="service-content">
                                                        <h5>Flight Booking</h5>
                                                        <p>Domestic and international flight reservations</p>
                                                    </div>
                                                </label>
                                            </div>

                                            <div class="service-card" data-service="car">
                                                <input type="checkbox" id="service_car" name="services[]" value="Car" class="service-checkbox">
                                                <label for="service_car" class="service-label">
                                                    <div class="service-icon">
                                                        <i class="fas fa-car"></i>
                                                    </div>
                                                    <div class="service-content">
                                                        <h5>Car Rental</h5>
                                                        <p>Vehicle rentals, chauffeur services</p>
                                                    </div>
                                                </label>
                                            </div>

                                            <div class="service-card" data-service="events">
                                                <input type="checkbox" id="service_events" name="services[]" value="Events" class="service-checkbox">
                                                <label for="service_events" class="service-label">
                                                    <div class="service-icon">
                                                        <i class="fas fa-calendar-alt"></i>
                                                    </div>
                                                    <div class="service-content">
                                                        <h5>Events & Activities</h5>
                                                        <p>Event tickets, tours, experiences</p>
                                                    </div>
                                                </label>
                                            </div>

                                           <!-- <div class="service-card" data-service="tours">
                                                <input type="checkbox" id="service_tours" name="services[]" value="Tours" class="service-checkbox" disabled>
                                                <label for="service_tours" class="service-label">
                                                    <div class="service-icon">
                                                        <i class="fas fa-map-marked-alt"></i>
                                                    </div>
                                                   <div class="service-content" >
                                                        <h5>Tours & Guides</h5>
                                                        <p>Tour packages, travel guides</p>
                                                    </div> 
                                                </label>
                                            </div>-->
                                        </div>
                                    </div>

                                    <div class="step-actions">
                                        <button type="button" class="btn-prev" onclick="prevStep(1)">
                                            <i class="fas fa-arrow-left"></i> Back
                                        </button>
                                        <button type="button" class="btn-next" onclick="nextStep(3)">
                                            Continue to Verification <i class="fas fa-arrow-right"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Step 3: Verification -->
                                <div class="form-step" id="step-3">
                                    <div class="step-content">
                                        <h4>Identity Verification</h4>
                                        <p class="step-description">Verify your identity for security purposes</p>

                                        <div class="verification-section">
                                            <div class="form-grid">
                                                <div class="form-group">
                                                    <label for="nin_passport" class="required">NIN/Passport Number</label>
                                                    <input type="text" id="nin_passport" name="nin_passport" 
                                                           placeholder="Enter your NIN or passport number" required>
                                                    <small>For identity verification only</small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="business_phone" class="required">Business Phone</label>
                                                    <input type="tel" id="business_phone" name="business_phone" 
                                                           value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                                                </div>
                                            </div>

                                            <div class="selfie-section">
                                                <h5>Live Selfie Verification</h5>
                                                <p>Take a live selfie for identity confirmation</p>

                                                <div class="camera-setup">
                                                    <div class="camera-container">
                                                        <div class="camera-view">
                                                            <video id="video" autoplay playsinline style="display: none;"></video>
                                                            <canvas id="canvas" style="display: none;"></canvas>
                                                            <div class="camera-overlay">
                                                                <div class="face-guide"></div>
                                                            </div>
                                                        </div>
                                                        <div class="camera-controls">
                                                            <button type="button" id="startCamera" class="camera-btn primary">
                                                                <i class="fas fa-camera"></i> Start Camera
                                                            </button>
                                                            <button type="button" id="captureBtn" class="camera-btn success" disabled>
                                                                <i class="fas fa-camera-retro"></i> Capture Photo
                                                            </button>
                                                            <button type="button" id="retakeBtn" class="camera-btn warning" style="display: none;">
                                                                <i class="fas fa-redo"></i> Retake
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <div class="preview-section">
                                                        <div id="preview" class="photo-preview" style="display: none;">
                                                            <div class="preview-header">
                                                                <h6>Photo Preview</h6>
                                                            </div>
                                                            <img id="photoPreview" src="" alt="Captured Photo">
                                                            <p class="preview-text">Make sure your face is clearly visible</p>
                                                        </div>
                                                        
                                                        <div class="instructions">
                                                            <h6>Photo Requirements:</h6>
                                                            <ul>
                                                                <li><i class="fas fa-check"></i> Good lighting</li>
                                                                <li><i class="fas fa-check"></i> Face the camera directly</li>
                                                                <li><i class="fas fa-check"></i> Neutral expression</li>
                                                                <li><i class="fas fa-check"></i> No sunglasses or hats</li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <input type="hidden" id="selfieData" name="selfie_picture" required>
                                            </div>
                                        </div>

                                        <div class="terms-section">
                                            <div class="terms-agreement">
                                                <input type="checkbox" id="agree_terms" name="agree_terms" required>
                                                <label for="agree_terms">
                                                    I agree to the <a href="#" target="_blank">Terms of Service</a> and 
                                                    <a href="#" target="_blank">Merchant Agreement</a>. I understand that my 
                                                    user account will be migrated to a merchant account after admin approval.
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="step-actions">
                                        <button type="button" class="btn-prev" onclick="prevStep(2)">
                                            <i class="fas fa-arrow-left"></i> Back
                                        </button>
                                        <button type="submit" class="btn-submit" name="create_business">
                                            <i class="fas fa-check-circle"></i> Submit for Approval
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="migration-notice">
                            <div class="notice-header">
                                <i class="fas fa-info-circle"></i>
                                <h5>Account Migration Notice</h5>
                            </div>
                            <div class="notice-content">
                                <p><strong>After submitting your business account application:</strong></p>
                                <ul>
                                    <li>Your application will be reviewed by our admin team</li>
                                    <li>You'll receive an email notification once approved</li>
                                    <li>After approval, your account will be upgraded to merchant status</li>
                                    <li>You'll gain access to the merchant dashboard</li>
                                    <li>Your current user account remains active during review</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <span class="close-button" id="closeEditModal">&times;</span>
            <h2>Edit Profile</h2>
            <form action="#" method="POST" id="editProfileForm">
                <input type="hidden" name="update_profile" value="1">
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-first-name">First Name *</label>
                        <input type="text" id="edit-first-name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-last-name">Last Name *</label>
                        <input type="text" id="edit-last-name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-gender">Gender *</label>
                        <select id="edit-gender" name="gender" required>
                            <option value="male" <?php echo (isset($user['gender']) && $user['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo (isset($user['gender']) && $user['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?php echo (isset($user['gender']) && $user['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-phone">Phone Number *</label>
                        <input type="text" id="edit-phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-email">Email *</label>
                        <input type="email" id="edit-email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-state">State *</label>
                        <select id="edit-state" name="state" required>
                            <?php foreach ($states as $state): ?>
                                <option value="<?php echo htmlspecialchars($state['name']); ?>" 
                                    <?php echo (isset($user['state']) && $user['state'] == $state['name']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($state['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit-country">Country of Residence *</label>
                    <select id="edit-country" name="country" required>
                        <?php foreach ($countries as $country): ?>
                            <option value="<?php echo htmlspecialchars($country['code']); ?>" 
                                <?php echo (isset($user['country']) && $user['country'] == $country['code']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($country['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="cancel-button" id="cancelEdit">Cancel</button>
                    <button type="submit" class="save-button">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Picture Modal -->
    <div class="modal" id="pictureModal">
        <div class="modal-content">
            <span class="close-button" id="closePictureModal">&times;</span>
            <h2>Change Profile Picture</h2>
            <form action="#" method="POST" enctype="multipart/form-data" id="pictureForm">
                <div class="picture-upload-form">
                    <img id="picturePreview" src="<?php echo $profile_picture_url; ?>" 
                         alt="Profile Picture Preview" class="picture-preview">
                    <input type="file" id="profile_picture" name="profile_picture" accept="image/*" required>
                    <p><small>Supported formats: JPG, PNG, GIF. Max size: 2MB</small></p>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="cancel-button" id="cancelPicture">Cancel</button>
                    <button type="submit" class="save-button">Upload Picture</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Wishlist Modal -->
    <div class="modal" id="wishlistModal">
        <div class="modal-content">
            <span class="close-button" id="closeWishlistModal">&times;</span>
            <h2>My Wishlist</h2>
            <div class="items-container">
                <?php if ($wishlist_count > 0): ?>
                    <?php foreach ($wishlist_items as $item): ?>
                        <div class="item-card">
                            <img src="<?php echo !empty($item['product_image']) ? htmlspecialchars($item['product_image']) : 'https://via.placeholder.com/60/28a745/ffffff?text=Product'; ?>" 
                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="item-image">
                            <div class="item-details">
                                <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                <div class="item-price">₦<?php echo number_format($item['product_price'], 2); ?></div>
                            </div>
                            <button class="remove-btn" onclick="removeFromWishlist(<?php echo $item['id']; ?>)">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-heart"></i>
                        <h3>Your wishlist is empty</h3>
                        <p>Start adding items you love to your wishlist!</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-buttons">
                <button type="button" class="cancel-button" id="closeWishlistBtn">Close</button>
            </div>
        </div>
    </div>

    <!-- Cart Modal -->
    <div class="modal" id="cartModal">
        <div class="modal-content">
            <span class="close-button" id="closeCartModal">&times;</span>
            <h2>My Shopping Cart</h2>
            <div class="items-container">
                <?php if ($cart_count > 0): ?>
                    <?php 
                    $total_amount = 0;
                    foreach ($cart_items as $item): 
                        $total_amount += $item['product_price'] * $item['quantity'];
                    ?>
                        <div class="item-card">
                            <img src="<?php echo !empty($item['product_image']) ? htmlspecialchars($item['product_image']) : 'https://via.placeholder.com/60/28a745/ffffff?text=Product'; ?>" 
                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="item-image">
                            <div class="item-details">
                                <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                <div class="item-price">₦<?php echo number_format($item['product_price'], 2); ?></div>
                                <div class="item-quantity">Quantity: <?php echo $item['quantity']; ?></div>
                            </div>
                            <button class="remove-btn" onclick="removeFromCart(<?php echo $item['id']; ?>)">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </div>
                    <?php endforeach; ?>
                    <div style="text-align: right; margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                        <strong>Total: ₦<?php echo number_format($total_amount, 2); ?></strong>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>Your cart is empty</h3>
                        <p>Add some items to get started!</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-buttons">
                <button type="button" class="cancel-button" id="closeCartBtn">Close</button>
                <?php if ($cart_count > 0): ?>
                    <button type="button" class="save-button" onclick="proceedToCheckout()">Proceed to Checkout</button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Mobile sidebar functionality
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const closeSidebar = document.getElementById('closeSidebar');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        function openSidebar() {
            sidebar.classList.add('active');
            sidebarOverlay.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        }

        function closeSidebarFunc() {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = ''; // Restore scrolling
        }

        mobileMenuToggle.addEventListener('click', openSidebar);
        closeSidebar.addEventListener('click', closeSidebarFunc);

        // Close sidebar when clicking on overlay
        sidebarOverlay.addEventListener('click', closeSidebarFunc);

        // Close sidebar when clicking on a link (mobile)
        document.querySelectorAll('.sidebar-menu a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    closeSidebarFunc();
                }
            });
        });

        // Close sidebar when pressing Escape key
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && sidebar.classList.contains('active')) {
                closeSidebarFunc();
            }
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                closeSidebarFunc();
            }
        });

        // Modal functionality
        const editModal = document.getElementById('editModal');
        const pictureModal = document.getElementById('pictureModal');
        const wishlistModal = document.getElementById('wishlistModal');
        const cartModal = document.getElementById('cartModal');
        
        const openEditModalBtn = document.getElementById('openEditModal');
        const openPictureModalBtn = document.getElementById('openPictureModal');
        const openWishlistModalBtn = document.getElementById('openWishlistModal');
        const openCartModalBtn = document.getElementById('openCartModal');
        
        const closeEditModalBtn = document.getElementById('closeEditModal');
        const closePictureModalBtn = document.getElementById('closePictureModal');
        const closeWishlistModalBtn = document.getElementById('closeWishlistModal');
        const closeCartModalBtn = document.getElementById('closeCartModal');
        
        const cancelEditBtn = document.getElementById('cancelEdit');
        const cancelPictureBtn = document.getElementById('cancelPicture');
        const closeWishlistBtn = document.getElementById('closeWishlistBtn');
        const closeCartBtn = document.getElementById('closeCartBtn');

        const profilePicture = document.getElementById('profilePicture');
        const picturePreview = document.getElementById('picturePreview');
        const pictureInput = document.getElementById('profile_picture');

        // Camera functionality
        let cameraStream = null;
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const startCameraBtn = document.getElementById('startCamera');
        const captureBtn = document.getElementById('captureBtn');
        const retakeBtn = document.getElementById('retakeBtn');
        const preview = document.getElementById('preview');
        const photoPreview = document.getElementById('photoPreview');
        const selfieData = document.getElementById('selfieData');

        // Fix for camera error - check if elements exist before accessing
        function safeCameraAccess() {
            try {
                if (!video || !canvas) {
                    console.error('Camera elements not found');
                    return false;
                }
                return true;
            } catch (error) {
                console.error('Error accessing camera elements:', error);
                return false;
            }
        }

        startCameraBtn.addEventListener('click', async function() {
            if (!safeCameraAccess()) {
                alert('Camera system not properly initialized. Please refresh the page.');
                return;
            }
            
            try {
                startCameraBtn.disabled = true;
                startCameraBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Starting Camera...';
                
                // Stop existing stream if any
                if (cameraStream) {
                    cameraStream.getTracks().forEach(track => track.stop());
                }
                
                // Get camera access
                cameraStream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        facingMode: 'user',
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    }, 
                    audio: false 
                });
                
                video.srcObject = cameraStream;
                
                // Wait for video to be ready
                video.onloadedmetadata = function() {
                    video.style.display = 'block';
                    captureBtn.disabled = false;
                    retakeBtn.style.display = 'none';
                    preview.style.display = 'none';
                    
                    startCameraBtn.innerHTML = '<i class="fas fa-camera"></i> Camera Active';
                };
                
            } catch (error) {
                console.error('Camera error:', error);
                let errorMessage = 'Error accessing camera: ';
                
                if (error.name === 'NotAllowedError') {
                    errorMessage += 'Camera permission denied. Please allow camera access and try again.';
                } else if (error.name === 'NotFoundError') {
                    errorMessage += 'No camera found. Please check if your camera is connected.';
                } else if (error.name === 'NotSupportedError') {
                    errorMessage += 'Camera not supported in this browser.';
                } else {
                    errorMessage += error.message;
                }
                
                alert(errorMessage);
                startCameraBtn.innerHTML = '<i class="fas fa-camera"></i> Start Camera';
                startCameraBtn.disabled = false;
            }
        });

        captureBtn.addEventListener('click', function() {
            if (!cameraStream || !safeCameraAccess()) {
                alert('Camera is not active. Please start the camera first.');
                return;
            }
            
            const context = canvas.getContext('2d');
            
            // Set canvas size to match video
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            // Draw current video frame to canvas
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Convert to data URL
            const imageData = canvas.toDataURL('image/jpeg', 0.8);
            
            // Show preview
            photoPreview.src = imageData;
            preview.style.display = 'block';
            
            // Store image data
            selfieData.value = imageData;
            
            // Update UI
            retakeBtn.style.display = 'inline-block';
            captureBtn.disabled = true;
            
            // Stop camera to save resources
            if (cameraStream) {
                cameraStream.getTracks().forEach(track => track.stop());
                cameraStream = null;
            }
        });

        retakeBtn.addEventListener('click', function() {
            preview.style.display = 'none';
            retakeBtn.style.display = 'none';
            captureBtn.disabled = true;
            selfieData.value = '';
            
            startCameraBtn.innerHTML = '<i class="fas fa-camera"></i> Start Camera';
            startCameraBtn.disabled = false;
        });

        // Clean up camera on page unload
        window.addEventListener('beforeunload', function() {
            if (cameraStream) {
                cameraStream.getTracks().forEach(track => track.stop());
            }
        });

        // Initialize camera elements when page loads
        document.addEventListener('DOMContentLoaded', function() {
            if (!safeCameraAccess()) {
                console.warn('Camera elements not properly initialized');
            }
        });

        // Open modals
        openEditModalBtn.addEventListener('click', () => editModal.style.display = 'flex');
        openPictureModalBtn.addEventListener('click', () => pictureModal.style.display = 'flex');
        openWishlistModalBtn.addEventListener('click', () => wishlistModal.style.display = 'flex');
        openCartModalBtn.addEventListener('click', () => cartModal.style.display = 'flex');

        // Close modals
        closeEditModalBtn.addEventListener('click', () => editModal.style.display = 'none');
        closePictureModalBtn.addEventListener('click', () => pictureModal.style.display = 'none');
        closeWishlistModalBtn.addEventListener('click', () => wishlistModal.style.display = 'none');
        closeCartModalBtn.addEventListener('click', () => cartModal.style.display = 'none');
        
        cancelEditBtn.addEventListener('click', () => editModal.style.display = 'none');
        cancelPictureBtn.addEventListener('click', () => pictureModal.style.display = 'none');
        closeWishlistBtn.addEventListener('click', () => wishlistModal.style.display = 'none');
        closeCartBtn.addEventListener('click', () => cartModal.style.display = 'none');

        // Close modal when clicking outside of it
        window.addEventListener('click', (event) => {
            if (event.target === editModal) editModal.style.display = 'none';
            if (event.target === pictureModal) pictureModal.style.display = 'none';
            if (event.target === wishlistModal) wishlistModal.style.display = 'none';
            if (event.target === cartModal) cartModal.style.display = 'none';
        });

        // Preview image before upload
        pictureInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    picturePreview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        // Form validation
        const editProfileForm = document.getElementById('editProfileForm');
        editProfileForm.addEventListener('submit', (event) => {
            const firstName = document.getElementById('edit-first-name').value.trim();
            const lastName = document.getElementById('edit-last-name').value.trim();
            const email = document.getElementById('edit-email').value.trim();
            const phone = document.getElementById('edit-phone').value.trim();
            
            if (!firstName || !lastName || !email || !phone) {
                event.preventDefault();
                alert('Please fill in all required fields.');
                return;
            }
            
            if (!isValidEmail(email)) {
                event.preventDefault();
                alert('Please enter a valid email address.');
                return;
            }
        });

        const pictureForm = document.getElementById('pictureForm');
        pictureForm.addEventListener('submit', (event) => {
            const file = pictureInput.files[0];
            if (!file) {
                event.preventDefault();
                alert('Please select a picture to upload.');
                return;
            }
            
            // Check file size (2MB max)
            if (file.size > 2 * 1024 * 1024) {
                event.preventDefault();
                alert('File size must be less than 2MB.');
                return;
            }
        });

        // Business account form validation
    // Business account form validation
const businessAccountForm = document.getElementById('businessAccountForm');
businessAccountForm.addEventListener('submit', function(event) {
    const companyName = document.getElementById('company_name').value.trim();
    const companyAddress = document.getElementById('company_address').value.trim();
    const businessType = document.getElementById('business_type').value;
    const ninPassport = document.getElementById('nin_passport').value.trim();
    const services = document.querySelectorAll('input[name="services[]"]:checked');
    const selfiePicture = document.getElementById('selfieData').value;
    const agreeTerms = document.getElementById('agree_terms').checked;
    
    if (!companyName || !companyAddress || !businessType || !ninPassport || services.length === 0 || !selfiePicture || !agreeTerms) {
        event.preventDefault();
        alert('Please fill in all required business information including company address, business type, selfie picture and agree to the terms.');
        return;
    }
});    function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        // Wishlist and Cart functionality
        function removeFromWishlist(itemId) {
            if (confirm('Are you sure you want to remove this item from your wishlist?')) {
                // AJAX call to remove from wishlist
                fetch('remove_from_wishlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'item_id=' + itemId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error removing item from wishlist.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error removing item from wishlist.');
                });
            }
        }

        function removeFromCart(itemId) {
            if (confirm('Are you sure you want to remove this item from your cart?')) {
                // AJAX call to remove from cart
                fetch('remove_from_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'item_id=' + itemId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error removing item from cart.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error removing item from cart.');
                });
            }
        }

        function proceedToCheckout() {
            window.location.href = 'checkout.php';
        }

        // Auto-hide success messages after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.display = 'none';
            });
        }, 5000);

        // Business Account Step Navigation
        function showStep(stepNumber) {
            // Hide all steps
            document.querySelectorAll('.form-step').forEach(step => {
                step.classList.remove('active');
            });
            
            // Show current step
            document.getElementById(`step-${stepNumber}`).classList.add('active');
            
            // Update step indicator
            document.querySelectorAll('.step').forEach(step => {
                step.classList.remove('active');
            });
            document.querySelector(`.step[data-step="${stepNumber}"]`).classList.add('active');
        }

        function nextStep(next) {
            const currentStep = document.querySelector('.form-step.active').id.split('-')[1];
            
            // Validate current step before proceeding
            if (validateStep(currentStep)) {
                showStep(next);
            }
        }

        function prevStep(prev) {
            showStep(prev);
        }

       function validateStep(stepNumber) {
    const step = document.getElementById(`step-${stepNumber}`);
    let isValid = true;
    
    if (stepNumber === '1') {
        const companyName = document.getElementById('company_name').value.trim();
        const companyAddress = document.getElementById('company_address').value.trim();
        const businessType = document.getElementById('business_type').value;
        
        if (!companyName) {
            alert('Please enter your company name.');
            isValid = false;
        }
        
        if (!companyAddress) {
            alert('Please enter your company address.');
            isValid = false;
        }
        
        if (!businessType) {
            alert('Please select your business type.');
            isValid = false;
        }
    }
    
    if (stepNumber === '2') {
        const services = document.querySelectorAll('input[name="services[]"]:checked');
        if (services.length === 0) {
            alert('Please select at least one service you want to offer.');
            isValid = false;
        }
    }
    
    if (stepNumber === '3') {
        const ninPassport = document.getElementById('nin_passport').value.trim();
        const selfieData = document.getElementById('selfieData').value;
        const agreeTerms = document.getElementById('agree_terms').checked;
        
        if (!ninPassport) {
            alert('Please enter your NIN/Passport number.');
            isValid = false;
        }
        
        if (!selfieData) {
            alert('Please take a selfie photo for verification.');
            isValid = false;
        }
        
        if (!agreeTerms) {
            alert('Please agree to the terms and conditions.');
            isValid = false;
        }
    }
    
    return isValid;
}

        // Initialize first step
        document.addEventListener('DOMContentLoaded', function() {
            showStep(1);
        });
    </script>
</body>
</html>
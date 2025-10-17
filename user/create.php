<?php
require_once "db_connection.php";
// Include the notification functions
require_once "notifications_functions.php"; // Create this file with the functions above

session_start();
$response = ['success' => false, 'message' => ''];

// Map country codes to names
require_once "country_mapping.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // [Your existing validation code remains the same...]
    
    // Sanitize and validate input
    $first_name = htmlspecialchars(trim($_POST['first_name']));
    $last_name = htmlspecialchars(trim($_POST['last_name']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    
    // Convert country code to full name
    $country_code = htmlspecialchars(trim($_POST['country']));
    $country = isset($countryCodeToName[$country_code]) ? $countryCodeToName[$country_code] : $country_code;
    
    $state = htmlspecialchars(trim($_POST['state']));
    $phone_code = htmlspecialchars(trim($_POST['phone_code']));
    $phone = htmlspecialchars(trim($_POST['phone']));
    $password = $_POST['password'];
    
    // [Your existing validation logic remains the same...]

    // If no errors, proceed with registration
    if (empty($errors)) {
        try {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user into database using prepared statements
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, country, state, phone_code, phone, password) 
                                   VALUES (:first_name, :last_name, :email, :country, :state, :phone_code, :phone, :password)");
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':country', $country);
            $stmt->bindParam(':state', $state);
            $stmt->bindParam(':phone_code', $phone_code);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':password', $hashed_password);
            
            if ($stmt->execute()) {
                // Get the newly created user ID
                $new_user_id = $pdo->lastInsertId();
                $user_name = $first_name . ' ' . $last_name;
                
                // ✅ CREATE NOTIFICATIONS FOR SUCCESSFUL REGISTRATION
                
                // 1. Welcome notification for the new user
                createWelcomeNotification($new_user_id, $user_name);
                
                // 2. Notify admins about new user registration
                $admins_notified = notifyAdminsAboutNewUser($new_user_id, $email, $user_name);
                
                // 3. Additional notification for the user about account setup
                $setup_title = "Get Started with Your Account";
                $setup_message = "Your account has been created successfully! ";
                $setup_message .= "Complete your profile, explore our services, and start your journey with Boseats Africa.";
                createNotification($new_user_id, $setup_title, $setup_message, 'account_setup', $new_user_id);
                
                // Set session variables
                $_SESSION['user_email'] = $email;
                $_SESSION['user_name'] = $user_name;
                $_SESSION['user_id'] = $new_user_id;

                $response['success'] = true;
                $response['message'] = "Registration successful! You can now login.";
                $response['user_id'] = $new_user_id;
                $response['admins_notified'] = $admins_notified;
                
            } else {
                $response['message'] = "Registration failed. Please try again.";
            }
            $stmt = null; // Close statement
        } catch (Exception $e) {
            $response['message'] = "Database error: " . $e->getMessage();
        }
    } else {
        $response['message'] = implode("<br>", $errors);
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>
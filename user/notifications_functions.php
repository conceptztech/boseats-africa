<?php
// Function to create notifications
function createNotification($user_id, $title, $message, $type = 'info', $related_id = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, is_read, related_id, created_at, updated_at) 
            VALUES (?, ?, ?, ?, 0, ?, NOW(), NOW())
        ");
        
        $success = $stmt->execute([$user_id, $title, $message, $type, $related_id]);
        
        if ($success) {
            error_log("Notification created: {$title} for user {$user_id}");
        }
        
        return $success;
    } catch (Exception $e) {
        error_log("Notification creation failed: " . $e->getMessage());
        return false;
    }
}

// Function to notify admins about new user registration
function notifyAdminsAboutNewUser($user_id, $user_email, $user_name) {
    global $pdo;
    
    try {
        $adminStmt = $pdo->prepare("SELECT id FROM admins WHERE is_active = 1");
        $adminStmt->execute();
        $admins = $adminStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($admins as $admin) {
            $title = "New User Registration";
            $message = "New user registered:\n";
            $message .= "• Name: {$user_name}\n";
            $message .= "• Email: {$user_email}\n";
            $message .= "• Time: " . date('Y-m-d H:i:s') . "\n";
            $message .= "• User ID: {$user_id}";
            
            createNotification($admin['id'], $title, $message, 'new_user', $user_id);
        }
        
        return count($admins);
    } catch (Exception $e) {
        error_log("Admin notification failed: " . $e->getMessage());
        return 0;
    }
}

// Function to create welcome notification for new user
function createWelcomeNotification($user_id, $user_name) {
    $title = "Welcome to Boseats Africa!";
    $message = "Hello {$user_name}, welcome to Boseats Africa! ";
    $message .= "We're excited to have you on board. ";
    $message .= "Start exploring our services and don't hesitate to contact us if you need any help.";
    
    return createNotification($user_id, $title, $message, 'welcome', $user_id);
}
?>
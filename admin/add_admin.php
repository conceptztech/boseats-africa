<?php
// Database connection
require_once "connect/db_connection.php";

// Define admin data
$email = "admin1@boseatsafrica.com"; // Change this to your desired email
$password = "admin123"; // Change this to your desired password
$full_name = "Admin User"; // Change this to the full name you want for the admin

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert the new admin into the database
try {
    $stmt = $pdo->prepare("INSERT INTO admins (email, password, full_name) VALUES (?, ?, ?)");
    $stmt->execute([$email, $hashed_password, $full_name]);

    echo "Admin added successfully!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

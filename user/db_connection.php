<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "boseatsafrica";

try {
    // Enable PDO error mode
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Display a detailed error message
    die("Connection failed: " . $e->getMessage());
}
?>

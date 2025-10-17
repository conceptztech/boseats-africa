<?php
// Fetch food items from the database using PDO
$foodData = [];
$sql = "SELECT * FROM food_items WHERE active = 1 ORDER BY name";
$stmt = $pdo->prepare($sql);

try {
    $stmt->execute();
    $foodData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($stmt->rowCount() > 0) {
        echo "";
    } else {
        echo "No food items available.";
    }
} catch (PDOException $e) {
    echo "Error executing query: " . $e->getMessage();
}
?>

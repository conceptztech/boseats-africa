<?php
// includes/hotel_api.php - API endpoint for hotel operations
header('Content-Type: application/json');
session_start();
include_once 'db_connection.php'; 

$response = ['success' => false, 'message' => 'An error occurred.'];

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

try {
    switch ($action) {
        case 'get_hotels':
            getHotels($pdo);
            break;
        case 'get_hotel_details':
            getHotelDetails($pdo);
            break;
        case 'toggle_favorite':
            toggleFavorite($pdo);
            break;
        // Add other cases as needed
        default:
            $response['message'] = 'Invalid action specified.';
            echo json_encode($response);
    }
} catch (PDOException $e) {
    error_log("API Error: " . $e->getMessage());
    $response['message'] = 'Database error.';
    echo json_encode($response);
}

function getHotels($pdo) {
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 12;
    $offset = ($page - 1) * $limit;
    
    $location = isset($_GET['location']) ? $_GET['location'] : '';
    $sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'all';
    
    $query = "SELECT h.*, 
              (SELECT image_url FROM hotel_images WHERE hotel_id = h.id AND is_primary = 1 LIMIT 1) as main_image
              FROM hotels h 
              WHERE h.status = 'active'";
    
    $params = [];
    
    if ($location) {
        $query .= " AND (h.location LIKE ? OR h.city LIKE ?)";
        $searchTerm = "%$location%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if ($sortBy === 'cheapest') {
        $query .= " ORDER BY h.price_per_night ASC";
    } elseif ($sortBy === 'best') {
        $query .= " ORDER BY h.rating DESC";
    } else {
        $query .= " ORDER BY h.created_at DESC";
    }
    
    $query .= " LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key + 1, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $hotels
    ]);
}

function getHotelDetails($pdo) {
    $hotelId = isset($_GET['hotel_id']) ? intval($_GET['hotel_id']) : 0;
    
    if (!$hotelId) {
        echo json_encode(['success' => false, 'message' => 'Hotel ID is required.']);
        return;
    }
    
    $query = "SELECT * FROM hotels WHERE id = ? AND status = 'active'";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$hotelId]);
    $hotel = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$hotel) {
        echo json_encode(['success' => false, 'message' => 'Hotel not found or is inactive.']);
        return;
    }
    
    // Fetch related data
    $imagesQuery = "SELECT image_url, is_primary FROM hotel_images WHERE hotel_id = ? ORDER BY is_primary DESC";
    $stmt = $pdo->prepare($imagesQuery);
    $stmt->execute([$hotelId]);
    $hotel['images'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $amenitiesQuery = "SELECT a.name, a.icon FROM amenities a JOIN hotel_amenities ha ON a.id = ha.amenity_id WHERE ha.hotel_id = ?";
    $stmt = $pdo->prepare($amenitiesQuery);
    $stmt->execute([$hotelId]);
    $hotel['amenities'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $roomTypesQuery = "SELECT * FROM room_types WHERE hotel_id = ? AND available = 1";
    $stmt = $pdo->prepare($roomTypesQuery);
    $stmt->execute([$hotelId]);
    $hotel['room_types'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $hotel]);
}

function toggleFavorite($pdo) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'You must be logged in to add favorites.']);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $hotelId = isset($data['hotel_id']) ? intval($data['hotel_id']) : 0;
    $userId = $_SESSION['user_id'];
    
    if (!$hotelId) {
        echo json_encode(['success' => false, 'message' => 'Invalid Hotel ID.']);
        return;
    }
    
    $checkQuery = "SELECT id FROM user_favorites WHERE user_id = ? AND item_id = ? AND item_type = 'hotel'";
    $stmt = $pdo->prepare($checkQuery);
    $stmt->execute([$userId, $hotelId]);
    
    if ($stmt->fetch()) {
        $deleteQuery = "DELETE FROM user_favorites WHERE user_id = ? AND item_id = ? AND item_type = 'hotel'";
        $stmt = $pdo->prepare($deleteQuery);
        $stmt->execute([$userId, $hotelId]);
        echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Removed from favorites.']);
    } else {
        $insertQuery = "INSERT INTO user_favorites (user_id, item_id, item_type) VALUES (?, ?, 'hotel')";
        $stmt = $pdo->prepare($insertQuery);
        $stmt->execute([$userId, $hotelId]);
        echo json_encode(['success' => true, 'action' => 'added', 'message' => 'Added to favorites.']);
    }
}
?>
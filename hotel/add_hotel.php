<?php
session_start();
include_once '../includes/db_connection.php'; 

// Check if user is logged in and is hotel owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'hotel_owner') {
    header('Location: ../login.php');
    exit;
}

$ownerId = $_SESSION['user_id'];

// Get all amenities for selection
$amenitiesQuery = "SELECT * FROM amenities ORDER BY name ASC";
$amenitiesResult = $pdo->query($amenitiesQuery);
$amenities = $amenitiesResult->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->beginTransaction();
    
    try {
        // Validate required data
        if (empty($_POST['room_types']) || !is_array($_POST['room_types'])) {
            throw new Exception("At least one room type is required");
        }
        
        if (empty($_FILES['images']['name'][0])) {
            throw new Exception("At least one hotel image is required");
        }
        
        // Get form data
        $companyName = trim($_POST['company_name']);
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $bookingrules = trim($_POST['bookingrules']);
        $location = trim($_POST['location']);
        $city = trim($_POST['city']);
        $country = trim($_POST['country']);
        $address = trim($_POST['address']);
        $latitude = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : null;
        $longitude = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : null;
        $type = $_POST['type'];
        $rating = intval($_POST['rating']);
        $pricePerNight = floatval($_POST['price_per_night']);
        $bedrooms = intval($_POST['bedrooms']);
        $bathrooms = intval($_POST['bathrooms']);
        $nights = intval($_POST['nights']);
        $featured = isset($_POST['featured']) ? 1 : 0;
        
        // Validate data
        if (empty($companyName) || empty($name) || empty($description) || empty($bookingrules)) {
            throw new Exception("All required fields must be filled");
        }
        
        if ($rating < 1 || $rating > 5) {
            throw new Exception("Rating must be between 1 and 5");
        }
        
        if ($pricePerNight <= 0 || $bedrooms <= 0 || $bathrooms <= 0 || $nights <= 0) {
            throw new Exception("Price, bedrooms, bathrooms, and nights must be greater than 0");
        }
        
        // Insert hotel
        $insertHotel = "INSERT INTO hotels (
            owner_id, company_name, name, description, bookingrules, location, city, country, 
            address, latitude, longitude, type, rating, price_per_night, bedrooms, bathrooms, 
            nights, featured, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
        
        $stmt = $pdo->prepare($insertHotel);
        $stmt->execute([
            $ownerId, $companyName, $name, $description, $bookingrules, $location, $city, $country,
            $address, $latitude, $longitude, $type, $rating, $pricePerNight, $bedrooms, $bathrooms, 
            $nights, $featured
        ]);
        
        $hotelId = $pdo->lastInsertId();
        
        // Handle image uploads
        $uploadDir = '../uploads/hotels/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $insertImage = "INSERT INTO hotel_images (hotel_id, image_url, is_primary) VALUES (?, ?, ?)";
        $imgStmt = $pdo->prepare($insertImage);
        $imageUploaded = false;

        foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                // Validate file type
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                $fileType = $_FILES['images']['type'][$key];
                
                if (!in_array($fileType, $allowedTypes)) {
                    continue; // Skip invalid file types
                }
                
                // Validate file size (max 5MB)
                if ($_FILES['images']['size'][$key] > 5242880) {
                    continue; // Skip files larger than 5MB
                }
                
                $fileExtension = strtolower(pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION));
                $fileName = time() . '_' . $key . '_' . uniqid() . '.' . $fileExtension;
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($tmpName, $targetPath)) {
                    $imageUrl = 'uploads/hotels/' . $fileName;
                    $isPrimary = ($key === 0) ? 1 : 0;
                    $imgStmt->execute([$hotelId, $imageUrl, $isPrimary]);
                    $imageUploaded = true;
                }
            }
        }
        
        // Ensure at least one image was uploaded successfully
        if (!$imageUploaded) {
            throw new Exception("Failed to upload hotel images. Please try again.");
        }
        
        // Insert selected amenities
        if (!empty($_POST['amenities']) && is_array($_POST['amenities'])) {
            $insertAmenity = "INSERT INTO hotel_amenities (hotel_id, amenity_id) VALUES (?, ?)";
            $amenityStmt = $pdo->prepare($insertAmenity);
            
            foreach ($_POST['amenities'] as $amenityId) {
                $amenityId = intval($amenityId);
                if ($amenityId > 0) {
                    $amenityStmt->execute([$hotelId, $amenityId]);
                }
            }
        }
        
        // Insert room types
        $insertRoom = "INSERT INTO room_types (hotel_id, name, description, bookingrules, price, capacity, available) VALUES (?, ?, ?, ?, ?, ?, 1)";
        $roomStmt = $pdo->prepare($insertRoom);
        $roomTypeAdded = false;
        
        foreach ($_POST['room_types'] as $room) {
            if (!empty($room['name']) && !empty($room['price'])) {
                $roomName = trim($room['name']);
                $roomDescription = trim($room['description'] ?? '');
                $roomBookingRules = trim($room['bookingrules'] ?? '');
                $roomPrice = floatval($room['price']);
                $roomCapacity = intval($room['capacity'] ?? 2);
                
                if ($roomPrice <= 0 || $roomCapacity <= 0) {
                    continue; // Skip invalid room types
                }
                
                $roomStmt->execute([
                    $hotelId,
                    $roomName,
                    $roomDescription,
                    $roomBookingRules,
                    $roomPrice,
                    $roomCapacity
                ]);
                $roomTypeAdded = true;
            }
        }
        
        // Ensure at least one room type was added
        if (!$roomTypeAdded) {
            throw new Exception("At least one valid room type must be added");
        }
        
        $pdo->commit();
        $_SESSION['success_message'] = "Hotel added successfully!";
        header('Location: dashboard.php');
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Failed to add hotel: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Hotel - BoseatsAfrica</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }

        .header h1 {
            font-size: 28px;
            color: #333;
        }

        .btn-back {
            padding: 10px 20px;
            background: #f0f0f0;
            color: #333;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .btn-back:hover {
            background: #e0e0e0;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 20px;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            transition: border 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #72A458;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }

        .amenity-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .amenity-checkbox:hover {
            background: #e9ecef;
        }

        .amenity-checkbox input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .room-types-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .room-type-item {
            display: grid;
            grid-template-columns: 2fr 3fr 1fr 1fr auto;
            gap: 10px;
            align-items: start;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .btn-remove {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-remove:hover {
            background: #c82333;
        }

        .btn-add {
            background: #72A458;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
        }

        .btn-add:hover {
            background: #5a8e43;
        }

        .image-upload-area {
            border: 2px dashed #e0e0e0;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .image-upload-area:hover {
            border-color: #72A458;
            background: #f8f9fa;
        }

        .image-upload-area i {
            font-size: 48px;
            color: #72A458;
            margin-bottom: 15px;
        }

        .btn-submit {
            width: 100%;
            padding: 15px;
            background: #72A458;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 30px;
        }

        .btn-submit:hover {
            background: #5a8e43;
            transform: translateY(-2px);
        }

        .error-message {
            background: #fee;
            border-left: 4px solid #dc3545;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #721c24;
        }

        .info-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .form-grid,
            .room-type-item {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-hotel"></i> Add New Hotel</h1>
            <a href="dashboard.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <?php if (isset($error)): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <!-- Basic Information -->
            <div class="form-section">
                <h2 class="section-title">Basic Information</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Company Name *</label>
                        <input type="text" name="company_name" required value="<?php echo isset($_POST['company_name']) ? htmlspecialchars($_POST['company_name']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Hotel Name *</label>
                        <input type="text" name="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    </div>

                    <div class="form-group full-width">
                        <label>Description *</label>
                        <textarea name="description" required placeholder="Describe your hotel..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>

                    <div class="form-group full-width">
                        <label>Booking Rules *</label>
                        <textarea name="bookingrules" required placeholder="What are your ground rules & instructions?"><?php echo isset($_POST['bookingrules']) ? htmlspecialchars($_POST['bookingrules']) : ''; ?></textarea>
                        <div class="info-text">Include check-in/check-out times, smoking policy, quiet hours, etc.</div>
                    </div>

                    <div class="form-group">
                        <label>Hotel Type *</label>
                        <select name="type" required>
                            <option value="">Select Type</option>
                            <option value="Hotel" <?php echo (isset($_POST['type']) && $_POST['type'] == 'Hotel') ? 'selected' : ''; ?>>Hotel</option>
                            <option value="Apartment" <?php echo (isset($_POST['type']) && $_POST['type'] == 'Apartment') ? 'selected' : ''; ?>>Apartment</option>
                            <option value="Resort" <?php echo (isset($_POST['type']) && $_POST['type'] == 'Resort') ? 'selected' : ''; ?>>Resort</option>
                            <option value="Guesthouse" <?php echo (isset($_POST['type']) && $_POST['type'] == 'Guesthouse') ? 'selected' : ''; ?>>Guesthouse</option>
                            <option value="Villa" <?php echo (isset($_POST['type']) && $_POST['type'] == 'Villa') ? 'selected' : ''; ?>>Villa</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Rating *</label>
                        <select name="rating" required>
                            <option value="1" <?php echo (isset($_POST['rating']) && $_POST['rating'] == '1') ? 'selected' : ''; ?>>1 Star</option>
                            <option value="2" <?php echo (isset($_POST['rating']) && $_POST['rating'] == '2') ? 'selected' : ''; ?>>2 Stars</option>
                            <option value="3" <?php echo (isset($_POST['rating']) && $_POST['rating'] == '3') ? 'selected' : 'selected'; ?>>3 Stars</option>
                            <option value="4" <?php echo (isset($_POST['rating']) && $_POST['rating'] == '4') ? 'selected' : ''; ?>>4 Stars</option>
                            <option value="5" <?php echo (isset($_POST['rating']) && $_POST['rating'] == '5') ? 'selected' : ''; ?>>5 Stars</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Price Per Night ($) *</label>
                        <input type="number" name="price_per_night" step="0.01" min="0.01" required value="<?php echo isset($_POST['price_per_night']) ? htmlspecialchars($_POST['price_per_night']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Bedrooms *</label>
                        <input type="number" name="bedrooms" min="1" required value="<?php echo isset($_POST['bedrooms']) ? htmlspecialchars($_POST['bedrooms']) : '1'; ?>">
                    </div>

                    <div class="form-group">
                        <label>Bathrooms *</label>
                        <input type="number" name="bathrooms" min="1" required value="<?php echo isset($_POST['bathrooms']) ? htmlspecialchars($_POST['bathrooms']) : '1'; ?>">
                    </div>

                    <div class="form-group">
                        <label>Minimum Nights *</label>
                        <input type="number" name="nights" min="1" required value="<?php echo isset($_POST['nights']) ? htmlspecialchars($_POST['nights']) : '1'; ?>">
                        <div class="info-text">Minimum number of nights required for booking</div>
                    </div>

                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 10px;">
                            <input type="checkbox" name="featured" value="1" style="width: auto;" <?php echo (isset($_POST['featured'])) ? 'checked' : ''; ?>>
                            Mark as Featured
                        </label>
                    </div>
                </div>
            </div>

            <!-- Location -->
            <div class="form-section">
                <h2 class="section-title">Location Details</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label>City *</label>
                        <input type="text" name="city" required value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Country *</label>
                        <input type="text" name="country" required value="<?php echo isset($_POST['country']) ? htmlspecialchars($_POST['country']) : 'Nigeria'; ?>">
                    </div>

                    <div class="form-group full-width">
                        <label>Full Location *</label>
                        <input type="text" name="location" required placeholder="e.g., Victoria Island, Lagos" value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>">
                    </div>

                    <div class="form-group full-width">
                        <label>Full Address *</label>
                        <textarea name="address" required placeholder="Enter complete address..."><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Latitude (Optional)</label>
                        <input type="number" name="latitude" step="0.00000001" placeholder="e.g., 6.4541" value="<?php echo isset($_POST['latitude']) ? htmlspecialchars($_POST['latitude']) : ''; ?>">
                        <div class="info-text">For map integration</div>
                    </div>

                    <div class="form-group">
                        <label>Longitude (Optional)</label>
                        <input type="number" name="longitude" step="0.00000001" placeholder="e.g., 3.3947" value="<?php echo isset($_POST['longitude']) ? htmlspecialchars($_POST['longitude']) : ''; ?>">
                        <div class="info-text">For map integration</div>
                    </div>
                </div>
            </div>

            <!-- Images -->
            <div class="form-section">
                <h2 class="section-title">Hotel Images</h2>
                <div class="form-group full-width">
                    <label>Upload Images (Multiple) *</label>
                    <div class="image-upload-area" onclick="document.getElementById('images').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Click to upload hotel images</p>
                        <small>First image will be the primary image. Max 5MB per image. Supported: JPG, PNG, GIF, WebP</small>
                    </div>
                    <input type="file" id="images" name="images[]" multiple accept="image/*" style="display: none;" required>
                </div>
            </div>

            <!-- Amenities -->
            <div class="form-section">
                <h2 class="section-title">Amenities</h2>
                <div class="amenities-grid">
                    <?php foreach ($amenities as $amenity): ?>
                    <label class="amenity-checkbox">
                        <input type="checkbox" name="amenities[]" value="<?php echo $amenity['id']; ?>" 
                            <?php echo (isset($_POST['amenities']) && in_array($amenity['id'], $_POST['amenities'])) ? 'checked' : ''; ?>>
                        <span><?php echo htmlspecialchars($amenity['name']); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Room Types -->
            <div class="form-section">
                <h2 class="section-title">Room Types *</h2>
                <div id="roomTypesContainer" class="room-types-container">
                    <div class="room-type-item">
                        <input type="text" name="room_types[0][name]" placeholder="Room Name *" required>
                        <input type="text" name="room_types[0][description]" placeholder="Description">
                        <input type="number" name="room_types[0][price]" placeholder="Price *" step="0.01" min="0.01" required>
                        <input type="number" name="room_types[0][capacity]" placeholder="Capacity *" min="1" value="2" required>
                        <button type="button" class="btn-remove" onclick="removeRoomType(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <button type="button" class="btn-add" onclick="addRoomType()">
                    <i class="fas fa-plus"></i> Add Room Type
                </button>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-save"></i> Add Hotel
            </button>
        </form>
    </div>

    <script>
        let roomTypeIndex = 1;

        function addRoomType() {
            const container = document.getElementById('roomTypesContainer');
            const newRoom = document.createElement('div');
            newRoom.className = 'room-type-item';
            newRoom.innerHTML = `
                <input type="text" name="room_types[${roomTypeIndex}][name]" placeholder="Room Name *" required>
                <input type="text" name="room_types[${roomTypeIndex}][description]" placeholder="Description">
                <input type="number" name="room_types[${roomTypeIndex}][price]" placeholder="Price *" step="0.01" min="0.01" required>
                <input type="number" name="room_types[${roomTypeIndex}][capacity]" placeholder="Capacity *" min="1" value="2" required>
                <button type="button" class="btn-remove" onclick="removeRoomType(this)">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            container.appendChild(newRoom);
            roomTypeIndex++;
        }

        function removeRoomType(button) {
            const container = document.getElementById('roomTypesContainer');
            if (container.children.length > 1) {
                button.parentElement.remove();
            } else {
                alert('At least one room type is required');
            }
        }

        // Image upload preview
        document.getElementById('images').addEventListener('change', function(e) {
            const files = e.target.files;
            const uploadArea = document.querySelector('.image-upload-area');
            if (files.length > 0) {
                uploadArea.innerHTML = `
                    <i class="fas fa-check-circle" style="color: #72A458;"></i>
                    <p>${files.length} image(s) selected</p>
                    <small>Click to change</small>
                `;
            }
        });

        // Form validation before submit
        document.querySelector('form').addEventListener('submit', function(e) {
            const roomTypes = document.querySelectorAll('.room-type-item');
            let hasValidRoom = false;
            
            roomTypes.forEach(room => {
                const name = room.querySelector('input[placeholder="Room Name *"]').value.trim();
                const price = room.querySelector('input[placeholder="Price *"]').value;
                if (name && price && parseFloat(price) > 0) {
                    hasValidRoom = true;
                }
            });
            
            if (!hasValidRoom) {
                e.preventDefault();
                alert('Please add at least one valid room type with name and price');
                return false;
            }
            
            const images = document.getElementById('images').files;
            if (images.length === 0) {
                e.preventDefault();
                alert('Please upload at least one hotel image');
                return false;
            }
        });
    </script>
</body>
</html>
<?php
session_start();
include_once __DIR__ . '/../includes/db_connection.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

$hotelId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$hotelId) {
    header('Location: index.php');
    exit;
}

// Get hotel details
$query = "SELECT * FROM hotels WHERE id = :id AND status = 'active'";
$stmt = $pdo->prepare($query);
$stmt->bindValue(':id', $hotelId, PDO::PARAM_INT);
$stmt->execute();
$hotel = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hotel) {
    header('Location: index.php');
    exit;
}

// Get hotel images
$imagesQuery = "SELECT * FROM hotel_images WHERE hotel_id = :hotel_id ORDER BY is_primary DESC, id ASC";
$stmt = $pdo->prepare($imagesQuery);
$stmt->bindValue(':hotel_id', $hotelId, PDO::PARAM_INT);
$stmt->execute();
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get hotel amenities
$amenitiesQuery = "SELECT a.* FROM amenities a 
                   JOIN hotel_amenities ha ON a.id = ha.amenity_id 
                   WHERE ha.hotel_id = :hotel_id";
$stmt = $pdo->prepare($amenitiesQuery);
$stmt->bindValue(':hotel_id', $hotelId, PDO::PARAM_INT);
$stmt->execute();
$amenities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available room types (lodges)
$roomTypesQuery = "SELECT * FROM room_types WHERE hotel_id = :hotel_id AND available = 1 ORDER BY price ASC";
$stmt = $pdo->prepare($roomTypesQuery);
$stmt->bindValue(':hotel_id', $hotelId, PDO::PARAM_INT);
$stmt->execute();
$roomTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$amenityIcons = [
    'Gate & Security' => 'fa-shield-alt',
    'Interlocked road' => 'fa-road',
    'All rooms en-suite' => 'fa-door-closed',
    'Ample car park' => 'fa-car',
    'Steady Electricity' => 'fa-bolt',
    'High speed Internet access' => 'fa-wifi',
    'Green Area' => 'fa-tree',
    'Portable water' => 'fa-water',
    'Swimming Pool' => 'fa-swimming-pool',
    'Gym' => 'fa-dumbbell',
    'Restaurant' => 'fa-utensils',
    'Bar' => 'fa-cocktail',
    'Spa' => 'fa-spa',
    'Conference Room' => 'fa-users',
    'Laundry Service' => 'fa-tshirt',
    'Room Service' => 'fa-concierge-bell',
    'Air Conditioning' => 'fa-fan',
    'TV' => 'fa-tv',
    'Minibar' => 'fa-glass-martini',
    'Safe' => 'fa-lock'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hotel['name']); ?> - BoseatsAfrica</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php 
    $pageTitle = "Hotel Details";
    include_once __DIR__ . '/../includes/e_header-hotel.php'; 
    ?>

    <section class="room-detail-section">
        <div class="container">
            <!-- Hotel Header with Image Gallery -->
            <div class="hotel-main-header">
                <div class="image-gallery-large">
                    <div class="gallery-main-large">
                        <img src="<?php echo htmlspecialchars($images[0]['image_url'] ?? '../assets/images/default-hotel.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($hotel['name']); ?>" 
                             id="mainImageLarge"
                             onerror="this.src='../assets/images/default-hotel.webp'">
                        <button class="nav-arrow prev-arrow" onclick="navigateImages(-1)">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="nav-arrow next-arrow" onclick="navigateImages(1)">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>

                <div class="hotel-title-section">
                    <h1 class="hotel-main-title"><?php echo htmlspecialchars($hotel['name']); ?></h1>
                    <p class="hotel-main-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($hotel['location']); ?>
                    </p>
                </div>
            </div>

            <!-- Room Selection Section -->
<div class="room-selection-wrapper">
    <div class="rooms-list">
        <h2 class="section-title-rooms">Available Rooms</h2>
        
        <?php if (empty($roomTypes)): ?>
            <div class="no-rooms-available">
                <i class="fas fa-door-closed"></i>
                <p>No rooms currently available</p>
            </div>
        <?php else: ?>
            <?php foreach ($roomTypes as $room): ?>
            <div class="room-option-card">
                <div class="room-image">
                    <img src="<?php echo $images[0]['image_url'] ?? '../assets/images/default-hotel.webp'; ?>" 
                         alt="<?php echo htmlspecialchars($room['name']); ?>"
                         onerror="this.src='../assets/images/default-hotel.webp'">
                </div>
                
                <div class="room-details">
                    <div class="room-header">
                        <h3 class="room-name"><?php echo htmlspecialchars($room['name']); ?></h3>
                        <div class="room-rating">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i < $hotel['rating'] ? 'active' : ''; ?>"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <p class="room-location-small">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($hotel['city']); ?>
                    </p>
                    
                    <div class="room-features-grid">
                        <span class="room-feature">
                            <i class="fas fa-bed"></i> 
                            <?php echo intval($room['capacity']); ?> Bedroom
                        </span>
                        <span class="room-feature">
                            <i class="fas fa-bath"></i> 
                            1 Bath
                        </span>
                        <span class="room-feature">
                            <i class="fas fa-utensils"></i> 
                            Breakfast
                        </span>
                    </div>
                    
                    <?php if (!empty($room['description'])): ?>
                    <p class="room-description-short">
                        <?php echo htmlspecialchars(substr($room['description'], 0, 100)); ?>...
                    </p>
                    <?php endif; ?>
                </div>
                
                <div class="room-booking-section">
                    <div class="room-price-block">
                        <div class="price-amount">$<?php echo number_format($room['price'], 2); ?></div>
                        <div class="price-label">per night</div>
                    </div>
                    
                    <div class="room-action-buttons">
                        <button class="btn-add-counter" onclick="decrementRoom(<?php echo $room['id']; ?>)">
                            <i class="fas fa-minus"></i>
                        </button>
                        <span class="room-counter" id="room-counter-<?php echo $room['id']; ?>">0</span>
                        <button class="btn-add-counter" onclick="incrementRoom(<?php echo $room['id']; ?>, '<?php echo htmlspecialchars(addslashes($room['name'])); ?>', <?php echo $room['price']; ?>)">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Booking Form Sidebar -->
    <aside class="booking-sidebar-fixed">
        <div class="booking-form-card">
            <h3>Book your room</h3>
            <div id="selected-rooms-list" class="selected-rooms-display">
                <p class="no-rooms-selected">No rooms selected</p>
            </div>
            
            <form id="bookingForm" method="POST" action="booking_details.php">
                <input type="hidden" name="hotel_id" value="<?php echo $hotelId; ?>">
                <input type="hidden" name="selected_rooms" id="selected-rooms-data" value="">
                
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="customer_name" required 
                           value="<?php echo $isLoggedIn ? htmlspecialchars($_SESSION['full_name'] ?? '') : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Phone number</label>
                    <input type="tel" name="phone" required 
                           value="<?php echo $isLoggedIn ? htmlspecialchars($_SESSION['phone'] ?? '') : ''; ?>">
                </div>
                
                <div class="form-group two-cols">
                    <div>
                        <label>Check-in</label>
                        <input type="date" name="checkin" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div>
                        <label>Check-out</label>
                        <input type="date" name="checkout" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>
                </div>
                
                <div class="form-group two-cols">
                    <div>
                        <label>Adults</label>
                        <select name="adults" required>
                            <option value="1">1 Adult</option>
                            <option value="2" selected>2 Adults</option>
                            <option value="3">3 Adults</option>
                            <option value="4">4+ Adults</option>
                        </select>
                    </div>
                    <div>
                        <label>Children</label>
                        <select name="children">
                            <option value="0" selected>0 Children</option>
                            <option value="1">1 Child</option>
                            <option value="2">2 Children</option>
                            <option value="3">3+ Children</option>
                        </select>
                    </div>
                </div>
                
                <div class="total-price-display">
                    <span class="total-label">Total</span>
                    <span class="total-amount" id="total-price">$0.00</span>
                </div>
                
                <button type="submit" class="btn-book-now" id="book-now-btn" disabled>
                    Book now
                </button>
            </form>
        </div>
    </aside>
</div>

            <!-- Overview Section -->
            <div class="overview-section">
                <h2>Overview</h2>
                <p><?php echo nl2br(htmlspecialchars($hotel['description'])); ?></p>
            </div>

            <!-- House Amenities -->
            <div class="amenities-section">
                <h2>House Amenities</h2>
                <div class="amenities-grid">
                    <?php foreach ($amenities as $amenity): 
                        $icon = $amenityIcons[$amenity['name']] ?? 'fa-check-circle';
                    ?>
                    <div class="amenity-item">
                        <i class="fas <?php echo $icon; ?>"></i>
                        <span><?php echo htmlspecialchars($amenity['name']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Booking Rules -->
            <?php if (!empty($hotel['bookingrules'])): ?>
            <div class="booking-rules-section">
                <h2>Booking Rules & Policies</h2>
                <div class="rules-content">
                    <?php echo nl2br(htmlspecialchars($hotel['bookingrules'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Location Map -->
            <div class="location-map-section">
                <h2>Location map</h2>
                <div id="map" class="map-container">
                    <iframe src="https://maps.google.com/maps?q=<?php echo urlencode($hotel['location']); ?>&t=&z=13&ie=UTF8&iwloc=&output=embed" 
                            width="100%" height="400" style="border:0;" allowfullscreen="" loading="lazy">
                    </iframe>
                </div>
            </div>
        </div>
    </section>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>

    <script>
        let selectedRooms = {};
        let totalPrice = 0;
        const images = <?php echo json_encode(array_column($images, 'image_url')); ?>;
        let currentImageIndex = 0;

        function navigateImages(direction) {
            currentImageIndex = (currentImageIndex + direction + images.length) % images.length;
            document.getElementById('mainImageLarge').src = images[currentImageIndex];
        }

        function incrementRoom(roomId, roomName, price) {
            if (!selectedRooms[roomId]) {
                selectedRooms[roomId] = { name: roomName, price: price, count: 0 };
            }
            selectedRooms[roomId].count++;
            updateDisplay();
        }

        function decrementRoom(roomId) {
            if (selectedRooms[roomId] && selectedRooms[roomId].count > 0) {
                selectedRooms[roomId].count--;
                if (selectedRooms[roomId].count === 0) {
                    delete selectedRooms[roomId];
                }
                updateDisplay();
            }
        }

        function updateDisplay() {
            // Update counters
            Object.keys(selectedRooms).forEach(roomId => {
                const counter = document.getElementById(`room-counter-${roomId}`);
                if (counter) counter.textContent = selectedRooms[roomId].count;
            });

            // Clear counts for removed rooms
            document.querySelectorAll('.room-counter').forEach(counter => {
                const roomId = counter.id.replace('room-counter-', '');
                if (!selectedRooms[roomId]) counter.textContent = '0';
            });

            // Update selected rooms list
            const listContainer = document.getElementById('selected-rooms-list');
            if (Object.keys(selectedRooms).length === 0) {
                listContainer.innerHTML = '<p class="no-rooms-selected">No rooms selected</p>';
                totalPrice = 0;
            } else {
                let html = '';
                totalPrice = 0;
                Object.values(selectedRooms).forEach(room => {
                    const roomTotal = room.price * room.count;
                    totalPrice += roomTotal;
                    html += `
                        <div class="selected-room-item">
                            <span>${room.count}x ${room.name}</span>
                            <span>${roomTotal.toFixed(2)}</span>
                        </div>
                    `;
                });
                listContainer.innerHTML = html;
            }

            // Update total price
            document.getElementById('total-price').textContent = `${totalPrice.toFixed(2)}`;

            // Update hidden field
            document.getElementById('selected-rooms-data').value = JSON.stringify(selectedRooms);

            // Enable/disable book button
            const bookBtn = document.getElementById('book-now-btn');
            bookBtn.disabled = Object.keys(selectedRooms).length === 0;
        }

        // Form validation
        const bookingForm = document.getElementById('bookingForm');
        if (bookingForm) {
            bookingForm.addEventListener('submit', function(e) {
                if (Object.keys(selectedRooms).length === 0) {
                    e.preventDefault();
                    alert('Please select at least one room');
                    return false;
                }

                const checkinEl = document.querySelector('input[name="checkin"]');
                const checkoutEl = document.querySelector('input[name="checkout"]');
                const checkin = new Date(checkinEl.value);
                const checkout = new Date(checkoutEl.value);
                
                if (checkout <= checkin) {
                    e.preventDefault();
                    alert('Check-out date must be after check-in date');
                    return false;
                }

                <?php if (!$isLoggedIn): ?>
                e.preventDefault();
                if (confirm('You need to login to book a room. Would you like to login now?')) {
                    window.location.href = '../login.php?redirect=' + encodeURIComponent(window.location.href);
                }
                return false;
                <?php endif; ?>
            });
        }

        // Date input setup
        const today = new Date().toISOString().split('T')[0];
        const checkinInput = document.querySelector('input[name="checkin"]');
        if (checkinInput) checkinInput.min = today;
        
        const checkoutInput = document.querySelector('input[name="checkout"]');
        if (checkinInput && checkoutInput) {
            checkinInput.addEventListener('change', function() {
                const checkinDate = new Date(this.value);
                checkinDate.setDate(checkinDate.getDate() + 1);
                const minCheckout = checkinDate.toISOString().split('T')[0];
                checkoutInput.min = minCheckout;
                if (checkoutInput.valueAsDate <= this.valueAsDate) {
                    checkoutInput.value = minCheckout;
                }
            });
        }
    </script>
</body>
</html>
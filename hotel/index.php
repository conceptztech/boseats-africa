<?php
session_start();
include_once __DIR__ . '/../includes/db_connection.php';
include_once '../includes/mobile_footer.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

// Pagination
$itemsPerPage = 12;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Filters
$location = isset($_GET['location']) ? $_GET['location'] : '';
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'all';

// Build query - NO price in listing
$query = "SELECT h.*, 
          (SELECT image_url FROM hotel_images WHERE hotel_id = h.id AND is_primary = 1 LIMIT 1) as main_image,
          (SELECT COUNT(*) FROM hotel_images WHERE hotel_id = h.id) as image_count,
          (SELECT COUNT(*) FROM room_types WHERE hotel_id = h.id AND available = 1) as room_count
          FROM hotels h 
          WHERE h.status = 'active'";

$params = [];

if ($location) {
    $query .= " AND (h.location LIKE :loc OR h.city LIKE :loc OR h.country LIKE :loc)";
    $params[':loc'] = "%{$location}%";
}

// Sorting
if ($sortBy === 'best') {
    $query .= " ORDER BY h.rating DESC";
} elseif ($sortBy === 'quickest') {
    $query .= " ORDER BY h.id DESC";
} else {
    $query .= " ORDER BY h.created_at DESC";
}

// Get total count
$countQuery = str_replace("SELECT h.*", "SELECT COUNT(*)", $query);
$countQuery = preg_replace('/ORDER BY.*/i', '', $countQuery);

try {
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalItems = (int) $stmt->fetchColumn();
} catch (Exception $e) {
    $totalItems = 0;
}
$totalPages = $totalItems > 0 ? ceil($totalItems / $itemsPerPage) : 1;

// Get hotels
$query .= " LIMIT :limit OFFSET :offset";
$params[':limit'] = (int)$itemsPerPage;
$params[':offset'] = (int)$offset;

$hotels = [];
try {
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        if ($key === ':limit' || $key === ':offset') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
    }
    $stmt->execute();
    $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $hotels = [];
}

// Featured cities
$featuredCities = [
    ['name' => 'Nigeria', 'image' => 'images/lagos.png', 'city' => 'Nigeria'],
    ['name' => 'Ghana', 'image' => 'images/ghana.png', 'city' => 'Accra'],
    ['name' => 'South Africa', 'image' => 'images/southafrica.png', 'city' => 'Cape Town']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Booking - BoseatsAfrica</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php 
    $pageTitle = "Hotels - Find Your Perfect Stay";
    include_once __DIR__ . '/../includes/e_header-hotel.php'; 
    ?>

    <div class="container page-content">
        <!-- Search Bar -->
        <form class="search-bar" method="GET" action="index.php">
            <div class="search-field with-label">
                <label for="location-input">Location</label>
                <input type="text" id="location-input" name="location" placeholder="e.g. Lagos" value="<?php echo htmlspecialchars($location); ?>">
            </div>
            <button type="submit" class="search-btn">
                <i class="fas fa-search"></i>
            </button>
            <div class="search-field with-label company-dropdown">
                <label>Choose company</label>
                <select name="company" id="company-select" class="company-select">
                    <option value="">All Companies</option>
                    <option value="boseats">BoseatsAfrica</option>
                    <option value="royal">Royal Hotels</option>
                    <option value="transcorp">Transcorp Hotels</option>
                </select>
                <i class="fas fa-chevron-down"></i>
            </div>
        </form>

        <!-- Featured Cities Carousel -->
        <div class="city-carousel-wrapper">
            <div class="city-cards-carousel">
                <?php foreach ($featuredCities as $city): ?>
                <div class="city-card-mini" onclick="searchByCity('<?php echo $city['city']; ?>')">
                    <img src="<?php echo $city['image']; ?>" alt="<?php echo $city['name']; ?>" onerror="this.src='../assets/images/default-city.jpg'">
                    <h3><?php echo $city['name']; ?></h3>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="carousel-dots"></div>
        </div>
    </div>

    <!-- Hotels Section -->
    <section class="hotels-section">
        <div class="container">
            <h2 class="section-heading">Hotels and Apartments</h2>
            
            <div class="hotels-grid">
                <?php if (empty($hotels)): ?>
                    <div class="no-results enhanced-no-results" role="status" aria-live="polite">
                        <div class="no-results-card container-small">
                            <img src="../assets/images/no-results-illustration.svg"
                                alt=""
                                class="no-results-illustration"
                                onerror="this.style.display='none'">
                            <div class="no-results-content">
                                <div class="no-results-heading">
                                    <i class="fas fa-search-location no-results-icon" aria-hidden="true"></i>
                                    <h3>No hotels match your search</h3>
                                </div>
                                <p class="no-results-sub">Try one of the suggestions below or explore popular destinations.</p>

                                <ul class="no-results-tips" aria-hidden="false">
                                    <li><strong>Check spelling</strong> — try nearby cities (e.g., Lagos, Accra, Cape Town)</li>
                                    <li><strong>Remove or change filters</strong> (company, date range)</li>
                                    <li><strong>Broaden dates</strong> — sometimes availability is limited for specific days</li>
                                </ul>

                                <div class="no-results-actions">
                                    <button type="button" class="btn btn-outline" onclick="clearFilters()">Clear filters</button>
                                    <button type="button" class="btn btn-primary" onclick="searchByCity('Lagos')">See popular hotels</button>
                                    <a href="../pages/service.php" class="btn btn-link">Need help? Contact us</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($hotels as $hotel): ?>
                    <div class="hotel-card">
                        <?php if ($hotel['featured']): ?>
                        <span class="badge-featured">For Rent</span>
                        <?php endif; ?>
                        
                        <button class="favorite-btn" onclick="toggleFavorite(<?php echo $hotel['id']; ?>, this)">
                            <i class="far fa-heart"></i>
                        </button>
                        
                        <a href="hotel_detail.php?id=<?php echo $hotel['id']; ?>">
                            <img src="<?php echo $hotel['main_image'] ?? '../assets/images/default-hotel.webp'; ?>" 
                                 alt="<?php echo htmlspecialchars($hotel['name']); ?>" 
                                 class="hotel-image"
                                 onerror="this.src='../assets/images/default-hotel.webp'">
                        </a>
                        
                        <div class="hotel-info">
                            <div class="hotel-header">
                                <span class="hotel-type"><?php echo htmlspecialchars($hotel['type']); ?></span>
                                <div class="hotel-rating">
                                    <?php for ($i = 0; $i < 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i < $hotel['rating'] ? 'active' : ''; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            
                            <h3 class="hotel-name">
                                <a href="hotel_detail.php?id=<?php echo $hotel['id']; ?>">
                                    <?php echo htmlspecialchars($hotel['name']); ?>
                                </a>
                            </h3>
                            <p class="hotel-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo htmlspecialchars($hotel['location']); ?>
                            </p>
                            
                            <div class="hotel-footer-simple">
                                <a href="hotel_detail.php?id=<?php echo $hotel['id']; ?>" class="btn-view-details">
                                    View details
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=1&location=<?php echo urlencode($location); ?>&sort=<?php echo $sortBy; ?>" class="page-link">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    <a href="?page=<?php echo $currentPage - 1; ?>&location=<?php echo urlencode($location); ?>&sort=<?php echo $sortBy; ?>" class="page-link">
                        <i class="fas fa-angle-left"></i>
                    </a>
                <?php endif; ?>

                <?php
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);
                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                    <a href="?page=<?php echo $i; ?>&location=<?php echo urlencode($location); ?>&sort=<?php echo $sortBy; ?>" 
                       class="page-link <?php echo $i === $currentPage ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?php echo $currentPage + 1; ?>&location=<?php echo urlencode($location); ?>&sort=<?php echo $sortBy; ?>" class="page-link">
                        <i class="fas fa-angle-right"></i>
                    </a>
                    <a href="?page=<?php echo $totalPages; ?>&location=<?php echo urlencode($location); ?>&sort=<?php echo $sortBy; ?>" class="page-link">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include_once '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        function filterHotels(sortType) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('sort', sortType);
            urlParams.set('page', '1');
            window.location.href = '?' + urlParams.toString();
        }

        function searchByCity(city) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('location', city);
            urlParams.set('page', '1');
            window.location.href = '?' + urlParams.toString();
        }

        function toggleFavorite(hotelId, element) {
            const icon = element.querySelector('i');
            icon.classList.toggle('far');
            icon.classList.toggle('fas');
            element.classList.toggle('active');
            
            fetch('../includes/toggle_favorite.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ item_id: hotelId, type: 'hotel' })
            });
        }

        function toggleFilters() {
            alert('Advanced filters coming soon!');
        }

        function clearFilters() {
            // Clear the visible search inputs if present
            const form = document.querySelector('.search-bar');
            if (form) {
                const loc = form.querySelector('#location-input');
                const comp = form.querySelector('#company-select');
                if (loc) loc.value = '';
                if (comp) comp.value = '';
            }

            // Remove filter params and reload to base listing
            const params = new URLSearchParams(window.location.search);
            params.delete('location');
            params.delete('company');
            params.delete('page');
            const query = params.toString();
            window.location.href = query ? ('?' + query) : window.location.pathname;
        }

        // City Carousel Functionality
        (function() {
            const carousel = document.querySelector('.city-cards-carousel');
            const cards = document.querySelectorAll('.city-card-mini');
            const dotsContainer = document.querySelector('.carousel-dots');
            
            if (!carousel || cards.length === 0) return;

            let currentIndex = 0;
            let autoplayInterval;
            let isUserInteracting = false;

            // Create dots
            cards.forEach((_, index) => {
                const dot = document.createElement('span');
                dot.classList.add('carousel-dot');
                if (index === 0) dot.classList.add('active');
                dot.addEventListener('click', () => goToSlide(index));
                dotsContainer.appendChild(dot);
            });

            const dots = document.querySelectorAll('.carousel-dot');

            function updateCarousel() {
                const isMobile = window.innerWidth <= 768;
                
                if (isMobile) {
                    // Mobile: Show one card at a time
                    const offset = -currentIndex * 100;
                    carousel.style.transform = `translateX(${offset}%)`;
                } else {
                    // Desktop: Show all cards
                    carousel.style.transform = 'translateX(0)';
                }

                // Update dots
                dots.forEach((dot, index) => {
                    dot.classList.toggle('active', index === currentIndex);
                });
            }

            function goToSlide(index) {
                currentIndex = index;
                updateCarousel();
                resetAutoplay();
            }

            function nextSlide() {
                currentIndex = (currentIndex + 1) % cards.length;
                updateCarousel();
            }

            function startAutoplay() {
                const isMobile = window.innerWidth <= 768;
                if (isMobile && !isUserInteracting) {
                    autoplayInterval = setInterval(nextSlide, 3000);
                }
            }

            function stopAutoplay() {
                clearInterval(autoplayInterval);
            }

            function resetAutoplay() {
                stopAutoplay();
                isUserInteracting = true;
                setTimeout(() => {
                    isUserInteracting = false;
                    startAutoplay();
                }, 5000);
            }

            // Touch/Swipe support
            let touchStartX = 0;
            let touchEndX = 0;

            carousel.addEventListener('touchstart', (e) => {
                touchStartX = e.changedTouches[0].screenX;
                stopAutoplay();
            }, { passive: true });

            carousel.addEventListener('touchend', (e) => {
                touchEndX = e.changedTouches[0].screenX;
                handleSwipe();
                resetAutoplay();
            }, { passive: true });

            function handleSwipe() {
                const swipeThreshold = 50;
                const diff = touchStartX - touchEndX;

                if (Math.abs(diff) > swipeThreshold) {
                    if (diff > 0) {
                        // Swipe left - next
                        currentIndex = (currentIndex + 1) % cards.length;
                    } else {
                        // Swipe right - previous
                        currentIndex = (currentIndex - 1 + cards.length) % cards.length;
                    }
                    updateCarousel();
                }
            }

            // Pause on hover/interaction
            carousel.addEventListener('mouseenter', stopAutoplay);
            carousel.addEventListener('mouseleave', () => {
                if (!isUserInteracting) startAutoplay();
            });

            // Handle window resize
            let resizeTimer;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(() => {
                    updateCarousel();
                    stopAutoplay();
                    startAutoplay();
                }, 250);
            });

            // Initialize
            updateCarousel();
            startAutoplay();
        })();
    </script>
</body>
</html>
<?php
// Start session at the VERY BEGINNING - ADD THIS LINE
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include_once 'includes/db_connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BoseaAfrica - Travel Booking</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/index_homepage.css">
    <style>
        /* Featured Products - Horizontal Scroll Layout */
        .featured-products {
            margin: 50px 100px 0;
            text-align: center;
            overflow: hidden;
            position: relative;
        }

        .featured-products h2 {
            font-size: 32px;
            margin-bottom: 40px;
            font-weight: bold;
            color: #333;
        }

        .featured-products-wrapper {
            overflow: hidden;
            width: 100%;
            position: relative;
        }

        .featured-products-container {
            display: flex;
            gap: 25px;
            padding: 20px 15px;
            overflow-x: auto;
            scroll-behavior: smooth;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .featured-products-container::-webkit-scrollbar {
            display: none;
        }

        .featured-product-card {
            flex: 0 0 300px;
            background: transparent;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
            min-height: 420px;
            display: flex;
            flex-direction: column;
            cursor: pointer;
        }

        .featured-product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .featured-product-image {
            width: 100%;
            height: 180px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .featured-product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .featured-product-card:hover .featured-product-image img {
            transform: scale(1.05);
        }

        .featured-product-info {
            padding: 20px;
            text-align: left;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .featured-product-info h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 0;
            color: #333;
            line-height: 1.3;
        }

        .featured-company {
            font-size: 14px;
            color: #666;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 0;
        }

        .featured-company i {
            color: #28a745;
        }

        .featured-description {
            font-size: 13px;
            color: #777;
            line-height: 1.4;
            margin-bottom: 0;
        }

        .featured-location {
            font-size: 13px;
            color: #555;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 5px;
            margin-bottom: 0;
        }

        .featured-location i {
            color: #dc3545;
        }

        .featured-delivery-badge {
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 10px;
            font-weight: 500;
        }

        .featured-price {
            font-size: 20px;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 0;
        }

        .featured-action-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: auto;
        }

        .featured-action-btn:hover {
            background: linear-gradient(135deg, #218838, #1ea085);
            transform: translateY(-2px);
        }

        .no-items {
            text-align: center;
            color: #666;
            font-size: 16px;
            padding: 40px;
            width: 100%;
        }

        /* Dot Indicators */
        .dot-indicators {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 25px;
        }

        .dot-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #ddd;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 0;
            font-size: 0;
        }

        .dot-indicator.active {
            background: #28a745;
            transform: scale(1.2);
        }

        .dot-indicator:hover {
            background: #bbb;
        }

        /* Mobile Responsive - FIXED FOR 3+ ITEMS */
        @media (max-width: 1200px) {
            .featured-products {
                margin: 50px 30px 0;
            }
        }

        @media (max-width: 768px) {
            .featured-products {
                margin: 40px 15px 0;
            }
            
            .featured-product-card {
                flex: 0 0 280px; /* Perfect for 3 items */
                min-height: 400px;
            }
            
            .featured-product-image {
                height: 140px;
            }
            
            .featured-product-info {
                padding: 15px;
            }
            
            .featured-product-info h3 {
                font-size: 16px;
            }
            
            .featured-company,
            .featured-description,
            .featured-location {
                font-size: 12px;
            }
            
            .featured-price {
                font-size: 16px;
            }
            
            .featured-action-btn {
                padding: 10px;
                font-size: 12px;
            }
        }

        @media (max-width: 480px) {
            .featured-products {
                margin: 30px 10px 0;
            }
            
            .featured-products h2 {
                font-size: 24px;
            }
            
            .featured-product-card {
                flex: 0 0 260px; /* Perfect for 3 items on small screens */
                min-height: 380px;
            }
            
            .featured-product-image {
                height: 120px;
            }
        }

        /* Food section heading */
        .featured-products h1 {
            text-align: left;
            margin: 0 0 20px 15px;
            font-size: 24px;
            color: #333;
            font-weight: 600;
        }

        /* User Profile Styles */
        .user-profile-container {
            position: relative;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-profile {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: #ddd;
            background-size: cover;
            background-position: center;
            cursor: pointer;
            border: 2px solid #fff;
            position: relative;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .user-name {
            font-weight: 600;
            font-size: 12px;
            color: #fdfafaff;
        }

        .user-role {
            font-size: 10px;
            color: #e8e8e8;
        }

        .dropdown-menu {
            position: absolute;
            top: 50px;
            right: 0;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            min-width: 160px;
            padding: 8px 0;
            z-index: 1000;
            display: none;
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-item {
            padding: 8px 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #333;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.2s;
        }

        .dropdown-item:hover {
            background-color: #f5f5f5;
        }

        .dropdown-divider {
            height: 1px;
            background-color: #eee;
            margin: 4px 0;
        }

        /* Mobile styles for user section */
        @media (max-width: 768px) {
            .user-info {
                display: none;
            }
            
            .user-profile {
                width: 32px;
                height: 32px;
            }
        }
    </style>
</head>
<body>
    <?php
    // Initialize user data variables
    $userData = null;
    $isLoggedIn = false;
    $userType = '';

    // Check if user is logged in via session
    if (isset($_SESSION['user_id'])) {
        $isLoggedIn = true;
        $userType = 'user';
        
        // Fetch user data from database
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    } 
    // Check if merchant is logged in via session
    elseif (isset($_SESSION['merchant_id'])) {
        $isLoggedIn = true;
        $userType = 'merchant';
        
        // Fetch merchant data from database
        $stmt = $pdo->prepare("SELECT * FROM merchants WHERE id = ?");
        $stmt->execute([$_SESSION['merchant_id']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Fetch featured food items for the landing page
    $featuredFoodData = [];
    $featuredSql = "SELECT * FROM food_items WHERE active = 1 ORDER BY RAND() LIMIT 12";
    $featuredStmt = $pdo->prepare($featuredSql);

    try {
        $featuredStmt->execute();
        $featuredFoodData = $featuredStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Continue with empty featured food data
    }

    // Function to get user's country
    function getUserCountry() {
        if (isset($_SESSION['user_country'])) {
            return $_SESSION['user_country'];
        }
        return 'NG'; // Default to Nigeria
    }

    // Function to get currency symbol
    function getCurrencySymbol($currencyCode) {
        $symbols = [
            'USD' => '$', 'NGN' => '₦', 'GBP' => '£', 'EUR' => '€',
            'KES' => 'KSh', 'GHS' => 'GH₵', 'ZAR' => 'R'
        ];
        return $symbols[$currencyCode] ?? $currencyCode;
    }

    // Set currency to Naira only
    $userCountry = getUserCountry();
    $currencySymbol = '₦'; // Force Naira symbol

    // Function to format price in Naira
    function formatPriceNaira($price) {
        return '₦' . number_format($price, 2);
    }
    ?>

    <!-- Hero Section -->
    <section class="hero">
        <!-- Top Bar -->
        <header class="top-bar">
            <div class="logo">
                <img src="https://leo.it.tab.digital/s/H5qHAKxTQHzXsyo/preview" alt="BoseaAfrica Logo"> <a href="index.php"></a>
            </div>
            
            <button class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </button>
            
            <nav class="top-nav">
                <a href="index.php">Home</a>
                <a href="pages/about.php">About</a>
                <a href="#contact">Contact</a>
                <a href="pages/service.php">Services</a>
            </nav>
            
            <div class="auth-buttons">
                <?php if ($isLoggedIn && $userData): ?>
                    <!-- Show user profile when logged in -->
                    <div class="user-profile-container">
                        <a style="text-decoration:none; display: block; color:white;" href="/boseatsafrica/user/register.php">
                            <button class="btn btn-book">Book Now</button>
                        </a>
                        <div class="user-info">
                            <div class="user-name">
                                <?php 
                                if ($userType === 'merchant') {
                                    echo htmlspecialchars($userData['company_name'] ?? 'Merchant');
                                } else {
                                    echo htmlspecialchars(($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? 'User'));
                                }
                                ?>
                            </div>
                            <div class="user-role">
                                <?php echo ucfirst($userType); ?>
                            </div>
                        </div>
                        <div class="user-profile" id="userProfile" 
                             style="<?php echo !empty($userData['profile_photo'] ?? $userData['picture_path'] ?? '') ? 'background-image: url(\'' . htmlspecialchars($userData['profile_photo'] ?? $userData['picture_path'] ?? '') . '\')' : ''; ?>">
                        </div>
                        <div class="dropdown-menu" id="dropdownMenu">
                            <a href="<?php echo $userType === 'merchant' ? 'merchant/dashboard.php' : 'user/dashboard.php'; ?>" class="dropdown-item">
                                <i class="fas fa-user"></i> My Profile
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="dropdown-item">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Show original login buttons when not logged in -->
                    <a style="text-decoration:none; display: block; color:white;" href="/boseatsafrica/user/register.php">
                        <button class="btn btn-book">Book Now</button>
                    </a>
                    <a style="text-decoration:none; display: block; color:green;" href="login.php">
                        <button class="btn btn-login">Login</button>
                    </a>
                <?php endif; ?>
            </div>
        </header>

        <!-- Your original hero content -->
        <div class="hero-content fade-in-up">
            <h1>Book Your Next Adventure. <br>All in One Place.</h1>
            <p>Discover and book flights, transport, and amazing events from top companies and local vendors. Securely. Instantly.</p>
            <a href="user/register.php" class="explore-button" style="text-decoration: none;"> Get Started</a>
        </div>
        
        <!-- Rating Box -->
        <div class="rating-box fade-in-right">
            <div class="rating-value">4.5</div>
            <div class="rating-text">Based on previous reviews</div>
            <div class="stars">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star-half-alt"></i>
            </div>
        </div>
    </section>

    <!-- Service Navigation -->
    <div class="service-nav fade-in-up">
        <div class="service-buttons">
            <a style="text-decoration:none; display: block; color:black;" href="/boseatsafrica/food/index.php"> 
            <button class="service-btn">
                <i class="fas fa-utensils"></i>
                <span>Food </span>
            </button> </a>

            <a style="text-decoration:none; display: block; color:black;" href="/boseatsafrica/hotel/index.php"> 
            <button class="service-btn">
                <i class="fas fa-hotel"></i>
                <span>Hotel</span>
            </button></a>

            <a style="text-decoration:none; display: block; color:black;" href="flight.php"> 
            <button class="service-btn">
                <i class="fas fa-plane"></i>
                <span>Flight</span>
            </button></a>

            <a style="text-decoration:none; display: block; color:black;" href="car/index.html"> 
            <button class="service-btn">
                <i class="fas fa-car"></i>
                <span>Car</span>
            </button></a>

            <a style="text-decoration:none; display: block; color:black;" href="/boseatsafrica/event/index.php"> 
            <button class="service-btn">
                <i class="fas fa-ticket-alt"></i>
                <span>Events</span>
            </button></a>
        </div>

        <div class="search-container">
            <input type="text" placeholder="Search, flight, car, event, hotel" id="search-input">
            <button id="search-button">Search</button>
        </div>
    </div>

    <!-- Featured Products -->
    <section class="featured-products">
        <h2 class="fade-in-up">OUR FEATURE PRODUCT AND COMPANIES</h2>
        <h1 style="text-align: left;">Food and Restaurant</h1>

        <div class="featured-products-wrapper">
            <div class="featured-products-container" id="featured-products-container">
                <?php if (empty($featuredFoodData)): ?>
                    <p class="no-items">No featured food items available at the moment.</p>
                <?php else: ?>
                    <?php foreach($featuredFoodData as $food): 
                        $deliveryOptions = isset($food['delivery_options']) ? $food['delivery_options'] : 'Home Delivery';
                        $hasHomeDelivery = stripos($deliveryOptions, 'Home Delivery') !== false;
                        $hasPickup = stripos($deliveryOptions, 'Pickup') !== false;
                        $location = isset($food['location']) ? htmlspecialchars($food['location']) : 'Location not specified';
                        $company = isset($food['company']) ? htmlspecialchars($food['company']) : 'Company not specified';
                        $description = isset($food['description']) ? htmlspecialchars($food['description']) : 'No description available';
                        $imageUrl = isset($food['image_url']) ? $food['image_url'] : '../food/images/default.png';
                    ?>
                        <!-- Make entire card clickable -->
                        <div class="featured-product-card" onclick="redirectToFoodDetail(<?php echo $food['id']; ?>)">
                            <div class="featured-product-image">
                                <img src="<?php echo $imageUrl; ?>" 
                                     alt="<?php echo htmlspecialchars($food['name']); ?>" 
                                     loading="lazy" 
                                     onerror="this.src='../food/images/default.png'; this.onerror=null;">
                            </div>
                            <div class="featured-product-info">
                                <h3><?php echo htmlspecialchars($food['name']); ?></h3>
                                <p class="featured-company">
                                    <i class="fas fa-building"></i> <?php echo $company; ?>
                                </p>
                                <p class="featured-description"><?php echo strlen($description) > 80 ? substr($description, 0, 80) . '...' : $description; ?></p>
                                <p class="featured-location">
                                    <i class="fas fa-map-marker-alt"></i> <?php echo $location; ?>
                                    <?php if ($hasHomeDelivery): ?>
                                        <span class="featured-delivery-badge delivery-available">
                                            <i class="fas fa-truck"></i> Delivery
                                        </span>
                                    <?php else: ?>
                                        <span class="featured-delivery-badge delivery-not-available">
                                            <i class="fas fa-store"></i> Pickup
                                        </span>
                                    <?php endif; ?>
                                </p>
                                <div class="featured-price">
                                    <?php echo formatPriceNaira($food['price']); ?>
                                </div>
                                <button class="featured-action-btn">
                                    View Details
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Dot Indicators -->
        <div class="dot-indicators" id="dot-indicators">
            <!-- Dots will be generated by JavaScript -->
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials-section">
        <h2 class="fade-in-up">What People Say About Us</h2>
        <div class="testimonials-wrapper">
            <div class="testimonials-container auto-scroll">
                <div class="testimonial-card fade-in-up">
                    <div class="profile-img">
                        <img src="assets/images/profile1.jpg" alt="Sandra T. Robinson">
                    </div>
                    <div class="testimonial-text">
                        <h3>Sandra T. Robinson</h3>
                        <div class="testimonial-stars">
                            <span>★★★★★</span>
                            <span class="rating">5.0</span>
                        </div>
                        <p>On the other hand, we denounce with righteous indignation and dislike men who are so beguiled and demoralized by the charms of pleasure of the moment, so blinded by desire.</p>
                    </div>
                </div>

                <div class="testimonial-card fade-in-up">
                    <div class="profile-img">
                        <img src="assets/images/Ellipse-43.png" alt="Andhika Pratama">
                    </div>
                    <div class="testimonial-text">
                        <h3>Andhika Pratama</h3>
                        <div class="testimonial-stars">
                            <span>★★★★★</span>
                            <span class="rating">5.0</span>
                        </div>
                        <p>Itaque earum rerum hic tenetur a sapiente delectus, ut aut reiciendis voluptatibus maiores alias consequatur aut perferendis doloribus asperiores repellat. Blanditiis praesentium.</p>
                    </div>
                </div>

                <div class="testimonial-card fade-in-up">
                    <div class="profile-img">
                        <img src="assets/images/profile3.jpg" alt="Denny Santoso">
                    </div>
                    <div class="testimonial-text">
                        <h3>Denny Santoso</h3>
                        <div class="testimonial-stars">
                            <span>★★★★★</span>
                            <span class="rating">5.0</span>
                        </div>
                        <p>Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placerat facere possimus, omnis voluptas assumenda est, omnis dolor repellendus.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="testimonial-controls">
            <button class="testimonial-btn" id="testimonial-prev-btn">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="testimonial-btn" id="testimonial-next-btn">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </section>

    <!-- Footer -->
    <?php 
    include_once 'includes/footer.php'; 
    include_once 'includes/mobile_footer.php'; 
    ?>

    <script>
    // Simple and reliable dropdown functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Get elements
        const userProfile = document.getElementById('userProfile');
        const dropdownMenu = document.getElementById('dropdownMenu');
        
        // Only initialize if elements exist (user is logged in)
        if (userProfile && dropdownMenu) {
            // Toggle dropdown when clicking profile
            userProfile.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdownMenu.classList.toggle('show');
            });
            
            // Close dropdown when clicking anywhere else
            document.addEventListener('click', function(e) {
                if (!userProfile.contains(e.target) && !dropdownMenu.contains(e.target)) {
                    dropdownMenu.classList.remove('show');
                }
            });
            
            // Close dropdown when clicking on dropdown items
            const dropdownItems = dropdownMenu.querySelectorAll('.dropdown-item');
            dropdownItems.forEach(item => {
                item.addEventListener('click', function() {
                    dropdownMenu.classList.remove('show');
                });
            });
        }
        
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const topNav = document.querySelector('.top-nav');
        const authButtons = document.querySelector('.auth-buttons');
        
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                topNav.classList.toggle('active');
                authButtons.classList.toggle('active');
                
                const icon = this.querySelector('i');
                if (topNav.classList.contains('active')) {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-times');
                } else {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            });
        }
        
        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.top-bar') && !event.target.closest('.mobile-menu-btn')) {
                if (topNav) topNav.classList.remove('active');
                if (authButtons) authButtons.classList.remove('active');
                if (mobileMenuBtn) {
                    const icon = mobileMenuBtn.querySelector('i');
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            }
        });

        // Initialize featured products
        initializeFeaturedProducts();
    });

    // Featured products functionality with dot indicators
    function initializeFeaturedProducts() {
        const container = document.getElementById('featured-products-container');
        const dotIndicators = document.getElementById('dot-indicators');
        
        if (!container || !dotIndicators) return;
        
        // Calculate how many dots we need based on visible items
        const cardWidth = 325; // Width of each card + gap
        const containerWidth = container.clientWidth;
        const totalCards = container.children.length;
        const visibleCards = Math.floor(containerWidth / cardWidth);
        const totalDots = Math.ceil(totalCards / visibleCards);
        
        // Clear existing dots
        dotIndicators.innerHTML = '';
        
        // Create dot indicators
        for (let i = 0; i < totalDots; i++) {
            const dot = document.createElement('button');
            dot.className = 'dot-indicator' + (i === 0 ? ' active' : '');
            dot.setAttribute('data-index', i);
            dot.addEventListener('click', function() {
                scrollToSection(i);
            });
            dotIndicators.appendChild(dot);
        }
        
        // Update active dot on scroll
        container.addEventListener('scroll', function() {
            updateActiveDot();
        });
    }

    function scrollToSection(sectionIndex) {
        const container = document.getElementById('featured-products-container');
        const cardWidth = 325; // Width of each card + gap
        const containerWidth = container.clientWidth;
        const scrollPosition = sectionIndex * containerWidth;
        
        container.scrollTo({
            left: scrollPosition,
            behavior: 'smooth'
        });
        
        // Update active dot
        const dots = document.querySelectorAll('.dot-indicator');
        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === sectionIndex);
        });
    }

    function updateActiveDot() {
        const container = document.getElementById('featured-products-container');
        const scrollPosition = container.scrollLeft;
        const containerWidth = container.clientWidth;
        const sectionIndex = Math.round(scrollPosition / containerWidth);
        
        const dots = document.querySelectorAll('.dot-indicator');
        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === sectionIndex);
        });
    }

   // Redirect to specific food detail page - UPDATED
function redirectToFoodDetail(foodId) {
    // Redirect to food index with ID parameter
    window.location.href = '/boseatsafrica/food/index.php?item_id=' + foodId;
}

    // Handle window resize
    window.addEventListener('resize', function() {
        initializeFeaturedProducts();
    });
    </script>
</body>
</html>
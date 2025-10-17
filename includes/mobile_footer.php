<?php
// Check if session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>

<?php if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])): ?>
    <!-- FOOTER FOR GUESTS/NOT LOGGED IN -->
    <div class="footer-mobile">
        <a href="/index.php" class="footer-icon">
            <i class="fas fa-home"></i><span>Home</span>
        </a>
        <a href="/pages/contact.php" class="footer-icon">
            <i class="fab fa-whatsapp"></i><span>Contact</span>
        </a>
        <a href="/user/cart.php" class="footer-icon">
            <i class="fas fa-shopping-bag"></i><span>Cart</span>
        </a>
        <a href="#" class="footer-icon menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i><span>Menu</span>
        </a>
        <a href="/boseatsafrica/login.php" class="footer-icon">
            <i class="fas fa-user"></i><span>Login</span>
        </a>
    </div>

    <!-- GUEST MENU -->
    <div class="footer-menu" id="dynamicMenu" style="display: none;">
        <div class="menu-header">
            <h4>Quick Menu</h4>
            <button class="close-menu" id="closeMenu">&times;</button>
        </div>
        <ul>
            <li><a href="boseatsafrica/index.php">Home</a></li>
            <li><a href="/food/index.php">Food</a></li>
            <li><a href="/hotel/index.php">Hotels</a></li>
            <li><a href="/flight/index.php">Flights</a></li>
            <li><a href="/car/index.php">Car Rental</a></li>
            <li><a href="/event/index.php">Events</a></li>
            <li class="menu-divider"></li>
            <li><a href="/pages/contact.php">Contact Us</a></li>
            <li><a href="/login.php" class="login-link"><i class="fas fa-sign-in-alt"></i> Login / Register</a></li>
        </ul>
    </div>

<?php else: ?>
    <!-- FOOTER FOR LOGGED IN USERS -->
    <?php
    // User is logged in - continue
    $user_id = $_SESSION['user_id'];
    $user_name = $_SESSION['full_name'] ?? '';
    $user_type = $_SESSION['user_type'];

    // Include database connection
    $db_path = __DIR__ . '/db_connection.php';
    if (file_exists($db_path)) {
        include_once $db_path;
    }

    // Initialize variables
    $cart_count = 0;
    $menu_items = [];

    // Set dashboard links based on user type
    if ($user_type == 'user') {
        $dashboard_link = '/user/dashboard.php';
    } elseif ($user_type == 'merchant') {
        $dashboard_link = '/merchant/dashboard.php';
    }

    // Fetch cart count for users
    if ($user_type == 'user' && isset($pdo)) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) AS cart_count FROM cart WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $user_id]);
            $cart_data = $stmt->fetch(PDO::FETCH_ASSOC);
            $cart_count = $cart_data['cart_count'] ?? 0;
        } catch (PDOException $e) {
            // Silently fail - cart count not critical
        }
    }

    // Dynamic Menu Links based on User Type
    if ($user_type == 'user') {
        $menu_items = [
            'Dashboard' => '/user/dashboard.php',
            'Food' => '/food/index.php',
            'Hotels' => '/hotel/index.php', 
            'Flights' => '/flight/index.php',
            'Car Rental' => '/car/index.php',
            'Events' => '/event/index.php',
            'My Cart' => '/user/cart.php',
            'Contact' => '/pages/contact.php'
        ];
    } elseif ($user_type == 'merchant') {
        $menu_items = [
            'Dashboard' => '/merchant/dashboard.php',
            'Manage Products' => '/merchant/manage_products.php',
            'Orders' => '/merchant/orders.php',
            'Contact' => '/pages/contact.php'
        ];
    }
    ?>

    <div class="footer-mobile">
        <!-- Home/Dashboard Link -->
        <a href="<?php echo $dashboard_link; ?>" class="footer-icon">
            <i class="fas fa-home"></i><span>Home</span>
        </a>
        
        <!-- Contact Link -->
        <a href="/pages/contact.php" class="footer-icon">
            <i class="fab fa-whatsapp"></i><span>Contact</span>
        </a>
        
        <!-- Cart for Users / Orders for Merchants -->
        <?php if ($user_type == 'user'): ?>
        <a href="/user/cart.php" class="footer-icon cart-icon">
            <i class="fas fa-shopping-bag"></i>
            <span>Cart 
                <?php if ($cart_count > 0): ?>
                    <span class="cart-badge"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </span>
        </a>
        <?php else: ?>
        <a href="/merchant/orders.php" class="footer-icon">
            <i class="fas fa-clipboard-list"></i>
            <span>Orders</span>
        </a>
        <?php endif; ?>
        
        <!-- Menu Toggle -->
        <a href="#" class="footer-icon menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i><span>Menu</span>
        </a>
        
        <!-- Profile Link -->
        <a href="<?php echo $dashboard_link; ?>" class="footer-icon">
            <i class="fas fa-user"></i><span>Profile</span>
        </a>
    </div>

    <!-- Dynamic Menu Based on User Type -->
    <div class="footer-menu" id="dynamicMenu" style="display: none;">
        <div class="menu-header">
            <h4>Quick Menu</h4>
            <button class="close-menu" id="closeMenu">&times;</button>
        </div>
        <ul>
            <?php foreach ($menu_items as $item => $link): ?>
                <li><a href="<?php echo $link; ?>"><?php echo $item; ?></a></li>
            <?php endforeach; ?>
            
            <!-- Logout Option -->
            <li class="menu-divider"></li>
            <li><a href="/logout.php" class="logout-link">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a></li>
        </ul>
    </div>

<?php endif; ?>

<!-- Overlay for menu -->
<div class="menu-overlay" id="menuOverlay" style="display: none;"></div>

<style>
/* Mobile Footer - Always show on mobile */
.footer-mobile {
    display: none;
}

@media screen and (max-width: 768px) {
    .footer-mobile {
        display: flex;
        position: fixed;
        bottom: 10px;
        left: 50%;
        transform: translateX(-50%);
        width: 90%;
        background-color: #28a745;
        justify-content: space-around;
        align-items: center;
        padding: 10px 0;
        box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.2);
        border-radius: 10px;
        z-index: 1000;
    }

    .footer-icon {
        text-align: center;
        color: white;
        text-decoration: none;
        font-size: 18px;
        position: relative;
        flex: 1;
        padding: 5px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .footer-icon i {
        font-size: 18px;
        margin-bottom: 3px;
    }

    .footer-icon span {
        font-size: 11px;
        line-height: 1.2;
    }

    .cart-badge {
        background: #ff4444;
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 10px;
        position: absolute;
        top: -5px;
        right: 5px;
    }

    /* Menu Styles */
    .footer-menu {
        position: fixed;
        bottom: 80px;
        left: 50%;
        transform: translateX(-50%);
        width: 80%;
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        z-index: 1001;
        max-height: 70vh;
        overflow-y: auto;
    }

    .menu-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        border-bottom: 1px solid #eee;
        background: #28a745;
        color: white;
        border-radius: 10px 10px 0 0;
    }

    .menu-header h4 {
        margin: 0;
        font-size: 16px;
    }

    .close-menu {
        background: none;
        border: none;
        color: white;
        font-size: 20px;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px;
    }

    .footer-menu ul {
        padding: 0;
        list-style: none;
        margin: 0;
    }

    .footer-menu li {
        border-bottom: 1px solid #f0f0f0;
    }

    .footer-menu li:last-child {
        border-bottom: none;
    }

    .footer-menu a {
        display: block;
        padding: 12px 15px;
        color: #333;
        text-decoration: none;
        font-size: 14px;
        transition: background 0.3s;
    }

    .footer-menu a:hover {
        background: #f8f9fa;
        color: #28a745;
    }

    .logout-link, .login-link {
        color: #dc3545 !important;
        font-weight: bold;
    }

    .menu-divider {
        height: 1px;
        background: #eee;
        margin: 5px 0;
    }

    .menu-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 999;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const dynamicMenu = document.getElementById('dynamicMenu');
    const menuOverlay = document.getElementById('menuOverlay');
    const closeMenu = document.getElementById('closeMenu');

    if (menuToggle && dynamicMenu && menuOverlay && closeMenu) {
        function toggleMenu() {
            const isVisible = dynamicMenu.style.display === 'block';
            dynamicMenu.style.display = isVisible ? 'none' : 'block';
            menuOverlay.style.display = isVisible ? 'none' : 'block';
            
            document.body.style.overflow = isVisible ? 'auto' : 'hidden';
        }

        menuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleMenu();
        });

        closeMenu.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleMenu();
        });

        menuOverlay.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleMenu();
        });

        // Close menu when clicking on menu links
        const menuLinks = document.querySelectorAll('.footer-menu a');
        menuLinks.forEach(link => {
            link.addEventListener('click', function() {
                toggleMenu();
            });
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (dynamicMenu.style.display === 'block') {
                if (!dynamicMenu.contains(e.target) && !menuToggle.contains(e.target)) {
                    toggleMenu();
                }
            }
        });
    }
});
</script>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BoseaAfrica Header</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            box-sizing: border-box;
        }

        .header1 {
            background-color: #28a745;
            position: relative;
            top: 0;
            right: 0;
            left: 0;
            padding: 10px;
        }

        .header {
            background-color: #f8faf8ff;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 10px 50px;
            border-radius: 50px;
            margin: 20px 50px;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            max-width: 1000px;
        }

        .logo img {
            height: 30px;
        }

        .nav-links {
            list-style: none;
            display: flex;
            padding: 0;
            margin: 0 0 0 30px;
        }

        .nav-links li {
            margin-left: 30px;
        }

        .nav-links li a {
            color: black;
            text-decoration: none;
            font-weight: 400;
            text-transform: uppercase;
            font-size: 16px;
        }

        .nav-links li a:hover {
            text-decoration: underline;
        }

        .search-result {
            color: white;
            font-weight: 400;
            font-size: 16px;
            margin-top: 10px;
            text-align: center;
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-profile {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #ddd;
            background-size: cover;
            background-position: center;
            cursor: pointer;
            position: relative;
            border: 2px solid #28a745;
        }

        .default-avatar {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            font-weight: bold;
            font-size: 14px;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .user-name {
            font-weight: 600;
            font-size: 14px;
            color: #333;
        }

        .user-role {
            font-size: 12px;
            color: #666;
        }

        .dropdown-menu {
            position: absolute;
            top: 50px;
            right: 0;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            min-width: 180px;
            padding: 10px 0;
            z-index: 100;
            display: none;
        }

        .dropdown-menu.active {
            display: block;
        }

        .dropdown-item {
            padding: 10px 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #333;
            text-decoration: none;
        }

        .dropdown-item:hover {
            background-color: #f5f5f5;
        }

        .dropdown-divider {
            height: 1px;
            background-color: #eee;
            margin: 5px 0;
        }

        .login-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }

        .login-btn:hover {
            background-color: #218838;
        }

        /* --- Mobile Styles --- */
        .menu-toggle {
            display: none;
            cursor: pointer;
            font-size: 30px;
        }

        @media (max-width: 768px) {
            .header {
                padding: 10px 20px;
                margin: 20px;
            }

            .header-content {
                flex-wrap: wrap;
                justify-content: space-between;
            }

            .nav-links {
                display: none;
                flex-direction: column;
                width: 100%;
                text-align: center;
                margin-top: 10px;
            }
            
            .nav-links.active {
                display: flex;
            }

            .nav-links li {
                margin: 10px 0;
            }

            .menu-toggle {
                display: block;
            }
            
            .user-info {
                display: none;
            }
            
            .logo {
                flex-grow: 1;
            }
            
            .logo img {
                height: 20px;
            }
        }
    </style>
</head>
<body>

<?php
// Include database connection - both are in the same includes folder
include_once 'db_connection.php';

// DO NOT start session here - assume it's already started in the main page
// Just check if session variables exist

// Initialize user data variables
$userData = null;
$isLoggedIn = false;
$userType = '';
$profilePicturePath = '';

// Check if user is logged in via session (session should be started in main page)
if (isset($_SESSION['user_id'])) {
    $isLoggedIn = true;
    $userType = 'user';
    
    // Fetch user data from database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userData) {
        // Build profile picture path for user
        if (!empty($userData['profile_picture'])) {
            $profilePicturePath = '../uploads/profile_pictures/' . $userData['profile_picture'];
        } else {
            $profilePicturePath = ''; // No profile picture
        }
    }
} 
// Check if merchant is logged in via session
elseif (isset($_SESSION['merchant_id'])) {
    $isLoggedIn = true;
    $userType = 'merchant';
    
    // Fetch merchant data from database
    $stmt = $pdo->prepare("SELECT * FROM merchants WHERE id = ?");
    $stmt->execute([$_SESSION['merchant_id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userData) {
        // Build profile picture path for merchant
        if (!empty($userData['picture_path'])) {
            $profilePicturePath = '../uploads/merchants/' . $userData['picture_path'];
        } else {
            $profilePicturePath = ''; // No profile picture
        }
    }
}

// Function to get initials for default avatar
function getInitials($name) {
    $initials = '';
    $words = explode(' ', $name);
    
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper(substr($word, 0, 1));
        }
    }
    
    return substr($initials, 0, 2);
}

// Get user display name and initials
$displayName = '';
$userInitials = '';

if ($isLoggedIn && $userData) {
    if ($userType === 'merchant') {
        $displayName = $userData['company_name'];
        $userInitials = getInitials($userData['company_name']);
    } else {
        $displayName = $userData['first_name'] . ' ' . $userData['last_name'];
        $userInitials = getInitials($userData['first_name'] . ' ' . $userData['last_name']);
    }
}
?>

<div class="header1">
    <div class="header">
        <div class="header-content">
            <div class="logo">
                <img src="https://leo.it.tab.digital/s/ayC7xSHyzWfyKZA/preview" alt="Logo">
            </div>
            
            <div class="menu-toggle" id="menu-toggle">
                &#9776;
            </div>

            <ul class="nav-links" id="nav-links">
                <li><a href="../index.php">Home</a></li>
                <li><a href="../flight/index.php">Flight</a></li>
                <li><a href="../hotel/index.php">Hotel</a></li>
                <li><a href="../car/index.php">Car</a></li>
                <li><a href="../food/index.php">Food</a></li>
                <li><a href="../event/index.php">Event</a></li>
            </ul>
            
            <div class="user-section">
                <?php if ($isLoggedIn && $userData): ?>
                    <div class="user-info" id="user-info">
                        <div class="user-name" id="user-name">
                            <?php echo htmlspecialchars($displayName); ?>
                        </div>
                        <div class="user-role" id="user-role">
                            <?php echo ucfirst($userType); ?>
                        </div>
                    </div>
                    <div class="user-profile" id="user-profile" 
                         style="<?php echo !empty($profilePicturePath) ? 'background-image: url(\'' . htmlspecialchars($profilePicturePath) . '\')' : ''; ?>">
                        <?php if (empty($profilePicturePath)): ?>
                            <div class="default-avatar">
                                <?php echo $userInitials; ?>
                            </div>
                        <?php endif; ?>
                        <div class="dropdown-menu" id="dropdown-menu">
                            <a href="<?php echo $userType === 'merchant' ? '../merchant/dashboard.php' : '../user/dashboard.php'; ?>" class="dropdown-item">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                            <a href="<?php echo $userType === 'merchant' ? '../merchant/profile.php' : '../user/profile.php'; ?>" class="dropdown-item">
                                <i class="fas fa-user"></i> My Profile
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="../logout.php" class="dropdown-item">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <button class="login-btn" id="login-btn">Login</button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="search-result">
        Search result
    </div>
</div>

<script>
    // DOM Elements
    const menuToggle = document.getElementById('menu-toggle');
    const navLinks = document.getElementById('nav-links');
    const userProfile = document.getElementById('user-profile');
    const dropdownMenu = document.getElementById('dropdown-menu');
    const loginBtn = document.getElementById('login-btn');

    // Toggle mobile menu
    if (menuToggle) {
        menuToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });
    }

    // Toggle dropdown menu
    if (userProfile) {
        userProfile.addEventListener('click', (e) => {
            e.stopPropagation();
            if (dropdownMenu) {
                dropdownMenu.classList.toggle('active');
            }
        });
    }

    // Close dropdown when clicking elsewhere
    document.addEventListener('click', () => {
        if (dropdownMenu) {
            dropdownMenu.classList.remove('active');
        }
    });

    // Login button handler
    if (loginBtn) {
        loginBtn.addEventListener('click', () => {
            // Redirect to login page in root directory
            window.location.href = '../login.php';
        });
    }

    // Close mobile menu when clicking on a link
    const navLinksItems = document.querySelectorAll('.nav-links a');
    navLinksItems.forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                navLinks.classList.remove('active');
            }
        });
    });
</script>

</body>
</html>
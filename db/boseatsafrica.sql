-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 16, 2025 at 11:59 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `boseatsafrica`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `user_type` enum('user','merchant','admin') DEFAULT 'admin',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `email`, `password`, `full_name`, `user_type`, `is_active`, `created_at`) VALUES
(3, 'admin@boseatsafrica.com', '$2y$10$hPMtf881chUW6TPZha4ddOJ3w6hZwqNx4TzrXK8niLvuq.BPJu76O', 'System Administrator', 'admin', 1, '2025-09-27 15:13:31');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_price` decimal(10,2) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `product_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `id` int(11) NOT NULL,
  `code` varchar(3) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone_code` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `countries`
--

INSERT INTO `countries` (`id`, `code`, `name`, `phone_code`) VALUES
(1, 'NG', 'Nigeria', '+234'),
(2, 'GH', 'Ghana', '+233'),
(3, 'KE', 'Kenya', '+254'),
(4, 'ZA', 'South Africa', '+27');

-- --------------------------------------------------------

--
-- Table structure for table `discount_offers`
--

CREATE TABLE `discount_offers` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `discount_type` enum('percentage','fixed','free_shipping') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `coupon_code` varchar(50) DEFAULT NULL,
  `min_order_amount` decimal(10,2) DEFAULT 0.00,
  `max_discount_amount` decimal(10,2) DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `applicable_categories` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applicable_categories`)),
  `applicable_products` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applicable_products`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `discount_offers`
--

INSERT INTO `discount_offers` (`id`, `title`, `description`, `discount_type`, `discount_value`, `coupon_code`, `min_order_amount`, `max_discount_amount`, `start_date`, `end_date`, `usage_limit`, `used_count`, `is_active`, `applicable_categories`, `applicable_products`, `created_at`, `updated_at`) VALUES
(1, 'Welcome Discount', 'Get 20% off on your first order', 'percentage', 20.00, 'WELCOME20', 0.00, 1000.00, '2025-10-09 18:39:26', '2025-11-08 18:39:26', 1000, 1, 1, NULL, NULL, '2025-10-09 17:39:26', '2025-10-09 17:51:35'),
(2, 'Free Shipping', 'Free shipping on all orders above ₦5000', 'free_shipping', 0.00, 'FREESHIP', 5000.00, NULL, '2025-10-09 18:39:26', '2025-12-08 18:39:26', NULL, 0, 1, NULL, NULL, '2025-10-09 17:39:26', '2025-10-09 17:39:26'),
(3, 'Summer Sale', 'Flat ₦1500 off on all orders', 'fixed', 1500.00, 'SUMMER1500', 3000.00, 1500.00, '2025-10-09 18:39:26', '2025-11-23 18:39:26', 500, 0, 1, NULL, NULL, '2025-10-09 17:39:26', '2025-10-09 17:39:26'),
(4, 'Weekend Special', '15% off on all food items', 'percentage', 15.00, 'WEEKEND15', 2000.00, 750.00, '2025-10-09 18:39:26', '2025-10-16 18:39:26', 200, 0, 1, NULL, NULL, '2025-10-09 17:39:26', '2025-10-09 17:39:26'),
(5, 'Clearance Sale', '₦2000 off on clearance items', 'fixed', 2000.00, 'CLEAR2000', 5000.00, 2000.00, '2025-10-09 18:39:26', '2025-10-24 18:39:26', 300, 0, 0, NULL, NULL, '2025-10-09 17:39:26', '2025-10-15 21:05:21');

-- --------------------------------------------------------

--
-- Table structure for table `food_items`
--

CREATE TABLE `food_items` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `category` varchar(50) DEFAULT 'other',
  `company` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `delivery_options` enum('Pickup Only','Home Delivery','Pickup & Home Delivery') NOT NULL,
  `company_logo` varchar(255) DEFAULT NULL,
  `merchant_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delivery_fee` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `food_items`
--

INSERT INTO `food_items` (`id`, `name`, `description`, `location`, `price`, `image_url`, `category`, `company`, `active`, `delivery_options`, `company_logo`, `merchant_id`, `created_at`, `updated_at`, `delivery_fee`) VALUES
(1, 'Jollof Rice and Chicken', 'Nigerian Jollof rice combines parboiled rice with a flavorful pepper-tomato-onion stew, served with succulent chicken.', 'Lugbe', 30.00, '../food/images/jollofrice.png', 'local', NULL, 1, 'Pickup Only', NULL, NULL, '2025-10-12 13:53:59', '2025-10-12 13:53:59', 0.00),
(2, 'Hot Pepper Soup', 'A spicy Nigerian soup made with assorted meats, fish, and traditional spices that will warm you up.', 'Lugbe', 25.00, '../food/images/peppersoup.jpg', 'local', NULL, 1, 'Home Delivery', NULL, NULL, '2025-10-12 13:53:59', '2025-10-12 13:53:59', 0.00),
(3, 'Fried Rice with Beef', 'Delicious fried rice with tender beef strips, mixed vegetables, and special seasoning.', 'Abuja', 35.00, '../food/images/friedrice.png', 'other', NULL, 1, 'Home Delivery', NULL, NULL, '2025-10-12 13:53:59', '2025-10-12 13:53:59', 0.00),
(5, 'Grilled Fish with Plantain', 'Fresh grilled fish marinated in special spices, served with fried plantain and spicy sauce.', 'Lagos', 45.00, '../food/images/grilledfish.png', 'local', NULL, 1, 'Pickup Only', NULL, NULL, '2025-10-12 13:53:59', '2025-10-12 14:13:10', 0.00),
(7, 'Chicken Shawarma', 'Grilled chicken wrapped in warm pita bread with fresh vegetables, garlic sauce, and tahini.', 'Lugbe', 15.00, '../food/images/shawarma.png', 'other', NULL, 1, 'Home Delivery', NULL, NULL, '2025-10-12 13:53:59', '2025-10-12 13:53:59', 0.00),
(8, 'Pizza Margherita', 'Classic Italian pizza with fresh tomato sauce, mozzarella cheese, and fresh basil leaves.', 'Abuja', 28.00, '../food/images/pizza.png', 'other', NULL, 1, 'Home Delivery', NULL, NULL, '2025-10-12 13:53:59', '2025-10-12 13:53:59', 0.00),
(13, 'Seafood Okro Soup', 'Fresh okra soup with assorted seafood including prawns, crab, and fish, served with eba.', 'Port Harcourt', 38.00, '../food/images/okrosoup.png', 'local', NULL, 1, 'Home Delivery', NULL, NULL, '2025-10-12 13:53:59', '2025-10-12 13:53:59', 0.00),
(18, 'Chinese Fried Rice', 'Authentic Chinese-style fried rice with vegetables, eggs, and your choice of protein.', 'Lagos', 30.00, '../food/images/chineserice.png', 'other', NULL, 1, 'Home Delivery', NULL, NULL, '2025-10-12 13:53:59', '2025-10-12 13:53:59', 0.00),
(27, 'Beef Burger with Fries', 'Classic beef burger with lettuce, tomato, cheese, special sauce, and crispy golden fries.', 'Kogi', 300.00, 'food/images/1760278689_burger.jpeg', 'local', 'FOOD RESTAURANT kis', 1, 'Pickup Only', 'merchant_17_1759920235.jpg', 11, '2025-10-12 14:18:10', '2025-10-12 14:18:10', 0.00),
(28, 'Chicken and Chips', 'Crispy fried chicken served with french fries and coleslaw, perfect for quick meals.', 'Kogi', 2500.00, 'food/images/1760279130_jollof.png', 'fast_food', 'FOOD RESTAURANT kis', 1, 'Pickup & Home Delivery', 'merchant_17_1759920235.jpg', 11, '2025-10-12 14:25:30', '2025-10-12 14:25:30', 0.00),
(29, 'Chicken Biryani', 'Fragrant rice dish cooked with marinated chicken, spices, and herbs in the traditional Indian style.', 'Ekiti', 5200.00, 'food/images/1760279417_BBQ-Ribs.jpeg', 'fast_food', 'FOOD RESTAURANT kis', 1, 'Pickup Only', 'merchant_17_1759920235.jpg', 11, '2025-10-12 14:30:17', '2025-10-12 22:45:19', 0.00),
(30, 'NathanGloria Ovorke OkpanachiPayne', 'NathanGloria Ovorke OkpanachiPayneNathanGloria Ovorke OkpanachiPayne', 'Ondo', 4344.00, 'uploads/products/68ec2fa055a34_1760309152.jpeg', 'fast_food', 'FOOD AND SAUCES', 1, '', NULL, 11, '2025-10-12 22:45:52', '2025-10-13 22:23:45', 0.00),
(31, 'JAMES CHRISTMAN', 'JAMES CHRISTMANJAMES CHRISTMANJAMES CHRISTMANJAMES CHRISTMAN', 'Abia', 5666.00, 'uploads/products/68ec32ee6838a_1760309998.jpeg', 'local', 'FOOD AND SAUCES', 1, 'Home Delivery', NULL, 11, '2025-10-12 22:59:58', '2025-10-13 21:24:55', 0.00),
(32, 'Kisco Tech', 'Kisco TechKisco TechKisco TechKisco TechKisco TechKisco Tech', 'Abia', 566666.00, 'uploads/products/68ed7cd2216f3_1760394450.jpeg', 'local', 'FOOD AND SAUCES', 1, 'Pickup & Home Delivery', NULL, 11, '2025-10-13 22:27:30', '2025-10-13 23:17:31', 0.00),
(33, 'Chicken Biryani', 'yyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy6y6y6666666666666666666666666666666666666666666666666', 'Abia', 7667.00, 'uploads/products/68ed7cf8db238_1760394488.jpeg', 'fast_food', 'FOOD AND SAUCES', 1, 'Pickup Only', NULL, 11, '2025-10-13 22:28:08', '2025-10-13 22:28:08', 0.00),
(34, 'yyyyyyyyyyyyyyyyy htht', 'tththhtht thth thttth thth t', 'Lagos', 999.00, 'uploads/products/68ed7d506fa4b_1760394576.jpeg', 'fast_food', 'FOOD AND SAUCES', 0, '', NULL, 11, '2025-10-13 22:29:36', '2025-10-15 20:29:15', 0.00),
(35, 'GARRI AND SUGAR', 'sweet Nigeria white garri with a lot of goodies.', 'Abia', 1500.00, 'uploads/products/68ed8dc97637e_1760398793.png', 'fast_food', 'FOOD AND SAUCES', 1, 'Pickup & Home Delivery', NULL, 11, '2025-10-13 22:32:32', '2025-10-14 10:24:34', 200.00),
(37, 'Kisco Tech', '50 sachet of maggie with salt and some other items', 'Abia', 2500.00, 'uploads/products/68ed8df463ff6_1760398836.jpeg', 'local', 'FOOD AND SAUCES', 1, 'Home Delivery', NULL, 11, '2025-10-13 23:40:36', '2025-10-14 10:26:39', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `merchants`
--

CREATE TABLE `merchants` (
  `id` int(11) NOT NULL,
  `company_name` varchar(100) NOT NULL,
  `owners_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `country` varchar(50) NOT NULL,
  `state` varchar(50) NOT NULL,
  `company_address` text DEFAULT NULL,
  `business_type` varchar(50) DEFAULT NULL,
  `phone_code` varchar(10) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `nin_passport` varchar(50) NOT NULL,
  `services` text DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `picture_path` varchar(255) DEFAULT NULL,
  `account_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_type` enum('user','merchant','admin') DEFAULT 'merchant',
  `is_active` tinyint(1) DEFAULT 1,
  `is_approved` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `merchants`
--

INSERT INTO `merchants` (`id`, `company_name`, `owners_name`, `email`, `country`, `state`, `company_address`, `business_type`, `phone_code`, `phone`, `nin_passport`, `services`, `password`, `picture_path`, `account_status`, `created_at`, `updated_at`, `user_type`, `is_active`, `is_approved`) VALUES
(11, 'FOOD AND SAUCES', 'PHILIP AYODELE', 'phillipayodele@gmail.com', '', 'Abia', 'Okene, Kogi State, Nigeria', 'car hiring', '+233', '8169485474', '4444444444444', 'Hotel', '$2y$10$cmmqBRzdDV8nf0TDVK8HPejcxtmYiP.Z9CiGOgI77tAHden0Yghh6', 'merchant_17_1759920235.jpg', 'pending', '2025-10-08 10:43:55', '2025-10-15 21:32:22', 'merchant', 1, 1),
(12, 'kisco company', 'KISCO TECH', 'kiscotech1@gmail.com', 'NG', 'Kogi', 'Federal Road, Ado Ekiti 360102, Ekiti, Nigeria', 'partnership', '+234', '08130041735', '4ww2333', 'Events, Tours', '$2y$10$5qS6mlHWky1pCS0WEPyLtuvnNZKiTosbL0UKNg9kLPU0/SOYnR4DO', 'merchant_18_1760045470.jpg', 'pending', '2025-10-09 21:31:10', '2025-10-12 13:21:02', 'merchant', 0, 0),
(14, 'KISCO TECH', 'OKE AYODELE', 'kiscotech6@gmail.com', 'Nigeria', 'Ekiti', NULL, NULL, '+234', '08169485474', '22331133889', '[\"Food\"]', '$2y$10$RUv02Ev8QecVyLn5ch8TN.waqEUYV9qX5Ch.PDlirAR686ZoJIJM6', '../uploads/merchants/68ec1df2575ff.jpg', '', '2025-10-12 21:30:26', '2025-10-15 03:42:30', 'merchant', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `merchant_payouts`
--

CREATE TABLE `merchant_payouts` (
  `id` int(11) NOT NULL,
  `merchant_id` int(11) NOT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `account_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `routing_number` varchar(50) DEFAULT NULL,
  `payout_method` enum('bank_transfer','paypal','paystack') DEFAULT 'bank_transfer',
  `payout_schedule` enum('daily','weekly','biweekly','monthly') DEFAULT 'weekly',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `merchant_preferences`
--

CREATE TABLE `merchant_preferences` (
  `id` int(11) NOT NULL,
  `merchant_id` int(11) NOT NULL,
  `email_orders` tinyint(4) DEFAULT 1,
  `sms_urgent` tinyint(4) DEFAULT 1,
  `weekly_reports` tinyint(4) DEFAULT 0,
  `promotional_emails` tinyint(4) DEFAULT 1,
  `low_stock_alerts` tinyint(4) DEFAULT 1,
  `new_customer_notifications` tinyint(4) DEFAULT 1,
  `language` varchar(10) DEFAULT 'en',
  `timezone` varchar(50) DEFAULT 'Africa/Lagos',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `merchant_preferences`
--

INSERT INTO `merchant_preferences` (`id`, `merchant_id`, `email_orders`, `sms_urgent`, `weekly_reports`, `promotional_emails`, `low_stock_alerts`, `new_customer_notifications`, `language`, `timezone`, `created_at`, `updated_at`) VALUES
(1, 11, 1, 1, 0, 1, 1, 1, 'en', 'Africa/Lagos', '2025-10-15 23:12:38', '2025-10-15 23:12:38');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('order_update','system','promotion','security') NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `related_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `related_id`, `created_at`, `updated_at`) VALUES
(11, 15, 'Welcome to BoseatsAfrica!', 'Thank you for joining our platform. Start exploring now!', 'system', 0, NULL, '2025-10-09 17:36:47', '2025-10-09 20:35:29'),
(12, 15, 'Special Discount', 'Get 20% off on your next purchase with code WELCOME20', 'promotion', 0, NULL, '2025-10-09 17:36:47', '2025-10-09 17:36:47'),
(13, 21, 'Welcome to Boseats Africa!', 'Hello KISCO TECH, welcome to Boseats Africa! We\'re excited to have you on board. Start exploring our services and don\'t hesitate to contact us if you need any help.', '', 0, 21, '2025-10-09 23:12:53', '2025-10-09 23:12:53'),
(15, 21, 'Get Started with Your Account', 'Your account has been created successfully! Complete your profile, explore our services, and start your journey with Boseats Africa.', '', 1, 21, '2025-10-09 23:12:53', '2025-10-09 23:15:31'),
(22, 25, 'Welcome to Boseats Africa!', 'Hello KISCO TECH, welcome to Boseats Africa! We\'re excited to have you on board. Start exploring our services and don\'t hesitate to contact us if you need any help.', '', 0, 25, '2025-10-10 14:22:04', '2025-10-10 14:22:04'),
(24, 25, 'Get Started with Your Account', 'Your account has been created successfully! Complete your profile, explore our services, and start your journey with Boseats Africa.', '', 0, 25, '2025-10-10 14:22:04', '2025-10-10 14:22:04'),
(34, 29, 'Welcome to Boseats Africa!', 'Hello James Ozavinoyi, welcome to Boseats Africa! We\'re excited to have you on board. Start exploring our services and don\'t hesitate to contact us if you need any help.', '', 1, 29, '2025-10-10 15:10:14', '2025-10-10 15:10:58'),
(36, 29, 'Get Started with Your Account', 'Your account has been created successfully! Complete your profile, explore our services, and start your journey with Boseats Africa.', '', 1, 29, '2025-10-10 15:10:14', '2025-10-10 15:11:01'),
(37, 21, 'Payment Successful', 'Your payment for order #30 was successful. Your order is being processed.', 'order_update', 0, NULL, '2025-10-15 02:38:29', '2025-10-15 02:38:29'),
(38, 21, 'Order Confirmed', 'Your order #30 has been confirmed and is being prepared.', 'order_update', 0, NULL, '2025-10-15 02:38:29', '2025-10-15 02:38:29');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `merchant_id` int(11) DEFAULT NULL,
  `order_data` text NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `original_amount` decimal(10,2) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `coupon_code` varchar(100) DEFAULT NULL,
  `payment_status` enum('pending','completed','failed') DEFAULT 'pending',
  `payment_reference` varchar(255) DEFAULT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `items` text DEFAULT NULL,
  `delivery_location` varchar(255) NOT NULL,
  `delivery_address` text NOT NULL,
  `order_status` enum('pending','preparing','delivered','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `merchant_id`, `order_data`, `total_amount`, `original_amount`, `discount_amount`, `coupon_code`, `payment_status`, `payment_reference`, `reference`, `items`, `delivery_location`, `delivery_address`, `order_status`, `created_at`) VALUES
(9, 18, NULL, '{\"items\":[{\"id\":25,\"name\":\"BBQ Ribs\",\"price\":42,\"imageUrl\":\"../food/images/BBQ-Ribs.jpeg\",\"description\":\"Slow-cooked pork ribs with barbecue sauce, served with coleslaw and cornbread.\",\"homeDelivery\":0,\"location\":\"Abuja\",\"quantity\":2},{\"id\":12,\"name\":\"Akara and Pap\",\"price\":10,\"imageUrl\":\"../food/images/akara.jpg\",\"description\":\"Deep-fried bean cakes served with freshly made corn pap, a classic Nigerian breakfast.\",\"homeDelivery\":0,\"location\":\"Lagos\",\"quantity\":1}],\"location\":\"Abuja\",\"note\":\"Pickup at restaurant location\",\"total\":94,\"currency\":\"NGN\",\"hasHomeDelivery\":false,\"timestamp\":\"2025-10-09T09:21:21.223Z\",\"user_id\":18,\"user_email\":\"kiscotech1@gmail.com\"}', 94.00, NULL, 0.00, NULL, 'completed', 'BOS4923288161760001681224', NULL, NULL, 'Abuja', 'Pickup at restaurant location', 'delivered', '2025-10-09 09:21:27'),
(10, 18, NULL, '{\"items\":[{\"id\":6,\"name\":\"Beef Burger with Fries\",\"price\":20,\"imageUrl\":\"../food/images/burger.jpeg\",\"description\":\"Classic beef burger with lettuce, tomato, cheese, special sauce, and crispy golden fries.\",\"homeDelivery\":0,\"location\":\"Abuja\",\"quantity\":1}],\"location\":\"Abuja\",\"note\":\"Pickup at restaurant location\",\"total\":20,\"currency\":\"NGN\",\"hasHomeDelivery\":false,\"timestamp\":\"2025-10-09T16:57:05.861Z\",\"user_id\":18,\"user_email\":\"kiscotech1@gmail.com\"}', 20.00, NULL, 0.00, NULL, 'completed', 'BOS9981673641760029025861', NULL, NULL, 'Abuja', 'Pickup at restaurant location', 'delivered', '2025-10-09 16:57:35'),
(11, 18, NULL, '{\"items\":[{\"id\":12,\"name\":\"Akara and Pap\",\"price\":10,\"imageUrl\":\"../food/images/akara.jpg\",\"description\":\"Deep-fried bean cakes served with freshly made corn pap, a classic Nigerian breakfast.\",\"homeDelivery\":0,\"location\":\"Lagos\",\"quantity\":1}],\"location\":\"Lagos\",\"note\":\"Pickup at restaurant location\",\"total\":10,\"currency\":\"NGN\",\"hasHomeDelivery\":false,\"timestamp\":\"2025-10-09T18:14:03.841Z\",\"user_id\":18,\"user_email\":\"kiscotech1@gmail.com\"}', 10.00, NULL, 0.00, NULL, 'completed', 'BOS7686070311760033643842', NULL, NULL, 'Lagos', 'Pickup at restaurant location', 'pending', '2025-10-09 18:14:23'),
(12, 21, NULL, '{\"items\":[{\"id\":12,\"name\":\"Akara and Pap\",\"price\":10,\"imageUrl\":\"../food/images/akara.jpg\",\"description\":\"Deep-fried bean cakes served with freshly made corn pap, a classic Nigerian breakfast.\",\"homeDelivery\":0,\"location\":\"Lagos\",\"quantity\":1}],\"location\":\"Lagos\",\"note\":\"Pickup at restaurant location\",\"total\":10,\"currency\":\"NGN\",\"hasHomeDelivery\":false,\"timestamp\":\"2025-10-10T13:12:02.932Z\",\"user_id\":21,\"user_email\":\"kiscotech2@gmail.com\"}', 10.00, NULL, 0.00, NULL, 'completed', 'BOS1602502131760101922933', NULL, NULL, 'Lagos', 'Pickup at restaurant location', 'pending', '2025-10-10 13:14:50'),
(13, 21, NULL, '{\"items\":[{\"id\":12,\"name\":\"Akara and Pap\",\"price\":10,\"imageUrl\":\"../food/images/akara.jpg\",\"description\":\"Deep-fried bean cakes served with freshly made corn pap, a classic Nigerian breakfast.\",\"homeDelivery\":0,\"location\":\"Lagos\",\"quantity\":1}],\"location\":\"Lagos\",\"note\":\"Pickup at restaurant location\",\"total\":10,\"currency\":\"NGN\",\"hasHomeDelivery\":false,\"timestamp\":\"2025-10-10T13:14:58.514Z\",\"user_id\":21,\"user_email\":\"kiscotech2@gmail.com\"}', 10.00, NULL, 0.00, NULL, 'completed', 'BOS1311622691760102098514', NULL, NULL, 'Lagos', 'Pickup at restaurant location', 'pending', '2025-10-10 13:15:48'),
(14, 21, NULL, '{\"items\":[{\"id\":12,\"name\":\"Akara and Pap\",\"price\":10,\"imageUrl\":\"../food/images/akara.jpg\",\"description\":\"Deep-fried bean cakes served with freshly made corn pap, a classic Nigerian breakfast.\",\"homeDelivery\":0,\"location\":\"Lagos\",\"quantity\":1}],\"location\":\"Lagos\",\"note\":\"Pickup at restaurant location\",\"total\":10,\"currency\":\"NGN\",\"hasHomeDelivery\":false,\"timestamp\":\"2025-10-12T11:27:58.321Z\",\"user_id\":21,\"user_email\":\"kiscotech2@gmail.com\"}', 10.00, NULL, 0.00, NULL, 'completed', 'BOS1932655811760268478335', NULL, NULL, 'Lagos', 'Pickup at restaurant location', 'pending', '2025-10-12 11:29:25'),
(15, 21, 11, '{\"items\":[{\"id\":27,\"name\":\"Beef Burger with Fries\",\"price\":300,\"imageUrl\":\"food\\/images\\/1760278689_burger.jpeg\",\"description\":\"Classic beef burger with lettuce, tomato, cheese, special sauce, and crispy golden fries.\",\"homeDelivery\":0,\"location\":\"Kogi\",\"companyAddress\":\"Okene, Kogi State, Nigeria\",\"companyName\":\"FOOD AND SAUCES\",\"quantity\":1},{\"id\":28,\"name\":\"Chicken and Chips\",\"price\":2500,\"imageUrl\":\"food\\/images\\/1760279130_jollof.png\",\"description\":\"Crispy fried chicken served with french fries and coleslaw, perfect for quick meals.\",\"homeDelivery\":1,\"location\":\"Kogi\",\"companyAddress\":\"Okene, Kogi State, Nigeria\",\"companyName\":\"FOOD AND SAUCES\",\"quantity\":1}],\"location\":\"Ekiti\",\"note\":\"Federal Road, Ado Ekiti 360102, Ekiti, Nigeria\",\"total\":2800,\"original_total\":2800,\"discount_amount\":0,\"coupon_code\":null,\"currency\":\"NGN\",\"hasHomeDelivery\":true,\"timestamp\":\"2025-10-12T18:57:32.220Z\",\"user_id\":21,\"user_email\":\"kiscotech2@gmail.com\"}', 2800.00, NULL, 0.00, NULL, 'completed', 'BOSEATS_216367236', NULL, NULL, 'Ekiti', 'Federal Road, Ado Ekiti 360102, Ekiti, Nigeria', 'pending', '2025-10-12 18:57:44'),
(16, 21, 11, '{\"items\":[{\"id\":28,\"name\":\"Chicken and Chips\",\"price\":2500,\"imageUrl\":\"food\\/images\\/1760279130_jollof.png\",\"description\":\"Crispy fried chicken served with french fries and coleslaw, perfect for quick meals.\",\"homeDelivery\":1,\"location\":\"Kogi\",\"companyAddress\":\"Okene, Kogi State, Nigeria\",\"companyName\":\"FOOD AND SAUCES\",\"quantity\":1}],\"location\":\"Ekiti\",\"note\":\"F01 Kubwa Abuja behind living faith church\",\"total\":2500,\"original_total\":2500,\"discount_amount\":0,\"coupon_code\":null,\"currency\":\"NGN\",\"hasHomeDelivery\":true,\"timestamp\":\"2025-10-12T18:59:05.530Z\",\"user_id\":21,\"user_email\":\"kiscotech2@gmail.com\"}', 2500.00, NULL, 0.00, NULL, 'completed', 'BOSEATS_995976063', NULL, NULL, 'Ekiti', 'F01 Kubwa Abuja behind living faith church', 'pending', '2025-10-12 18:59:20'),
(17, 21, 11, '{\"items\":[{\"id\":29,\"name\":\"Chicken Biryani\",\"price\":5500,\"imageUrl\":\"food\\/images\\/1760279417_BBQ-Ribs.jpeg\",\"description\":\"Fragrant rice dish cooked with marinated chicken, spices, and herbs in the traditional Indian style.\",\"homeDelivery\":1,\"location\":\"Ekiti\",\"companyAddress\":\"Okene, Kogi State, Nigeria\",\"companyName\":\"FOOD AND SAUCES\",\"quantity\":2}],\"location\":\"Ekiti\",\"note\":\"Amoyo, Ilorin\",\"total\":11000,\"original_total\":11000,\"discount_amount\":0,\"coupon_code\":null,\"currency\":\"NGN\",\"hasHomeDelivery\":true,\"timestamp\":\"2025-10-12T19:40:50.025Z\",\"user_id\":21,\"user_email\":\"kiscotech2@gmail.com\"}', 11000.00, NULL, 0.00, NULL, 'completed', 'BOSEATS_779257191', NULL, NULL, 'Ekiti', 'Amoyo, Ilorin', 'pending', '2025-10-12 19:41:16'),
(18, 21, 11, '{\"items\":[{\"id\":27,\"name\":\"Beef Burger with Fries\",\"price\":300,\"imageUrl\":\"food\\/images\\/1760278689_burger.jpeg\",\"description\":\"Classic beef burger with lettuce, tomato, cheese, special sauce, and crispy golden fries.\",\"homeDelivery\":0,\"location\":\"Kogi\",\"companyAddress\":\"Okene, Kogi State, Nigeria\",\"companyName\":\"FOOD AND SAUCES\",\"quantity\":1}],\"location\":\"Ekiti\",\"note\":\"Okene, Kogi State, Nigeria\",\"total\":300,\"original_total\":300,\"discount_amount\":0,\"coupon_code\":null,\"currency\":\"NGN\",\"hasHomeDelivery\":true,\"timestamp\":\"2025-10-12T19:59:45.272Z\",\"user_id\":21,\"user_email\":\"kiscotech2@gmail.com\"}', 300.00, NULL, 0.00, NULL, 'completed', 'BOSEATS_242466443', NULL, NULL, 'Ekiti', 'Okene, Kogi State, Nigeria', 'pending', '2025-10-12 20:00:31'),
(19, 21, 11, '{\"items\":[{\"id\":28,\"name\":\"Chicken and Chips\",\"price\":2500,\"imageUrl\":\"food\\/images\\/1760279130_jollof.png\",\"description\":\"Crispy fried chicken served with french fries and coleslaw, perfect for quick meals.\",\"homeDelivery\":1,\"location\":\"Kogi\",\"companyAddress\":\"Okene, Kogi State, Nigeria\",\"companyName\":\"FOOD AND SAUCES\",\"quantity\":1},{\"id\":29,\"name\":\"Chicken Biryani\",\"price\":5200,\"imageUrl\":\"food\\/images\\/1760279417_BBQ-Ribs.jpeg\",\"description\":\"Fragrant rice dish cooked with marinated chicken, spices, and herbs in the traditional Indian style.\",\"homeDelivery\":0,\"location\":\"Ekiti\",\"companyAddress\":\"Okene, Kogi State, Nigeria\",\"companyName\":\"FOOD AND SAUCES\",\"quantity\":1}],\"location\":\"Ekiti\",\"note\":\"AANUOLUWAPO STREET,BEHIND TESSY GAS,ILAWE ROAD ,ADO EKITI\",\"total\":7700,\"original_total\":7700,\"discount_amount\":0,\"coupon_code\":null,\"currency\":\"NGN\",\"hasHomeDelivery\":true,\"timestamp\":\"2025-10-12T23:17:20.612Z\",\"user_id\":21,\"user_email\":\"kiscotech2@gmail.com\"}', 7700.00, NULL, 0.00, NULL, 'completed', 'BOSEATS_90133318', NULL, NULL, 'Ekiti', 'AANUOLUWAPO STREET,BEHIND TESSY GAS,ILAWE ROAD ,ADO EKITI', 'pending', '2025-10-12 23:17:34'),
(20, 21, 11, '{\"items\":[{\"id\":30,\"name\":\"NathanGloria Ovorke OkpanachiPayne\",\"price\":4344,\"imageUrl\":\"uploads\\/products\\/68ec2fa055a34_1760309152.jpeg\",\"description\":\"NathanGloria Ovorke OkpanachiPayneNathanGloria Ovorke OkpanachiPayne\",\"homeDelivery\":0,\"location\":\"Ondo\",\"companyAddress\":\"Okene, Kogi State, Nigeria\",\"companyName\":\"FOOD AND SAUCES\",\"quantity\":1}],\"location\":\"Ekiti\",\"note\":\"Okene, Kogi State, Nigeria\",\"total\":4344,\"original_total\":4344,\"discount_amount\":0,\"coupon_code\":null,\"currency\":\"NGN\",\"hasHomeDelivery\":true,\"timestamp\":\"2025-10-12T23:58:25.821Z\",\"user_id\":21,\"user_email\":\"kiscotech2@gmail.com\"}', 4344.00, NULL, 0.00, NULL, 'completed', 'BOSEATS_649122834', NULL, NULL, 'Ekiti', 'Okene, Kogi State, Nigeria', 'pending', '2025-10-12 23:58:49'),
(21, 21, 11, '{\"items\":[{\"id\":30,\"name\":\"NathanGloria Ovorke OkpanachiPayne\",\"price\":4344,\"imageUrl\":\"uploads\\/products\\/68ec2fa055a34_1760309152.jpeg\",\"description\":\"NathanGloria Ovorke OkpanachiPayneNathanGloria Ovorke OkpanachiPayne\",\"homeDelivery\":0,\"location\":\"Ondo\",\"companyAddress\":\"Okene, Kogi State, Nigeria\",\"companyName\":\"FOOD AND SAUCES\",\"quantity\":1}],\"location\":\"Lagos\",\"note\":\"Okene, Kogi State, Nigeria\",\"total\":4344,\"original_total\":4344,\"discount_amount\":0,\"coupon_code\":null,\"currency\":\"NGN\",\"hasHomeDelivery\":true,\"timestamp\":\"2025-10-12T23:59:31.694Z\",\"user_id\":21,\"user_email\":\"kiscotech2@gmail.com\"}', 4344.00, NULL, 0.00, NULL, 'completed', 'BOSEATS_99252616', NULL, NULL, 'Lagos', 'Okene, Kogi State, Nigeria', 'pending', '2025-10-12 23:59:40'),
(24, 21, 11, '{\"items\":[{\"id\":28,\"name\":\"Chicken and Chips\",\"price\":2500,\"imageUrl\":\"food\\/images\\/1760279130_jollof.png\",\"description\":\"Crispy fried chicken served with french fries and coleslaw, perfect for quick meals.\",\"homeDelivery\":1,\"location\":\"Kogi\",\"companyAddress\":\"Okene, Kogi State, Nigeria\",\"companyName\":\"FOOD AND SAUCES\",\"quantity\":1}],\"location\":\"Kogi\",\"note\":\"Federal Road, Ado Ekiti 360102, Ekiti, Nigeria\",\"total\":2500,\"original_total\":2500,\"discount_amount\":0,\"coupon_code\":null,\"currency\":\"NGN\",\"hasHomeDelivery\":true,\"timestamp\":\"2025-10-13T00:29:25.877Z\",\"user_id\":21,\"user_email\":\"kiscotech2@gmail.com\"}', 2500.00, NULL, 0.00, NULL, 'completed', 'BOSEATS_598300322', NULL, NULL, 'Kogi', 'Federal Road, Ado Ekiti 360102, Ekiti, Nigeria', 'pending', '2025-10-13 00:29:39'),
(25, 21, 11, '{\"items\":[{\"id\":27,\"name\":\"Beef Burger with Fries\",\"price\":300,\"imageUrl\":\"food\\/images\\/1760278689_burger.jpeg\",\"description\":\"Classic beef burger with lettuce, tomato, cheese, special sauce, and crispy golden fries.\",\"homeDelivery\":0,\"location\":\"Kogi\",\"companyAddress\":\"Okene, Kogi State, Nigeria\",\"companyName\":\"FOOD AND SAUCES\",\"quantity\":1}],\"location\":\"Ekiti\",\"note\":\"Okene, Kogi State, Nigeria\",\"total\":300,\"original_total\":300,\"discount_amount\":0,\"coupon_code\":null,\"currency\":\"NGN\",\"hasHomeDelivery\":true,\"timestamp\":\"2025-10-13T00:35:33.639Z\",\"user_id\":21,\"user_email\":\"kiscotech2@gmail.com\"}', 300.00, NULL, 0.00, NULL, 'completed', 'BOSEATS_754014004', NULL, NULL, 'Ekiti', 'Okene, Kogi State, Nigeria', 'delivered', '2025-10-13 00:35:41'),
(26, 21, 11, '{\"items\":[{\"id\":30,\"name\":\"NathanGloria Ovorke OkpanachiPayne\",\"price\":4344,\"imageUrl\":\"uploads\\/products\\/68ec2fa055a34_1760309152.jpeg\",\"description\":\"NathanGloria Ovorke OkpanachiPayneNathanGloria Ovorke OkpanachiPayne\",\"homeDelivery\":0,\"location\":\"Ondo\",\"companyAddress\":\"Okene, Kogi State, Nigeria\",\"companyName\":\"FOOD AND SAUCES\",\"deliveryFee\":0,\"quantity\":3}],\"location\":\"Ekiti\",\"note\":\"Okene, Kogi State, Nigeria\",\"total\":13032,\"original_total\":13032,\"discount_amount\":0,\"delivery_fee\":0,\"coupon_code\":null,\"currency\":\"NGN\",\"hasHomeDelivery\":false,\"timestamp\":\"2025-10-13T23:43:02.880Z\",\"user_id\":21,\"user_email\":\"kiscotech2@gmail.com\"}', 13032.00, NULL, 0.00, NULL, 'completed', 'BOSEATS_513799563', NULL, NULL, 'Ekiti', 'Okene, Kogi State, Nigeria', 'pending', '2025-10-13 23:43:12'),
(27, 21, 11, '{\"items\":[{\"id\":35,\"name\":\"GARRI AND SUGAR\",\"price\":1500,\"imageUrl\":\"uploads\\/products\\/68ed8dc97637e_1760398793.png\",\"description\":\"sweet Nigeria white garri with a lot of goodies.\",\"homeDelivery\":1,\"location\":\"Abia\",\"companyAddress\":\"Okene, Kogi State, Nigeria\",\"companyName\":\"FOOD AND SAUCES\",\"deliveryFee\":200,\"quantity\":1}],\"location\":\"Abuja\",\"note\":\"F01 Kubwa Abuja behind living faith church\",\"total\":1700,\"original_total\":1700,\"discount_amount\":0,\"delivery_fee\":200,\"coupon_code\":null,\"currency\":\"NGN\",\"hasHomeDelivery\":true,\"timestamp\":\"2025-10-14T10:29:27.407Z\",\"user_id\":21,\"user_email\":\"kiscotech2@gmail.com\"}', 1700.00, NULL, 0.00, NULL, 'completed', 'BOSEATS_989493705', NULL, NULL, 'Abuja', 'F01 Kubwa Abuja behind living faith church', 'pending', '2025-10-14 10:29:43'),
(28, 21, 11, '{\"items\":[{\"id\":29,\"name\":\"Chicken Biryani\",\"price\":5200,\"imageUrl\":\"..\\/uploads\\/products\\/1760279417_BBQ-Ribs.jpeg\",\"description\":\"Fragrant rice dish cooked with marinated chicken, spices, and herbs in the traditional Indian style.\",\"homeDelivery\":0,\"pickupAvailable\":1,\"location\":\"Ekiti\",\"companyAddress\":\"Okene, Kogi State, Nigeria\",\"companyName\":\"FOOD AND SAUCES\",\"deliveryFee\":0,\"quantity\":1},{\"id\":28,\"name\":\"Chicken and Chips\",\"price\":2500,\"imageUrl\":\"..\\/uploads\\/products\\/1760279130_jollof.png\",\"description\":\"Crispy fried chicken served with french fries and coleslaw, perfect for quick meals.\",\"homeDelivery\":1,\"pickupAvailable\":1,\"location\":\"Kogi\",\"companyAddress\":\"Okene, Kogi State, Nigeria\",\"companyName\":\"FOOD AND SAUCES\",\"deliveryFee\":0,\"quantity\":1}],\"location\":\"Kogi\",\"note\":\"AANUOLUWAPO STREET,BEHIND TESSY GAS,ILAWE ROAD ,ADO EKITI\",\"total\":7700,\"original_total\":7700,\"discount_amount\":0,\"delivery_fee\":0,\"coupon_code\":null,\"currency\":\"NGN\",\"hasHomeDelivery\":true,\"hasPickupItems\":true,\"timestamp\":\"2025-10-15T00:32:50.820Z\",\"user_id\":21,\"user_email\":\"kiscotech2@gmail.com\"}', 7700.00, NULL, 0.00, NULL, 'completed', 'BOSEATS_782099402', NULL, NULL, 'Kogi', 'AANUOLUWAPO STREET,BEHIND TESSY GAS,ILAWE ROAD ,ADO EKITI', 'pending', '2025-10-15 00:33:01'),
(29, 21, 11, '{\"items\":[{\"id\":28,\"name\":\"Chicken and Chips\",\"price\":2500,\"imageUrl\":\"..\\/uploads\\/products\\/1760279130_jollof.png\",\"description\":\"Crispy fried chicken served with french fries and coleslaw, perfect for quick meals.\",\"homeDelivery\":1,\"pickupAvailable\":1,\"location\":\"Kogi\",\"companyAddress\":\"Okene, Kogi State, Nigeria\",\"companyName\":\"FOOD AND SAUCES\",\"deliveryFee\":0,\"quantity\":1}],\"location\":\"Ekiti\",\"note\":\"AANUOLUWAPO STREET,BEHIND TESSY GAS,ILAWE ROAD ,ADO EKITI\",\"total\":2500,\"original_total\":2500,\"discount_amount\":0,\"delivery_fee\":0,\"coupon_code\":null,\"currency\":\"NGN\",\"hasHomeDelivery\":true,\"hasPickupItems\":true,\"timestamp\":\"2025-10-15T02:11:39.142Z\",\"user_id\":21,\"user_email\":\"kiscotech2@gmail.com\"}', 2500.00, NULL, 0.00, NULL, 'completed', 'BOSEATS_601037593', NULL, NULL, 'Ekiti', 'AANUOLUWAPO STREET,BEHIND TESSY GAS,ILAWE ROAD ,ADO EKITI', 'pending', '2025-10-15 02:11:48'),
(30, 21, 11, '{\"items\":[{\"id\":28,\"name\":\"Chicken and Chips\",\"price\":2500,\"imageUrl\":\"..\\/uploads\\/products\\/1760279130_jollof.png\",\"description\":\"Crispy fried chicken served with french fries and coleslaw, perfect for quick meals.\",\"homeDelivery\":1,\"pickupAvailable\":1,\"location\":\"Kogi\",\"companyAddress\":\"Okene, Kogi State, Nigeria\",\"companyName\":\"FOOD AND SAUCES\",\"deliveryFee\":0,\"quantity\":1}],\"location\":\"Kogi\",\"note\":\"AANUOLUWAPO STREET,BEHIND TESSY GAS,ILAWE ROAD ,ADO EKITI\",\"total\":2500,\"original_total\":2500,\"discount_amount\":0,\"delivery_fee\":0,\"coupon_code\":null,\"currency\":\"NGN\",\"hasHomeDelivery\":true,\"hasPickupItems\":true,\"timestamp\":\"2025-10-15T02:38:21.150Z\",\"user_id\":21,\"user_email\":\"kiscotech2@gmail.com\"}', 2500.00, NULL, 0.00, NULL, '', 'BOSEATS_333997301', NULL, NULL, 'Kogi', 'AANUOLUWAPO STREET,BEHIND TESSY GAS,ILAWE ROAD ,ADO EKITI', '', '2025-10-15 02:38:29');

-- --------------------------------------------------------

--
-- Table structure for table `order_tracking`
--

CREATE TABLE `order_tracking` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `states`
--

CREATE TABLE `states` (
  `id` int(11) NOT NULL,
  `country_code` varchar(3) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `states`
--

INSERT INTO `states` (`id`, `country_code`, `name`) VALUES
(1, 'NG', 'Lagos'),
(2, 'NG', 'Abuja'),
(3, 'NG', 'Rivers'),
(4, 'GH', 'Accra'),
(5, 'GH', 'Kumasi'),
(6, 'KE', 'Nairobi'),
(7, 'KE', 'Mombasa'),
(8, 'ZA', 'Johannesburg'),
(9, 'ZA', 'Cape Town'),
(10, 'NG', 'Abia'),
(11, 'NG', 'Adamawa'),
(12, 'NG', 'Akwa Ibom'),
(13, 'NG', 'Anambra'),
(14, 'NG', 'Bauchi'),
(15, 'NG', 'Bayelsa'),
(16, 'NG', 'Benue'),
(17, 'NG', 'Borno'),
(18, 'NG', 'Cross River'),
(19, 'NG', 'Delta'),
(20, 'NG', 'Ebonyi'),
(21, 'NG', 'Edo'),
(22, 'NG', 'Ekiti'),
(23, 'NG', 'Enugu'),
(24, 'NG', 'Federal Capital Territory'),
(25, 'NG', 'Gombe'),
(26, 'NG', 'Imo'),
(27, 'NG', 'Jigawa'),
(28, 'NG', 'Kaduna'),
(29, 'NG', 'Kano'),
(30, 'NG', 'Katsina'),
(31, 'NG', 'Kebbi'),
(32, 'NG', 'Kogi'),
(33, 'NG', 'Kwara'),
(34, 'NG', 'Lagos'),
(35, 'NG', 'Nasarawa'),
(36, 'NG', 'Niger'),
(37, 'NG', 'Ogun'),
(38, 'NG', 'Ondo'),
(39, 'NG', 'Osun'),
(40, 'NG', 'Oyo'),
(41, 'NG', 'Plateau'),
(42, 'NG', 'Rivers'),
(43, 'NG', 'Sokoto'),
(44, 'NG', 'Taraba'),
(45, 'NG', 'Yobe'),
(46, 'NG', 'Zamfara'),
(47, 'GH', 'Accra'),
(48, 'GH', 'Kumasi'),
(49, 'GH', 'Tamale'),
(50, 'KE', 'Nairobi'),
(51, 'KE', 'Mombasa'),
(52, 'KE', 'Kisumu'),
(53, 'ZA', 'Johannesburg'),
(54, 'ZA', 'Cape Town'),
(55, 'ZA', 'Durban');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `country` varchar(50) NOT NULL,
  `state` varchar(50) NOT NULL,
  `phone_code` varchar(10) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `gender`, `country`, `state`, `phone_code`, `phone`, `profile_picture`, `password`, `created_at`, `updated_at`) VALUES
(15, 'ss', 'HEPHZIBAH', 'kiscotech1d@gmail.com', NULL, 'Nigeria', 'Bauchi', '+234', '09023723389', NULL, '$2y$10$neznF.8d0u3MuEsVeJkEmOpdI3DsT5x9J00gkgV0ES1on9jrg0DH6', '2025-09-28 22:13:19', '2025-09-28 22:13:19'),
(18, 'KISCO', 'TECH', 'kiscotech1@gmail.com', 'male', 'NG', 'Kogi', '+234', '08130041735', 'user_18_1760000996.jpg', '$2y$10$5qS6mlHWky1pCS0WEPyLtuvnNZKiTosbL0UKNg9kLPU0/SOYnR4DO', '2025-10-08 12:18:29', '2025-10-09 20:24:49'),
(21, 'KISCO', 'TECH', 'kiscotech2@gmail.com', NULL, 'Nigeria', 'Delta', '+234', '08130041735', 'user_21_1760051668.jpg', '$2y$10$tG9ww9nzmpTpIpkpCz1N9.Pb0sRzikhQtZ8IrfD5VyHixgqyGCGYO', '2025-10-09 23:12:53', '2025-10-09 23:14:28'),
(25, 'KISCO', 'TECH', 'kiscotech3@gmail.com', NULL, 'Nigeria', 'Adamawa', '+234', '08130041735', NULL, '$2y$10$vWphfAkDqgoZ.Y1JYLAp6.3eJvUsf1W5bkkBSix9UHJsFYSbD17Fy', '2025-10-10 14:22:03', '2025-10-10 14:22:03'),
(29, 'James', 'Ozavinoyi', 'friday.m2104484@st.futminna.edu.ng', NULL, 'South Africa', 'North West', '+27', '08135991482', NULL, '$2y$10$IB4rwAgqPRw2j9Epf.V1MuKqScRiw26EFgw5iHRQqBxjXQdFjJKDu', '2025-10-10 15:10:14', '2025-10-10 15:10:14');

-- --------------------------------------------------------

--
-- Table structure for table `user_carts`
--

CREATE TABLE `user_carts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `food_items` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_coupons`
--

CREATE TABLE `user_coupons` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `coupon_code` varchar(50) NOT NULL,
  `discount_offer_id` int(11) NOT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_coupons`
--

INSERT INTO `user_coupons` (`id`, `user_id`, `coupon_code`, `discount_offer_id`, `used_at`, `created_at`) VALUES
(1, 18, 'WELCOME20', 1, NULL, '2025-10-09 17:51:35');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_price` decimal(10,2) NOT NULL,
  `product_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `discount_offers`
--
ALTER TABLE `discount_offers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `coupon_code` (`coupon_code`);

--
-- Indexes for table `food_items`
--
ALTER TABLE `food_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_food_items_merchant` (`merchant_id`);

--
-- Indexes for table `merchants`
--
ALTER TABLE `merchants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `merchant_payouts`
--
ALTER TABLE `merchant_payouts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `merchant_id` (`merchant_id`);

--
-- Indexes for table `merchant_preferences`
--
ALTER TABLE `merchant_preferences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `merchant_id` (`merchant_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `merchant_id` (`merchant_id`);

--
-- Indexes for table `order_tracking`
--
ALTER TABLE `order_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `states`
--
ALTER TABLE `states`
  ADD PRIMARY KEY (`id`),
  ADD KEY `country_code` (`country_code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_carts`
--
ALTER TABLE `user_carts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_coupons`
--
ALTER TABLE `user_coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_coupon` (`user_id`,`coupon_code`),
  ADD KEY `discount_offer_id` (`discount_offer_id`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `discount_offers`
--
ALTER TABLE `discount_offers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `food_items`
--
ALTER TABLE `food_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `merchants`
--
ALTER TABLE `merchants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `merchant_payouts`
--
ALTER TABLE `merchant_payouts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `merchant_preferences`
--
ALTER TABLE `merchant_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `order_tracking`
--
ALTER TABLE `order_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `states`
--
ALTER TABLE `states`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `user_carts`
--
ALTER TABLE `user_carts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `user_coupons`
--
ALTER TABLE `user_coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `food_items`
--
ALTER TABLE `food_items`
  ADD CONSTRAINT `fk_food_items_merchant` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `merchant_payouts`
--
ALTER TABLE `merchant_payouts`
  ADD CONSTRAINT `merchant_payouts_ibfk_1` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `merchant_preferences`
--
ALTER TABLE `merchant_preferences`
  ADD CONSTRAINT `merchant_preferences_ibfk_1` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`);

--
-- Constraints for table `order_tracking`
--
ALTER TABLE `order_tracking`
  ADD CONSTRAINT `order_tracking_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `states`
--
ALTER TABLE `states`
  ADD CONSTRAINT `states_ibfk_1` FOREIGN KEY (`country_code`) REFERENCES `countries` (`code`);

--
-- Constraints for table `user_carts`
--
ALTER TABLE `user_carts`
  ADD CONSTRAINT `user_carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_coupons`
--
ALTER TABLE `user_coupons`
  ADD CONSTRAINT `user_coupons_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_coupons_ibfk_2` FOREIGN KEY (`discount_offer_id`) REFERENCES `discount_offers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- Complete Hotel Booking Database Schema
-- BoseatsAfrica Hotel Management System

-- ================================================
-- TABLES
-- ================================================

-- Hotels Table
CREATE TABLE IF NOT EXISTS `hotels` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `owner_id` INT(11) NOT NULL,
  `company_name` VARCHAR(255) DEFAULT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `bookingrules` TEXT,
  `location` VARCHAR(255) NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `country` VARCHAR(100) NOT NULL,
  `address` TEXT,
  `latitude` DECIMAL(10, 8) DEFAULT NULL,
  `longitude` DECIMAL(11, 8) DEFAULT NULL,
  `type` VARCHAR(100) DEFAULT 'Hotel',
  `rating` INT(1) DEFAULT 3,
  `price_per_night` DECIMAL(10, 2) NOT NULL,
  `bedrooms` INT(3) DEFAULT 1,
  `bathrooms` INT(3) DEFAULT 1,
  `nights` INT(3) DEFAULT 1,
  `featured` TINYINT(1) DEFAULT 0,
  `status` ENUM('active', 'inactive', 'pending') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `owner_id` (`owner_id`),
  KEY `city` (`city`),
  KEY `status` (`status`),
  KEY `featured` (`featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Hotel Images Table
CREATE TABLE IF NOT EXISTS `hotel_images` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `hotel_id` INT(11) NOT NULL,
  `image_url` VARCHAR(500) NOT NULL,
  `is_primary` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `hotel_id` (`hotel_id`),
  FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Room Types Table
CREATE TABLE IF NOT EXISTS `room_types` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `hotel_id` INT(11) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `bookingrules` TEXT,
  `price` DECIMAL(10, 2) NOT NULL,
  `capacity` INT(2) DEFAULT 2,
  `available` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `hotel_id` (`hotel_id`),
  FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Amenities Table
CREATE TABLE IF NOT EXISTS `amenities` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `icon` VARCHAR(50) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Hotel Amenities Junction Table
CREATE TABLE IF NOT EXISTS `hotel_amenities` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `hotel_id` INT(11) NOT NULL,
  `amenity_id` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `hotel_id` (`hotel_id`),
  KEY `amenity_id` (`amenity_id`),
  FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`amenity_id`) REFERENCES `amenities`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `hotel_amenity` (`hotel_id`, `amenity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Hotel Bookings Table
CREATE TABLE IF NOT EXISTS `hotel_bookings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `hotel_id` INT(11) NOT NULL,
  `customer_name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `checkin_date` DATE NOT NULL,
  `checkout_date` DATE NOT NULL,
  `room_type_id` INT(11) NOT NULL,
  `children` INT(2) DEFAULT 0,
  `adults` INT(2) DEFAULT 1,
  `room_number` VARCHAR(50) DEFAULT NULL,
  `nights` INT(3) NOT NULL,
  `total_price` DECIMAL(10, 2) NOT NULL,
  `payment_reference` VARCHAR(100) NOT NULL,
  `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
  `booking_status` ENUM('confirmed', 'cancelled', 'completed', 'no-show') DEFAULT 'confirmed',
  `booking_reference` VARCHAR(50) NOT NULL,
  `special_requests` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `hotel_id` (`hotel_id`),
  KEY `booking_reference` (`booking_reference`),
  KEY `payment_reference` (`payment_reference`),
  KEY `payment_status` (`payment_status`),
  KEY `booking_status` (`booking_status`),
  FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`room_type_id`) REFERENCES `room_types`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Hotel Reviews Table
CREATE TABLE IF NOT EXISTS `hotel_reviews` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `hotel_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `booking_id` INT(11) DEFAULT NULL,
  `rating` INT(1) NOT NULL,
  `review` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `hotel_id` (`hotel_id`),
  KEY `user_id` (`user_id`),
  KEY `booking_id` (`booking_id`),
  FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`booking_id`) REFERENCES `hotel_bookings`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User Favorites Table
CREATE TABLE IF NOT EXISTS `user_favorites` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `item_id` INT(11) NOT NULL,
  `item_type` ENUM('hotel', 'food', 'car', 'flight', 'ticket') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `item_id` (`item_id`),
  KEY `item_type` (`item_type`),
  UNIQUE KEY `unique_favorite` (`user_id`, `item_id`, `item_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================
-- AMENITIES DATA
-- ================================================

INSERT INTO `amenities` (`name`, `icon`) VALUES
('Gate & Security', 'fa-shield-alt'),
('Interlocked road', 'fa-road'),
('All rooms en-suite', 'fa-door-closed'),
('Ample car park', 'fa-car'),
('Steady Electricity', 'fa-bolt'),
('High speed Internet access', 'fa-wifi'),
('Green Area', 'fa-tree'),
('Portable water', 'fa-water'),
('Swimming Pool', 'fa-swimming-pool'),
('Gym', 'fa-dumbbell'),
('Restaurant', 'fa-utensils'),
('Bar', 'fa-cocktail'),
('Spa', 'fa-spa'),
('Conference Room', 'fa-users'),
('Laundry Service', 'fa-tshirt'),
('Room Service', 'fa-concierge-bell'),
('Air Conditioning', 'fa-fan'),
('TV', 'fa-tv'),
('Minibar', 'fa-glass-martini'),
('Safe', 'fa-lock')
ON DUPLICATE KEY UPDATE `name`=`name`;

-- ================================================
-- DEMO HOTELS DATA
-- ================================================

INSERT INTO `hotels` (
  `id`,
  `owner_id`, 
  `company_name`, 
  `name`, 
  `description`,
  `bookingrules`, 
  `location`, 
  `city`, 
  `country`, 
  `address`,
  `type`, 
  `rating`, 
  `price_per_night`, 
  `bedrooms`, 
  `bathrooms`, 
  `nights`,
  `featured`, 
  `status`
) VALUES
(1, 1, 'BoseatsAfrica', 'Lagos Marriott Hotel', 
'Welcome to Lagos Marriott Hotel, where sophisticated elegance meets unparalleled comfort in the heart of downtown Lagos bustling business district. Designed for both corporate stays and serene getaways, we offer a sanctuary of refined luxury.',
'Check-in / Check-out: Check-in is at 3:00 PM, and check-out is at 11:00 AM. Please contact the front desk for late check-out requests.
No Smoking: Smoking is strictly prohibited in all indoor areas, including guest rooms. A significant cleaning fee will apply for violations.
Quiet Hours: Please observe quiet hours from 10:00 PM to 7:00 AM for the comfort of all guests.
Property Damage: Guests are responsible for any damage caused to hotel property. Charges will apply for repairs or replacements.
Occupancy & Visitors: Adhere to the maximum room occupancy. Visitors must register at the front desk and are subject to hotel policies.', 
'Ikeja, Lagos State', 'Lagos', 'Nigeria', 
'123 Obafemi Awolowo Way, Ikeja, Lagos State, Nigeria',
'Hotel', 4, 30.00, 1, 1, 1, 1, 'active'),

(2, 1, 'Royal Hotels', 'Eko Hotels & Suites', 
'Luxury apartment in the heart of Victoria Island with stunning views and modern amenities. Experience world-class hospitality in Nigeria\'s most prestigious location.',
'Check-in / Check-out: Check-in is at 3:00 PM, and check-out is at 11:00 AM. Please contact the front desk for late check-out requests.
No Smoking: Smoking is strictly prohibited in all indoor areas, including guest rooms. A significant cleaning fee will apply for violations.
Quiet Hours: Please observe quiet hours from 10:00 PM to 7:00 AM for the comfort of all guests.
Property Damage: Guests are responsible for any damage caused to hotel property. Charges will apply for repairs or replacements.
Occupancy & Visitors: Adhere to the maximum room occupancy. Visitors must register at the front desk and are subject to hotel policies.', 
'Victoria Island, Lagos', 'Lagos', 'Nigeria',
'Plot 1415 Adetokunbo Ademola Street, Victoria Island, Lagos, Nigeria',
'Apartment', 5, 50.00, 3, 2, 1, 1, 'active'),

(3, 1, 'Transcorp Hotels', 'Transcorp Hilton Abuja', 
'Experience world-class hospitality in Nigeria\'s capital city with premium facilities. Located in the heart of Abuja\'s diplomatic zone, offering unparalleled luxury and service.',
'Check-in / Check-out: Check-in is at 3:00 PM, and check-out is at 11:00 AM. Please contact the front desk for late check-out requests.
No Smoking: Smoking is strictly prohibited in all indoor areas, including guest rooms. A significant cleaning fee will apply for violations.
Quiet Hours: Please observe quiet hours from 10:00 PM to 7:00 AM for the comfort of all guests.
Property Damage: Guests are responsible for any damage caused to hotel property. Charges will apply for repairs or replacements.
Occupancy & Visitors: Adhere to the maximum room occupancy. Visitors must register at the front desk and are subject to hotel policies.', 
'Maitama District, Abuja', 'Abuja', 'Nigeria',
'1 Aguiyi Ironsi Street, Maitama District, Abuja, Nigeria',
'Hotel', 5, 45.00, 2, 2, 1, 0, 'active');

-- ================================================
-- DEMO ROOM TYPES DATA
-- ================================================

INSERT INTO `room_types` (`hotel_id`, `name`, `description`, `bookingrules`, `price`, `capacity`, `available`) VALUES
-- Lagos Marriott Hotel Rooms
(1, 'Studio Apartment', 'Cozy studio apartment with all modern amenities including kitchenette, work desk, and comfortable seating area.', 'Maximum 2 guests. No pets allowed. Late checkout subject to availability.', 30.00, 2, 1),
(1, 'Deluxe Room', 'Spacious room with king-size bed, luxury bathroom, minibar, and stunning city views.', 'Maximum 2 guests. Complimentary breakfast included. No smoking.', 40.00, 2, 1),
(1, 'Executive Suite', 'Luxurious suite with separate living area, work space, premium amenities, and panoramic city views.', 'Maximum 3 guests. Club lounge access included. Butler service available.', 60.00, 3, 1),
(1, 'Family Room', 'Large room with two queen beds, perfect for families. Includes separate sitting area.', 'Maximum 4 guests. Complimentary breakfast for 2 adults and 2 children.', 75.00, 4, 1),

-- Eko Hotels & Suites Rooms
(2, 'Standard Room', 'Comfortable room with modern furnishings and essential amenities for business or leisure travelers.', 'Maximum 2 guests. Daily housekeeping included. No smoking.', 50.00, 2, 1),
(2, 'Executive Room', 'Perfect for business travelers with work desk, high-speed internet, and complimentary business center access.', 'Maximum 2 guests. Executive lounge access. Complimentary breakfast.', 70.00, 2, 1),
(2, 'Junior Suite', 'Spacious suite with separate bedroom and living area, offering comfort and elegance.', 'Maximum 3 guests. Mini-bar included. Late checkout available.', 100.00, 3, 1),
(2, 'Presidential Suite', 'Ultimate luxury experience with multiple bedrooms, dining area, kitchen, and private balcony with ocean views.', 'Maximum 6 guests. Personal butler service. Private check-in/out. Complimentary spa access.', 150.00, 6, 1),

-- Transcorp Hilton Abuja Rooms
(3, 'Standard Room', 'Comfortable room with essential amenities, work desk, and modern bathroom facilities.', 'Maximum 2 guests. Complimentary WiFi. Daily housekeeping.', 45.00, 2, 1),
(3, 'Superior Room', 'Enhanced room with better views, larger space, and upgraded amenities.', 'Maximum 2 guests. Complimentary breakfast. Club access.', 60.00, 2, 1),
(3, 'Family Room', 'Spacious room designed for families with multiple beds and sitting area.', 'Maximum 4 guests. Connecting rooms available. Kids amenities included.', 70.00, 4, 1),
(3, 'Diplomatic Suite', 'Elegant suite with separate living and dining areas, perfect for hosting small meetings.', 'Maximum 4 guests. Butler service. Private lounge access. Meeting room access.', 120.00, 4, 1);

-- ================================================
-- DEMO HOTEL IMAGES DATA
-- ================================================

INSERT INTO `hotel_images` (`hotel_id`, `image_url`, `is_primary`) VALUES
-- Lagos Marriott Hotel Images
(1, '../assets/images/hotels/marriott1.jpg', 1),
(1, '../assets/images/hotels/marriott2.jpg', 0),
(1, '../assets/images/hotels/marriott3.jpg', 0),
(1, '../assets/images/hotels/marriott4.jpg', 0),

-- Eko Hotels & Suites Images
(2, '../assets/images/hotels/eko1.jpg', 1),
(2, '../assets/images/hotels/eko2.jpg', 0),
(2, '../assets/images/hotels/eko3.jpg', 0),
(2, '../assets/images/hotels/eko4.jpg', 0),

-- Transcorp Hilton Abuja Images
(3, '../assets/images/hotels/transcorp1.jpg', 1),
(3, '../assets/images/hotels/transcorp2.jpg', 0),
(3, '../assets/images/hotels/transcorp3.jpg', 0),
(3, '../assets/images/hotels/transcorp4.jpg', 0);

-- ================================================
-- LINK HOTELS WITH AMENITIES
-- ================================================

-- Lagos Marriott Hotel Amenities
INSERT INTO `hotel_amenities` (`hotel_id`, `amenity_id`) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7), (1, 8), 
(1, 9), (1, 10), (1, 11), (1, 16), (1, 17), (1, 18);

-- Eko Hotels & Suites Amenities
INSERT INTO `hotel_amenities` (`hotel_id`, `amenity_id`) VALUES
(2, 1), (2, 3), (2, 4), (2, 5), (2, 6), (2, 9), (2, 10), (2, 11), 
(2, 12), (2, 13), (2, 14), (2, 15), (2, 16), (2, 17), (2, 18), (2, 19), (2, 20);

-- Transcorp Hilton Abuja Amenities
INSERT INTO `hotel_amenities` (`hotel_id`, `amenity_id`) VALUES
(3, 1), (3, 3), (3, 4), (3, 5), (3, 6), (3, 9), (3, 10), (3, 11), 
(3, 13), (3, 14), (3, 15), (3, 16), (3, 17), (3, 18), (3, 20);

-- ================================================
-- INDEXES FOR PERFORMANCE
-- ================================================

-- Additional indexes for better query performance
CREATE INDEX idx_hotels_city_status ON hotels(city, status);
CREATE INDEX idx_hotels_featured_status ON hotels(featured, status);
CREATE INDEX idx_room_types_hotel_available ON room_types(hotel_id, available);
CREATE INDEX idx_bookings_user_status ON hotel_bookings(user_id, booking_status);
CREATE INDEX idx_bookings_hotel_dates ON hotel_bookings(hotel_id, checkin_date, checkout_date);
CREATE INDEX idx_bookings_reference ON hotel_bookings(booking_reference, payment_status);

-- ================================================
-- USEFUL QUERIES FOR MANAGEMENT
-- ================================================

-- Get all bookings for a specific booking reference
-- SELECT * FROM hotel_bookings WHERE booking_reference = 'HOTEL20251017ABC123';

-- Get booking summary with room breakdown
-- SELECT 
--     hb.booking_reference,
--     h.name as hotel_name,
--     hb.customer_name,
--     COUNT(*) as total_rooms,
--     GROUP_CONCAT(DISTINCT rt.name SEPARATOR ', ') as room_types,
--     SUM(hb.total_price) as total_amount,
--     hb.checkin_date,
--     hb.checkout_date,
--     hb.payment_status
-- FROM hotel_bookings hb
-- JOIN hotels h ON hb.hotel_id = h.id
-- JOIN room_types rt ON hb.room_type_id = rt.id
-- GROUP BY hb.booking_reference;

-- Get available rooms for a hotel
-- SELECT rt.*, COUNT(hb.id) as current_bookings
-- FROM room_types rt
-- LEFT JOIN hotel_bookings hb ON rt.id = hb.room_type_id 
--     AND hb.booking_status = 'confirmed'
--     AND hb.checkin_date <= '2025-10-20'
--     AND hb.checkout_date >= '2025-10-17'
-- WHERE rt.hotel_id = 1 AND rt.available = 1
-- GROUP BY rt.id;

-- ================================================
-- COMPLETION MESSAGE
-- ================================================
-- Database schema created successfully!
-- Demo data inserted for 3 hotels with multiple room types each
-- Ready for multi-room booking functionality
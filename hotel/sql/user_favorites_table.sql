-- User Favorites Table (for hotels, food, and other items)
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

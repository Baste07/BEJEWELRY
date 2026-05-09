-- Migration: add order_courier_ratings table
-- Created: 2026-05-07

CREATE TABLE IF NOT EXISTS `order_courier_ratings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` VARCHAR(64) NOT NULL,
  `user_id` INT NOT NULL,
  `courier_user_id` INT DEFAULT NULL,
  `courier_name` VARCHAR(160) DEFAULT NULL,
  `rating` TINYINT NOT NULL,
  `body` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_order_user` (`order_id`, `user_id`),
  KEY `idx_courier_user` (`courier_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

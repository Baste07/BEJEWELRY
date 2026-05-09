-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 16, 2026 at 09:43 PM
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
-- Database: `bejewelry`
--

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `label` varchar(50) DEFAULT 'Home',
  `name` varchar(160) NOT NULL,
  `street` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `province` varchar(100) DEFAULT NULL,
  `zip` varchar(20) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `addresses`
--

INSERT INTO `addresses` (`id`, `user_id`, `label`, `name`, `street`, `city`, `province`, `zip`, `phone`, `is_default`) VALUES
(2, 3, 'Home', 'PATRICK IVAN PILAPIL', '2868 Orion St. Tondo Manila', 'Manila', 'NCR', '1012', '09394551110', 1),
(4, 15, 'Home', 'PATRICK IVAN PILAPIL', '2868 Orion St. Tondo Manila', 'Manila', 'NCR', '1012', '09394551110', 1);

-- --------------------------------------------------------

--
-- Table structure for table `app_settings`
--

CREATE TABLE `app_settings` (
  `key` varchar(80) NOT NULL,
  `value` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `app_settings`
--

INSERT INTO `app_settings` (`key`, `value`, `updated_at`) VALUES
('notifications', '{\"admin_email\":\"\",\"new_order\":true,\"low_stock\":true,\"new_review\":true,\"customer_reg\":true,\"daily_summary\":false}', '2026-03-17 07:43:31'),
('shipping', '{\"shipping_fee\":150,\"free_ship_threshold\":2000,\"tax_rate\":0,\"carrier\":\"lbc\",\"cod_enabled\":true,\"same_day_enabled\":false}', '2026-03-17 07:50:10'),
('store', '{\"store_name\":\"Bejewelry\",\"tagline\":\"Fine Jewellery\",\"currency\":\"PHP\",\"contact_email\":\"\",\"phone\":\"\"}', '2026-03-17 07:28:38');

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `action` enum('login','logout') NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `user_id`, `email`, `action`, `ip`, `user_agent`, `created_at`) VALUES
(1, NULL, 'larga_johnsebastian@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 15:39:28'),
(2, NULL, 'larga_johnsebastian@plpasig.edu.ph', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 15:46:41'),
(3, 9, 'patrickpilapil1313@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 16:12:59'),
(4, 9, 'patrickpilapil1313@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 16:25:03'),
(5, NULL, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 16:25:50'),
(6, NULL, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 16:31:14'),
(7, NULL, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 16:33:40'),
(8, 9, 'patrickpilapil1313@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 06:17:17'),
(9, 9, 'patrickpilapil1313@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 06:39:24'),
(10, 19, 'larga_johnsebastian@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 06:40:40'),
(11, 19, 'larga_johnsebastian@plpasig.edu.ph', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 06:44:20'),
(12, 19, 'larga_johnsebastian@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 06:44:49'),
(13, 19, 'larga_johnsebastian@plpasig.edu.ph', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 06:45:50'),
(14, 19, 'larga_johnsebastian@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 07:42:24'),
(15, 19, 'larga_johnsebastian@plpasig.edu.ph', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 07:43:35'),
(16, 19, 'larga_johnsebastian@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 07:44:20'),
(17, 19, 'larga_johnsebastian@plpasig.edu.ph', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 07:45:13'),
(18, 20, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 07:45:57'),
(19, 20, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 07:46:01'),
(20, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 07:46:54'),
(21, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 07:56:50'),
(22, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 08:39:09'),
(23, 25, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 08:58:51'),
(24, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 09:00:20');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `size` varchar(50) DEFAULT 'One Size',
  `qty` int(11) NOT NULL DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`id`, `user_id`, `product_id`, `size`, `qty`, `added_at`) VALUES
(3, 9, 2, 'One Size', 1, '2026-03-17 07:33:48'),
(15, 25, 1, 'Ring', 1, '2026-04-16 08:39:15'),
(16, 25, 5, 'Ring', 1, '2026-04-16 08:39:20');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(80) NOT NULL,
  `slug` varchar(80) NOT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `sort_order`) VALUES
(1, 'Rings', 'rings', 1),
(2, 'Necklaces', 'necklaces', 2),
(3, 'Bracelets', 'bracelets', 3),
(4, 'Earrings', 'earrings', 4);

-- --------------------------------------------------------

--
-- Table structure for table `customer_notes`
--

CREATE TABLE `customer_notes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `note` text NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_prefs`
--

CREATE TABLE `email_prefs` (
  `user_id` int(11) NOT NULL,
  `order_updates` tinyint(1) DEFAULT 1,
  `launches` tinyint(1) DEFAULT 1,
  `promos` tinyint(1) DEFAULT 0,
  `wishlist` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_prefs`
--

INSERT INTO `email_prefs` (`user_id`, `order_updates`, `launches`, `promos`, `wishlist`, `updated_at`) VALUES
(3, 1, 1, 0, 1, '2026-03-17 03:56:38');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('processing','shipped','delivered','cancelled') DEFAULT 'processing',
  `subtotal` decimal(10,2) NOT NULL,
  `shipping_fee` decimal(10,2) DEFAULT 150.00,
  `total` decimal(10,2) NOT NULL,
  `ship_name` varchar(160) DEFAULT NULL,
  `ship_street` varchar(255) DEFAULT NULL,
  `ship_city` varchar(100) DEFAULT NULL,
  `ship_province` varchar(100) DEFAULT NULL,
  `ship_zip` varchar(20) DEFAULT NULL,
  `ship_phone` varchar(30) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT 'cod',
  `notes` text DEFAULT NULL,
  `courier_user_id` int(11) DEFAULT NULL,
  `courier_name` varchar(160) DEFAULT NULL,
  `courier_assigned_at` datetime DEFAULT NULL,
  `is_flagged` tinyint(1) NOT NULL DEFAULT 0,
  `flag_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `status`, `subtotal`, `shipping_fee`, `total`, `ship_name`, `ship_street`, `ship_city`, `ship_province`, `ship_zip`, `ship_phone`, `payment_method`, `notes`, `is_flagged`, `flag_reason`, `created_at`, `updated_at`) VALUES
('BJ-2026-0001', 3, 'processing', 580.00, 0.00, 580.00, 'PATRICK IVAN PILAPIL', '2868 Orion St. Tondo Manila', 'Manila', 'NCR', '1012', '09394551110', 'cod', 'Standard Shipping · 3-5 business days', 0, NULL, '2026-03-17 01:48:54', '2026-03-17 03:57:04'),
('BJ-2026-0002', 3, 'processing', 580.00, 0.00, 580.00, 'PATRICK IVAN PILAPIL', '2868 Orion St. Tondo Manila', 'Manila', 'NCR', '1012', '09394551110', 'cod', 'Standard Shipping · 3-5 business days', 0, NULL, '2026-03-17 01:49:49', '2026-03-17 02:19:08'),
('BJ-2026-0003', 3, 'pending', 750.00, 0.00, 750.00, 'PATRICK IVAN PILAPIL', '2868 Orion St. Tondo Manila', 'Manila', 'NCR', '1012', '09394551110', 'cod', 'Standard Shipping · 3-5 business days', 0, NULL, '2026-03-17 07:42:35', '2026-03-17 07:42:35'),
('BJ-2026-0004', 3, 'pending', 650.00, 0.00, 650.00, 'PATRICK IVAN PILAPIL', '2868 Orion St. Tondo Manila', 'Manila', 'NCR', '1012', '09394551110', 'ewallet', 'Standard Shipping · 3-5 business days', 0, NULL, '2026-03-17 07:54:53', '2026-03-17 07:54:53'),
('BJ-2026-0005', 3, 'pending', 700.00, 0.00, 700.00, 'PATRICK IVAN PILAPIL', '2868 Orion St. Tondo Manila', 'Manila', 'NCR', '1012', '09394551110', 'ewallet', 'Standard Shipping · 3-5 business days', 0, NULL, '2026-03-17 08:10:12', '2026-03-17 08:10:12'),
('BJ-2026-0006', 3, 'pending', 970.00, 0.00, 970.00, 'PATRICK IVAN PILAPIL', '2868 Orion St. Tondo Manila', 'Manila', 'NCR', '1012', '09394551110', 'ewallet', 'Standard Shipping · 3-5 business days', 0, NULL, '2026-03-17 08:15:00', '2026-03-17 08:15:00'),
('BJ-2026-0007', 3, 'pending', 580.00, 150.00, 730.00, 'PATRICK IVAN PILAPIL', '2868 Orion St. Tondo Manila', 'Manila', 'NCR', '1012', '09394551110', 'paymongo', 'Standard delivery · est. 3–5 business days | paymongo_session:cs_1aa55ac80296ab8e882b0cc3', 0, NULL, '2026-03-26 11:00:19', '2026-03-26 11:00:19'),
('BJ-2026-0008', 3, 'pending', 890.00, 150.00, 1040.00, 'PATRICK IVAN PILAPIL', '2868 Orion St. Tondo Manila', 'Manila', 'NCR', '1012', '09394551110', 'paymongo', 'Standard delivery · est. 3–5 business days | paymongo_session:cs_490bc90039c53f3d45467df2', 0, NULL, '2026-03-26 11:02:52', '2026-03-26 11:02:52'),
('BJ-2026-0009', 3, 'pending', 890.00, 150.00, 1040.00, 'PATRICK IVAN PILAPIL', '2868 Orion St. Tondo Manila', 'Manila', 'NCR', '1012', '09394551110', 'paymongo', 'Standard delivery · est. 3–5 business days | paymongo_session:cs_0bde783a56ae35d0ad9e2718', 0, NULL, '2026-03-26 11:37:45', '2026-03-26 11:37:45'),
('BJ-2026-0010', 15, 'pending', 890.00, 150.00, 1040.00, 'PATRICK IVAN PILAPIL', '2868 Orion St. Tondo Manila', 'Manila', 'NCR', '1012', '09394551110', 'paymongo', 'Standard delivery · est. 3–5 business days | paymongo_session:cs_731f6822ee95a11b23fffb8b', 0, NULL, '2026-03-26 11:46:51', '2026-03-26 11:46:51');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

--
-- Table structure for table `order_delivery_proofs`
--

CREATE TABLE `order_delivery_proofs` (
  `id` int(11) NOT NULL,
  `order_id` varchar(20) NOT NULL,
  `carrier_name` varchar(120) NOT NULL,
  `carrier_reference` varchar(120) DEFAULT NULL,
  `proof_photo` varchar(255) NOT NULL,
  `note` text DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` varchar(20) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `name` varchar(160) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `cat` varchar(80) DEFAULT NULL,
  `size` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `qty` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `name`, `image`, `cat`, `size`, `price`, `qty`) VALUES
(1, 'BJ-2026-0001', 1, 'Solara Ring', NULL, 'Rings', 'Ring', 580.00, 1),
(2, 'BJ-2026-0002', 1, 'Solara Ring', NULL, 'Rings', 'Ring', 580.00, 1),
(3, 'BJ-2026-0003', 5, 'Infinity Band', NULL, 'Rings', 'Ring', 750.00, 1),
(4, 'BJ-2026-0004', 7, 'Diamond Hoops', 'd62014b2daa29d8067892df6.png', 'Earrings', 'One Size', 650.00, 1),
(5, 'BJ-2026-0005', 4, 'Dia Charm Bracelet', NULL, 'Bracelets', 'One Size', 700.00, 1),
(6, 'BJ-2026-0006', 3, 'Celestial Necklace', NULL, 'Necklaces', '18\"', 970.00, 1),
(7, 'BJ-2026-0007', 1, 'Solara Ring', NULL, 'Rings', 'Ring', 580.00, 1),
(8, 'BJ-2026-0008', 8, 'Aurora Bangle', '54a2fb881fe3bddcda9e6120.png', 'Bracelets', 'One Size', 890.00, 1),
(9, 'BJ-2026-0009', 8, 'Aurora Bangle', '54a2fb881fe3bddcda9e6120.png', 'Bracelets', 'One Size', 890.00, 1),
(10, 'BJ-2026-0010', 8, 'Aurora Bangle', '54a2fb881fe3bddcda9e6120.png', 'Bracelets', 'One Size', 890.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `paymongo_checkout_pending`
--

CREATE TABLE `paymongo_checkout_pending` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `checkout_session_id` varchar(128) NOT NULL,
  `pm_state` varchar(64) NOT NULL,
  `expected_total_cents` int(11) NOT NULL,
  `post_json` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `paymongo_checkout_pending`
--

INSERT INTO `paymongo_checkout_pending` (`id`, `user_id`, `checkout_session_id`, `pm_state`, `expected_total_cents`, `post_json`, `created_at`) VALUES
(7, 25, 'cs_06d5787ca8794068eb746430', '373d55ff1f472a0835d8632c05c004b6', 148000, '{\"ship_name\":\"BOGART BATUMBAKAL\",\"ship_street\":\"sampl1\",\"ship_city\":\"Pasig\",\"ship_province\":\"metro manila\",\"ship_zip\":\"1609\",\"ship_phone\":\"09925801741\",\"payment_method\":\"paymongo\",\"shipping_fee\":150,\"notes\":\"Standard delivery · est. 3–5 business days\"}', '2026-04-16 08:39:57');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(160) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `orig_price` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `badge` enum('','new','best','sale') DEFAULT '',
  `stars` tinyint(4) DEFAULT 5,
  `reviews` int(11) DEFAULT 0,
  `size_default` varchar(50) DEFAULT 'One Size',
  `material` varchar(100) DEFAULT NULL,
  `stock` int(11) DEFAULT 100,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `category_id`, `price`, `orig_price`, `image`, `badge`, `stars`, `reviews`, `size_default`, `material`, `stock`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Solara Ring', 'A radiant ring inspired by the sun, crafted in 14K gold.', 1, 580.00, NULL, NULL, 'best', 5, 128, 'Ring', '14K Gold', 100, 1, '2026-03-16 23:52:50', '2026-03-16 23:52:50'),
(2, 'Luna Earrings', 'Delicate stud earrings with a luminous sterling silver finish.', 4, 500.00, NULL, NULL, 'best', 5, 94, 'One Size', 'Sterling Silver', 100, 1, '2026-03-16 23:52:50', '2026-03-16 23:52:50'),
(3, 'Celestial Necklace', 'An 18-inch gold necklace adorned with star and moon charms.', 2, 970.00, NULL, NULL, 'best', 5, 203, '18\"', '14K Gold', 100, 1, '2026-03-16 23:52:50', '2026-03-16 23:52:50'),
(4, 'Dia Charm Bracelet', 'A charm bracelet featuring dazzling crystal accents.', 3, 700.00, NULL, NULL, 'best', 4, 67, 'One Size', 'Sterling Silver', 100, 1, '2026-03-16 23:52:50', '2026-03-16 23:52:50'),
(5, 'Infinity Band', 'A sleek infinity-symbol band in polished 14K gold.', 1, 750.00, NULL, NULL, '', 5, 45, 'Ring', '14K Gold', 100, 1, '2026-03-16 23:52:50', '2026-03-16 23:52:50'),
(6, 'Star Pendant', 'A sparkling star-shaped pendant on a 16-inch rose gold chain.', 2, 820.00, 1020.00, NULL, 'sale', 4, 88, '16\"', 'Rose Gold', 99, 1, '2026-03-16 23:52:50', '2026-03-26 15:44:19'),
(7, 'Diamond Hoops', 'Classic hoop earrings with a diamond-cut finish in 14K gold.', 4, 650.00, NULL, 'd62014b2daa29d8067892df6.png', '', 5, 112, 'One Size', '14K Gold', 100, 1, '2026-03-16 23:52:50', '2026-03-17 01:34:44'),
(8, 'Aurora Bangle', 'A wide rose-gold bangle with an iridescent enamel finish.', 3, 890.00, NULL, '54a2fb881fe3bddcda9e6120.png', '', 4, 56, 'One Size', 'Rose Gold', 1, 1, '2026-03-16 23:52:50', '2026-03-26 11:46:51');

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` varchar(20) DEFAULT NULL,
  `rating` tinyint(4) NOT NULL,
  `title` varchar(160) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(160) DEFAULT NULL,
  `type` enum('percent','fixed') NOT NULL DEFAULT 'percent',
  `value` decimal(10,2) NOT NULL,
  `min_order` decimal(10,2) DEFAULT 0.00,
  `max_uses` int(11) DEFAULT NULL,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `start_at` datetime DEFAULT NULL,
  `end_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `promotions`
--

INSERT INTO `promotions` (`id`, `code`, `name`, `type`, `value`, `min_order`, `max_uses`, `used_count`, `start_at`, `end_at`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'WELCOME10', 'Welcome 10% off', 'percent', 10.00, 500.00, 1000, 0, NULL, NULL, 1, '2026-03-17 00:54:50', '2026-03-17 00:54:50'),
(2, 'FREESHIP', 'Free shipping over ₱2k', 'fixed', 150.00, 2000.00, NULL, 0, NULL, NULL, 1, '2026-03-17 00:54:50', '2026-03-17 00:54:50');

-- --------------------------------------------------------

--
-- Table structure for table `promotion_redemptions`
--

CREATE TABLE `promotion_redemptions` (
  `id` int(11) NOT NULL,
  `promotion_id` int(11) NOT NULL,
  `order_id` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `discount_amt` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report_snapshots`
--

CREATE TABLE `report_snapshots` (
  `id` int(11) NOT NULL,
  `name` varchar(160) NOT NULL,
  `period` varchar(50) DEFAULT NULL,
  `data_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data_json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_history`
--

CREATE TABLE `stock_history` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty_added` int(11) NOT NULL,
  `stock_after` int(11) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT 'Inventory Manager',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` varchar(20) NOT NULL,
  `type` enum('wrong_item','damaged','not_delivered','delayed','refund','missing_item','other') NOT NULL,
  `description` text NOT NULL,
  `status` enum('open','resolved','closed') NOT NULL DEFAULT 'open',
  `admin_note` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(80) NOT NULL,
  `last_name` varchar(80) NOT NULL,
  `username` varchar(80) DEFAULT NULL,
  `email` varchar(191) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `gender` enum('Male','Female','Other','Prefer not to say') DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `role` enum('customer','admin','super_admin','manager','inventory','courier') NOT NULL DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `email_verified_at` datetime DEFAULT NULL,
  `activation_token` varchar(64) DEFAULT NULL,
  `activation_expires` datetime DEFAULT NULL,
  `totp_secret` varchar(64) DEFAULT NULL,
  `failed_login_attempts` int(11) NOT NULL DEFAULT 0,
  `locked_at` datetime DEFAULT NULL,
  `locked_by` int(11) DEFAULT NULL,
  `lock_reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `username`, `email`, `password_hash`, `phone`, `gender`, `birthday`, `city`, `role`, `created_at`, `updated_at`, `email_verified_at`, `activation_token`, `activation_expires`, `totp_secret`) VALUES
(1, 'Admin', 'Bejewelry', NULL, 'admin@bejewelry.ph', 'Admin123', NULL, NULL, NULL, NULL, 'inventory', '2026-03-16 23:52:50', '2026-03-26 09:45:08', '2026-03-17 07:52:50', NULL, NULL, NULL),
(3, 'PATRICK IVAN', 'PILAPIL', NULL, 'pilapil_patrickivan@plpasig.edu.ph', '$2y$10$8bRoCLl75x.pRyz43DrVx.6g9NePaUTXcLFsIov4cvG5Uxl0JuffO', '09394551110', 'Male', '2010-08-27', 'Manila', 'customer', '2026-03-17 01:17:11', '2026-03-26 10:04:53', '2026-03-17 09:17:11', NULL, NULL, 'ACMBEJ7DMQ766P3F'),
(9, 'Patrick', 'Ivan Pilapil', NULL, 'patrickpilapil1313@gmail.com', '$2y$10$sT89Fck73wJGG1eRD2DqEuWLnK2lGn3fnZ8KfzU60fQZK9h3vdVgO', NULL, NULL, NULL, NULL, 'super_admin', '2026-03-17 06:53:11', '2026-03-26 11:10:31', '2026-03-17 14:53:11', NULL, NULL, 'PJE65A2ZIAFVWK4W'),
(12, 'Vincent', 'Andrada', NULL, 'andrada_jonvincent@plpasig.edu.ph', '$2y$10$lYUO9KDcT7jQBCkbfQzlreMZNFCrnU3wGSctQBeLsmLEwskH8DDpC', NULL, NULL, NULL, NULL, 'customer', '2026-03-26 09:57:20', '2026-03-26 09:57:20', NULL, '02066e36d597f813e9bd7a2198b86d094bdf3d7ef408de8f11e3d7a952b2eb39', '2026-03-28 10:57:20', NULL),
(15, 'PATRICK', 'IVAN PILAPIL', NULL, 'patrickpilapil27@gmail.com', '$2y$10$QK9VML7iPzG.0xVeXcb2duoVxUJi.FtGfNWK0mAG9zpiOQfzTzP/.', '09394551110', 'Male', '2026-03-25', 'Manila', 'inventory', '2026-03-26 11:32:36', '2026-03-26 11:42:21', NULL, NULL, NULL, 'CIUPK3YGZKIFORHF'),
(19, 'SUPER', 'ADMIN LARGA', NULL, 'larga_johnsebastian@plpasig.edu.ph', '$2y$10$Vl6BfJ0QbsI8Y7N3IEG/guiuDWiH5Xp6joydEFSzcJ3DD88vPXfw6', NULL, NULL, NULL, NULL, 'super_admin', '2026-04-11 06:38:24', '2026-04-11 06:40:40', NULL, NULL, NULL, 'ABVZR7PWSCCPX3JC'),
(20, 'INVENTORY', 'LARGA', NULL, 'sebasteestab@gmail.com', '$2y$10$KXiwRbxmMnBiPN5TmMeWaeU7iVw1.AFjx7ixQ02GNRW96C0HohTbO', NULL, NULL, NULL, NULL, 'inventory', '2026-04-11 06:41:44', '2026-04-16 07:45:57', NULL, NULL, NULL, 'MKJNX5A4N75AHXHO'),
(21, 'ORDER', 'LARGA', NULL, 'sebastianlarga6@gmail.com', '$2y$10$okqLzuatUmCUBkdTqkr19O3dZ3aZbnMV28Cax/lKBLFdozDX65aWW', NULL, NULL, NULL, NULL, 'manager', '2026-04-11 06:44:11', '2026-04-16 07:46:54', NULL, NULL, NULL, 'NJDC656JPNZ7RRWZ'),
(25, 'BOGART', 'BATUMBAKAL', NULL, 'melitalarga4@gmail.com', '$2y$10$uT2zU7jiiDELR3XBOb/Y0O1eDaux0E2P6feeqcPCTZRZyk8ws64Ne', NULL, NULL, NULL, NULL, 'customer', '2026-04-16 08:36:35', '2026-04-16 08:39:09', '2026-04-16 16:38:18', NULL, NULL, 'JIHLAAGPMNS6VU22');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `app_settings`
--
ALTER TABLE `app_settings`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cart` (`user_id`,`product_id`,`size`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `customer_notes`
--
ALTER TABLE `customer_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `email_prefs`
--
ALTER TABLE `email_prefs`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_orders_courier_user_id` (`courier_user_id`);

--
-- Indexes for table `order_delivery_proofs`
--
ALTER TABLE `order_delivery_proofs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_delivery_proofs_order` (`order_id`),
  ADD KEY `idx_order_delivery_proofs_created` (`created_at`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `paymongo_checkout_pending`
--
ALTER TABLE `paymongo_checkout_pending`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_pm_state` (`pm_state`),
  ADD UNIQUE KEY `uq_checkout_session` (`checkout_session_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `promotion_redemptions`
--
ALTER TABLE `promotion_redemptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `promotion_id` (`promotion_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `report_snapshots`
--
ALTER TABLE `report_snapshots`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stock_history`
--
ALTER TABLE `stock_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `customer_notes`
--
ALTER TABLE `customer_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `order_delivery_proofs`
--
ALTER TABLE `order_delivery_proofs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `paymongo_checkout_pending`
--
ALTER TABLE `paymongo_checkout_pending`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `promotion_redemptions`
--
ALTER TABLE `promotion_redemptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `report_snapshots`
--
ALTER TABLE `report_snapshots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_history`
--
ALTER TABLE `stock_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_notes`
--
ALTER TABLE `customer_notes`
  ADD CONSTRAINT `customer_notes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `email_prefs`
--
ALTER TABLE `email_prefs`
  ADD CONSTRAINT `email_prefs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`courier_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_delivery_proofs`
--
ALTER TABLE `order_delivery_proofs`
  ADD CONSTRAINT `order_delivery_proofs_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `paymongo_checkout_pending`
--
ALTER TABLE `paymongo_checkout_pending`
  ADD CONSTRAINT `paymongo_checkout_pending_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_reviews_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `promotion_redemptions`
--
ALTER TABLE `promotion_redemptions`
  ADD CONSTRAINT `promotion_redemptions_ibfk_1` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `promotion_redemptions_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `promotion_redemptions_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD CONSTRAINT `support_tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

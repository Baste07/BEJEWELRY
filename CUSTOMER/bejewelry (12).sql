-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 07, 2026 at 08:56 PM
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
-- Table structure for table `account_lock_logs`
--

CREATE TABLE `account_lock_logs` (
  `id` int(11) NOT NULL,
  `username` varchar(191) NOT NULL,
  `failed_login_attempts` int(11) NOT NULL,
  `locked_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `account_lock_logs`
--

INSERT INTO `account_lock_logs` (`id`, `username`, `failed_login_attempts`, `locked_at`) VALUES
(1, 'melitalarga4@gmail.com', 3, '2026-05-05 05:30:15'),
(2, 'patrickpilapil27@gmail.com', 3, '2026-05-07 08:34:43'),
(3, 'sebastianlarga6@gmail.com', 3, '2026-05-07 10:45:55'),
(4, 'pilapil_patrickivan@plpasig.edu.ph', 3, '2026-05-07 18:08:46'),
(5, 'pilapil_patrickivan@plpasig.edu.ph', 3, '2026-05-07 18:12:26'),
(6, 'pilapil_patrickivan@plpasig.edu.ph', 3, '2026-05-07 18:22:19');

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `label` varchar(50) DEFAULT 'Home',
  `name` varchar(160) NOT NULL,
  `street` varchar(1024) NOT NULL,
  `city` varchar(512) NOT NULL,
  `province` varchar(512) DEFAULT NULL,
  `zip` varchar(512) DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `phone` varchar(512) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `addresses`
--

INSERT INTO `addresses` (`id`, `user_id`, `label`, `name`, `street`, `city`, `province`, `zip`, `latitude`, `longitude`, `phone`, `is_default`) VALUES
(4, 15, 'Home', 'PATRICK IVAN PILAPIL', '2868 Orion St. Tondo Manila', 'Manila', 'NCR', '1012', NULL, NULL, '09394551110', 1),
(5, 31, 'Home', 'Andrew', 'Kapasigan', 'Pasig', 'Manila', '1600', NULL, NULL, '09925801741', 1),
(10, 3, 'Home', 'PATRICK IVAN PILAPIL', 'enc:v1:fIkt1wkbtgOWuq9sx24+XMA4l04RqH4wKY2wK3t/0dQC2VQxPi1Jzxg1T33WFB4g0gRLPgX7ng==', 'enc:v1:2D+KqH6/nbFIAtv6/TI6uJeUb2NrYey5PHge9iXgmw9GNQ==', 'enc:v1:ieKOzrnJGrcHnGqoDDOE4/MjtuO9N86XtoeCA632Ag==', 'enc:v1:iujGmurb5GawaWh83soFXFWesQqasUUEOCUlaNYMVts=', NULL, NULL, 'enc:v1:5axZQx9HV2k9DfRGdBeB5hZlqGCSnbTB8XC7hJuGLxbVLdkxQf9O', 1);

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
('login_lock_settings', '{\"max_attempts\":3}', '2026-05-02 09:58:33'),
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
  `action` varchar(64) NOT NULL,
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
(18, NULL, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 07:45:57'),
(19, NULL, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 07:46:01'),
(20, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 07:46:54'),
(21, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 07:56:50'),
(22, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 08:39:09'),
(23, 25, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 08:58:51'),
(24, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 09:00:20'),
(25, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 15:35:00'),
(26, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 15:37:38'),
(27, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 15:45:13'),
(28, NULL, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 15:58:30'),
(29, NULL, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 16:14:39'),
(30, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 16:15:19'),
(31, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 16:16:14'),
(32, NULL, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 16:16:35'),
(33, NULL, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 16:28:38'),
(34, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 16:29:17'),
(35, 25, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 16:30:08'),
(36, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 16:31:29'),
(37, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 16:32:01'),
(38, NULL, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 16:32:21'),
(39, NULL, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 16:34:38'),
(40, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 16:34:57'),
(41, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 16:35:44'),
(42, NULL, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 16:36:06'),
(43, NULL, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 11:17:20'),
(44, NULL, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 11:18:49'),
(45, 19, 'larga_johnsebastian@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 11:22:09'),
(46, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 12:53:23'),
(47, 25, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 12:54:29'),
(48, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 12:55:58'),
(49, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:02:32'),
(50, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:02:58'),
(51, 25, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:03:46'),
(52, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:05:40'),
(53, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:07:22'),
(54, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:08:00'),
(55, 25, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:08:40'),
(56, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:09:10'),
(57, 25, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:26:49'),
(58, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:27:17'),
(59, 25, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:36:55'),
(60, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:42:22'),
(61, 25, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:42:31'),
(62, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:43:16'),
(63, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:44:44'),
(64, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:48:45'),
(65, 25, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:48:48'),
(66, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:49:29'),
(67, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:49:34'),
(68, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:50:05'),
(69, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:50:17'),
(70, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:50:24'),
(71, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:50:33'),
(72, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:50:39'),
(73, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:50:54'),
(74, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:51:03'),
(75, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 14:02:19'),
(76, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 14:05:37'),
(77, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 15:00:51'),
(78, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 15:01:05'),
(79, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 15:13:14'),
(80, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 15:13:39'),
(81, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 16:17:58'),
(82, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 06:17:14'),
(83, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 06:17:43'),
(84, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 06:48:38'),
(85, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 06:51:38'),
(86, 19, 'larga_johnsebastian@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 06:53:22'),
(87, 19, 'larga_johnsebastian@plpasig.edu.ph', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 06:53:46'),
(88, NULL, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 07:41:13'),
(89, NULL, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 07:41:35'),
(90, NULL, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 07:44:00'),
(91, NULL, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 07:44:06'),
(92, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 07:44:51'),
(93, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 07:55:45'),
(94, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 07:56:25'),
(95, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 08:08:08'),
(96, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 08:20:54'),
(97, 25, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 08:21:04'),
(98, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 15:20:08'),
(99, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 04:31:21'),
(100, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 04:37:10'),
(101, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 04:37:28'),
(102, 25, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 04:38:08'),
(103, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 04:38:21'),
(104, 25, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 05:04:40'),
(105, 30, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 05:06:24'),
(106, 30, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 05:07:14'),
(107, 30, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 05:07:26'),
(108, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 05:07:37'),
(109, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 05:42:29'),
(110, 30, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 05:42:44'),
(111, 30, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 05:43:36'),
(112, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 05:43:53'),
(113, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 11:41:02'),
(114, 25, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 11:41:26'),
(115, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 11:41:42'),
(116, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 16:33:01'),
(117, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 17:18:31'),
(118, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 17:19:03'),
(119, 25, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 17:31:15'),
(120, 19, 'larga_johnsebastian@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 17:31:47'),
(121, 19, 'larga_johnsebastian@plpasig.edu.ph', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 17:55:38'),
(122, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 17:56:23'),
(123, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 18:02:20'),
(124, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 18:03:00'),
(125, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 18:05:33'),
(126, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 18:05:53'),
(127, 25, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 18:09:45'),
(128, 30, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 18:10:41'),
(129, 30, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 18:11:57'),
(130, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 18:12:08'),
(131, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 19:03:21'),
(132, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 05:50:25'),
(133, 25, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 05:52:54'),
(134, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 05:53:07'),
(135, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 05:53:52'),
(136, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 05:54:04'),
(137, 25, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 05:54:39'),
(138, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 05:55:14'),
(139, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 05:55:31'),
(140, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 05:56:35'),
(141, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 05:58:42'),
(142, 19, 'larga_johnsebastian@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 05:59:02'),
(143, 19, 'larga_johnsebastian@plpasig.edu.ph', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 06:00:48'),
(144, 30, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 06:01:06'),
(145, 30, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 06:01:38'),
(146, 31, 'fernandez_jamesandrew@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 09:51:50'),
(147, 31, 'fernandez_jamesandrew@plpasig.edu.ph', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 09:56:14'),
(148, 31, 'fernandez_jamesandrew@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 09:56:43'),
(149, 31, 'fernandez_jamesandrew@plpasig.edu.ph', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 09:57:21'),
(150, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 09:58:13'),
(151, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 10:02:25'),
(152, 31, 'fernandez_jamesandrew@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 10:02:45'),
(153, 31, 'fernandez_jamesandrew@plpasig.edu.ph', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 10:05:44'),
(154, 30, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 10:06:01'),
(155, 30, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 10:11:23'),
(156, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 10:11:53'),
(157, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 10:15:31'),
(158, 19, 'larga_johnsebastian@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 10:16:03'),
(159, 19, 'larga_johnsebastian@plpasig.edu.ph', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 10:21:51'),
(160, 30, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-30 17:16:00'),
(161, 30, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-30 17:51:51'),
(162, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-30 17:52:16'),
(163, 25, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-30 17:52:57'),
(164, 30, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-30 17:53:11'),
(165, 30, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-30 18:23:50'),
(166, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-30 18:24:38'),
(167, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-30 18:29:51'),
(168, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-30 18:30:16'),
(169, 25, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-30 18:41:37'),
(170, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-30 18:42:14'),
(171, 25, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-30 18:42:44'),
(172, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-30 18:43:05'),
(173, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-30 18:44:01'),
(174, 15, 'patrickpilapil27@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-02 06:54:28'),
(175, 15, 'patrickpilapil27@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-02 06:54:51'),
(176, 9, 'patrickpilapil1313@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-02 06:56:29'),
(178, 15, 'patrickpilapil27@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:23:23'),
(179, 15, 'patrickpilapil27@gmail.com', 'restock_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:29:23'),
(180, 15, 'patrickpilapil27@gmail.com', 'edit_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:35:44'),
(181, 15, 'patrickpilapil27@gmail.com', 'edit_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:35:54'),
(182, 15, 'patrickpilapil27@gmail.com', 'edit_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:36:02'),
(183, 15, 'patrickpilapil27@gmail.com', 'edit_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:39:28'),
(184, 15, 'patrickpilapil27@gmail.com', 'edit_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:39:38'),
(185, 15, 'patrickpilapil27@gmail.com', 'edit_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:39:49'),
(186, 15, 'patrickpilapil27@gmail.com', 'edit_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:39:49'),
(187, 15, 'patrickpilapil27@gmail.com', 'edit_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:41:55'),
(188, 15, 'patrickpilapil27@gmail.com', 'edit_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:44:46'),
(189, 15, 'patrickpilapil27@gmail.com', 'edit_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:49:28'),
(190, 15, 'patrickpilapil27@gmail.com', 'edit_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:50:46'),
(191, 15, 'patrickpilapil27@gmail.com', 'edit_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:52:15'),
(192, 15, 'patrickpilapil27@gmail.com', 'edit_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:53:21'),
(193, 15, 'patrickpilapil27@gmail.com', 'edit_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:54:36'),
(194, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 07:56:27'),
(195, 15, 'patrickpilapil27@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 08:24:45'),
(196, 15, 'patrickpilapil27@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 08:29:57'),
(197, 15, 'patrickpilapil27@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 08:30:07'),
(198, 15, 'patrickpilapil27@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 09:54:38'),
(199, 15, 'patrickpilapil27@gmail.com', 'lock_account', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 09:54:47'),
(200, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:00:06'),
(201, 25, 'melitalarga4@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:02:53'),
(202, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:03:11'),
(203, 25, 'melitalarga4@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:06:37'),
(204, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:07:05'),
(205, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:11:36'),
(206, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:12:00'),
(207, 25, 'melitalarga4@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:14:42'),
(208, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:15:07'),
(209, 25, 'melitalarga4@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:20:31'),
(210, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:20:42'),
(211, 25, 'melitalarga4@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:22:51'),
(212, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:23:06'),
(213, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:27:43'),
(214, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:40:56'),
(215, 25, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:42:41'),
(216, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:42:49'),
(217, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:43:34'),
(218, 19, 'larga_johnsebastian@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:43:50'),
(219, 19, 'larga_johnsebastian@plpasig.edu.ph', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:45:00'),
(220, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:45:08'),
(221, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:47:26'),
(222, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:48:58'),
(223, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:49:09'),
(224, 19, 'larga_johnsebastian@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:49:28'),
(225, 19, 'larga_johnsebastian@plpasig.edu.ph', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:49:31'),
(226, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:49:49'),
(227, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:52:22'),
(228, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:53:36'),
(229, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:56:14'),
(230, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:56:37'),
(231, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:59:31'),
(232, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:59:44'),
(233, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 04:04:16'),
(234, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 04:04:30'),
(235, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 04:09:06'),
(236, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 04:09:29'),
(237, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 04:15:36'),
(238, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 04:15:52'),
(239, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 04:18:09'),
(240, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 04:19:00'),
(241, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 04:24:01'),
(242, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 04:26:25'),
(243, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 04:33:18'),
(244, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 04:33:39'),
(245, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 04:33:57'),
(246, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 04:36:42'),
(247, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 04:38:16'),
(248, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 04:41:39'),
(249, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 04:41:54'),
(250, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 05:09:54'),
(251, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 05:10:01'),
(252, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 05:10:41'),
(253, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 05:10:51'),
(254, 25, 'melitalarga4@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 05:15:37'),
(255, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 05:16:00'),
(256, 25, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 05:17:03'),
(257, 19, 'larga_johnsebastian@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 05:17:28'),
(258, 19, 'larga_johnsebastian@plpasig.edu.ph', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 05:17:34');
INSERT INTO `audit_log` (`id`, `user_id`, `email`, `action`, `ip`, `user_agent`, `created_at`) VALUES
(259, 21, 'sebastianlarga6@gmail.com', 'lock_account', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 05:20:04'),
(260, 19, 'larga_johnsebastian@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 05:20:13'),
(261, 19, 'larga_johnsebastian@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 05:22:22'),
(262, 25, 'melitalarga4@gmail.com', 'lock_account', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 05:30:15'),
(263, 19, 'larga_johnsebastian@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 05:30:39'),
(264, 19, 'larga_johnsebastian@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 05:33:47'),
(265, 19, 'larga_johnsebastian@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 09:41:25'),
(266, 19, 'larga_johnsebastian@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 09:43:48'),
(267, 19, 'larga_johnsebastian@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 15:39:36'),
(268, 19, 'larga_johnsebastian@plpasig.edu.ph', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 15:39:41'),
(269, 19, 'larga_johnsebastian@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 15:52:52'),
(270, 19, 'larga_johnsebastian@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 15:55:14'),
(271, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 16:03:54'),
(272, 25, 'melitalarga4@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 16:07:47'),
(273, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 16:36:03'),
(274, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 09:45:30'),
(275, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 09:50:17'),
(276, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 13:00:00'),
(277, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 13:02:32'),
(278, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 13:05:25'),
(279, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 13:08:08'),
(280, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 14:40:47'),
(281, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 14:45:25'),
(282, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 14:45:44'),
(283, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 14:50:41'),
(284, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 14:54:21'),
(285, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 14:58:10'),
(286, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 15:05:02'),
(287, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 15:09:44'),
(288, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 15:13:23'),
(289, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 15:17:45'),
(290, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 18:25:57'),
(291, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 18:28:52'),
(292, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 18:34:07'),
(293, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 18:39:00'),
(294, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 18:39:20'),
(295, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 18:43:26'),
(296, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 19:11:42'),
(297, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 19:16:13'),
(298, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 19:34:40'),
(299, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 19:38:03'),
(300, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 19:39:38'),
(301, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 19:42:42'),
(302, 9, 'patrickpilapil1313@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 20:50:18'),
(303, 9, 'patrickpilapil1313@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 20:53:04'),
(304, 9, 'patrickpilapil1313@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 20:54:52'),
(305, 9, 'patrickpilapil1313@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 21:00:57'),
(306, 35, 'lenrapilapil@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 21:01:50'),
(307, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 21:03:12'),
(308, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 21:05:44'),
(309, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 21:10:43'),
(310, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 21:14:09'),
(311, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 21:21:07'),
(312, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 21:25:27'),
(313, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 21:27:13'),
(314, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 21:39:07'),
(315, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 21:39:54'),
(316, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 21:43:42'),
(317, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 22:02:34'),
(318, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 22:04:50'),
(319, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 22:28:58'),
(320, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 22:31:06'),
(321, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 22:51:52'),
(322, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 22:54:39'),
(323, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 22:59:44'),
(324, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 23:05:46'),
(325, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 23:06:16'),
(326, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-06 23:09:59'),
(327, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 07:35:58'),
(328, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 07:42:29'),
(329, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 07:44:52'),
(330, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 07:57:01'),
(331, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 08:08:35'),
(332, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 08:12:13'),
(333, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 08:13:57'),
(334, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 08:17:47'),
(335, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 08:19:03'),
(336, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 08:26:02'),
(337, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 08:27:04'),
(338, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 08:29:36'),
(339, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 08:31:18'),
(340, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 08:32:58'),
(341, 9, 'patrickpilapil1313@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 08:33:26'),
(342, 15, 'patrickpilapil27@gmail.com', 'lock_account', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 08:34:43'),
(343, 15, 'patrickpilapil27@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 08:36:29'),
(344, 15, 'patrickpilapil27@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 08:38:10'),
(345, 15, 'patrickpilapil27@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 08:38:35'),
(346, 9, 'patrickpilapil1313@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 08:42:06'),
(347, 15, 'patrickpilapil27@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 08:44:25'),
(348, 15, 'patrickpilapil27@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 08:45:07'),
(349, 9, 'patrickpilapil1313@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 08:46:37'),
(350, 15, 'patrickpilapil27@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 08:49:46'),
(351, 9, 'patrickpilapil1313@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 08:49:47'),
(352, 15, 'patrickpilapil27@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 08:51:34'),
(353, 15, 'patrickpilapil27@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 08:53:18'),
(354, 15, 'patrickpilapil27@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 08:53:32'),
(355, 15, 'patrickpilapil27@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 08:57:59'),
(356, 15, 'patrickpilapil27@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 08:58:44'),
(357, 15, 'patrickpilapil27@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:02:43'),
(358, 15, 'patrickpilapil27@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:04:00'),
(359, 15, 'patrickpilapil27@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:06:50'),
(360, 15, 'patrickpilapil27@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:07:37'),
(361, 9, 'patrickpilapil1313@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 09:07:54'),
(362, 9, 'patrickpilapil1313@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 09:08:21'),
(363, 9, 'patrickpilapil1313@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 09:08:29'),
(364, 15, 'patrickpilapil27@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:09:44'),
(365, 9, 'patrickpilapil1313@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 09:12:17'),
(366, 9, 'patrickpilapil1313@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 09:18:18'),
(367, 15, 'patrickpilapil27@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:20:57'),
(368, 9, 'patrickpilapil1313@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 09:22:18'),
(369, 9, 'patrickpilapil1313@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 09:22:26'),
(370, 15, 'patrickpilapil27@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:23:33'),
(371, 9, 'patrickpilapil1313@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 09:28:41'),
(372, 9, 'patrickpilapil1313@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 09:30:58'),
(373, 9, 'patrickpilapil1313@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 09:33:11'),
(374, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 09:38:35'),
(375, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 09:41:32'),
(376, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 09:41:40'),
(378, 19, 'larga_johnsebastian@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:01:17'),
(379, 19, 'larga_johnsebastian@plpasig.edu.ph', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:04:11'),
(380, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:05:39'),
(381, 25, 'melitalarga4@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:12:08'),
(382, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:14:12'),
(383, 25, 'melitalarga4@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:16:53'),
(384, 19, 'larga_johnsebastian@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:21:08'),
(385, 19, 'larga_johnsebastian@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:23:17'),
(386, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:30:27'),
(387, 25, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:33:57'),
(388, 30, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:34:09'),
(389, 30, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:35:03'),
(390, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:35:15'),
(391, 25, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:36:16'),
(392, 30, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:36:29'),
(393, 30, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:36:44'),
(394, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:36:55'),
(395, 25, 'melitalarga4@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:40:56'),
(396, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:41:13'),
(397, 25, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:41:36'),
(398, 30, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:41:44'),
(399, 30, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:41:59'),
(400, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:42:16'),
(401, 25, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:43:22'),
(402, 30, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:43:48'),
(403, 30, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:44:00'),
(404, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:44:22'),
(405, 25, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:45:16'),
(406, 21, 'sebastianlarga6@gmail.com', 'lock_account', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:45:55'),
(407, 19, 'larga_johnsebastian@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:46:12'),
(408, 19, 'larga_johnsebastian@plpasig.edu.ph', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:46:21'),
(409, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:48:21'),
(410, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:48:53'),
(411, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:49:27'),
(412, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:50:17'),
(413, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:50:36'),
(414, 25, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:51:13'),
(415, 30, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:51:39'),
(416, 30, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:51:52'),
(417, 25, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:52:21'),
(418, 25, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:52:38'),
(419, 19, 'larga_johnsebastian@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:54:42'),
(420, 19, 'larga_johnsebastian@plpasig.edu.ph', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:56:22'),
(421, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 14:13:29'),
(422, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 14:17:06'),
(423, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 14:27:25'),
(424, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 14:31:51'),
(425, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 14:36:29'),
(426, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 14:39:27'),
(427, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 15:20:20'),
(428, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 15:22:42'),
(429, 9, 'patrickpilapil1313@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 15:58:44'),
(430, 9, 'patrickpilapil1313@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 16:03:03'),
(431, 9, 'patrickpilapil1313@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 17:11:10'),
(432, 15, 'patrickpilapil27@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 17:12:16'),
(433, 15, 'patrickpilapil27@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 17:14:43'),
(434, 9, 'patrickpilapil1313@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 17:17:59'),
(435, 9, 'patrickpilapil1313@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 17:18:12'),
(436, 9, 'patrickpilapil1313@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 17:20:39'),
(437, 9, 'patrickpilapil1313@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 17:20:44'),
(438, 9, 'patrickpilapil1313@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 17:22:58'),
(439, 9, 'patrickpilapil1313@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 17:25:21'),
(440, 9, 'patrickpilapil1313@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 17:27:38'),
(441, 9, 'patrickpilapil1313@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 17:31:56'),
(442, 9, 'patrickpilapil1313@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 17:35:03'),
(443, 9, 'patrickpilapil1313@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 17:35:15'),
(444, 9, 'patrickpilapil1313@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 17:37:57'),
(445, 9, 'patrickpilapil1313@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 17:38:04'),
(446, 9, 'patrickpilapil1313@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 17:41:01'),
(447, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'lock_account', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 18:08:46'),
(448, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'request_account_unlock', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 18:08:54'),
(449, 9, 'patrickpilapil1313@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 18:09:12'),
(450, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 18:11:42'),
(451, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'request_account_deletion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 18:11:49'),
(452, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 18:15:43'),
(453, 9, 'patrickpilapil1313@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 18:16:31'),
(454, 9, 'patrickpilapil1313@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 18:20:39'),
(455, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'archive_account', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 18:20:44'),
(456, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'archive_account', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 18:21:51'),
(457, 9, 'patrickpilapil1313@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 18:25:42'),
(458, 9, 'patrickpilapil1313@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 18:27:20'),
(459, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'archive_account', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 18:27:32'),
(460, 9, 'patrickpilapil1313@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 18:30:57'),
(461, 9, 'patrickpilapil1313@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 18:31:13'),
(462, 9, 'patrickpilapil1313@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 18:34:55'),
(463, 9, 'patrickpilapil1313@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 18:37:10'),
(464, 9, 'patrickpilapil1313@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 18:40:27'),
(465, 9, 'patrickpilapil1313@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 18:43:44'),
(466, 9, 'patrickpilapil1313@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 18:47:35'),
(467, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 18:50:05'),
(468, 9, 'patrickpilapil1313@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 18:50:46'),
(469, 9, 'patrickpilapil1313@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 18:52:52'),
(470, 9, 'patrickpilapil1313@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 18:55:44'),
(471, 9, 'patrickpilapil1313@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 18:55:55');

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
(52, 3, 1, 'Ring', 1, '2026-05-07 08:31:23');

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
  `status` enum('processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'processing',
  `subtotal` decimal(10,2) NOT NULL,
  `shipping_fee` decimal(10,2) DEFAULT 150.00,
  `total` decimal(10,2) NOT NULL,
  `ship_name` varchar(160) DEFAULT NULL,
  `ship_street` varchar(1024) DEFAULT NULL,
  `ship_city` varchar(512) DEFAULT NULL,
  `ship_province` varchar(512) DEFAULT NULL,
  `ship_zip` varchar(512) DEFAULT NULL,
  `ship_phone` varchar(512) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT 'cod',
  `notes` text DEFAULT NULL,
  `courier_user_id` int(11) DEFAULT NULL,
  `courier_name` varchar(160) DEFAULT NULL,
  `courier_assigned_at` datetime DEFAULT NULL,
  `tracking_number` varchar(120) DEFAULT NULL,
  `is_flagged` tinyint(1) NOT NULL DEFAULT 0,
  `flag_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `status`, `subtotal`, `shipping_fee`, `total`, `ship_name`, `ship_street`, `ship_city`, `ship_province`, `ship_zip`, `ship_phone`, `payment_method`, `notes`, `courier_user_id`, `courier_name`, `courier_assigned_at`, `tracking_number`, `is_flagged`, `flag_reason`, `created_at`, `updated_at`) VALUES
('BJ-2026-0001', 25, 'delivered', 1330.00, 150.00, 1480.00, 'BOGART BATUMBAKAL', 'BLISS 3 WESTBANK', 'PASIG', 'METRO MANILA', '1607', '09925801741', 'cod', 'Standard delivery ?? est. 3???5 business days | carrier_delivery:JOHN LARGA | proof:delivery_BJ-2026-0001_20260421134312_df8c3319.png | delivered_at:2026-04-21T13:42:00+08:00 | note:Sir tapos na po', 30, 'JOHN LARGA', '2026-04-21 13:30:05', 'TRACK-2026-0001', 0, NULL, '2026-04-21 05:03:45', '2026-04-21 05:43:12'),
('BJ-2026-0002', 31, 'delivered', 11330.00, 0.00, 11330.00, 'Andrew Fernandez', 'Kapasigan', 'Pasig', 'Manila', '1600', '09925801741', 'paymongo', 'Standard delivery ?? est. 3???5 business days | paymongo_session:cs_ceb9417d572056fea2a5f15d | carrier_delivery:JOHN LARGA | proof:delivery_BJ-2026-0002_20260501014527_9b918b36.png | delivered_at:2026-05-01T01:45:00+08:00', 30, 'JOHN LARGA', '2026-05-01 01:28:55', 'TRACK-2026-BJ-2026-0002', 0, NULL, '2026-04-25 09:55:28', '2026-04-30 17:45:27'),
('BJ-2026-0003', 25, 'delivered', 11910.00, 0.00, 11910.00, 'BOGART BATUMBAKAL', 'Kapasigan', 'Pasig', 'Manila', '1600', '09925801741', 'cod', 'Standard delivery ?? est. 3???5 business days | carrier_delivery:JOHN LARGA | proof:delivery_BJ-2026-0003_20260501015414_ecb3d3ca.png | delivered_at:2026-05-01T01:54:00+08:00', 30, 'JOHN LARGA', '2026-05-01 01:53:20', 'TRACK-2026-BJ-2026-0003', 0, NULL, '2026-04-30 17:52:35', '2026-04-30 17:54:14'),
('BJ-2026-0004', 25, 'delivered', 2940.00, 0.00, 2940.00, 'BOGART BATUMBAKAL', 'Kapasigan', 'Pasig', 'Manila', '1600', '09925801741', 'cod', 'Standard delivery ?? est. 3???5 business days | carrier_delivery:JOHN LARGA | proof:delivery_BJ-2026-0004_20260507183642_4d76b4a7.png | delivered_at:2026-05-07T18:36:00+08:00', 30, 'JOHN LARGA', '2026-05-01 01:54:30', 'TRACK-2026-BJ-2026-0004', 0, NULL, '2026-04-30 17:52:54', '2026-05-07 10:36:42'),
('BJ-2026-0005', 3, 'delivered', 750.00, 150.00, 900.00, 'PATRICK IVAN PILAPIL', '2868 Orion St. Tondo Manila', 'Manila', 'NCR', '1012', '09394551110', 'paymongo', 'Standard delivery ?? est. 3???5 business days | paymongo_session:cs_cb84d2f62515591100fc0c4b | carrier_delivery:Lenra Pilaps | proof:delivery_BJ-2026-0005_20260506230242_d7f668e4.png | delivered_at:2026-05-07T05:02:00+02:00', 35, 'Lenra Pilaps', '2026-05-07 05:01:58', 'TRACK-2026-BJ-2026-0005', 0, NULL, '2026-05-02 07:57:12', '2026-05-06 21:02:42'),
('BJ-2026-0006', 3, 'delivered', 820.00, 150.00, 970.00, 'PATRICK IVAN PILAPIL', '2868 Orion St. Tondo Manila', 'Manila', 'NCR', '1012', '09394551110', 'paymongo', 'Standard delivery ?? est. 3???5 business days | paymongo_session:cs_cd8ca2d09d45c20c29f26fa9 | carrier_delivery:Lenra Pilaps | proof:delivery_BJ-2026-0006_20260507102107_7ca40863.png | delivered_at:2026-05-07T16:21:00+02:00', 35, 'Lenra Pilaps', '2026-05-07 16:20:51', 'TRACK-2026-BJ-2026-0006', 0, NULL, '2026-05-02 08:09:08', '2026-05-07 08:21:07'),
('BJ-2026-0007', 3, 'delivered', 580.00, 150.00, 730.00, 'PATRICK IVAN PILAPIL', '2868 Orion St. Tondo Manila', 'Manila', 'NCR', '1012', '09394551110', 'paymongo', 'Standard delivery ?? est. 3???5 business days | paymongo_session:cs_8dc068ab71531b449fbdb851 | carrier_delivery:Lenra Pilaps | proof:delivery_BJ-2026-0007_20260506232200_ef25a0ff.png | delivered_at:2026-05-07T05:21:00+02:00', 35, 'Lenra Pilaps', '2026-05-07 05:21:43', 'TRACK-2026-BJ-2026-0007', 0, NULL, '2026-05-02 08:56:44', '2026-05-06 21:22:00'),
('BJ-2026-0008', 3, 'shipped', 970.00, 150.00, 1120.00, 'PATRICK IVAN PILAPIL', '2868 Orion St. Tondo Manila', 'Manila', 'NCR', '1012', '09394551110', 'paymongo', 'Standard delivery ?? est. 3???5 business days | paymongo_session:cs_98cfafd04c3d13084cfd0add', 30, 'JOHN LARGA', '2026-05-07 18:34:15', 'TRACK-2026-BJ-2026-0008', 0, NULL, '2026-05-02 09:01:25', '2026-05-07 10:34:15'),
('BJ-2026-0009', 3, 'delivered', 500.00, 150.00, 650.00, 'PATRICK IVAN PILAPIL', '2868 Orion St. Tondo Manila', 'Manila', 'NCR', '1012', '09394551110', 'paymongo', 'Standard delivery ?? est. 3???5 business days | paymongo_session:cs_2934486b4928e56b52718eb3 | carrier_delivery:JOHN LARGA | proof:delivery_BJ-2026-0009_20260507183500_f2f57bec.png | delivered_at:2026-05-07T18:34:00+08:00', 30, 'JOHN LARGA', '2026-05-07 18:34:16', 'TRACK-2026-BJ-2026-0009', 0, NULL, '2026-05-02 09:20:01', '2026-05-07 10:35:00'),
('BJ-2026-0010', 3, 'delivered', 890.00, 150.00, 1040.00, 'PATRICK IVAN PILAPIL', '2868 Orion St. Tondo Manila', 'Manila', 'NCR', '1012', '09394551110', 'paymongo', 'Standard delivery ?? est. 3???5 business days | paymongo_session:cs_ea144cc0a1b46f2770761473 | carrier_delivery:JOHN LARGA | proof:delivery_BJ-2026-0010_20260507183436_eb30ca6e.png | delivered_at:2026-05-07T18:34:00+08:00 | note:Done', 30, 'JOHN LARGA', '2026-05-07 18:34:17', 'TRACK-2026-BJ-2026-0010', 0, NULL, '2026-05-02 09:27:51', '2026-05-07 10:34:36'),
('BJ-2026-0011', 3, 'delivered', 1160.00, 150.00, 1310.00, 'PATRICK IVAN PILAPIL', 'enc:v1:3vWRxc0Zc1YOOivHXMj3WhDzLrlRZ6edlCbUhbNVTj4IHT8fenb/t//19zfP1NGY0fGbqOEUAg==', 'enc:v1:wLwO0ZL4p1ZzH9TaXwL0k3D1xYBYWbMCYGM33cJ/Kqj9Dg==', 'enc:v1:hYu3wd6grW67VZ4M7ANOcWAGqgcjGq3OUcwX7eFbLQ==', 'enc:v1:JbWqvy4ly9T+hThrKQ25OZQhbux65SmiGKBF/1f45j0=', 'enc:v1:dpi41uXs7HlwVbvIsI82YUvLrc44qZy9607j+Bwtc8GIn/dbRrp8', 'paymongo', 'Standard delivery ?? est. 3???5 business days | paymongo_session:cs_6c8870d56b6cb85cbf107890 | carrier_delivery:JOHN LARGA | proof:delivery_BJ-2026-0011_20260507183448_f931688c.png | delivered_at:2026-05-07T18:34:00+08:00', 30, 'JOHN LARGA', '2026-05-07 18:34:17', 'TRACK-2026-BJ-2026-0011', 0, NULL, '2026-05-07 08:20:16', '2026-05-07 10:34:48'),
('BJ-2026-0012', 25, 'delivered', 580.00, 150.00, 730.00, 'BOGART BATUMBAKAL', 'enc:v1:jW01aAmQpHLTTti6Vm7W9KzVmCD65PTBlZHXZ24mL25bbXcxvA==', 'enc:v1:MR31jF3rhwcKShjfXV5L5VTBAz1B1lC1lMLNo8SXhuAJ', 'enc:v1:slpTzKO5F7iNUQJwUpR0LcJ/Yj+fCE3oSBVaP/79WxLLCA==', 'enc:v1:+3F41Or3A9pFH9sX+YOmoNnXyTcSkNuss93HGbTavUA=', 'enc:v1:iHs8UzVvvRn6kRJTxOca+fq8sAi1eX9Jz0I60jR7IZ2a/TpqLYtr', 'paymongo', 'Standard delivery ?? est. 3???5 business days | paymongo_session:cs_3968d9d74b03a35459708fc2 | carrier_delivery:JOHN LARGA | proof:delivery_BJ-2026-0012_20260507184155_b31cd0f9.png | delivered_at:2026-05-07T18:41:00+08:00', 30, 'JOHN LARGA', '2026-05-07 18:41:47', 'TRACK-2026-BJ-2026-0012', 0, NULL, '2026-05-07 10:36:06', '2026-05-07 10:41:55'),
('BJ-2026-0013', 25, 'delivered', 1330.00, 150.00, 1480.00, 'BOGART BATUMBAKAL', 'enc:v1:bseO+7tq32erlNQ4kZuMqN5sF1Go0F0hydruSmkR3VIab8pFKw==', 'enc:v1:QAHL07B29zzGb03DS6VoYcbTifHYAShQ+an653Te7SK0', 'enc:v1:Sqy7wB1SatbLgCkU2TxUSu1uQPSST1HLKebC/wSq+If5GQ==', 'enc:v1:NdLKwK3mwvDvMYWyK7G9dowXsXOgmxE8l9m89/s87e8=', 'enc:v1:6e6+faaeBSIV9jDfUsGUAI8ynDJNlyNJubF7oUMzLCO9HvjgVjAG', 'paymongo', 'Standard delivery ?? est. 3???5 business days | paymongo_session:cs_0b215af7dd828aeed6363d75 | carrier_delivery:JOHN LARGA | proof:delivery_BJ-2026-0013_20260507184356_04fcd35c.png', 30, 'JOHN LARGA', '2026-05-07 18:43:49', 'TRACK-2026-BJ-2026-0013', 0, NULL, '2026-05-07 10:43:19', '2026-05-07 10:43:56'),
('BJ-2026-0014', 25, 'delivered', 1150.00, 150.00, 1300.00, 'BOGART BATUMBAKAL', 'enc:v1:Apa6/ex0/X/Xg04Q2jLN21B2g0DaK3BBaW9YbpLvWzzgcezUNA==', 'enc:v1:Vv5s2ZOhIwJew2rRBmpzwNiX2lFmhXsiIPWiX0lSr5vP', 'enc:v1:GyodyQf1zRcF7MjR4h4B+F9gt0N5BUrKk3K3t85ONU2hog==', 'enc:v1:QwGJ6u218R5J8/ddQliUG/N39lOfH4oGHjGYobMXCIU=', 'enc:v1:idq88Q7XwX186zFijH36MCviyiXMQDggJyrmszEY/eIK7zOsWjG0', 'paymongo', 'Standard delivery ?? est. 3???5 business days | paymongo_session:cs_25c953040d4959a87f5beb31 | carrier_delivery:JOHN LARGA | proof:delivery_BJ-2026-0014_20260507185150_9198ef46.png | delivered_at:2026-05-07T18:51:00+08:00', 30, 'JOHN LARGA', '2026-05-07 18:51:41', 'TRACK-2026-BJ-2026-0014', 0, NULL, '2026-05-07 10:51:07', '2026-05-07 10:51:50');

-- --------------------------------------------------------

--
-- Table structure for table `order_courier_ratings`
--

CREATE TABLE `order_courier_ratings` (
  `id` int(11) NOT NULL,
  `order_id` varchar(64) NOT NULL,
  `user_id` int(11) NOT NULL,
  `courier_user_id` int(11) DEFAULT NULL,
  `courier_name` varchar(160) DEFAULT NULL,
  `rating` tinyint(4) NOT NULL,
  `body` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_courier_ratings`
--

INSERT INTO `order_courier_ratings` (`id`, `order_id`, `user_id`, `courier_user_id`, `courier_name`, `rating`, `body`, `created_at`) VALUES
(1, 'BJ-2026-0014', 25, 30, 'JOHN LARGA', 5, NULL, '2026-05-07 10:52:31'),
(2, 'BJ-2026-0009', 3, 30, 'JOHN LARGA', 5, NULL, '2026-05-07 14:13:53');

-- --------------------------------------------------------

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

--
-- Dumping data for table `order_delivery_proofs`
--

INSERT INTO `order_delivery_proofs` (`id`, `order_id`, `carrier_name`, `carrier_reference`, `proof_photo`, `note`, `delivered_at`, `created_at`) VALUES
(2, 'BJ-2026-0001', 'JOHN LARGA', NULL, 'delivery_BJ-2026-0001_20260421134312_df8c3319.png', 'Sir tapos na po', '2026-04-21 13:42:00', '2026-04-21 05:43:12'),
(3, 'BJ-2026-0002', 'JOHN LARGA', NULL, 'delivery_BJ-2026-0002_20260501014527_9b918b36.png', NULL, '2026-05-01 01:45:00', '2026-04-30 17:45:27'),
(4, 'BJ-2026-0003', 'JOHN LARGA', NULL, 'delivery_BJ-2026-0003_20260501015414_ecb3d3ca.png', NULL, '2026-05-01 01:54:00', '2026-04-30 17:54:14'),
(5, 'BJ-2026-0005', 'Lenra Pilaps', NULL, 'delivery_BJ-2026-0005_20260506230242_d7f668e4.png', NULL, '2026-05-07 05:02:00', '2026-05-06 21:02:42'),
(6, 'BJ-2026-0007', 'Lenra Pilaps', NULL, 'delivery_BJ-2026-0007_20260506232200_ef25a0ff.png', NULL, '2026-05-07 05:21:00', '2026-05-06 21:22:00'),
(7, 'BJ-2026-0006', 'Lenra Pilaps', NULL, 'delivery_BJ-2026-0006_20260507102107_7ca40863.png', NULL, '2026-05-07 16:21:00', '2026-05-07 08:21:07'),
(8, 'BJ-2026-0010', 'JOHN LARGA', NULL, 'delivery_BJ-2026-0010_20260507183436_eb30ca6e.png', 'Done', '2026-05-07 18:34:00', '2026-05-07 10:34:36'),
(9, 'BJ-2026-0011', 'JOHN LARGA', NULL, 'delivery_BJ-2026-0011_20260507183448_f931688c.png', NULL, '2026-05-07 18:34:00', '2026-05-07 10:34:48'),
(10, 'BJ-2026-0009', 'JOHN LARGA', NULL, 'delivery_BJ-2026-0009_20260507183500_f2f57bec.png', NULL, '2026-05-07 18:34:00', '2026-05-07 10:35:00'),
(11, 'BJ-2026-0004', 'JOHN LARGA', NULL, 'delivery_BJ-2026-0004_20260507183642_4d76b4a7.png', NULL, '2026-05-07 18:36:00', '2026-05-07 10:36:42'),
(12, 'BJ-2026-0012', 'JOHN LARGA', NULL, 'delivery_BJ-2026-0012_20260507184155_b31cd0f9.png', NULL, '2026-05-07 18:41:00', '2026-05-07 10:41:55'),
(13, 'BJ-2026-0013', 'JOHN LARGA', NULL, 'delivery_BJ-2026-0013_20260507184356_04fcd35c.png', NULL, NULL, '2026-05-07 10:43:56'),
(14, 'BJ-2026-0014', 'JOHN LARGA', NULL, 'delivery_BJ-2026-0014_20260507185150_9198ef46.png', NULL, '2026-05-07 18:51:00', '2026-05-07 10:51:50');

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
(18, 'BJ-2026-0001', 1, 'Solara Ring', NULL, 'Rings', 'Ring', 580.00, 1),
(19, 'BJ-2026-0001', 5, 'Infinity Band', NULL, 'Rings', 'Ring', 750.00, 1),
(20, 'BJ-2026-0002', 1, 'Solara Ring', NULL, 'Rings', 'Ring', 580.00, 1),
(21, 'BJ-2026-0002', 5, 'Infinity Band', NULL, 'Rings', 'Ring', 750.00, 1),
(22, 'BJ-2026-0002', 10, 'sample', NULL, 'Rings', 'One Size', 10000.00, 1),
(23, 'BJ-2026-0003', 1, 'Solara Ring', NULL, 'Rings', 'Ring', 580.00, 2),
(24, 'BJ-2026-0003', 5, 'Infinity Band', NULL, 'Rings', 'Ring', 750.00, 1),
(25, 'BJ-2026-0003', 10, 'sample', NULL, 'Rings', 'One Size', 10000.00, 1),
(26, 'BJ-2026-0004', 2, 'Luna Earrings', NULL, 'Earrings', 'One Size', 500.00, 1),
(27, 'BJ-2026-0004', 7, 'Diamond Hoops', 'd62014b2daa29d8067892df6.png', 'Earrings', 'One Size', 650.00, 1),
(28, 'BJ-2026-0004', 3, 'Celestial Necklace', NULL, 'Necklaces', '18\"', 970.00, 1),
(29, 'BJ-2026-0004', 6, 'Star Pendant', NULL, 'Necklaces', '16\"', 820.00, 1),
(30, 'BJ-2026-0005', 5, 'Infinity Band', '35d7ece7dc0629322eda9d8e.png', 'Rings', 'Ring', 750.00, 1),
(31, 'BJ-2026-0006', 6, 'Star Pendant', '2c9aa303bb3804764cdd2562.png', 'Necklaces', '16\"', 820.00, 1),
(32, 'BJ-2026-0007', 1, 'Solara Ring', '61f40b464da7e81c090a19ea.png', 'Rings', 'Ring', 580.00, 1),
(33, 'BJ-2026-0008', 3, 'Celestial Necklace', '05d0fda466ec262414281abc.png', 'Necklaces', '18\"', 970.00, 1),
(34, 'BJ-2026-0009', 2, 'Luna Earrings', '41230039f40d5bac45635d38.png', 'Earrings', 'One Size', 500.00, 1),
(35, 'BJ-2026-0010', 8, 'Aurora Bangle', '34b0d0bcfd642486dd5d9ee2.png', 'Bracelets', 'One Size', 890.00, 1),
(36, 'BJ-2026-0011', 1, 'Solara Ring', '61f40b464da7e81c090a19ea.png', 'Rings', 'Ring', 580.00, 2),
(37, 'BJ-2026-0012', 1, 'Solara Ring', '61f40b464da7e81c090a19ea.png', 'Rings', 'Ring', 580.00, 1),
(38, 'BJ-2026-0013', 1, 'Solara Ring', '61f40b464da7e81c090a19ea.png', 'Rings', 'Ring', 580.00, 1),
(39, 'BJ-2026-0013', 5, 'Infinity Band', '35d7ece7dc0629322eda9d8e.png', 'Rings', 'Ring', 750.00, 1),
(40, 'BJ-2026-0014', 2, 'Luna Earrings', '41230039f40d5bac45635d38.png', 'Earrings', 'One Size', 500.00, 1),
(41, 'BJ-2026-0014', 7, 'Diamond Hoops', 'ff4e65ceb246a8f19f5c1f19.png', 'Earrings', 'One Size', 650.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `order_receipts`
--

CREATE TABLE `order_receipts` (
  `id` int(11) NOT NULL,
  `order_id` varchar(64) NOT NULL,
  `user_id` int(11) NOT NULL,
  `received_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_receipts`
--

INSERT INTO `order_receipts` (`id`, `order_id`, `user_id`, `received_at`) VALUES
(1, 'BJ-2026-0007', 3, '2026-05-07 16:31:32'),
(2, 'BJ-2026-0006', 3, '2026-05-07 16:31:36'),
(3, 'BJ-2026-0005', 3, '2026-05-07 16:32:06'),
(4, 'BJ-2026-0003', 25, '2026-05-07 18:05:44'),
(5, 'BJ-2026-0001', 25, '2026-05-07 18:14:32'),
(6, 'BJ-2026-0004', 25, '2026-05-07 18:37:11'),
(7, 'BJ-2026-0012', 25, '2026-05-07 18:42:22'),
(8, 'BJ-2026-0013', 25, '2026-05-07 18:44:26'),
(9, 'BJ-2026-0014', 25, '2026-05-07 18:52:25'),
(10, 'BJ-2026-0009', 3, '2026-05-07 22:13:45');

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
  `post_json` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 'Solara Ring', 'A radiant ring inspired by the sun, crafted in 14K gold.', 1, 580.00, NULL, '61f40b464da7e81c090a19ea.png', '', 5, 128, 'Ring', '14K Gold', 88, 1, '2026-03-16 23:52:50', '2026-05-07 10:43:19'),
(2, 'Luna Earrings', 'Delicate stud earrings with a luminous sterling silver finish.', 4, 500.00, NULL, '41230039f40d5bac45635d38.png', '', 5, 94, 'One Size', 'Sterling Silver', 97, 1, '2026-03-16 23:52:50', '2026-05-07 10:51:07'),
(3, 'Celestial Necklace', 'An 18-inch gold necklace adorned with star and moon charms.', 2, 970.00, NULL, '05d0fda466ec262414281abc.png', '', 5, 203, '18\"', '14K Gold', 98, 1, '2026-03-16 23:52:50', '2026-05-02 09:01:25'),
(4, 'Dia Charm Bracelet', 'A charm bracelet featuring dazzling crystal accents.', 3, 700.00, NULL, 'f20ac1d0172a57aa878f8ee1.png', '', 4, 67, 'One Size', 'Sterling Silver', 100, 1, '2026-03-16 23:52:50', '2026-05-02 07:41:55'),
(5, 'Infinity Band', 'A sleek infinity-symbol band in polished 14K gold.', 1, 750.00, NULL, '35d7ece7dc0629322eda9d8e.png', '', 5, 45, 'Ring', '14K Gold', 93, 1, '2026-03-16 23:52:50', '2026-05-07 10:43:19'),
(6, 'Star Pendant', 'A sparkling star-shaped pendant on a 16-inch rose gold chain.', 2, 820.00, 1020.00, '2c9aa303bb3804764cdd2562.png', '', 4, 88, '16\"', 'Rose Gold', 95, 1, '2026-03-16 23:52:50', '2026-05-02 08:09:08'),
(7, 'Diamond Hoops', 'Classic hoop earrings with a diamond-cut finish in 14K gold.', 4, 650.00, NULL, 'ff4e65ceb246a8f19f5c1f19.png', '', 5, 112, 'One Size', '14K Gold', 98, 1, '2026-03-16 23:52:50', '2026-05-07 10:51:07'),
(8, 'Aurora Bangle', 'A wide rose-gold bangle with an iridescent enamel finish.', 3, 890.00, NULL, '34b0d0bcfd642486dd5d9ee2.png', '', 4, 56, 'One Size', 'Rose Gold', 99, 1, '2026-03-16 23:52:50', '2026-05-02 09:27:51'),
(10, 'sample', 'maganda', 1, 10000.00, NULL, NULL, 'best', 5, 0, 'One Size', 'Sterling Silver', 98, 1, '2026-04-25 05:58:26', '2026-04-30 17:52:35');

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

--
-- Dumping data for table `product_reviews`
--

INSERT INTO `product_reviews` (`id`, `product_id`, `user_id`, `order_id`, `rating`, `title`, `body`, `status`, `created_at`, `updated_at`) VALUES
(6, 2, 25, 'BJ-2026-0004', 5, NULL, NULL, 'pending', '2026-05-07 10:37:15', '2026-05-07 10:37:15'),
(7, 7, 25, 'BJ-2026-0004', 5, NULL, NULL, 'pending', '2026-05-07 10:37:18', '2026-05-07 10:37:18'),
(8, 3, 25, 'BJ-2026-0004', 5, NULL, NULL, 'pending', '2026-05-07 10:37:20', '2026-05-07 10:37:20'),
(9, 6, 25, 'BJ-2026-0004', 5, NULL, NULL, 'pending', '2026-05-07 10:37:22', '2026-05-07 10:37:22'),
(10, 1, 25, 'BJ-2026-0012', 5, NULL, 'good', 'pending', '2026-05-07 10:42:29', '2026-05-07 10:42:29'),
(11, 1, 25, 'BJ-2026-0013', 5, NULL, NULL, 'pending', '2026-05-07 10:44:31', '2026-05-07 10:44:31'),
(12, 5, 25, 'BJ-2026-0013', 5, NULL, NULL, 'pending', '2026-05-07 10:44:32', '2026-05-07 10:44:32'),
(13, 2, 25, 'BJ-2026-0014', 5, NULL, NULL, 'pending', '2026-05-07 10:52:27', '2026-05-07 10:52:27'),
(14, 7, 25, 'BJ-2026-0014', 5, NULL, NULL, 'pending', '2026-05-07 10:52:29', '2026-05-07 10:52:29'),
(15, 2, 3, 'BJ-2026-0009', 5, NULL, NULL, 'pending', '2026-05-07 14:13:48', '2026-05-07 14:13:48');

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
(2, 'FREESHIP', 'Free shipping over ???2k', 'fixed', 150.00, 2000.00, NULL, 0, NULL, NULL, 1, '2026-03-17 00:54:50', '2026-03-17 00:54:50');

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
-- Table structure for table `refund_logs`
--

CREATE TABLE `refund_logs` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `customer_name` varchar(160) NOT NULL,
  `order_id` varchar(20) NOT NULL,
  `refund_amount` decimal(10,2) NOT NULL,
  `ticket_category` varchar(32) NOT NULL,
  `approved_by` int(11) NOT NULL,
  `approved_by_name` varchar(160) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `refund_logs`
--

INSERT INTO `refund_logs` (`id`, `ticket_id`, `user_id`, `customer_name`, `order_id`, `refund_amount`, `ticket_category`, `approved_by`, `approved_by_name`, `created_at`) VALUES
(1, 3, 25, 'BOGART BATUMBAKAL', 'BJ-2026-0003', 11910.00, 'damaged', 21, 'ORDER LARGA', '2026-05-05 11:56:49');

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
  `price` decimal(10,2) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT 'Inventory Manager',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_history`
--

INSERT INTO `stock_history` (`id`, `product_id`, `qty_added`, `stock_after`, `price`, `note`, `updated_by`, `created_at`) VALUES
(1, 3, -1, 98, NULL, 'Order BJ-2026-0008', 'BJ-2026-0008', '2026-05-02 09:01:25'),
(2, 2, -1, 98, NULL, NULL, 'PATRICK IVAN PILAPIL', '2026-05-02 09:20:01'),
(3, 8, -1, 99, 890.00, NULL, 'PATRICK IVAN PILAPIL', '2026-05-02 09:27:51'),
(4, 1, -2, 90, 580.00, NULL, 'PATRICK IVAN PILAPIL', '2026-05-07 08:20:16'),
(5, 1, -1, 89, 580.00, NULL, 'BOGART BATUMBAKAL', '2026-05-07 10:36:06'),
(6, 1, -1, 88, 580.00, NULL, 'BOGART BATUMBAKAL', '2026-05-07 10:43:19'),
(7, 5, -1, 93, 750.00, NULL, 'BOGART BATUMBAKAL', '2026-05-07 10:43:19'),
(8, 2, -1, 97, 500.00, NULL, 'BOGART BATUMBAKAL', '2026-05-07 10:51:07'),
(9, 7, -1, 98, 650.00, NULL, 'BOGART BATUMBAKAL', '2026-05-07 10:51:07');

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
  `photo_path` varchar(255) DEFAULT NULL,
  `resolution` enum('reorder','redeliver','refund') DEFAULT NULL,
  `status` enum('open','resolved','closed') NOT NULL DEFAULT 'open',
  `admin_note` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `category` varchar(32) DEFAULT 'product',
  `scope` enum('manager','super_admin') NOT NULL DEFAULT 'manager'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `support_tickets`
--

INSERT INTO `support_tickets` (`id`, `user_id`, `order_id`, `type`, `description`, `photo_path`, `resolution`, `status`, `admin_note`, `rejection_reason`, `reviewed_by`, `reviewed_at`, `created_at`, `updated_at`, `category`, `scope`) VALUES
(1, 25, 'BJ-2026-0013', 'wrong_item', 'wrong item', NULL, NULL, 'open', NULL, NULL, NULL, NULL, '2026-04-17 21:36:49', '2026-04-17 21:36:49', 'product', 'manager'),
(2, 25, 'BJ-2026-0004', 'wrong_item', 'wrong item', NULL, NULL, 'open', NULL, NULL, NULL, NULL, '2026-05-01 02:42:38', '2026-05-01 02:42:38', 'product', 'manager'),
(3, 25, 'BJ-2026-0003', 'damaged', 'i want refund', 'ticket_20260505_114239_20ce24c3a719.jpg', 'refund', 'resolved', NULL, NULL, 21, '2026-05-05 11:56:54', '2026-05-05 11:42:39', '2026-05-05 11:56:54', 'product', 'manager'),
(4, 3, 'UNLOCK-3', 'other', 'Account unlock request submitted from the login page. Reason: s', NULL, NULL, 'resolved', 'Unlocked by super admin.', NULL, 9, '2026-05-08 02:11:01', '2026-05-08 02:08:54', '2026-05-08 02:11:01', 'other', 'super_admin'),
(5, 3, 'ACCDEL-3', 'other', 'Account deletion request submitted from profile settings (pilapil_patrickivan@plpasig.edu.ph)', NULL, NULL, 'resolved', NULL, NULL, NULL, NULL, '2026-05-08 02:11:49', '2026-05-08 02:21:23', 'other', 'super_admin');

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
  `phone` varchar(512) DEFAULT NULL,
  `gender` enum('Male','Female','Other','Prefer not to say') DEFAULT NULL,
  `birthday` varchar(255) DEFAULT NULL,
  `city` varchar(512) DEFAULT NULL,
  `role` enum('customer','super_admin','manager','inventory','courier') NOT NULL DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `email_verified_at` datetime DEFAULT NULL,
  `activation_token` varchar(64) DEFAULT NULL,
  `activation_expires` datetime DEFAULT NULL,
  `totp_secret` varchar(64) DEFAULT NULL,
  `failed_login_attempts` int(11) NOT NULL DEFAULT 0,
  `locked_at` datetime DEFAULT NULL,
  `locked_by` int(11) DEFAULT NULL,
  `lock_reason` varchar(255) DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL,
  `archived_by` int(11) DEFAULT NULL,
  `archive_reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `username`, `email`, `password_hash`, `phone`, `gender`, `birthday`, `city`, `role`, `created_at`, `updated_at`, `email_verified_at`, `activation_token`, `activation_expires`, `totp_secret`, `failed_login_attempts`, `locked_at`, `locked_by`, `lock_reason`, `archived_at`, `archived_by`, `archive_reason`) VALUES
(1, 'Admin', 'Bejewelry', NULL, 'admin@bejewelry.ph', 'Admin123', NULL, NULL, NULL, NULL, 'inventory', '2026-03-16 23:52:50', '2026-03-26 09:45:08', '2026-03-17 07:52:50', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'PATRICK IVAN', 'PILAPIL', NULL, 'pilapil_patrickivan@plpasig.edu.ph', '$2y$10$8bRoCLl75x.pRyz43DrVx.6g9NePaUTXcLFsIov4cvG5Uxl0JuffO', '09394551110', 'Male', '2010-08-27', 'Manila', 'customer', '2026-03-17 01:17:11', '2026-05-07 18:50:05', '2026-03-17 09:17:11', NULL, NULL, 'ACMBEJ7DMQ766P3F', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(9, 'Patrick', 'Ivan Pilapil', NULL, 'patrickpilapil1313@gmail.com', '$2y$10$p6HSW4B5bFV38By4gPtBk.L08LfhxH.kPsLvxaNgXVGlNyI9aBf9m', NULL, NULL, NULL, NULL, 'super_admin', '2026-03-17 06:53:11', '2026-05-07 18:55:44', '2026-03-17 14:53:11', NULL, NULL, 'PJE65A2ZIAFVWK4W', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(12, 'Vincent', 'Andrada', NULL, 'andrada_jonvincent@plpasig.edu.ph', '$2y$10$lYUO9KDcT7jQBCkbfQzlreMZNFCrnU3wGSctQBeLsmLEwskH8DDpC', NULL, NULL, NULL, NULL, 'customer', '2026-03-26 09:57:20', '2026-03-26 09:57:20', NULL, '02066e36d597f813e9bd7a2198b86d094bdf3d7ef408de8f11e3d7a952b2eb39', '2026-03-28 10:57:20', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
(15, 'PATRICK', 'IVAN PILAPIL', NULL, 'patrickpilapil27@gmail.com', '$2y$10$.hepEVVUagk4Ia5CZ6Srn.w.XYL8eFL7GnzYV11bagOu5FEUC/dhS', '09394551110', 'Male', '2026-03-25', 'Manila', 'manager', '2026-03-26 11:32:36', '2026-05-07 17:12:16', NULL, NULL, NULL, 'CIUPK3YGZKIFORHF', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(19, 'SUPER', 'ADMIN LARGA', NULL, 'larga_johnsebastian@plpasig.edu.ph', '$2y$10$dPKaiyr43VedUXLKeCp53uEGnaQAl17jugTiOk/.SZbjY6QFIihMy', NULL, NULL, NULL, NULL, 'super_admin', '2026-04-11 06:38:24', '2026-05-07 10:54:42', NULL, NULL, NULL, 'ABVZR7PWSCCPX3JC', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(21, 'ORDER', 'LARGA', NULL, 'sebastianlarga6@gmail.com', '$2y$10$CguPlskSqbmI1UQYu0K/aeTQHFo4/8WE336PO.pZ9UhLbDaFsRMAm', '09925801741', 'Male', '2026-04-16', 'Pasig', 'inventory', '2026-04-11 06:44:11', '2026-05-07 10:49:27', NULL, NULL, NULL, 'NJDC656JPNZ7RRWZ', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(25, 'BOGART', 'BATUMBAKAL', NULL, 'melitalarga4@gmail.com', '$2y$10$uT2zU7jiiDELR3XBOb/Y0O1eDaux0E2P6feeqcPCTZRZyk8ws64Ne', '09925801741', 'Male', '2026-04-17', 'Pasig', 'customer', '2026-04-16 08:36:35', '2026-05-07 10:52:21', '2026-04-16 16:38:18', NULL, NULL, 'JIHLAAGPMNS6VU22', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(30, 'JOHN', 'LARGA', NULL, 'sebasteestab@gmail.com', '$2y$10$tgLf4Wk9bnYSr4dby8Yvx.R6fIH7RX3mXbIGlI.FbbA1Ienss249C', NULL, NULL, NULL, NULL, 'courier', '2026-04-21 05:05:09', '2026-05-07 10:51:39', '2026-04-21 13:05:34', NULL, NULL, '5AW7WS63DYWPW7YN', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(31, 'Andrew', 'Fernandez', NULL, 'fernandez_jamesandrew@plpasig.edu.ph', '$2y$10$3yNB1zrpRdCwakVJGy3F7.Wys.GfMDSoTuV44vrq4T0RvLvqhN/Vi', '09925801741', 'Male', '2005-08-10', 'Pasig', 'customer', '2026-04-25 09:49:28', '2026-04-25 10:02:45', '2026-04-25 17:51:06', NULL, NULL, 'Q2BNA255YDTBF34A', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(32, 'Vernalyn', 'Miguel', NULL, 'miguel_vernalyn@plpasig.edu.ph', '$2y$10$ncUtz0OPzYZAfv.RlMQaCur6q8Ad6Jjym6RjsInnI7sDPwSjKsYEi', NULL, NULL, NULL, NULL, 'customer', '2026-05-02 12:27:29', '2026-05-02 12:27:29', NULL, 'c6129430c8e050ee5fe25bdf5da7740c9ace3a9a2a1d8296fd85c40923e9872c', '2026-05-04 14:27:29', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
(35, 'Lenra', 'Pilaps', NULL, 'lenrapilapil@gmail.com', '$2y$10$FnDG/akFr/MqVIC2fZa/zOrFWgCSKDdoJx6Tl5Iph5PVq/HW.F4lO', 'enc:v1:bx/ssW8nBBibZ1SP/SqNxZCfyH7jqr+Id8bv/YZslaBys3BHXo3F', NULL, NULL, NULL, 'courier', '2026-05-06 21:01:31', '2026-05-06 21:01:50', '2026-05-07 05:01:31', NULL, NULL, 'USQXMWVFD2CID22W', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(36, 'MARITHE', 'DE LEON', NULL, 'deleon_marithefrancine@plpasig.edu.ph', '$2y$10$asAxoSjbBZDIXL/XxRj1P.bMd4wi30/Rjrz9sE.QIpvbIYzNq4ejS', NULL, NULL, NULL, NULL, 'super_admin', '2026-05-07 10:55:48', '2026-05-07 10:56:55', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL);

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
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`user_id`, `product_id`, `added_at`) VALUES
(3, 1, '2026-05-07 14:36:40'),
(25, 1, '2026-04-25 05:50:51'),
(25, 2, '2026-04-25 05:51:16'),
(25, 3, '2026-04-25 05:51:20'),
(25, 6, '2026-04-25 05:51:59');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account_lock_logs`
--
ALTER TABLE `account_lock_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_locked_at` (`locked_at`);

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
  ADD KEY `idx_orders_courier_user_id` (`courier_user_id`),
  ADD KEY `idx_orders_tracking_number` (`tracking_number`);

--
-- Indexes for table `order_courier_ratings`
--
ALTER TABLE `order_courier_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_order_user` (`order_id`,`user_id`),
  ADD KEY `idx_courier_user` (`courier_user_id`);

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
-- Indexes for table `order_receipts`
--
ALTER TABLE `order_receipts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_order_user` (`order_id`,`user_id`),
  ADD KEY `idx_user_received` (`user_id`,`received_at`);

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
-- Indexes for table `refund_logs`
--
ALTER TABLE `refund_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_refund_ticket` (`ticket_id`),
  ADD KEY `idx_refund_created` (`created_at`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `approved_by` (`approved_by`);

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
-- AUTO_INCREMENT for table `account_lock_logs`
--
ALTER TABLE `account_lock_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=472;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `customer_notes`
--
ALTER TABLE `customer_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_courier_ratings`
--
ALTER TABLE `order_courier_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_delivery_proofs`
--
ALTER TABLE `order_delivery_proofs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `order_receipts`
--
ALTER TABLE `order_receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `paymongo_checkout_pending`
--
ALTER TABLE `paymongo_checkout_pending`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `promotion_redemptions`
--
ALTER TABLE `promotion_redemptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `refund_logs`
--
ALTER TABLE `refund_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `report_snapshots`
--
ALTER TABLE `report_snapshots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_history`
--
ALTER TABLE `stock_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

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
  ADD CONSTRAINT `fk_orders_courier_user` FOREIGN KEY (`courier_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `refund_logs`
--
ALTER TABLE `refund_logs`
  ADD CONSTRAINT `refund_logs_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `refund_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `refund_logs_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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

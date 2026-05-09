-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 09, 2026 at 07:17 AM
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
('shipping', '{\"shipping_fee\":170}', '2026-05-08 23:00:48'),
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
(22, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 08:39:09'),
(23, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 08:58:51'),
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
(34, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 16:29:17'),
(35, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 16:30:08'),
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
(46, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 12:53:23'),
(47, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 12:54:29'),
(48, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 12:55:58'),
(49, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:02:32'),
(50, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:02:58'),
(51, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:03:46'),
(52, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:05:40'),
(53, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:07:22'),
(54, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:08:00'),
(55, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:08:40'),
(56, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:09:10'),
(57, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:26:49'),
(58, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:27:17'),
(59, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:36:55'),
(60, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:42:22'),
(61, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:42:31'),
(62, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:43:16'),
(63, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:44:44'),
(64, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:48:45'),
(65, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 13:48:48'),
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
(96, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 08:20:54'),
(97, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 08:21:04'),
(98, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 15:20:08'),
(99, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 04:31:21'),
(100, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 04:37:10'),
(101, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 04:37:28'),
(102, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 04:38:08'),
(103, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 04:38:21'),
(104, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 05:04:40'),
(105, 30, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 05:06:24'),
(106, 30, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 05:07:14'),
(107, 30, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 05:07:26'),
(108, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 05:07:37'),
(109, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 05:42:29'),
(110, 30, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 05:42:44'),
(111, 30, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 05:43:36'),
(112, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 05:43:53'),
(113, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 11:41:02'),
(114, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 11:41:26'),
(115, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 11:41:42'),
(116, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 16:33:01'),
(117, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 17:18:31'),
(118, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 17:19:03'),
(119, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 17:31:15'),
(120, 19, 'larga_johnsebastian@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 17:31:47'),
(121, 19, 'larga_johnsebastian@plpasig.edu.ph', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 17:55:38'),
(122, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 17:56:23'),
(123, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 18:02:20'),
(124, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 18:03:00'),
(125, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 18:05:33'),
(126, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 18:05:53'),
(127, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 18:09:45'),
(128, 30, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 18:10:41'),
(129, 30, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 18:11:57'),
(130, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 18:12:08'),
(131, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 19:03:21'),
(132, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 05:50:25'),
(133, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 05:52:54'),
(134, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 05:53:07'),
(135, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 05:53:52'),
(136, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 05:54:04'),
(137, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 05:54:39'),
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
(162, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-30 17:52:16'),
(163, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-30 17:52:57'),
(164, 30, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-30 17:53:11'),
(165, 30, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-30 18:23:50'),
(166, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-30 18:24:38'),
(167, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-30 18:29:51'),
(168, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-30 18:30:16'),
(169, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-30 18:41:37'),
(170, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-30 18:42:14'),
(171, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-30 18:42:44'),
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
(200, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:00:06'),
(201, NULL, 'melitalarga4@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:02:53'),
(202, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:03:11'),
(203, NULL, 'melitalarga4@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:06:37'),
(204, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:07:05'),
(205, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:11:36'),
(206, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:12:00'),
(207, NULL, 'melitalarga4@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:14:42'),
(208, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:15:07'),
(209, NULL, 'melitalarga4@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:20:31'),
(210, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:20:42'),
(211, NULL, 'melitalarga4@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:22:51'),
(212, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:23:06'),
(213, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:27:43'),
(214, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:40:56'),
(215, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 03:42:41'),
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
(253, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 05:10:51'),
(254, NULL, 'melitalarga4@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 05:15:37'),
(255, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 05:16:00'),
(256, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 05:17:03'),
(257, 19, 'larga_johnsebastian@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 05:17:28');
INSERT INTO `audit_log` (`id`, `user_id`, `email`, `action`, `ip`, `user_agent`, `created_at`) VALUES
(258, 19, 'larga_johnsebastian@plpasig.edu.ph', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 05:17:34'),
(259, 21, 'sebastianlarga6@gmail.com', 'lock_account', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 05:20:04'),
(260, 19, 'larga_johnsebastian@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 05:20:13'),
(261, 19, 'larga_johnsebastian@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 05:22:22'),
(262, NULL, 'melitalarga4@gmail.com', 'lock_account', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 05:30:15'),
(263, 19, 'larga_johnsebastian@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 05:30:39'),
(264, 19, 'larga_johnsebastian@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 05:33:47'),
(265, 19, 'larga_johnsebastian@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 09:41:25'),
(266, 19, 'larga_johnsebastian@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 09:43:48'),
(267, 19, 'larga_johnsebastian@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 15:39:36'),
(268, 19, 'larga_johnsebastian@plpasig.edu.ph', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 15:39:41'),
(269, 19, 'larga_johnsebastian@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 15:52:52'),
(270, 19, 'larga_johnsebastian@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 15:55:14'),
(271, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 16:03:54'),
(272, NULL, 'melitalarga4@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 16:07:47'),
(273, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 16:36:03'),
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
(380, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:05:39'),
(381, NULL, 'melitalarga4@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:12:08'),
(382, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:14:12'),
(383, NULL, 'melitalarga4@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:16:53'),
(384, 19, 'larga_johnsebastian@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:21:08'),
(385, 19, 'larga_johnsebastian@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:23:17'),
(386, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:30:27'),
(387, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:33:57'),
(388, 30, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:34:09'),
(389, 30, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:35:03'),
(390, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:35:15'),
(391, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:36:16'),
(392, 30, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:36:29'),
(393, 30, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:36:44'),
(394, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:36:55'),
(395, NULL, 'melitalarga4@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:40:56'),
(396, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:41:13'),
(397, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:41:36'),
(398, 30, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:41:44'),
(399, 30, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:41:59'),
(400, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:42:16'),
(401, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:43:22'),
(402, 30, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:43:48'),
(403, 30, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:44:00'),
(404, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:44:22'),
(405, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:45:16'),
(406, 21, 'sebastianlarga6@gmail.com', 'lock_account', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:45:55'),
(407, 19, 'larga_johnsebastian@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:46:12'),
(408, 19, 'larga_johnsebastian@plpasig.edu.ph', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:46:21'),
(409, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:48:21'),
(410, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:48:53'),
(411, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:49:27'),
(412, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:50:17'),
(413, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:50:36'),
(414, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:51:13'),
(415, 30, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:51:39'),
(416, 30, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:51:52'),
(417, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:52:21'),
(418, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:52:38'),
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
(471, 9, 'patrickpilapil1313@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-07 18:55:55'),
(472, NULL, 'melitalarga4@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 14:48:25'),
(473, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 14:49:08'),
(474, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 14:49:54'),
(475, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 14:50:05'),
(476, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 14:50:14'),
(477, 30, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 14:50:49'),
(478, 30, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 14:51:09'),
(479, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 14:51:18'),
(480, NULL, 'melitalarga4@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 14:54:13'),
(481, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 15:23:10'),
(482, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 15:24:18'),
(483, 30, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 15:24:30'),
(484, 30, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 15:24:49'),
(485, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 15:25:06'),
(486, NULL, 'melitalarga4@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 15:28:50'),
(487, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 15:29:34'),
(488, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 15:30:05'),
(489, 19, 'larga_johnsebastian@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 15:30:17'),
(490, 19, 'larga_johnsebastian@plpasig.edu.ph', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 15:30:23'),
(491, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 15:30:53'),
(492, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 15:32:51'),
(493, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 15:33:06'),
(494, NULL, 'melitalarga4@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 15:36:15'),
(495, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 15:36:23'),
(496, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 15:39:14'),
(497, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 15:39:26'),
(498, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 15:39:43'),
(499, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 15:40:46');
INSERT INTO `audit_log` (`id`, `user_id`, `email`, `action`, `ip`, `user_agent`, `created_at`) VALUES
(500, 21, 'sebastianlarga6@gmail.com', 'add_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 15:41:12'),
(501, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 15:41:16'),
(502, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 15:41:34'),
(503, NULL, 'melitalarga4@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 15:47:04'),
(504, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 15:52:51'),
(505, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:15:17'),
(506, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:18:12'),
(507, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:22:21'),
(508, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:24:08'),
(509, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:24:22'),
(510, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:26:37'),
(511, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:26:42'),
(512, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:28:56'),
(513, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:29:08'),
(514, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:33:07'),
(515, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:33:13'),
(516, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:35:46'),
(517, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:35:53'),
(518, 9, 'patrickpilapil1313@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-08 16:36:37'),
(519, 15, 'patrickpilapil27@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:37:37'),
(520, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:38:13'),
(521, 15, 'patrickpilapil27@gmail.com', 'add_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:38:16'),
(522, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:38:28'),
(523, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:40:31'),
(524, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:40:39'),
(525, 15, 'patrickpilapil27@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:41:35'),
(526, 15, 'patrickpilapil27@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:41:49'),
(527, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:43:01'),
(528, 9, 'patrickpilapil1313@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-08 16:43:03'),
(529, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:45:35'),
(530, 15, 'patrickpilapil27@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:46:44'),
(531, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:48:40'),
(532, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:48:49'),
(533, 9, 'patrickpilapil1313@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-08 16:49:39'),
(534, 9, 'patrickpilapil1313@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-08 16:49:45'),
(535, 15, 'patrickpilapil27@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:49:59'),
(536, 15, 'patrickpilapil27@gmail.com', 'add_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:50:35'),
(537, 15, 'patrickpilapil27@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:53:29'),
(538, 9, 'patrickpilapil1313@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-08 16:54:05'),
(539, 15, 'patrickpilapil27@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:54:16'),
(540, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:55:55'),
(541, 9, 'patrickpilapil1313@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-08 16:56:28'),
(542, 15, 'patrickpilapil27@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:58:46'),
(543, 15, 'patrickpilapil27@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 16:59:42'),
(544, 15, 'patrickpilapil27@gmail.com', 'toggle_sale_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:01:57'),
(545, 15, 'patrickpilapil27@gmail.com', 'toggle_sale_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:02:12'),
(546, 15, 'patrickpilapil27@gmail.com', 'toggle_sale_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:02:14'),
(547, 15, 'patrickpilapil27@gmail.com', 'toggle_sale_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:02:16'),
(548, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:03:37'),
(549, 15, 'patrickpilapil27@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:05:07'),
(550, 15, 'patrickpilapil27@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:05:14'),
(551, 15, 'patrickpilapil27@gmail.com', 'toggle_sale_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:05:31'),
(552, 15, 'patrickpilapil27@gmail.com', 'toggle_sale_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:06:09'),
(553, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:07:56'),
(554, 15, 'patrickpilapil27@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:12:11'),
(555, 15, 'patrickpilapil27@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:12:23'),
(556, 15, 'patrickpilapil27@gmail.com', 'toggle_sale_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:12:56'),
(557, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:13:10'),
(558, 15, 'patrickpilapil27@gmail.com', 'toggle_sale_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:13:45'),
(559, 15, 'patrickpilapil27@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:15:54'),
(560, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:16:27'),
(561, 15, 'patrickpilapil27@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:17:37'),
(562, 15, 'patrickpilapil27@gmail.com', 'toggle_sale_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:17:47'),
(563, 15, 'patrickpilapil27@gmail.com', 'toggle_sale_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:19:52'),
(564, 15, 'patrickpilapil27@gmail.com', 'toggle_sale_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:19:55'),
(565, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:20:21'),
(566, 9, 'patrickpilapil1313@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-08 17:21:23'),
(567, 15, 'patrickpilapil27@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:22:09'),
(568, 15, 'patrickpilapil27@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:22:21'),
(569, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:23:13'),
(570, 9, 'patrickpilapil1313@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-08 17:23:48'),
(571, 15, 'patrickpilapil27@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:27:40'),
(572, 15, 'patrickpilapil27@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:28:58'),
(573, 15, 'patrickpilapil27@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:35:06'),
(574, 15, 'patrickpilapil27@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:48:01'),
(575, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:48:41'),
(576, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:50:56'),
(577, 15, 'patrickpilapil27@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:50:59'),
(578, 15, 'patrickpilapil27@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:51:05'),
(579, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:52:48'),
(580, 15, 'patrickpilapil27@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:54:32'),
(581, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:57:37'),
(582, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 18:03:35'),
(583, 3, 'pilapil_patrickivan@plpasig.edu.ph', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 18:06:00'),
(584, NULL, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 18:16:31'),
(585, NULL, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 18:17:34'),
(586, 38, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 18:24:34'),
(587, 38, 'melitalarga4@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 18:31:52'),
(588, 38, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 18:38:36'),
(589, 38, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 18:40:51'),
(590, 38, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 18:42:41'),
(591, 38, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 18:44:21'),
(592, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 18:44:39'),
(593, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 18:44:52'),
(594, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 18:45:14'),
(595, 21, 'sebastianlarga6@gmail.com', 'create_promotion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 18:46:20'),
(596, 21, 'sebastianlarga6@gmail.com', 'edit_promotion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 18:46:30'),
(597, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 18:47:02'),
(598, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 18:47:35'),
(599, 21, 'sebastianlarga6@gmail.com', 'toggle_sale_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 18:48:10'),
(600, 21, 'sebastianlarga6@gmail.com', 'toggle_sale_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 18:48:23'),
(601, 21, 'sebastianlarga6@gmail.com', 'toggle_sale_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 18:48:58'),
(602, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 18:49:07'),
(603, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 18:49:25'),
(604, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 18:49:37'),
(605, 38, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 18:49:53'),
(606, 38, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 18:53:25'),
(607, 38, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 18:56:40'),
(608, 38, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 18:57:01'),
(609, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 18:57:18'),
(610, 21, 'sebastianlarga6@gmail.com', 'toggle_sale_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 18:57:40'),
(611, 21, 'sebastianlarga6@gmail.com', 'toggle_sale_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 18:57:44'),
(612, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 18:57:47'),
(613, 38, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 18:58:09'),
(614, 38, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:00:41'),
(615, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:01:04'),
(616, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:01:39'),
(617, 30, 'sebasteestab@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:01:51'),
(618, 30, 'sebasteestab@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:02:10'),
(619, 38, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:02:50'),
(620, 38, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:10:18'),
(621, 38, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:12:01'),
(622, 38, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:12:31'),
(623, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:12:52'),
(624, 21, 'sebastianlarga6@gmail.com', 'toggle_sale_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:13:01'),
(625, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:13:06'),
(626, 38, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:13:15'),
(627, 38, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:19:23'),
(628, 38, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:20:44'),
(629, 38, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:26:57'),
(630, 38, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:31:13'),
(631, 38, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:40:16'),
(632, 38, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:43:04'),
(633, 38, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:45:54'),
(634, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:46:35'),
(635, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:49:11'),
(636, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:49:33'),
(637, 21, 'sebastianlarga6@gmail.com', 'restock_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:50:24'),
(638, 21, 'sebastianlarga6@gmail.com', 'restock_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:50:31'),
(639, 21, 'sebastianlarga6@gmail.com', 'restock_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:50:44'),
(640, 21, 'sebastianlarga6@gmail.com', 'restock_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:51:10'),
(641, 21, 'sebastianlarga6@gmail.com', 'restock_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:51:24'),
(642, 21, 'sebastianlarga6@gmail.com', 'restock_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:51:44'),
(643, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:55:40'),
(644, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:57:33'),
(645, 21, 'sebastianlarga6@gmail.com', 'restock_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:58:36'),
(646, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:59:45'),
(647, 38, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 19:59:57'),
(648, 38, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:00:41'),
(649, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:00:53'),
(650, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:03:15'),
(651, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:03:26'),
(652, 21, 'sebastianlarga6@gmail.com', 'restock_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:03:43'),
(653, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:05:43'),
(654, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:10:45'),
(655, 21, 'sebastianlarga6@gmail.com', 'restock_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:11:00'),
(656, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:11:16'),
(657, 38, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:11:27'),
(658, 38, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:12:13'),
(659, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:12:25'),
(660, 21, 'sebastianlarga6@gmail.com', 'restock_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:12:44'),
(661, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:17:53'),
(662, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:18:03'),
(663, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:19:36'),
(664, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:19:47'),
(665, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:22:40'),
(666, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:22:55'),
(667, 21, 'sebastianlarga6@gmail.com', 'edit_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:30:05'),
(668, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:36:59'),
(669, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:37:13'),
(670, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:38:38'),
(671, 38, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:38:51'),
(672, 38, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:39:48'),
(673, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:40:02'),
(674, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:41:16'),
(675, 38, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:41:30'),
(676, 38, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:42:31'),
(677, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:42:48'),
(678, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:43:51'),
(679, 38, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:44:03'),
(680, 38, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:48:48'),
(681, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:49:33'),
(682, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:50:29'),
(683, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:51:10'),
(684, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:54:02'),
(685, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:54:42'),
(686, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:59:13'),
(687, 38, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 20:59:24'),
(688, 38, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 21:01:18'),
(689, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 21:01:27'),
(690, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 21:04:39'),
(691, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 21:04:55'),
(692, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 21:10:06'),
(693, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 21:10:44'),
(694, 21, 'sebastianlarga6@gmail.com', 'edit_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 21:13:07'),
(695, 21, 'sebastianlarga6@gmail.com', 'delete_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 21:14:42'),
(696, 21, 'sebastianlarga6@gmail.com', 'delete_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 21:14:46'),
(697, 21, 'sebastianlarga6@gmail.com', 'delete_product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 21:14:49'),
(698, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 21:20:25'),
(699, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 21:31:53'),
(700, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 21:34:30'),
(701, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 21:34:49'),
(702, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 21:40:39'),
(703, 38, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 21:40:51'),
(704, 38, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 21:43:39'),
(705, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 21:43:52'),
(706, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 21:46:29'),
(707, 38, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 21:46:40'),
(708, 38, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 21:50:21'),
(709, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 21:57:02'),
(710, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 22:01:20'),
(711, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 22:03:26'),
(712, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 22:05:54'),
(713, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 22:06:02'),
(714, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 22:09:56'),
(715, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 22:10:06'),
(716, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 22:13:22'),
(717, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 22:13:46'),
(718, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 22:16:26'),
(719, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 22:16:36'),
(720, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 22:16:55'),
(721, 38, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 22:17:10'),
(722, 38, 'melitalarga4@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 22:20:06'),
(723, 38, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 22:20:40'),
(724, 38, 'melitalarga4@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 22:25:00'),
(725, 38, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 22:25:19'),
(726, 38, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 22:33:26'),
(727, 38, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 22:33:40'),
(728, 38, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 22:39:14'),
(729, 38, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 22:39:51'),
(730, 38, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 22:42:04'),
(731, 38, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 22:42:30'),
(732, 38, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 22:47:41'),
(733, 38, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 22:50:01'),
(734, 38, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 22:52:15'),
(735, 38, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 22:57:22'),
(736, 38, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 22:58:37'),
(737, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 23:00:39'),
(738, 21, 'sebastianlarga6@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 23:00:51'),
(739, 38, 'melitalarga4@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 23:01:03'),
(740, 38, 'melitalarga4@gmail.com', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 23:03:45'),
(741, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 23:04:05'),
(742, 21, 'sebastianlarga6@gmail.com', 'deactivate_promotion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 23:04:11'),
(743, 21, 'sebastianlarga6@gmail.com', 'edit_promotion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 23:04:38'),
(744, 21, 'sebastianlarga6@gmail.com', 'edit_promotion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 23:04:51'),
(745, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 23:08:24'),
(746, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 23:08:51'),
(747, 21, 'sebastianlarga6@gmail.com', 'edit_promotion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 23:09:02'),
(748, 21, 'sebastianlarga6@gmail.com', 'create_promotion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 23:10:03'),
(749, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 23:12:27'),
(750, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 23:12:43'),
(751, 21, 'sebastianlarga6@gmail.com', 'create_promotion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 23:13:16'),
(752, 21, 'sebastianlarga6@gmail.com', 'session_timeout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 23:15:36');
INSERT INTO `audit_log` (`id`, `user_id`, `email`, `action`, `ip`, `user_agent`, `created_at`) VALUES
(753, 21, 'sebastianlarga6@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 23:15:47'),
(754, 21, 'sebastianlarga6@gmail.com', 'edit_promotion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 23:15:55'),
(755, 21, 'sebastianlarga6@gmail.com', 'edit_promotion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 23:16:04');

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
(65, 3, 1, 'Ring', 1, '2026-05-08 17:53:25'),
(98, 38, 6, '16\"', 1, '2026-05-08 23:01:25');

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
-- Table structure for table `customer_notifications`
--

CREATE TABLE `customer_notifications` (
  `id` bigint(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(40) NOT NULL,
  `event_key` varchar(191) NOT NULL,
  `title` varchar(160) NOT NULL,
  `message` varchar(255) NOT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customer_notifications`
--

INSERT INTO `customer_notifications` (`id`, `user_id`, `type`, `event_key`, `title`, `message`, `link_url`, `is_read`, `read_at`, `created_at`) VALUES
(1, 38, 'promotions', 'new_product:13', 'New product added', 'ss is now available in the shop.', 'product_detail.php?id=13', 1, '2026-05-09 03:12:17', '2026-05-08 18:38:36'),
(2, 38, 'promotions', 'new_product:12', 'New product added', 'sss is now available in the shop.', 'product_detail.php?id=12', 1, '2026-05-09 03:12:17', '2026-05-08 18:38:36'),
(3, 38, 'promotions', 'new_product:11', 'New product added', 'BOGART BATUMBAKAL is now available in the shop.', 'product_detail.php?id=11', 1, '2026-05-09 03:12:17', '2026-05-08 18:38:36'),
(4, 38, 'promotions', 'new_product:10', 'New product added', 'sample is now available in the shop.', 'product_detail.php?id=10', 1, '2026-05-09 03:12:17', '2026-05-08 18:38:36'),
(5, 38, 'promotions', 'new_promo:1', 'New promotion available', 'WELCOME10 can now be used on eligible orders.', 'product-list.php?badge=sale', 1, '2026-05-09 03:12:17', '2026-05-08 18:38:36'),
(6, 38, 'promotions', 'new_promo:2', 'New promotion available', 'FREESHIP can now be used on eligible orders.', 'product-list.php?badge=sale', 1, '2026-05-09 03:12:17', '2026-05-08 18:38:36'),
(61, 3, 'promotions', 'new_promo:13', 'New promotion available', 'SAMPLE10 can now be used on eligible orders.', 'product-list.php?badge=sale', 0, NULL, '2026-05-08 18:46:20'),
(62, 12, 'promotions', 'new_promo:13', 'New promotion available', 'SAMPLE10 can now be used on eligible orders.', 'product-list.php?badge=sale', 0, NULL, '2026-05-08 18:46:20'),
(63, 31, 'promotions', 'new_promo:13', 'New promotion available', 'SAMPLE10 can now be used on eligible orders.', 'product-list.php?badge=sale', 0, NULL, '2026-05-08 18:46:20'),
(64, 32, 'promotions', 'new_promo:13', 'New promotion available', 'SAMPLE10 can now be used on eligible orders.', 'product-list.php?badge=sale', 0, NULL, '2026-05-08 18:46:20'),
(65, 38, 'promotions', 'new_promo:13', 'New promotion available', 'SAMPLE10 can now be used on eligible orders.', 'product-list.php?badge=sale', 1, '2026-05-09 03:12:17', '2026-05-08 18:46:20'),
(157, 38, 'wishlist', 'wishlist_sale:2:350.00', 'Wishlist item on sale', 'Luna Earrings is on sale now.', 'product_detail.php?id=2', 1, '2026-05-09 03:12:17', '2026-05-08 18:57:40'),
(158, 3, 'wishlist', 'wishlist_sale:1:406.00', 'Wishlist item on sale', 'Solara Ring is on sale now.', 'product_detail.php?id=1', 0, NULL, '2026-05-08 18:57:44'),
(159, 38, 'wishlist', 'wishlist_sale:1:406.00', 'Wishlist item on sale', 'Solara Ring is on sale now.', 'product_detail.php?id=1', 1, '2026-05-09 03:12:17', '2026-05-08 18:57:44'),
(209, 38, 'order_updates', 'order_status:BJ-2026-0012:processing', 'Order BJ-2026-0012 is processing', 'Your order is now being prepared.', 'order_history.php', 1, '2026-05-09 03:12:17', '2026-05-08 19:00:00'),
(249, 38, 'order_updates', 'order_status:BJ-2026-0012:delivered', 'Order BJ-2026-0012 was delivered', 'Your order has been marked as delivered.', 'order_history.php', 1, '2026-05-09 03:12:22', '2026-05-08 19:02:50'),
(369, 3, 'promotions', 'new_promo:14', 'New promotion available', 'SUPER GIVE AWAY can now be used on eligible orders.', 'product-list.php?badge=sale', 0, NULL, '2026-05-08 23:10:03'),
(370, 12, 'promotions', 'new_promo:14', 'New promotion available', 'SUPER GIVE AWAY can now be used on eligible orders.', 'product-list.php?badge=sale', 0, NULL, '2026-05-08 23:10:03'),
(371, 31, 'promotions', 'new_promo:14', 'New promotion available', 'SUPER GIVE AWAY can now be used on eligible orders.', 'product-list.php?badge=sale', 0, NULL, '2026-05-08 23:10:03'),
(372, 32, 'promotions', 'new_promo:14', 'New promotion available', 'SUPER GIVE AWAY can now be used on eligible orders.', 'product-list.php?badge=sale', 0, NULL, '2026-05-08 23:10:03'),
(373, 3, 'promotions', 'new_promo:15', 'New promotion available', 'SUPER SAMPLE can now be used on eligible orders.', 'product-list.php?badge=sale', 0, NULL, '2026-05-08 23:13:16'),
(374, 12, 'promotions', 'new_promo:15', 'New promotion available', 'SUPER SAMPLE can now be used on eligible orders.', 'product-list.php?badge=sale', 0, NULL, '2026-05-08 23:13:16'),
(375, 31, 'promotions', 'new_promo:15', 'New promotion available', 'SUPER SAMPLE can now be used on eligible orders.', 'product-list.php?badge=sale', 0, NULL, '2026-05-08 23:13:16'),
(376, 32, 'promotions', 'new_promo:15', 'New promotion available', 'SUPER SAMPLE can now be used on eligible orders.', 'product-list.php?badge=sale', 0, NULL, '2026-05-08 23:13:16');

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
(3, 1, 1, 0, 1, '2026-03-17 03:56:38'),
(38, 0, 0, 0, 0, '2026-05-08 19:12:13');

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
  `promotion_id` int(11) DEFAULT NULL,
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

INSERT INTO `orders` (`id`, `user_id`, `status`, `subtotal`, `shipping_fee`, `total`, `promotion_id`, `ship_name`, `ship_street`, `ship_city`, `ship_province`, `ship_zip`, `ship_phone`, `payment_method`, `notes`, `courier_user_id`, `courier_name`, `courier_assigned_at`, `tracking_number`, `is_flagged`, `flag_reason`, `created_at`, `updated_at`) VALUES
('BJ-2026-0001', 38, 'processing', 88676.80, 0.00, 44338.40, 13, 'JOHN LARGA', 'enc:v1:u2/VZhUZzA5KucgH+jyfoRGWInE2Gc1LiQaMUlwXjaMFFqo8FQ==', 'enc:v1:z+cvk3oLNuLSdr1nk9mYsUB0BKOAfocbXfNS81WupoL+', 'enc:v1:/6Iq8uZeoXoarvaUz6l2GGvpjRseFasXbvsguPewi+p0jw==', 'enc:v1:vaakul+vXGhyc8TqUbgkc6rmk4TqD7O4wFaOX6y2snY=', 'enc:v1:wmdHKaPexhHqztsJpduZvpntlfYbX+4fJtfHV/OFJ0UoyDO1EcZj', 'paymongo', 'Standard delivery · est. 3–5 business days | paymongo_session:cs_cc127f401a63595f0f03b6ae', NULL, NULL, NULL, NULL, 0, NULL, '2026-05-08 19:34:34', '2026-05-08 19:34:34'),
('BJ-2026-0002', 38, 'processing', 1000.00, 150.00, 1150.00, NULL, 'JOHN LARGA', 'enc:v1:VMvwx+BqZEPK2YscnxtZSgQKpunoONfNf4z/wkgIv8rV1ZChrg==', 'enc:v1:WbiJgPrHX1tWMXQqTH3sKU5ae5JDD+pUFnjTQ1T63OYg', 'enc:v1:hi9Ir98fOA5HQD3AG0OhGpA6vfvcsnycT4uYLrAXrHpyIg==', 'enc:v1:pczv7zIW/oaLpnDIi+WVaCB5bwKJG8u4XS5xcU7zVts=', 'enc:v1:ncg4uoH+5SUJF7rXm15H7WHa1la5qbkuouqJ3T2mdNbnYtmSbYxr', 'paymongo', 'Standard delivery · est. 3–5 business days | paymongo_session:cs_872c87cc613eaaa863377802', NULL, NULL, NULL, NULL, 0, NULL, '2026-05-08 20:00:34', '2026-05-08 20:00:34'),
('BJ-2026-0003', 38, 'processing', 1590.00, 150.00, 1740.00, NULL, 'JOHN LARGA', 'enc:v1:eUcuf56rUzIL6ul1poa1zRSFuiC91GJuN4vp5aTlUU4RCL9mhQ==', 'enc:v1:gG2923Ca26GbKXx6CSsLmsq7KpOJSk0CdmVbqtAy7Auk', 'enc:v1:4QSY805wK5zuSoAjVjLueo2wwG+3vS+SbHeeCjA92Lrc3A==', 'enc:v1:/1fXDa3Gam/bOa6KZL4eVowgiQqhEy2VRTKA7jVo6dU=', 'enc:v1:Cu2Qk/U8UEVc+lMC2qFCa828CVf6k0DVEfgv9MhZocIPI/1pk2uc', 'paymongo', 'Standard delivery · est. 3–5 business days | paymongo_session:cs_9ac67d8b8b5c38f903597543', NULL, NULL, NULL, NULL, 0, NULL, '2026-05-08 20:12:02', '2026-05-08 20:12:02'),
('BJ-2026-0004', 38, 'processing', 406.00, 150.00, 556.00, NULL, 'JOHN LARGA', 'enc:v1:ho4jwR+i1C3oOHo2YC0e9jjjwiKyxQTI/5SPfDfkllz/md3WSA==', 'enc:v1:GzwvXevR32Qn/8x1KYXdW36DFeL623HDrgWivZPqZygZ', 'enc:v1:mFSnIj2teAPZAhf3Pm9bOsL1/FKBBkBNOCSO5+Kzh/rrlg==', 'enc:v1:n7QFI80592+7ZsYHujDk8Wz6hjus0pz+BqciZu+tOwM=', 'enc:v1:XYf5eOEnPF0OkebKVXfesk6otFhrYPCIkwcYz9OdQHHYSMQ/fiLy', 'paymongo', 'Standard delivery · est. 3–5 business days | paymongo_session:cs_aa3bd77716aef7d2c1c65f7e', NULL, NULL, NULL, NULL, 0, NULL, '2026-05-08 20:39:27', '2026-05-08 20:39:27'),
('BJ-2026-0005', 38, 'processing', 812.00, 150.00, 962.00, NULL, 'JOHN LARGA', 'enc:v1:k1TL8HzEReefv+rQTzWtIyXYKdME4fO/MV8oibxnjzVWZFfdZA==', 'enc:v1:IuOW4u4ohq4XvDraUjTwjFUng/y3eMA4Qh/hHsCog9eb', 'enc:v1:vUoj4t4x6QW7l/mZjVzMwt0l4lwNkUl1BBiZzAi22TI/hA==', 'enc:v1:fGLIXkfNy3a1h5BOqC7p9blo39tb5DSyXd6rH/6VPys=', 'enc:v1:CfKB5CBBCM706plMy4uAo9kS9Z8NZmX0p1xIe149+mTvQpLmy9B8', 'paymongo', 'Standard delivery · est. 3–5 business days | paymongo_session:cs_087860f02bed2bc249bbd532', NULL, NULL, NULL, NULL, 0, NULL, '2026-05-08 20:42:24', '2026-05-08 20:42:24'),
('BJ-2026-0006', 38, 'processing', 2030.00, 0.00, 2030.00, NULL, 'JOHN LARGA', 'enc:v1:KoJwBWQcaKus1apSoz67MRuUpVJ8tQlvlISHRyMULUlvNqZNYtSuzJbzSgASLg==', 'enc:v1:2iBCul3X8c3VGq3xcrS1BJ0gebUbwfWw9xAI6ZIl+Yug', 'enc:v1:VVW/I1vqTMZFo+2JA87ejV7okET5oQR1LIgs/3zkDPOv', 'enc:v1:XUcJ+suROJ4r7cyOD2Bf0N/gLsdmXlx2O7ZAo9rfxWFA', 'enc:v1:KezcLQbGb2V14PQuN0qt7jkifqFuABWJxA6MJS9PRFOzsICccUYl', 'paymongo', 'Standard delivery · est. 7–10 business days | paymongo_session:cs_a902bf12fbd982161f505267', NULL, NULL, NULL, NULL, 0, NULL, '2026-05-08 20:48:39', '2026-05-08 20:48:39'),
('BJ-2026-0007', 38, 'processing', 10809.10, 0.00, 10809.10, NULL, 'JOHN LARGA', 'enc:v1:DrewVjfzcrqiAHmrmSzbCqsFmqbsF8noRAnXkKpJP+xvwmAryg==', 'enc:v1:EXFDQM633TERtUuXLCLo2I20MYuSmQK2XR4yRSZ+5Ea7', 'enc:v1:yn6uORXOYynHiuhbhapn9hZLFCOZ4rnXu6SRfhpnep9edg==', 'enc:v1:80zaav4TbpCQ+W8I5g9Iu4ApQDvjUL5GVhqyaVWL8xI=', 'enc:v1:Vsk8X9juFW9AAPfhC4LJawBdaR1SFBTSQ/8buSFACYdfn14zY9cw', 'paymongo', 'Standard delivery · est. 3–5 business days | paymongo_session:cs_f0ee5d2764e4ea23501f9dc6', NULL, NULL, NULL, NULL, 0, NULL, '2026-05-08 21:43:33', '2026-05-08 21:43:33'),
('BJ-2026-0008', 38, 'processing', 750.00, 160.00, 910.00, NULL, 'JOHN LARGA', 'enc:v1:A+ddvvuU6s4p+l3BWI+vB5pmUrvDUQH01d9fwxtt+w12/CI5nA==', 'enc:v1:s2qkuKmWgS/fl6E9c7a4aNU+MM3lupjqZ8QDh8SLeBI7', 'enc:v1:UAMgliXhwqWvD7JSMfXnVA61WFZ9dY1PKnhE5bEgFiKt6g==', 'enc:v1:+Hd4EQSWWrc4pi6K+BILTin6SG5Zy8b0o8MkfAUd+B8=', 'enc:v1:0rHBOasMUu12T3qWIX3iqEB6cqzdFL9FqiWk04GlHlHMCQ+SusOO', 'paymongo', 'Standard delivery · est. 3–5 business days | paymongo_session:cs_2f07043c834db37c3816ef93', NULL, NULL, NULL, NULL, 0, NULL, '2026-05-08 22:58:14', '2026-05-08 22:58:14');

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
(2, 'BJ-2026-0009', 3, 30, 'JOHN LARGA', 5, NULL, '2026-05-07 14:13:53'),
(3, 'BJ-2026-0015', 25, 30, 'JOHN LARGA', 5, 'asdadas', '2026-05-08 14:51:49'),
(4, 'BJ-2026-0016', 25, 30, 'JOHN LARGA', 5, 'good', '2026-05-08 15:25:27'),
(5, 'BJ-2026-0012', 38, 30, 'JOHN LARGA', 5, NULL, '2026-05-08 19:04:00');

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

-- --------------------------------------------------------

--
-- Table structure for table `order_email_logs`
--

CREATE TABLE `order_email_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` varchar(20) NOT NULL,
  `email_type` varchar(50) NOT NULL,
  `recipient_email` varchar(191) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` enum('sent','failed') NOT NULL DEFAULT 'sent',
  `error_message` text DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_email_logs`
--

INSERT INTO `order_email_logs` (`id`, `user_id`, `order_id`, `email_type`, `recipient_email`, `subject`, `status`, `error_message`, `sent_at`, `created_at`) VALUES
(6, 38, 'BJ-2026-0001', 'processing', 'melitalarga4@gmail.com', 'Your Bejewelry order is now processing', 'sent', NULL, '2026-05-09 03:34:38', '2026-05-08 19:34:38'),
(7, 38, 'BJ-2026-0002', 'processing', 'melitalarga4@gmail.com', 'Your Bejewelry order is now processing', 'sent', NULL, '2026-05-09 04:00:39', '2026-05-08 20:00:39'),
(8, 38, 'BJ-2026-0003', 'processing', 'melitalarga4@gmail.com', 'Your Bejewelry order is now processing', 'sent', NULL, '2026-05-09 04:12:06', '2026-05-08 20:12:06'),
(9, 38, 'BJ-2026-0004', 'processing', 'melitalarga4@gmail.com', 'Your Bejewelry order is now processing', 'sent', NULL, '2026-05-09 04:39:31', '2026-05-08 20:39:31'),
(10, 38, 'BJ-2026-0005', 'processing', 'melitalarga4@gmail.com', 'Your Bejewelry order is now processing', 'sent', NULL, '2026-05-09 04:42:29', '2026-05-08 20:42:29'),
(11, 38, 'BJ-2026-0006', 'processing', 'melitalarga4@gmail.com', 'Your Bejewelry order is now processing', 'sent', NULL, '2026-05-09 04:48:43', '2026-05-08 20:48:43'),
(12, 38, 'BJ-2026-0007', 'processing', 'melitalarga4@gmail.com', 'Your Bejewelry order is now processing', 'sent', NULL, '2026-05-09 05:43:37', '2026-05-08 21:43:37'),
(13, 38, 'BJ-2026-0008', 'processing', 'melitalarga4@gmail.com', 'Your Bejewelry order is now processing', 'sent', NULL, '2026-05-09 06:58:19', '2026-05-08 22:58:19');

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
(52, 'BJ-2026-0001', NULL, 'sample', NULL, 'Rings', 'One Size', 10000.00, 1),
(53, 'BJ-2026-0001', NULL, 'BOGART BATUMBAKAL', '0ad3670277c63bb46efa0e09.png', 'Rings', 'One Size', 77777.70, 1),
(54, 'BJ-2026-0001', 12, 'sss', '7d402f79d2dacd06fa348b30.png', 'Necklaces', 'One Size', 899.10, 1),
(55, 'BJ-2026-0002', 2, 'Luna Earrings', '41230039f40d5bac45635d38.png', 'Earrings', 'One Size', 350.00, 1),
(56, 'BJ-2026-0002', 7, 'Diamond Hoops', 'ff4e65ceb246a8f19f5c1f19.png', 'Earrings', 'One Size', 650.00, 1),
(57, 'BJ-2026-0003', 4, 'Dia Charm Bracelet', 'f20ac1d0172a57aa878f8ee1.png', 'Bracelets', 'One Size', 700.00, 1),
(58, 'BJ-2026-0003', 8, 'Aurora Bangle', '34b0d0bcfd642486dd5d9ee2.png', 'Bracelets', 'One Size', 890.00, 1),
(59, 'BJ-2026-0004', 1, 'Solara Ring', '61f40b464da7e81c090a19ea.png', 'Rings', 'Ring', 406.00, 1),
(60, 'BJ-2026-0005', 1, 'Solara Ring', '61f40b464da7e81c090a19ea.png', 'Rings', 'Ring', 406.00, 2),
(61, 'BJ-2026-0006', 1, 'Solara Ring', '61f40b464da7e81c090a19ea.png', 'Rings', 'Ring', 406.00, 5),
(62, 'BJ-2026-0007', 1, 'Solara Ring', '61f40b464da7e81c090a19ea.png', 'Rings', 'Ring', 406.00, 20),
(63, 'BJ-2026-0007', 3, 'Celestial Necklace', '05d0fda466ec262414281abc.png', 'Necklaces', '18\"', 970.00, 1),
(64, 'BJ-2026-0007', 6, 'Star Pendant', '2c9aa303bb3804764cdd2562.png', 'Necklaces', '16\"', 820.00, 1),
(65, 'BJ-2026-0007', 12, 'sss', '7d402f79d2dacd06fa348b30.png', 'Necklaces', 'One Size', 899.10, 1),
(66, 'BJ-2026-0008', 5, 'Infinity Band', '35d7ece7dc0629322eda9d8e.png', 'Rings', 'Ring', 750.00, 1);

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
(10, 'BJ-2026-0009', 3, '2026-05-07 22:13:45'),
(11, 'BJ-2026-0015', 25, '2026-05-08 22:51:24'),
(12, 'BJ-2026-0016', 25, '2026-05-08 23:25:13'),
(13, 'BJ-2026-0012', 38, '2026-05-09 03:03:42');

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

--
-- Dumping data for table `paymongo_checkout_pending`
--

INSERT INTO `paymongo_checkout_pending` (`id`, `user_id`, `checkout_session_id`, `pm_state`, `expected_total_cents`, `post_json`, `created_at`) VALUES
(26, 3, 'cs_ce32c89985fcd1b67732448a', '143e55f4c2e9cf1fa4d184175f1cfb05', 73000, 'enc:v1:5It8+cKNvDO13IV62ZZAdN13KSFbTk7vDENAqq/KW5EqvsGlpQK4el2dQ1BJevkzu7h8OI5/cMnFgNiiUJoBtAyDQ+FlXvZiHv9w8b/+F29SffZv/eOzBBYP2q1PJhWLXpZ/nRMa/EjxJgAqnKqpQjsdvwJKV7Dx84THh1vHg1WIGGGiaoYLD+0yE8iuIL8w9TZADKfKOXwNVVX0LiTuHS1VrFo4pxLtzeCGTPGNRlnX7UAtpC2GP8J86h+tDkA8YB94dhyeiciB0n0eIJi46dkTcBVKE/+uoZBqxqwj8txlGSnhwgjbTJqixhPjJAPzuXEADId5BVsMmh9ZRLY5/Qvn80aFLM/dVTkayfUMPWwni+v6iDlH7em34Do5dJa39AJtntAReo3EKU4=', '2026-05-08 17:53:48');

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
(1, 'Solara Ring', 'A radiant ring inspired by the sun, crafted in 14K gold.', 1, 406.00, 580.00, '61f40b464da7e81c090a19ea.png', 'sale', 5, 128, 'Ring', '14K Gold', 59, 1, '2026-03-16 23:52:50', '2026-05-08 21:43:33'),
(2, 'Luna Earrings', 'Delicate stud earrings with a luminous sterling silver finish.', 4, 350.00, 500.00, '41230039f40d5bac45635d38.png', 'sale', 5, 94, 'One Size', 'Sterling Silver', 95, 1, '2026-03-16 23:52:50', '2026-05-08 20:00:34'),
(3, 'Celestial Necklace', 'An 18-inch gold necklace adorned with star and moon charms.', 2, 970.00, NULL, '05d0fda466ec262414281abc.png', '', 5, 203, '18\"', '14K Gold', 101, 1, '2026-03-16 23:52:50', '2026-05-08 21:43:33'),
(4, 'Dia Charm Bracelet', 'A charm bracelet featuring dazzling crystal accents.', 3, 700.00, NULL, 'f20ac1d0172a57aa878f8ee1.png', '', 4, 67, 'One Size', 'Sterling Silver', 97, 1, '2026-03-16 23:52:50', '2026-05-08 20:12:44'),
(5, 'Infinity Band', 'A sleek infinity-symbol band in polished 14K gold.', 1, 750.00, NULL, '35d7ece7dc0629322eda9d8e.png', '', 5, 45, 'Ring', '14K Gold', 91, 1, '2026-03-16 23:52:50', '2026-05-08 22:58:15'),
(6, 'Star Pendant', 'A sparkling star-shaped pendant on a 16-inch rose gold chain.', 2, 820.00, 1020.00, '2c9aa303bb3804764cdd2562.png', '', 4, 88, '16\"', 'Rose Gold', 93, 1, '2026-03-16 23:52:50', '2026-05-08 21:43:33'),
(7, 'Diamond Hoops', 'Classic hoop earrings with a diamond-cut finish in 14K gold.', 4, 650.00, NULL, 'ff4e65ceb246a8f19f5c1f19.png', '', 5, 112, 'One Size', '14K Gold', 97, 1, '2026-03-16 23:52:50', '2026-05-08 20:00:34'),
(8, 'Aurora Bangle', 'A wide rose-gold bangle with an iridescent enamel finish.', 3, 1000.00, NULL, '34b0d0bcfd642486dd5d9ee2.png', '', 4, 56, 'One Size', 'Rose Gold', 107, 1, '2026-03-16 23:52:50', '2026-05-08 20:30:05'),
(12, 'sss', 'dasdsd', 2, 899.10, 999.00, '7d402f79d2dacd06fa348b30.png', 'sale', 5, 0, 'One Size', 'ss', 17, 1, '2026-05-08 16:38:16', '2026-05-08 21:43:33');

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
(15, 2, 3, NULL, 5, NULL, NULL, 'approved', '2026-05-07 14:13:48', '2026-05-08 17:49:46'),
(20, 4, 38, NULL, 5, NULL, NULL, 'pending', '2026-05-08 19:03:48', '2026-05-08 19:03:48'),
(21, 5, 38, NULL, 5, NULL, NULL, 'pending', '2026-05-08 19:03:51', '2026-05-08 19:03:51'),
(22, 1, 38, NULL, 5, NULL, NULL, 'pending', '2026-05-08 19:03:52', '2026-05-08 19:03:52'),
(23, 2, 38, NULL, 5, NULL, NULL, 'pending', '2026-05-08 19:03:57', '2026-05-08 19:03:57');

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
  `apply_to` varchar(50) DEFAULT 'all',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `promotions`
--

INSERT INTO `promotions` (`id`, `code`, `name`, `type`, `value`, `min_order`, `max_uses`, `used_count`, `start_at`, `end_at`, `is_active`, `apply_to`, `created_at`, `updated_at`) VALUES
(1, 'WELCOME10', 'Welcome 10% off', 'percent', 10.00, 500.00, 1000, 0, NULL, NULL, 1, 'bracelets', '2026-03-17 00:54:50', '2026-05-08 23:16:04'),
(2, 'FREESHIP', 'Free shipping over ???2k', 'fixed', 150.00, 2000.00, NULL, 0, NULL, NULL, 1, 'all', '2026-03-17 00:54:50', '2026-03-17 00:54:50'),
(13, 'GIVE AWAY', 'SAMPLE', 'percent', 50.00, 1000.00, 5, 1, '2026-05-09 00:00:00', '2026-05-10 23:59:59', 0, 'rings', '2026-05-08 18:46:20', '2026-05-08 23:09:02'),
(14, 'SUPER GIVE AWAY', 'SUPER GIVE AWAY', 'percent', 1000.00, 1000.00, 1, 0, '2026-05-09 00:00:00', '2026-05-11 23:59:59', 1, 'rings', '2026-05-08 23:10:02', '2026-05-08 23:10:02'),
(15, 'SUPER SAMPLE', 'SUPER SAMPLE', 'percent', 100.00, 1111.00, 6, 0, '2026-05-09 00:00:00', '2026-05-09 23:59:59', 1, 'earrings', '2026-05-08 23:13:16', '2026-05-08 23:15:55');

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

--
-- Dumping data for table `promotion_redemptions`
--

INSERT INTO `promotion_redemptions` (`id`, `promotion_id`, `order_id`, `user_id`, `discount_amt`, `created_at`) VALUES
(1, 13, 'BJ-2026-0001', 38, 44338.40, '2026-05-08 19:34:34');

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
(23, 2, -1, 95, 350.00, NULL, 'JOHN LARGA', '2026-05-08 20:00:34'),
(24, 7, -1, 97, 650.00, NULL, 'JOHN LARGA', '2026-05-08 20:00:34'),
(25, 12, 4, 18, 899.10, 'regular', 'ORDER LARGA', '2026-05-08 20:11:00'),
(26, 4, -1, 96, 700.00, NULL, 'JOHN LARGA', '2026-05-08 20:12:02'),
(27, 8, -1, 107, 890.00, NULL, 'JOHN LARGA', '2026-05-08 20:12:02'),
(28, 4, 1, 97, 700.00, 'regular', 'ORDER LARGA', '2026-05-08 20:12:44'),
(29, 1, -1, 86, 406.00, NULL, 'JOHN LARGA', '2026-05-08 20:39:27'),
(30, 1, -2, 84, 406.00, NULL, 'JOHN LARGA', '2026-05-08 20:42:24'),
(31, 1, -5, 79, 406.00, NULL, 'JOHN LARGA', '2026-05-08 20:48:39'),
(32, 1, -20, 59, 406.00, NULL, 'JOHN LARGA', '2026-05-08 21:43:33'),
(33, 3, -1, 101, 970.00, NULL, 'JOHN LARGA', '2026-05-08 21:43:33'),
(34, 6, -1, 93, 820.00, NULL, 'JOHN LARGA', '2026-05-08 21:43:33'),
(35, 12, -1, 17, 899.10, NULL, 'JOHN LARGA', '2026-05-08 21:43:33'),
(36, 5, -1, 91, 750.00, NULL, 'JOHN LARGA', '2026-05-08 22:58:15');

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
(3, 'PATRICK IVAN', 'PILAPIL', NULL, 'pilapil_patrickivan@plpasig.edu.ph', '$2y$10$8bRoCLl75x.pRyz43DrVx.6g9NePaUTXcLFsIov4cvG5Uxl0JuffO', '09394551110', 'Male', '2010-08-27', 'Manila', 'customer', '2026-03-17 01:17:11', '2026-05-08 18:03:35', '2026-03-17 09:17:11', NULL, NULL, 'ACMBEJ7DMQ766P3F', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(9, 'Patrick', 'Ivan Pilapil', NULL, 'patrickpilapil1313@gmail.com', '$2y$10$p6HSW4B5bFV38By4gPtBk.L08LfhxH.kPsLvxaNgXVGlNyI9aBf9m', NULL, NULL, NULL, NULL, 'super_admin', '2026-03-17 06:53:11', '2026-05-08 17:21:23', '2026-03-17 14:53:11', NULL, NULL, 'PJE65A2ZIAFVWK4W', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(12, 'Vincent', 'Andrada', NULL, 'andrada_jonvincent@plpasig.edu.ph', '$2y$10$lYUO9KDcT7jQBCkbfQzlreMZNFCrnU3wGSctQBeLsmLEwskH8DDpC', NULL, NULL, NULL, NULL, 'customer', '2026-03-26 09:57:20', '2026-03-26 09:57:20', NULL, '02066e36d597f813e9bd7a2198b86d094bdf3d7ef408de8f11e3d7a952b2eb39', '2026-03-28 10:57:20', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
(15, 'PATRICK', 'IVAN PILAPIL', NULL, 'patrickpilapil27@gmail.com', '$2y$10$.hepEVVUagk4Ia5CZ6Srn.w.XYL8eFL7GnzYV11bagOu5FEUC/dhS', '09394551110', 'Male', '2026-03-25', 'Manila', 'manager', '2026-03-26 11:32:36', '2026-05-08 17:51:05', NULL, NULL, NULL, 'CIUPK3YGZKIFORHF', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(19, 'SUPER', 'ADMIN LARGA', NULL, 'larga_johnsebastian@plpasig.edu.ph', '$2y$10$dPKaiyr43VedUXLKeCp53uEGnaQAl17jugTiOk/.SZbjY6QFIihMy', NULL, NULL, NULL, NULL, 'super_admin', '2026-04-11 06:38:24', '2026-05-08 15:30:17', NULL, NULL, NULL, 'ABVZR7PWSCCPX3JC', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(21, 'ORDER', 'LARGA', NULL, 'sebastianlarga6@gmail.com', '$2y$10$CguPlskSqbmI1UQYu0K/aeTQHFo4/8WE336PO.pZ9UhLbDaFsRMAm', '09925801741', 'Male', '2026-04-16', 'Pasig', 'manager', '2026-04-11 06:44:11', '2026-05-08 23:15:47', NULL, NULL, NULL, 'NJDC656JPNZ7RRWZ', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(30, 'JOHN', 'LARGA', NULL, 'sebasteestab@gmail.com', '$2y$10$tgLf4Wk9bnYSr4dby8Yvx.R6fIH7RX3mXbIGlI.FbbA1Ienss249C', NULL, NULL, NULL, NULL, 'courier', '2026-04-21 05:05:09', '2026-05-08 19:01:51', '2026-04-21 13:05:34', NULL, NULL, '5AW7WS63DYWPW7YN', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(31, 'Andrew', 'Fernandez', NULL, 'fernandez_jamesandrew@plpasig.edu.ph', '$2y$10$3yNB1zrpRdCwakVJGy3F7.Wys.GfMDSoTuV44vrq4T0RvLvqhN/Vi', '09925801741', 'Male', '2005-08-10', 'Pasig', 'customer', '2026-04-25 09:49:28', '2026-04-25 10:02:45', '2026-04-25 17:51:06', NULL, NULL, 'Q2BNA255YDTBF34A', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(32, 'Vernalyn', 'Miguel', NULL, 'miguel_vernalyn@plpasig.edu.ph', '$2y$10$ncUtz0OPzYZAfv.RlMQaCur6q8Ad6Jjym6RjsInnI7sDPwSjKsYEi', NULL, NULL, NULL, NULL, 'customer', '2026-05-02 12:27:29', '2026-05-02 12:27:29', NULL, 'c6129430c8e050ee5fe25bdf5da7740c9ace3a9a2a1d8296fd85c40923e9872c', '2026-05-04 14:27:29', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
(35, 'Lenra', 'Pilaps', NULL, 'lenrapilapil@gmail.com', '$2y$10$FnDG/akFr/MqVIC2fZa/zOrFWgCSKDdoJx6Tl5Iph5PVq/HW.F4lO', 'enc:v1:bx/ssW8nBBibZ1SP/SqNxZCfyH7jqr+Id8bv/YZslaBys3BHXo3F', NULL, NULL, NULL, 'courier', '2026-05-06 21:01:31', '2026-05-06 21:01:50', '2026-05-07 05:01:31', NULL, NULL, 'USQXMWVFD2CID22W', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(36, 'MARITHE', 'DE LEON', NULL, 'deleon_marithefrancine@plpasig.edu.ph', '$2y$10$asAxoSjbBZDIXL/XxRj1P.bMd4wi30/Rjrz9sE.QIpvbIYzNq4ejS', NULL, NULL, NULL, NULL, 'super_admin', '2026-05-07 10:55:48', '2026-05-07 10:56:55', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
(38, 'JOHN', 'LARGA', NULL, 'melitalarga4@gmail.com', '$2y$10$ZQ1MuKbm/Bdd9qCQOfHfQeOTFTIgXxreFWunswSTCnY4B2OWoejve', NULL, NULL, NULL, NULL, 'customer', '2026-05-08 18:22:46', '2026-05-08 23:01:03', '2026-05-09 02:23:13', NULL, NULL, 'EGO7AIHZ6K7DRLLT', 0, NULL, NULL, NULL, NULL, NULL, NULL);

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
(38, 1, '2026-05-08 18:56:54'),
(38, 2, '2026-05-08 18:56:55');

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
-- Indexes for table `customer_notifications`
--
ALTER TABLE `customer_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_customer_notifications_event` (`user_id`,`event_key`),
  ADD KEY `idx_customer_notifications_feed` (`user_id`,`is_read`,`created_at`);

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
  ADD KEY `idx_orders_tracking_number` (`tracking_number`),
  ADD KEY `idx_orders_promotion_id` (`promotion_id`);

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
-- Indexes for table `order_email_logs`
--
ALTER TABLE `order_email_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_order_email_log` (`user_id`,`order_id`,`email_type`),
  ADD KEY `idx_order_email_user` (`user_id`,`created_at`),
  ADD KEY `idx_order_email_order` (`order_id`,`created_at`);

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=756;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `customer_notes`
--
ALTER TABLE `customer_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_notifications`
--
ALTER TABLE `customer_notifications`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=377;

--
-- AUTO_INCREMENT for table `order_courier_ratings`
--
ALTER TABLE `order_courier_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `order_delivery_proofs`
--
ALTER TABLE `order_delivery_proofs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `order_email_logs`
--
ALTER TABLE `order_email_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `order_receipts`
--
ALTER TABLE `order_receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `paymongo_checkout_pending`
--
ALTER TABLE `paymongo_checkout_pending`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `promotion_redemptions`
--
ALTER TABLE `promotion_redemptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

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
-- Constraints for table `customer_notifications`
--
ALTER TABLE `customer_notifications`
  ADD CONSTRAINT `fk_customer_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `fk_orders_promotion_id` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_delivery_proofs`
--
ALTER TABLE `order_delivery_proofs`
  ADD CONSTRAINT `order_delivery_proofs_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_email_logs`
--
ALTER TABLE `order_email_logs`
  ADD CONSTRAINT `fk_order_email_logs_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_email_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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

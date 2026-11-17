-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 16, 2026 at 09:00 PM
-- Server version: 11.8.9-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u940930907_ubamus`
--

-- --------------------------------------------------------

--
-- Table structure for table `about`
--

CREATE TABLE `about` (
  `id` int(100) NOT NULL,
  `company_id` int(100) NOT NULL,
  `who_we_are` text NOT NULL,
  `our_vision` text NOT NULL,
  `our_mission` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `about`
--

INSERT INTO `about` (`id`, `company_id`, `who_we_are`, `our_vision`, `our_mission`) VALUES
(7, 31, 'Coming Soon', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `about_sections`
--

CREATE TABLE `about_sections` (
  `id` int(11) NOT NULL,
  `about_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `section_title` varchar(255) NOT NULL,
  `section_content` longtext NOT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `about_sections`
--

INSERT INTO `about_sections` (`id`, `about_id`, `company_id`, `section_title`, `section_content`, `display_order`, `created_at`, `updated_at`) VALUES
(8, 7, 31, 'Company Overview', 'Coming Soon', 1, '2026-11-14 20:07:10', '2026-11-14 20:07:10');

-- --------------------------------------------------------

--
-- Table structure for table `chat_conversations`
--

CREATE TABLE `chat_conversations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` int(11) NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `conversation_type` enum('direct','group') NOT NULL DEFAULT 'direct',
  `title` varchar(150) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_by_type` enum('employee','customer') NOT NULL DEFAULT 'employee',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `group_image` varchar(500) DEFAULT NULL,
  `group_description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_conversations`
--

INSERT INTO `chat_conversations` (`id`, `company_id`, `location_id`, `conversation_type`, `title`, `created_by`, `created_by_type`, `created_at`, `updated_at`, `group_image`, `group_description`) VALUES
(1, 31, 89, 'group', 'Home', 216, 'employee', '2026-10-31 23:52:38', '2026-11-02 14:34:59', NULL, NULL),
(2, 31, 89, 'direct', NULL, 216, 'employee', '2026-10-31 23:54:37', '2026-11-05 15:38:09', NULL, NULL),
(3, 31, 89, 'direct', 'Customer: Henry', 216, 'employee', '2026-11-01 22:59:18', '2026-11-01 22:59:33', NULL, NULL),
(4, 31, 89, 'direct', NULL, 216, 'employee', '2026-11-03 17:07:43', '2026-11-14 19:38:30', NULL, NULL),
(5, 31, NULL, 'direct', NULL, 216, 'employee', '2026-11-03 17:07:54', '2026-11-03 17:07:59', NULL, NULL),
(6, 31, 89, 'direct', NULL, 218, 'employee', '2026-11-08 17:19:47', '2026-11-08 17:19:52', NULL, NULL),
(7, 31, 89, 'direct', 'Customer: Omoeheffe', 96, 'customer', '2026-11-11 20:48:04', '2026-11-11 20:48:04', NULL, NULL),
(8, 31, 89, 'direct', 'Customer: Omoeheffe', 96, 'customer', '2026-11-11 20:48:04', '2026-11-11 20:48:04', NULL, NULL),
(9, 31, 89, 'direct', 'Customer: Omoeheffe', 96, 'customer', '2026-11-11 20:48:04', '2026-11-11 20:48:04', NULL, NULL),
(10, 31, 89, 'direct', 'Customer: Omoeheffe', 96, 'customer', '2026-11-11 20:48:04', '2026-11-11 20:48:04', NULL, NULL),
(11, 31, 89, 'direct', 'Customer: Henro', 97, 'customer', '2026-11-11 20:59:02', '2026-11-11 20:59:02', NULL, NULL),
(12, 31, 89, 'direct', 'Customer: Henro', 97, 'customer', '2026-11-11 20:59:02', '2026-11-11 20:59:02', NULL, NULL),
(13, 31, 89, 'direct', 'Customer: Henro', 97, 'customer', '2026-11-11 20:59:02', '2026-11-11 20:59:02', NULL, NULL),
(14, 31, 89, 'direct', 'Customer: Henro', 97, 'customer', '2026-11-11 20:59:02', '2026-11-11 20:59:02', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `chat_live_activity`
--

CREATE TABLE `chat_live_activity` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `conversation_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` enum('typing','uploading') NOT NULL,
  `activity_text` varchar(255) DEFAULT NULL,
  `progress_percent` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `expires_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `conversation_id` bigint(20) UNSIGNED NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_type` enum('employee','customer') NOT NULL DEFAULT 'employee',
  `message_type` enum('text','image','video','audio','file') NOT NULL DEFAULT 'text',
  `body` text DEFAULT NULL,
  `reply_to_message_id` bigint(20) UNSIGNED DEFAULT NULL,
  `forwarded_from_message_id` bigint(20) UNSIGNED DEFAULT NULL,
  `attachment_path` varchar(500) DEFAULT NULL,
  `attachment_name` varchar(255) DEFAULT NULL,
  `attachment_mime` varchar(150) DEFAULT NULL,
  `attachment_size` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `deleted_for_everyone` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `conversation_id`, `sender_id`, `sender_type`, `message_type`, `body`, `reply_to_message_id`, `forwarded_from_message_id`, `attachment_path`, `attachment_name`, `attachment_mime`, `attachment_size`, `created_at`, `deleted_at`, `deleted_for_everyone`) VALUES
(1, 1, 216, 'employee', 'text', 'Hello', NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-31 23:52:45', NULL, 0),
(2, 2, 216, 'employee', 'text', 'Hello Henry', NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-31 23:54:47', NULL, 0),
(3, 2, 216, 'employee', 'video', NULL, NULL, NULL, 'uploads/chat/videos/63526ceac7399e4b1109d800a6a98ff85074.webm', 'voice-message.webm', 'video/webm', 160652, '2026-10-31 23:55:09', NULL, 0),
(4, 2, 216, 'employee', 'image', NULL, NULL, NULL, 'uploads/chat/images/5c1c85af733ae9d23d40c4cb29face0a8d6a.png', '8.png', 'image/png', 125695, '2026-11-01 01:22:24', NULL, 0),
(5, 2, 216, 'employee', 'audio', NULL, NULL, NULL, 'uploads/chat/audio/8b4e74fa337c149114b1f6561c01c9e92949.mp3', '2pac - end of time.mp3', 'audio/mpeg', 5751221, '2026-11-01 01:23:02', NULL, 0),
(6, 3, 216, 'employee', 'text', 'Hello Sir...', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-01 22:59:33', NULL, 0),
(7, 2, 219, 'employee', 'text', 'OKay Sir dfder', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-01 23:30:23', NULL, 0),
(8, 2, 216, 'employee', 'text', 'hello', 4, NULL, NULL, NULL, NULL, NULL, '2026-11-02 13:41:41', NULL, 0),
(9, 2, 216, 'employee', 'text', NULL, 7, NULL, NULL, NULL, NULL, NULL, '2026-11-02 14:17:14', '2026-11-02 14:17:31', 1),
(10, 2, 219, 'employee', 'text', 'saddasd', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-02 18:42:15', NULL, 0),
(11, 2, 219, 'employee', 'text', 'cmnxcxmvmvxc', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-02 18:42:46', NULL, 0),
(12, 4, 216, 'employee', 'text', 'Hello', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-03 17:07:48', NULL, 0),
(13, 5, 216, 'employee', 'text', 'Baba', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-03 17:07:59', NULL, 0),
(14, 2, 216, 'employee', 'text', 'Welcome to myu local server', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 15:37:18', NULL, 0),
(15, 2, 219, 'employee', 'text', 'Thank you...', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 15:37:50', NULL, 0),
(16, 2, 216, 'employee', 'text', 'youresdsasd', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 15:38:09', NULL, 0),
(17, 6, 218, 'employee', 'text', 'Hello', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-08 17:19:52', NULL, 0),
(18, 4, 120, 'employee', 'text', 'hello', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-14 19:38:30', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `chat_message_deletions`
--

CREATE TABLE `chat_message_deletions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `participant_type` enum('employee','customer') NOT NULL DEFAULT 'employee',
  `deleted_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_message_receipts`
--

CREATE TABLE `chat_message_receipts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` int(11) NOT NULL,
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_participants`
--

CREATE TABLE `chat_participants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `conversation_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `participant_type` enum('employee','customer') NOT NULL DEFAULT 'employee',
  `participant_role` enum('member','admin') NOT NULL DEFAULT 'member',
  `joined_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_read_message_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `cleared_before_message_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `hidden_at` datetime DEFAULT NULL,
  `left_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_participants`
--

INSERT INTO `chat_participants` (`id`, `conversation_id`, `user_id`, `participant_type`, `participant_role`, `joined_at`, `last_read_message_id`, `cleared_before_message_id`, `hidden_at`, `left_at`) VALUES
(1, 1, 216, 'employee', 'admin', '2026-10-31 23:52:38', 1, 0, NULL, NULL),
(2, 1, 219, 'employee', 'member', '2026-10-31 23:52:38', 1, 0, NULL, NULL),
(3, 2, 216, 'employee', 'member', '2026-10-31 23:54:37', 16, 3, NULL, NULL),
(4, 2, 219, 'employee', 'member', '2026-10-31 23:54:37', 16, 0, NULL, NULL),
(5, 3, 216, 'employee', 'member', '2026-11-01 22:59:18', 6, 0, NULL, NULL),
(6, 3, 88, 'customer', 'member', '2026-11-01 22:59:18', 0, 0, NULL, NULL),
(7, 1, 220, 'employee', 'member', '2026-11-02 14:34:59', 0, 0, NULL, NULL),
(8, 4, 216, 'employee', 'member', '2026-11-03 17:07:43', 12, 0, NULL, NULL),
(9, 4, 120, 'employee', 'member', '2026-11-03 17:07:43', 18, 0, NULL, NULL),
(10, 5, 216, 'employee', 'member', '2026-11-03 17:07:54', 13, 0, NULL, NULL),
(11, 5, 121, 'employee', 'member', '2026-11-03 17:07:54', 0, 0, NULL, NULL),
(12, 6, 218, 'employee', 'member', '2026-11-08 17:19:47', 17, 0, NULL, NULL),
(13, 6, 220, 'employee', 'member', '2026-11-08 17:19:47', 0, 0, NULL, NULL),
(14, 7, 96, 'customer', 'member', '2026-11-11 20:48:04', 0, 0, NULL, NULL),
(15, 7, 120, 'employee', 'admin', '2026-11-11 20:48:04', 0, 0, NULL, NULL),
(16, 8, 96, 'customer', 'member', '2026-11-11 20:48:04', 0, 0, NULL, NULL),
(17, 8, 216, 'employee', 'admin', '2026-11-11 20:48:04', 0, 0, NULL, NULL),
(18, 9, 96, 'customer', 'member', '2026-11-11 20:48:04', 0, 0, NULL, NULL),
(19, 9, 218, 'employee', 'admin', '2026-11-11 20:48:04', 0, 0, NULL, NULL),
(20, 10, 96, 'customer', 'member', '2026-11-11 20:48:04', 0, 0, NULL, NULL),
(21, 10, 221, 'employee', 'admin', '2026-11-11 20:48:04', 0, 0, NULL, NULL),
(22, 11, 97, 'customer', 'member', '2026-11-11 20:59:02', 0, 0, NULL, NULL),
(23, 11, 120, 'employee', 'admin', '2026-11-11 20:59:02', 0, 0, NULL, NULL),
(24, 12, 97, 'customer', 'member', '2026-11-11 20:59:02', 0, 0, NULL, NULL),
(25, 12, 216, 'employee', 'admin', '2026-11-11 20:59:02', 0, 0, NULL, NULL),
(26, 13, 97, 'customer', 'member', '2026-11-11 20:59:02', 0, 0, NULL, NULL),
(27, 13, 218, 'employee', 'admin', '2026-11-11 20:59:02', 0, 0, NULL, NULL),
(28, 14, 97, 'customer', 'member', '2026-11-11 20:59:02', 0, 0, NULL, NULL),
(29, 14, 221, 'employee', 'admin', '2026-11-11 20:59:02', 0, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `chat_user_activity`
--

CREATE TABLE `chat_user_activity` (
  `conversation_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `activity` varchar(20) NOT NULL DEFAULT 'none',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_user_activity`
--

INSERT INTO `chat_user_activity` (`conversation_id`, `user_id`, `company_id`, `activity`, `updated_at`) VALUES
(2, 216, 31, 'none', '2026-11-05 15:38:09'),
(4, 120, 31, 'none', '2026-11-14 19:38:30'),
(4, 216, 31, 'none', '2026-11-03 17:07:48'),
(5, 216, 31, 'none', '2026-11-03 17:10:06'),
(6, 218, 31, 'none', '2026-11-08 17:20:10'),
(11, 120, 31, 'none', '2026-11-14 19:38:57');

-- --------------------------------------------------------

--
-- Table structure for table `chat_user_presence`
--

CREATE TABLE `chat_user_presence` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `last_seen_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_user_presence`
--

INSERT INTO `chat_user_presence` (`id`, `company_id`, `location_id`, `user_id`, `last_seen_at`) VALUES
(1, 31, 0, 216, '2026-11-05 16:17:31'),
(492, 31, 0, 218, '2026-11-09 13:59:59'),
(579, 31, 0, 120, '2026-11-14 19:38:46');

-- --------------------------------------------------------

--
-- Table structure for table `chat_user_profiles`
--

CREATE TABLE `chat_user_profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `display_name` varchar(150) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_user_sessions`
--

CREATE TABLE `chat_user_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `session_hash` char(64) NOT NULL,
  `last_activity_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `ended_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_user_sessions`
--

INSERT INTO `chat_user_sessions` (`id`, `user_id`, `company_id`, `session_hash`, `last_activity_at`, `expires_at`, `ended_at`, `created_at`) VALUES
(1, 216, 31, '831abbb19a0d3443fc28ad22f79f4e7b9c4be2dadc7de9f9d03f526075a522a3', '2026-11-02 21:50:33', '2026-11-02 22:20:33', NULL, '2026-11-02 21:42:17'),
(4, 219, 31, '8605ed69afd1b9f5f4199d7011bf74264b5ef14cae805084fe21d47d9383ee56', '2026-11-02 21:47:16', '2026-11-02 22:17:16', NULL, '2026-11-02 21:42:38');

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` int(11) NOT NULL,
  `harsh` varchar(255) NOT NULL,
  `creator` varchar(255) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `company_slug` varchar(255) NOT NULL,
  `company_logo` varchar(255) NOT NULL,
  `company_domain` varchar(255) DEFAULT NULL,
  `company_industry` varchar(100) NOT NULL,
  `company_office_address` text NOT NULL,
  `company_customer_care` varchar(100) NOT NULL,
  `customer_care_password` varchar(100) NOT NULL,
  `company_support_email` varchar(100) NOT NULL,
  `support_email_password` varchar(100) NOT NULL,
  `company_email` varchar(255) NOT NULL,
  `company_mobile` varchar(50) NOT NULL,
  `company_head_office` text NOT NULL,
  `company_state` varchar(100) NOT NULL,
  `company_city` varchar(100) NOT NULL,
  `location_id` varchar(255) NOT NULL,
  `date_created` date NOT NULL DEFAULT curdate(),
  `time_created` time NOT NULL DEFAULT curtime(),
  `status` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `harsh`, `creator`, `company_name`, `company_slug`, `company_logo`, `company_domain`, `company_industry`, `company_office_address`, `company_customer_care`, `customer_care_password`, `company_support_email`, `support_email_password`, `company_email`, `company_mobile`, `company_head_office`, `company_state`, `company_city`, `location_id`, `date_created`, `time_created`, `status`) VALUES
(31, '33b53058c29eab5588fe98e02a7c7200', 'Ubamus', 'UBAMUS GLOBAL TECHNOLOGY', 'Renewing Technological Infrastructures Differently...', 'company_31_7cccf65a4221eae9.png', 'ubamus.com', 'Production Company', 'F.O.T. Junction, Onne, Eleme, Rivers State, Port Harcourt', 'customercare@ubamus.com', 'Zaida@25', 'support@ubamus.com', 'Zaida@25', 'customercare@ubamus.com', '08029497000', 'F.O.T. Onne', 'Rivers', 'Port Harcourt', '25', '2007-04-26', '08:21:53', 'Active'),
(81, '070fc3f8db6cdd6c37d845effc431ac5', 'Ubamus', 'Excess Led and Sign', 'We Lift it, they see it, they come', 'logo_6ab050ac7faf70.79167624.jpg', 'excessledsign.com', 'Production Company', 'Aba Road', 'customercare@excessledsign.com', 'Dusmansign@26', 'support@excessledsign.com', 'Dusmansign@26', 'callymus32@yahoo.com', '4567890', 'Aba Road', 'Rivers', 'port Harcourt', '87', '2020-09-26', '22:31:24', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(100) NOT NULL,
  `company_id` int(100) NOT NULL,
  `location_id` int(100) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(100) NOT NULL,
  `created_at` varchar(100) NOT NULL,
  `time_created` varchar(100) NOT NULL,
  `created_by` varchar(100) NOT NULL,
  `status` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `company_id`, `location_id`, `fullname`, `email`, `phone`, `created_at`, `time_created`, `created_by`, `status`) VALUES
(85, 81, 87, 'Mbaise', 'customercare@deroyaltygroup.com', '08050563014', '2026-09-21 14:59:50', '', '215', '1'),
(86, 81, 87, 'Nma', 'nmamusa1@gmail.com', '23145', '2026-09-21 15:06:18', '', '215', '1'),
(87, 81, 87, 'Henry', 'scripted345@gmail.com', '08050718757', '2026-09-23 11:30:36', '', '214', '1'),
(88, 31, 90, 'Henry', 'henryakiojano@gmail.com', '08038853742', '2026-10-17 15:16:56', '', '120', '1'),
(89, 31, 90, 'Princewill', 'theobatala333@gmail.com', '908098098', '2026-11-04 15:18:08', '15:18:08', 'Marketplace', '1'),
(90, 31, 90, 'Peter', 'peter@gmail.com', '2131231', '2026-11-05 16:45:26', '16:45:26', 'Marketplace', '1'),
(91, 31, 90, 'James', 'james@gmail.com', '378979387', '2026-11-05 16:46:31', '16:46:31', 'Marketplace', '1'),
(100, 31, 90, 'Henro', 'scripted34@gmail.com', '08038853742', '2026-11-11 21:18:42', '21:18:42', 'Marketplace', '1'),
(104, 31, 90, 'Omoeheffe', 'henryakiojano671@gmail.com', '07045915907', '2026-11-12 16:35:11', '16:35:11', 'Marketplace', '1'),
(105, 31, 90, 'Baba cally', 'callymus23@yahoo.com', '08050563014', '2026-11-14 20:52:28', '20:52:28', 'Marketplace', '1'),
(106, 31, 91, 'usman baba musa', 'ubmgt@hotmail.com', '45678', '2026-11-14 23:01:14', '2026-11-14 23:01:14', '236', '1');

-- --------------------------------------------------------

--
-- Table structure for table `customer_accounts`
--

CREATE TABLE `customer_accounts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `user_id` int(100) NOT NULL,
  `login_email` varchar(190) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `account_status` enum('pending','active','suspended') NOT NULL DEFAULT 'pending',
  `profile_photo` varchar(500) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `activation_token_hash` char(64) DEFAULT NULL,
  `activation_expires_at` datetime DEFAULT NULL,
  `activated_at` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customer_accounts`
--

INSERT INTO `customer_accounts` (`id`, `company_id`, `customer_id`, `user_id`, `login_email`, `password_hash`, `account_status`, `profile_photo`, `bio`, `activation_token_hash`, `activation_expires_at`, `activated_at`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1, 31, 89, 0, 'theobatala333@gmail.com', '$2y$10$SkTsLksixKRL1ixsfnxHs.6mEBpwCFmwlvZeVUO86sfIkDlc8iHSC', 'active', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-04 15:18:08', '2026-11-04 15:18:08'),
(2, 31, 90, 0, 'peter@gmail.com', '$2y$10$oLqTRAmU6hsqDSGSyLPSUOgMJdryiUm.8Jar.Q7C46Y1upGxSSnwe', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 16:45:26', '2026-11-05 16:45:26'),
(3, 31, 91, 0, 'james@gmail.com', '$2y$10$VLrbZ0r5a1cmh.w1SEpEwOwDscmeGDO4syLdal8WyXnAoh6128uYa', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 16:46:31', '2026-11-05 16:46:31'),
(12, 31, 100, 230, 'scripted34@gmail.com', '$2y$10$N2hEwxLm.fU8in5WagZyKus.5Qtf9iIJK9aEX53cooIaa0asr4niK', 'active', NULL, NULL, NULL, NULL, '2026-11-11 21:19:04', NULL, '2026-11-11 21:18:42', '2026-11-12 16:09:52'),
(16, 31, 104, 234, 'henryakiojano671@gmail.com', '$2y$10$8MqzACXfygxzUn7d5tX7Euie0tR1h5asv0ehrh..WaLQ6IaknNNSa', 'active', NULL, NULL, NULL, NULL, '2026-11-12 16:35:34', '2026-11-12 16:35:34', '2026-11-12 16:35:11', '2026-11-12 16:35:34'),
(17, 31, 105, 235, 'callymus23@yahoo.com', '$2y$10$o2PZxkSAi9j5PbyHPwjQ.ucUCOWAOWQqmhvCn9v29dD9jFLyHopvu', 'active', NULL, NULL, NULL, NULL, '2026-11-14 19:53:50', '2026-11-14 19:53:50', '2026-11-14 19:52:28', '2026-11-14 19:53:50');

-- --------------------------------------------------------

--
-- Table structure for table `customer_awards`
--

CREATE TABLE `customer_awards` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `certificate_number` varchar(100) NOT NULL,
  `award_title` varchar(255) NOT NULL,
  `award_category` varchar(100) NOT NULL,
  `award_description` text DEFAULT NULL,
  `award_period_from` date DEFAULT NULL,
  `award_period_to` date DEFAULT NULL,
  `certificate_file` varchar(255) DEFAULT NULL,
  `issued_by` int(11) NOT NULL,
  `issued_at` datetime NOT NULL DEFAULT current_timestamp(),
  `email_sent` enum('Yes','No') DEFAULT 'No'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_debts`
--

CREATE TABLE `customer_debts` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `invoice_number` varchar(100) NOT NULL,
  `debt_by` int(100) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL,
  `debt_type` varchar(100) NOT NULL,
  `total_amount` decimal(12,2) DEFAULT NULL,
  `amount_paid` decimal(14,2) NOT NULL,
  `balance` decimal(12,2) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` varchar(100) NOT NULL,
  `created_at` varchar(100) DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customer_debts`
--

INSERT INTO `customer_debts` (`id`, `transaction_id`, `invoice_number`, `debt_by`, `customer_id`, `company_id`, `location_id`, `debt_type`, `total_amount`, `amount_paid`, `balance`, `due_date`, `status`, `created_at`) VALUES
(172, 350, 'EXA-SALES-20261115-000002', 236, 106, 31, 91, 'Sales', 705600.00, 600000.00, 105600.00, '2026-11-25', 'Unpaid', '2026-11-14 23:01:14');

-- --------------------------------------------------------

--
-- Table structure for table `deleted_jobs`
--

CREATE TABLE `deleted_jobs` (
  `id` int(100) NOT NULL,
  `job_id` varchar(100) NOT NULL,
  `company_id` varchar(100) NOT NULL,
  `location_id` varchar(100) NOT NULL,
  `customer_id` varchar(100) NOT NULL,
  `user_id` varchar(100) NOT NULL,
  `invoice_number` varchar(100) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(100) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `grand_total` varchar(100) NOT NULL,
  `discount_percent` varchar(100) NOT NULL,
  `discount_amount` varchar(100) NOT NULL,
  `final_total` varchar(100) NOT NULL,
  `notes` varchar(100) NOT NULL,
  `payment_status` varchar(100) NOT NULL,
  `amount_paid` varchar(100) NOT NULL,
  `balance` varchar(100) NOT NULL,
  `due_date` varchar(100) NOT NULL,
  `created_at` varchar(100) NOT NULL,
  `time_created` varchar(100) NOT NULL,
  `deleted_by` varchar(100) NOT NULL,
  `deleted_at` datetime(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deleted_products`
--

CREATE TABLE `deleted_products` (
  `id` int(100) NOT NULL,
  `product_id` int(100) NOT NULL,
  `company_id` int(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `barcode` varchar(100) NOT NULL,
  `category_id` int(100) NOT NULL,
  `stock_type` varchar(100) NOT NULL,
  `unit_id` int(100) NOT NULL,
  `cost_price` decimal(14,2) NOT NULL,
  `selling_price` decimal(14,2) NOT NULL,
  `description` varchar(100) NOT NULL,
  `created_at` datetime(6) NOT NULL,
  `created_by` varchar(10) NOT NULL,
  `deleted_at` datetime(6) NOT NULL,
  `deleted_by` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `deleted_products`
--

INSERT INTO `deleted_products` (`id`, `product_id`, `company_id`, `name`, `slug`, `barcode`, `category_id`, `stock_type`, `unit_id`, `cost_price`, `selling_price`, `description`, `created_at`, `created_by`, `deleted_at`, `deleted_by`) VALUES
(20, 288, 31, 'Microsoft Surface Book 3', 'microsoft-surface-book-3', 'UBA475946', 175, 'Sales Only', 98, 900000.00, 1150000.00, 'Microsoft Surface Computer', '2026-11-13 03:05:35.000000', '120', '2026-11-13 04:05:56.000000', '120'),
(21, 292, 31, 'Faster 8Gb Flash Drive', 'faster-8gb-flash-drive', 'UBA194397', 177, 'Sales Only', 98, 5500.00, 6000.00, 'USB 3.1 Flash Drive 8Gb Super-Speed Portable Storage', '2026-11-13 05:51:03.000000', '120', '2026-11-14 22:44:50.000000', '120'),
(22, 293, 31, 'Faster 16Gb Flash Drive', 'faster-16gb-flash-drive', 'UBA691797', 177, 'Sales Only', 98, 7000.00, 7500.00, 'USB 3.1 Flash Drive 16GB Super-Speed Portable Storage', '2026-11-13 05:58:11.000000', '120', '2026-11-14 22:47:44.000000', '120');

-- --------------------------------------------------------

--
-- Table structure for table `deleted_product_images`
--

CREATE TABLE `deleted_product_images` (
  `id` int(11) NOT NULL,
  `original_image_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `deleted_by` varchar(255) DEFAULT NULL,
  `deleted_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `deleted_product_images`
--

INSERT INTO `deleted_product_images` (`id`, `original_image_id`, `product_id`, `company_id`, `image_path`, `deleted_by`, `deleted_at`) VALUES
(17, 160, 288, 31, '20261113040535_ce2d5e054e8324d5.jpg', '120', '2026-11-13 04:05:56'),
(18, 178, 292, 31, '20261113065103_121f416c36ca52a9.jpg', '120', '2026-11-14 22:44:50'),
(19, 179, 293, 31, '20261113065811_e9144c25179d7c40.png', '120', '2026-11-14 22:47:44');

-- --------------------------------------------------------

--
-- Table structure for table `deleted_stocks`
--

CREATE TABLE `deleted_stocks` (
  `id` int(100) NOT NULL,
  `stock_id` int(100) NOT NULL,
  `product_id` int(100) NOT NULL,
  `company_id` int(100) NOT NULL,
  `location_id` int(100) NOT NULL,
  `quantity` int(100) NOT NULL,
  `supplier` varchar(100) NOT NULL,
  `created_at` varchar(100) NOT NULL,
  `stocked_by` varchar(100) NOT NULL,
  `deleted_by` varchar(100) NOT NULL,
  `deleted_at` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deleted_stock_in`
--

CREATE TABLE `deleted_stock_in` (
  `id` int(100) NOT NULL,
  `stock_in_id` int(100) NOT NULL,
  `product_id` int(100) NOT NULL,
  `stock_id` int(100) NOT NULL,
  `company_id` int(100) NOT NULL,
  `location_id` int(100) NOT NULL,
  `transfer_ID` varchar(100) NOT NULL,
  `quantity` int(100) NOT NULL,
  `type` varchar(100) NOT NULL,
  `supplier` text NOT NULL,
  `created_at` varchar(100) NOT NULL,
  `recorded_by` int(100) NOT NULL,
  `deleted_by` varchar(100) NOT NULL,
  `deleted_at` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deleted_stock_movement`
--

CREATE TABLE `deleted_stock_movement` (
  `id` int(100) NOT NULL,
  `stock_movement_id` int(100) NOT NULL,
  `company_id` int(100) NOT NULL,
  `product_id` int(100) NOT NULL,
  `stock_id` int(100) NOT NULL,
  `from_location_id` int(100) NOT NULL,
  `to_location_id` int(100) NOT NULL,
  `transfer_ID` varchar(100) NOT NULL,
  `quantity` int(100) NOT NULL,
  `movement_type` varchar(100) NOT NULL,
  `reference` varchar(100) NOT NULL,
  `created_by` int(100) NOT NULL,
  `created_at` varchar(100) NOT NULL,
  `deleted_by` varchar(100) NOT NULL,
  `deleted_at` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `company_id` int(255) NOT NULL,
  `expense_title` varchar(255) NOT NULL,
  `type` varchar(100) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `staff_responsible` varchar(100) DEFAULT NULL,
  `created_by` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `receipt` varchar(255) DEFAULT NULL,
  `created_at` varchar(100) DEFAULT current_timestamp(),
  `time_created` int(50) NOT NULL,
  `stat` int(100) NOT NULL,
  `approved_by` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(100) NOT NULL,
  `company_id` int(100) NOT NULL,
  `location_id` int(100) NOT NULL,
  `customer_id` int(100) NOT NULL,
  `user_id` int(100) NOT NULL,
  `invoice_number` varchar(100) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(100) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `grand_total` decimal(14,2) NOT NULL,
  `discount_percent` int(100) NOT NULL,
  `discount_amount` decimal(14,2) NOT NULL,
  `final_total` decimal(14,2) NOT NULL,
  `notes` varchar(100) NOT NULL,
  `payment_status` varchar(100) NOT NULL,
  `amount_paid` decimal(14,2) NOT NULL,
  `balance` decimal(14,2) NOT NULL,
  `due_date` varchar(100) NOT NULL,
  `created_at` varchar(100) NOT NULL,
  `time_created` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_categories`
--

CREATE TABLE `job_categories` (
  `id` int(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `company_id` int(255) NOT NULL,
  `location_id` int(255) NOT NULL,
  `created_by` varchar(255) NOT NULL,
  `date` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_items`
--

CREATE TABLE `job_items` (
  `id` int(100) NOT NULL,
  `job_id` int(100) NOT NULL,
  `service_id` int(100) NOT NULL,
  `quantity` int(100) NOT NULL,
  `unit_cost` decimal(14,2) NOT NULL,
  `total_cost` decimal(14,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` varchar(100) DEFAULT NULL,
  `created_by` varchar(100) NOT NULL,
  `address_line1` varchar(255) DEFAULT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `lga` varchar(255) NOT NULL,
  `status` int(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `company_id`, `type`, `name`, `created_at`, `created_by`, `address_line1`, `address_line2`, `city`, `state`, `lga`, `status`) VALUES
(90, 31, 'head_office', 'F.O.T. Junction', '2026-11-13 03:29:18', 'engineermusa01@yahoo.com', 'Onne', '', 'Onne', 'Rivers', 'Eleme', 1),
(91, 31, 'branch', 'REFINARY Junction', '2026-11-13 03:54:20', 'engineermusa01@yahoo.com', 'Alesa,', '', 'Nchia', 'Rivers', 'Eleme', 1);

-- --------------------------------------------------------

--
-- Table structure for table `marketplace_carts`
--

CREATE TABLE `marketplace_carts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `customer_account_id` bigint(20) UNSIGNED NOT NULL,
  `cart_status` enum('active','converted','abandoned') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marketplace_cart_events`
--

CREATE TABLE `marketplace_cart_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `cart_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cart_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `event_type` varchar(40) NOT NULL,
  `item_type` enum('product','service') DEFAULT NULL,
  `item_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `geo_country` varchar(100) DEFAULT NULL,
  `geo_state` varchar(100) DEFAULT NULL,
  `geo_city` varchar(100) DEFAULT NULL,
  `geo_lga` varchar(100) DEFAULT NULL,
  `geo_latitude` decimal(10,7) DEFAULT NULL,
  `geo_longitude` decimal(10,7) DEFAULT NULL,
  `geo_accuracy` varchar(50) DEFAULT NULL,
  `geo_provider` varchar(100) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `event_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`event_data`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marketplace_cart_items`
--

CREATE TABLE `marketplace_cart_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cart_id` bigint(20) UNSIGNED NOT NULL,
  `company_id` int(11) NOT NULL,
  `line_key` char(36) NOT NULL,
  `item_type` enum('product','service') NOT NULL,
  `item_id` int(11) NOT NULL,
  `variant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `service_note` text DEFAULT NULL,
  `required_work` text DEFAULT NULL,
  `preferred_date` date DEFAULT NULL,
  `service_location` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `geo_country` varchar(100) DEFAULT NULL,
  `geo_state` varchar(100) DEFAULT NULL,
  `geo_city` varchar(100) DEFAULT NULL,
  `geo_lga` varchar(100) DEFAULT NULL,
  `geo_latitude` decimal(10,7) DEFAULT NULL,
  `geo_longitude` decimal(10,7) DEFAULT NULL,
  `geo_accuracy` varchar(50) DEFAULT NULL,
  `geo_provider` varchar(100) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `captured_at` datetime DEFAULT NULL,
  `variant_name_snapshot` varchar(500) DEFAULT NULL,
  `variant_sku_snapshot` varchar(100) DEFAULT NULL,
  `variant_attributes_snapshot` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marketplace_customer_otps`
--

CREATE TABLE `marketplace_customer_otps` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `account_id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(190) NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `purpose` enum('register','login') NOT NULL,
  `expires_at` datetime NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marketplace_orders`
--

CREATE TABLE `marketplace_orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `company_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL DEFAULT 0,
  `product_subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `order_status` varchar(40) NOT NULL DEFAULT 'pending',
  `customer_note` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `geo_country` varchar(100) DEFAULT NULL,
  `geo_state` varchar(100) DEFAULT NULL,
  `geo_city` varchar(100) DEFAULT NULL,
  `geo_lga` varchar(100) DEFAULT NULL,
  `geo_latitude` decimal(10,7) DEFAULT NULL,
  `geo_longitude` decimal(10,7) DEFAULT NULL,
  `geo_accuracy` varchar(50) DEFAULT NULL,
  `geo_provider` varchar(100) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `customer_delivery_address` text DEFAULT NULL,
  `assigned_location_id` int(11) DEFAULT NULL,
  `location_captured_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marketplace_order_assignments`
--

CREATE TABLE `marketplace_order_assignments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `assigned_user_id` int(11) DEFAULT NULL,
  `assigned_role` varchar(50) DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `assignment_status` enum('assigned','accepted','in_progress','completed','cancelled') NOT NULL DEFAULT 'assigned',
  `assignment_note` text DEFAULT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marketplace_order_items`
--

CREATE TABLE `marketplace_order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `item_type` enum('product','service') NOT NULL,
  `item_id` int(11) NOT NULL,
  `variant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `item_status` varchar(40) NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `service_note` text DEFAULT NULL,
  `required_work` text DEFAULT NULL,
  `preferred_date` date DEFAULT NULL,
  `service_location` varchar(500) DEFAULT NULL,
  `pricing_type` enum('fixed','quotation') NOT NULL DEFAULT 'quotation',
  `quotation_status` varchar(40) NOT NULL DEFAULT 'not_required',
  `catalogue_reference` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `geo_country` varchar(100) DEFAULT NULL,
  `geo_state` varchar(100) DEFAULT NULL,
  `geo_city` varchar(100) DEFAULT NULL,
  `geo_lga` varchar(100) DEFAULT NULL,
  `geo_latitude` decimal(10,7) DEFAULT NULL,
  `geo_longitude` decimal(10,7) DEFAULT NULL,
  `geo_accuracy` varchar(50) DEFAULT NULL,
  `geo_provider` varchar(100) DEFAULT NULL,
  `captured_at` datetime DEFAULT NULL,
  `variant_name_snapshot` varchar(500) DEFAULT NULL,
  `variant_sku_snapshot` varchar(100) DEFAULT NULL,
  `variant_attributes_snapshot` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marketplace_service_settings`
--

CREATE TABLE `marketplace_service_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `pricing_type` enum('fixed','quotation') NOT NULL DEFAULT 'quotation',
  `fixed_price` decimal(14,2) DEFAULT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `otp_verification`
--

CREATE TABLE `otp_verification` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tracking` int(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp` varchar(10) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `otp_verification`
--

INSERT INTO `otp_verification` (`id`, `user_id`, `tracking`, `email`, `otp`, `created_at`) VALUES
(327, 236, 92, 'engineermusa01@hotmail.com', '69701602', '2026-11-14 22:54:46');

-- --------------------------------------------------------

--
-- Table structure for table `paid_dept`
--

CREATE TABLE `paid_dept` (
  `id` int(100) NOT NULL,
  `company_id` int(100) NOT NULL,
  `location_id` int(100) NOT NULL,
  `pdtransaction_id` int(100) NOT NULL,
  `pdbalance` decimal(14,2) NOT NULL,
  `pdprevious_invoice_number` varchar(100) NOT NULL,
  `pdpaid_invoice_number` varchar(100) NOT NULL,
  `pdcreated_at` varchar(100) NOT NULL,
  `pdstatus` varchar(100) NOT NULL,
  `pdcreated_by` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `privacy_policy`
--

CREATE TABLE `privacy_policy` (
  `id` int(11) NOT NULL,
  `company_id` int(100) NOT NULL,
  `eyebrow` varchar(255) DEFAULT 'Your Privacy',
  `title` varchar(255) NOT NULL DEFAULT 'Privacy Policy',
  `intro` text DEFAULT NULL,
  `information_collect_title` varchar(255) DEFAULT 'Information We Collect',
  `information_collect_content` longtext DEFAULT NULL,
  `information_use_title` varchar(255) DEFAULT 'How We Use Your Information',
  `information_use_content` longtext DEFAULT NULL,
  `security_title` varchar(255) DEFAULT 'Information Security',
  `security_content` longtext DEFAULT NULL,
  `sharing_title` varchar(255) DEFAULT 'Sharing of Information',
  `sharing_content` longtext DEFAULT NULL,
  `cookies_title` varchar(255) DEFAULT 'Cookies and Website Technologies',
  `cookies_content` longtext DEFAULT NULL,
  `changes_title` varchar(255) DEFAULT 'Changes to This Policy',
  `changes_content` longtext DEFAULT NULL,
  `contact_title` varchar(255) DEFAULT 'Contact Us',
  `contact_content` longtext DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `production_raw_materials`
--

CREATE TABLE `production_raw_materials` (
  `id` int(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `company_id` int(100) NOT NULL,
  `location_id` int(100) NOT NULL,
  `production_id` int(100) NOT NULL,
  `quantity_used` int(100) NOT NULL,
  `unit_cost` varchar(100) NOT NULL,
  `total_cost` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `production_services`
--

CREATE TABLE `production_services` (
  `id` int(100) NOT NULL,
  `production_id` int(100) NOT NULL,
  `company_id` int(100) NOT NULL,
  `location_id` int(100) NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `cost` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `production_team`
--

CREATE TABLE `production_team` (
  `id` int(100) NOT NULL,
  `company_id` int(100) NOT NULL,
  `location_id` int(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_by` int(100) NOT NULL,
  `created_at` int(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `production_team_members`
--

CREATE TABLE `production_team_members` (
  `id` int(100) NOT NULL,
  `production_team_id` int(100) NOT NULL,
  `staff_id` int(100) NOT NULL,
  `date` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(100) NOT NULL,
  `company_id` int(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `barcode` varchar(100) NOT NULL,
  `category_id` int(100) NOT NULL,
  `stock_type` varchar(100) NOT NULL,
  `imei_tracking` tinyint(1) NOT NULL DEFAULT 0,
  `unit_id` int(100) NOT NULL,
  `cost_price` varchar(100) NOT NULL,
  `selling_price` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `created_at` varchar(100) NOT NULL,
  `created_by` varchar(50) NOT NULL,
  `identifier_tracking` enum('none','imei','serial','vin') NOT NULL DEFAULT 'none',
  `has_variants` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `company_id`, `name`, `slug`, `barcode`, `category_id`, `stock_type`, `imei_tracking`, `unit_id`, `cost_price`, `selling_price`, `description`, `created_at`, `created_by`, `identifier_tracking`, `has_variants`) VALUES
(289, 31, 'Microsoft Surface Book 3 (Used)', 'microsoft-surface-book-3', 'UBA144327', 175, 'Sales Only', 0, 98, '900000', '1150000', 'The Microsoft Surface Book 3 is a powerful, versatile 2-in-1 laptop with excellent performance and a detachable tablet, though its design feels dated and upgrade options are limited.\r\nDesign and Build\r\nThe Surface Book 3 maintains the same convertible design as its predecessors, featuring a detachable tablet and a fulcrum hinge that supports the weight of the screen while preventing accidental touchscreen presses while typing \r\nTech Advisor\r\nTech Advisor\r\n. The device has a premium all-metal build, but the design is considered slightly bulky and retro compared to modern ultrabooks like the Dell XPS or MacBook Pro \r\nTrusted Reviews\r\nTrusted Reviews\r\n+1\r\n. The 13.5-inch and 15-inch models share similar aesthetics, with a large bezel around the screen giving it a dated look \r\nTrusted Reviews\r\nTrusted Reviews\r\n. The keyboard dock is robust, with a tactile, responsive typing experience and a large, precise trackpad \r\nTech Advisor\r\nTech Advisor\r\n.\r\n\r\nDisplay\r\nThe Surface Book 3 features a high-resolution PixelSense display: 3240×2140 for the 15-inch model and 3000×2000 for the 13.5-inch model \r\nTech Advisor\r\nTech Advisor\r\n. The screen offers excellent contrast (1633:1), vibrant colors, and a 3:2 aspect ratio that is ideal for productivity \r\nTrusted Reviews\r\nTrusted Reviews\r\n. While the display is not full 4K, it is sharp enough for everyday use, media consumption, and gaming. However, color gamut coverage (63.9% Adobe RGB, 65.7% DCI-P3) is insufficient for professional creative work \r\nTrusted Reviews\r\nTrusted Reviews\r\n.\r\n\r\nPerformance\r\nThe Surface Book 3 is powered by Intel 10th-generation Ice Lake CPUs and discrete Nvidia GPUs (GTX 1650, GTX 1660 Ti, or Quadro RTX 3000 for corporate users), \r\nNotebookcheck\r\nNotebookcheck\r\n+1\r\n. While the CPU performance lags behind competitors like the MacBook Pro 16-inch or Dell XPS 15 with six- or eight-core processors, the GPU options make it suitable for gaming, video editing, and creative workloads \r\nEngadget\r\nEngadget\r\n. The tablet portion alone uses integrated Intel Iris graphics, which limits performance when detached \r\nTech Advisor\r\nTech Advisor\r\n. RAM options go up to 32GB, and storage is configurable with SSDs, providing smooth multitasking and fast file access \r\nNotebookcheck\r\nNotebookcheck\r\n.\r\n\r\nPort Selection and Connectivity\r\nThe device includes 2 USB-A ports, 1 USB-C port, a headphone jack, and a full-size SD card slot, split between the tablet and keyboard dock for full functionality in tablet mode \r\nTech Advisor\r\nTech Advisor\r\n. It uses Surface Connect ports for charging, and while it supports accessories like the Surface Pen and Surface Dial, these are sold separately \r\nTech Advisor\r\nTech Advisor\r\n. Maintenance options are extremely limited, with no user-accessible screws or upgrade paths \r\nNotebookcheck\r\nNotebookcheck\r\n.\r\n\r\nCamera, Audio, and Security\r\nThe Surface Book 3 has an 8MP rear camera and 5MP front camera, suitable for video calls but not competitive with high-end smartphones \r\nNotebookcheck\r\nNotebookcheck\r\n. Audio clarity is good for voices, but bass and volume are limited due to speaker placement \r\nTech Advisor\r\nTech Advisor\r\n. Security features include TPM 2.0 and Windows Hello facial recognition, but no fingerprint sensor or Kensington lock \r\nNotebookcheck\r\nNotebookcheck\r\n.\r\n\r\nUsability\r\nThe detachable tablet is impressive for touch and pen input, though heavy at 1.9kg for the 15-inch model, making extended handheld use cumbersome \r\nTech Advisor\r\nTech Advisor\r\n. The hinge design allows creative positioning, but the device cannot detach the screen when performing high-performance tasks like video editing or gaming \r\nTech Advisor\r\nTech Advisor\r\n. Battery life is decent, with the keyboard dock housing a larger battery to support longer use.\r\n\r\nPros and Cons\r\nPros:\r\n\r\nPowerful GPU options for gaming and creative work \r\nEngadget\r\nEngadget\r\nExcellent keyboard and trackpad experience \r\nTech Advisor\r\nTech Advisor\r\nHigh-resolution, vibrant display with 3:2 aspect ratio \r\nTrusted Reviews\r\nTrusted Reviews\r\nDetachable tablet for versatile use \r\nEngadget\r\nEngadget\r\n\r\nCons:\r\nCPU performance lags behind modern competitors \r\nEngadget\r\nEngadget\r\nDesign feels dated and bulky \r\nTrusted Reviews\r\nTrusted Reviews\r\nLimited color gamut for professional creative work \r\nTrusted Reviews\r\nTrusted Reviews\r\nPoor maintenance and upgrade options \r\nNotebookcheck\r\nNotebookcheck\r\nExpensive optional accessories \r\nNotebookcheck\r\nNotebookcheck\r\nConclusion\r\nThe Surface Book 3 remains a unique 2-in-1 device that excels in versatility and GPU performance, making it ideal for power users, creatives, and gamers who value a detachable tablet. However, its dated design, limited CPU performance, and high cost of accessories may make it less appealing compared to slimmer, more modern laptops like the MacBook Pro or Dell XPS 15 \r\nNotebookcheck\r\nNotebookcheck\r\n+1\r\n. It is best suited for users who prioritize flexibility and GPU power over cutting-edge CPU performance or ultra-slim design.', '2026-11-13 03:23:12', '120', 'none', 0),
(290, 31, 'Samsung Galaxy Z Fold 8 512gb', 'samsung-galaxy-fold-8-512gb', 'UBA982831', 176, 'Sales Only', 0, 99, '2520160', '2520170', '1. Measured diagonally, Galaxy Z Fold8\'s Cover Screen size is 5.5” in the full rectangle and 5.4” accounting for the rounded corners. The Main Screen size is 7.6” in the full rectangle and 7.6” accounting for the rounded corners; actual viewable area is smaller due to the rounded corners and camera hole.\r\n2. Compared to previous Galaxy Z Fold models.\r\n3. Typical value tested under third-party laboratory condition. Typical value is the estimated average value considering the deviation in battery capacity among the battery samples tested under IEC 61960 standard. Rated capacity is 4660 mAh for Galaxy Z Fold8.\r\n4. Based on Samsung internal lab test conditions with pre-release version of given model connected to earphone via Bluetooth under default settings over LTE. Estimated against battery capacity and measured current over battery power consumption during video playback (video file resolution 720 p, saved on device) on the main screen. Actual video playback time may vary by network connection, settings, video file format, screen brightness, battery condition and many other factors.\r\n5. Results may vary depending on light condition and/or shooting conditions including multiple subjects, being out of focus or moving subjects.\r\n6. Now Nudge feature requires a Samsung Account login. Available functions and features may vary by country, region and language. Text message prompts available with certain third-party messaging apps including Samsung Messages, Google Messages, Google Chat. Split view supported apps may vary. Share photos in Now Nudge works by analyzing available image content. Accuracy of results is not guaranteed.', '2026-11-13 03:50:54', '120', 'none', 0),
(291, 31, 'Zap 70g A4 Paper', 'zap-70g-a4-paper', 'UBA856252', 179, 'Sales Only', 0, 100, '3280', '4000', 'Printing Paper', '2026-11-13 05:31:30', '120', 'none', 0),
(296, 31, 'Chupesk Flash Drive', 'chupesk-flash-drive', 'UBA442661', 177, 'Sales Only', 0, 98, '000000', '000000000', 'ksjdfhkjsdhkfjhsd', '2026-11-13 14:00:51', '216', 'none', 1),
(297, 31, 'Galaxy Watch8 (Bluetooth, 44 mm)', 'galaxy-watch8-bluetooth-44-mm', 'UBA709556', 180, 'Sales Only', 0, 99, '391850', '392000', 'Ultra comfort,\r\nfrom sleep to workout\r\n\r\nThe thinnest Samsung Galaxy Watch yet, Galaxy Watch8 is ready to make an impact. Now, boldly reimagined — from the iconic new cushion design to the Dynamic Lug System for the snug, comfortable fit — all while performing powerfully with the 3 nm processor. Experience proactive coaching, from sleep to running to holistic health, that fuses intelligence with individuality. Make the most of your moments with Galaxy AI keeping you ahead, topped with a whole-new One UI experience.', '2026-11-14 22:10:06', '120', 'none', 1),
(298, 31, 'Faster, Flash Drive', 'faster-flash-drive', 'UBA437431', 177, 'Sales Only', 0, 98, '3500', '4000', 'USB 3.1 Flash Drive Super-Speed Portable Storage', '2026-11-14 23:13:19', '120', 'none', 1);

-- --------------------------------------------------------

--
-- Table structure for table `product_attributes`
--

CREATE TABLE `product_attributes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `attribute_name` varchar(100) NOT NULL,
  `input_type` enum('select','swatch','text') NOT NULL DEFAULT 'select',
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_attributes`
--

INSERT INTO `product_attributes` (`id`, `company_id`, `product_id`, `attribute_name`, `input_type`, `is_required`, `display_order`, `status`, `created_at`) VALUES
(3, 31, 296, 'Size', 'select', 1, 0, 1, '2026-11-13 14:00:51'),
(4, 31, 297, 'Color', 'select', 1, 0, 1, '2026-11-14 21:10:06'),
(5, 31, 298, 'Capacity', 'select', 1, 0, 1, '2026-11-14 22:13:19');

-- --------------------------------------------------------

--
-- Table structure for table `product_attribute_values`
--

CREATE TABLE `product_attribute_values` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `attribute_id` bigint(20) UNSIGNED NOT NULL,
  `value_name` varchar(150) NOT NULL,
  `value_code` varchar(100) DEFAULT NULL,
  `color_code` varchar(20) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_attribute_values`
--

INSERT INTO `product_attribute_values` (`id`, `attribute_id`, `value_name`, `value_code`, `color_code`, `display_order`, `status`, `created_at`) VALUES
(9, 3, '4gb', NULL, NULL, 0, 1, '2026-11-13 14:00:51'),
(10, 3, '6gb', NULL, NULL, 1, 1, '2026-11-13 14:00:51'),
(11, 3, '8gb', NULL, NULL, 2, 1, '2026-11-13 14:00:51'),
(12, 3, '12gb', NULL, NULL, 3, 1, '2026-11-13 14:00:51'),
(13, 4, 'Silver', NULL, NULL, 0, 1, '2026-11-14 21:10:06'),
(14, 4, 'Black', NULL, NULL, 1, 1, '2026-11-14 21:10:06'),
(15, 4, 'White', NULL, NULL, 2, 1, '2026-11-14 21:10:06'),
(16, 5, '4Gb', NULL, NULL, 0, 1, '2026-11-14 22:13:19'),
(17, 5, '8Gb', NULL, NULL, 1, 1, '2026-11-14 22:13:19'),
(18, 5, '16Gb', NULL, NULL, 2, 1, '2026-11-14 22:13:19'),
(19, 5, '32Gb', NULL, NULL, 3, 1, '2026-11-14 22:13:19'),
(20, 5, '64Gb', NULL, NULL, 4, 1, '2026-11-14 22:13:19'),
(21, 5, '128Gb', NULL, NULL, 5, 1, '2026-11-14 22:13:19'),
(22, 5, '256Gb', NULL, NULL, 6, 1, '2026-11-14 22:13:19');

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `parent_id` int(50) DEFAULT NULL,
  `unit_type` varchar(100) NOT NULL,
  `unit_id` int(100) NOT NULL,
  `stock_type` varchar(100) NOT NULL,
  `dept` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`id`, `company_id`, `name`, `slug`, `parent_id`, `unit_type`, `unit_id`, `stock_type`, `dept`, `description`, `created_at`, `created_by`, `updated_at`) VALUES
(175, 31, 'Microsoft Surface Computer', 'microsoft-surface-computer', NULL, 'General / Universal Units', 98, 'Sales Only', 'Purchase Only', 'Microsoft Surface Computer and Accessories', '2026-11-13 03:02:15', 120, NULL),
(176, 31, 'Phone and Tablet', 'phone-and-tablet', NULL, 'General / Universal Units', 99, 'Sales Only', 'Purchase Only', 'Mobile Phones And Accessories', '2026-11-13 03:04:13', 120, NULL),
(177, 31, 'Flash Drive', 'flash-drive', NULL, 'General / Universal Units', 98, 'Sales Only', 'Purchase Only', 'Computer accessories', '2026-11-13 05:18:45', 120, NULL),
(178, 31, 'Keyboard', 'keyboard', NULL, 'General / Universal Units', 98, 'Sales Only', 'Purchase Only', 'Computer Accessories', '2026-11-13 05:20:22', 120, NULL),
(179, 31, 'A4 Paper', 'a4-paper', NULL, 'Paper / Flat Items', 100, 'Sales Only', 'Purchase Only', 'Printing Paper', '2026-11-13 05:28:00', 120, NULL),
(180, 31, 'Smart Watch', 'smart-watch', NULL, 'General / Universal Units', 99, 'Sales Only', 'Purchase Only', 'Smart Watches', '2026-11-14 20:32:34', 120, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_features`
--

CREATE TABLE `product_features` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `feature_name` varchar(255) NOT NULL,
  `feature_value` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_features`
--

INSERT INTO `product_features` (`id`, `product_id`, `feature_name`, `feature_value`, `created_at`) VALUES
(16, 290, 'Color', 'Cream', '2026-11-13 05:04:42'),
(17, 290, 'Storage', '512Gb', '2026-11-13 05:05:54'),
(18, 289, 'Dimensions', '13.5\"', '2026-11-13 05:19:53'),
(19, 289, 'Memory', '16Gb', '2026-11-13 05:21:21'),
(20, 289, 'Storage', '512Gb', '2026-11-13 05:21:48'),
(21, 289, 'Processor', 'Quad-core 10th Gen Intel® Core™ i7-1065G7 Processor', '2026-11-13 05:22:34'),
(22, 289, 'Graphics', 'NVIDIA® GeForce® GTX 1660 Ti with Max-Q Design w/6GB  Graphics GDDR6 graphics memory (on Intel® i7-1065G7 models); NVIDIA® Quadro RTX™ 3000 with Max-Q Design', '2026-11-13 05:23:57'),
(23, 289, 'Security', 'TPM 2.0 chip for enterprise security Enterprise-grade protection with Windows Hello face sign-in', '2026-11-13 05:24:41');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `created_at`) VALUES
(162, 289, 'e7bb43d7c04bb46090a8a0afed037114.jpg', '2026-11-13 03:33:42'),
(163, 289, 'e6d6d3637256d72bed8dac1941847d72.jpg', '2026-11-13 03:34:03'),
(164, 289, 'a15f100dae40fc1a1f1719eefbbfc2db.jpg', '2026-11-13 03:34:20'),
(166, 290, '3aad13f42e23fd63bd31fd7621ec3488.jpg', '2026-11-13 03:52:19'),
(168, 290, '0d96bbf514cb3dde57c7e3c80c3bc758.jpg', '2026-11-13 03:57:35'),
(169, 290, 'e297cdc758a0ca45a87742651a82a661.png', '2026-11-13 04:07:33'),
(170, 290, '6fe7321e6e2b1c84dafc5a6932a75e54.webp', '2026-11-13 04:08:06'),
(171, 290, 'f46a74bdb99855547072723eaf32783a.png', '2026-11-13 04:08:37'),
(172, 290, 'b14506097bbe5bff03b76659bf3917a4.png', '2026-11-13 04:09:09'),
(173, 290, '1a991569cb2c99ac5e173bcddee2707d.png', '2026-11-13 04:09:45'),
(174, 290, 'b91153914fde94394983a575f49b8a99.png', '2026-11-13 04:10:18'),
(175, 290, '3d89bad4b94684c994b5f9db92f22516.png', '2026-11-13 04:10:41'),
(176, 290, 'a4e75fde71f312861f1c00f8c6cf79cd.png', '2026-11-13 04:11:05'),
(177, 291, '20261113063130_3ddac39c7d6be18b.png', '2026-11-13 05:31:30'),
(182, 296, '20261113140051_54032eccc87c4155.jpg', '2026-11-13 13:00:51'),
(184, 297, '1ed6dbac929b8ede9b61bbfd93834167.jpg', '2026-11-14 21:10:59'),
(185, 297, 'a11e38439d86cba2abc33f5ca13da762.jpg', '2026-11-14 21:11:14'),
(186, 297, 'ff89e507be31dad7c2914bb7ab8e3616.jpg', '2026-11-14 21:11:28'),
(187, 297, '63048e9f889134f046006730ea7c2e44.jpg', '2026-11-14 21:11:46'),
(188, 297, 'a3b1f6db887066998a3ce1a303684166.jpg', '2026-11-14 21:12:00'),
(190, 298, '62372c8a2486454ee48dba3752bbbaf7.jpg', '2026-11-14 22:18:22'),
(191, 298, '5a8a02523cac159bb026ca74b9a4fde6.jpg', '2026-11-14 22:19:00'),
(192, 298, 'e8cc57b48557476affd54a2a96eca797.png', '2026-11-14 22:21:37'),
(193, 298, '7aea49c92f502185e484cc72336f46ad.jpg', '2026-11-14 22:22:40'),
(194, 298, '1680772e1b3206bf6e9841feacfc9983.jpg', '2026-11-14 22:25:39');

-- --------------------------------------------------------

--
-- Table structure for table `product_media`
--

CREATE TABLE `product_media` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `company_id` int(11) NOT NULL,
  `media_type` enum('image','video') NOT NULL,
  `media_path` varchar(500) NOT NULL,
  `thumbnail_path` varchar(500) DEFAULT NULL,
  `media_name` varchar(255) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `file_size` bigint(20) UNSIGNED DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `legacy_image_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_media`
--

INSERT INTO `product_media` (`id`, `product_id`, `variant_id`, `company_id`, `media_type`, `media_path`, `thumbnail_path`, `media_name`, `mime_type`, `file_size`, `display_order`, `is_primary`, `legacy_image_id`, `created_by`, `created_at`, `updated_at`) VALUES
(22, 288, NULL, 31, 'image', '20261113040535_ce2d5e054e8324d5.jpg', NULL, 'Microsoft Surface Book 3 - Featured image', 'image/jpeg', 675578, 0, 1, NULL, 120, '2026-11-13 03:05:35', '2026-11-13 03:05:35'),
(23, 289, NULL, 31, 'image', '20261113042312_d12c88a3515eafb5.jpg', NULL, 'Microsoft Surface Book 3 - Featured image', 'image/jpeg', 675578, 0, 1, NULL, 120, '2026-11-13 03:23:12', '2026-11-13 03:23:12'),
(24, 289, NULL, 31, 'video', '20261113042312_7a21b11aa716d7cb.mp4', NULL, 'Microsoft Surface  Laptops, Tablets   Computers   Microsoft Surface (1).mp4', 'video/mp4', 2888339, 1, 0, NULL, 120, '2026-11-13 03:23:12', '2026-11-13 03:23:12'),
(27, 289, NULL, 31, 'image', 'e7bb43d7c04bb46090a8a0afed037114.jpg', NULL, '1.jpg', 'image/jpeg', 77177, 2, 0, 162, 120, '2026-11-13 03:33:42', '2026-11-13 03:33:42'),
(28, 289, NULL, 31, 'image', 'e6d6d3637256d72bed8dac1941847d72.jpg', NULL, '2.jpg', 'image/jpeg', 92494, 3, 0, 163, 120, '2026-11-13 03:34:03', '2026-11-13 03:34:03'),
(29, 289, NULL, 31, 'image', 'a15f100dae40fc1a1f1719eefbbfc2db.jpg', NULL, '6.jpg', 'image/jpeg', 482800, 4, 0, 164, 120, '2026-11-13 03:34:20', '2026-11-13 03:34:20'),
(31, 290, NULL, 31, 'video', '20261113045054_8823839ba2bd498f.mp4', NULL, 'Galaxy fold 8.mp4', 'video/mp4', 410087, 1, 0, NULL, 120, '2026-11-13 03:50:54', '2026-11-13 03:50:54'),
(32, 290, NULL, 31, 'image', '3aad13f42e23fd63bd31fd7621ec3488.jpg', NULL, 'in_the_box_lavender_PC.jpg', 'image/jpeg', 69154, 2, 0, 166, 120, '2026-11-13 03:52:19', '2026-11-13 03:52:19'),
(34, 290, NULL, 31, 'image', '0d96bbf514cb3dde57c7e3c80c3bc758.jpg', NULL, 'see_actual_size_PC.jpg', 'image/jpeg', 37983, 3, 0, 168, 120, '2026-11-13 03:57:35', '2026-11-13 03:57:35'),
(35, 290, NULL, 31, 'image', 'e297cdc758a0ca45a87742651a82a661.png', NULL, 'africa-en-galaxy-z-fold8-f971-sm-f971blvoafb-553722089.png', 'image/png', 4241242, 4, 0, 169, 120, '2026-11-13 04:07:33', '2026-11-13 04:07:33'),
(36, 290, NULL, 31, 'image', '6fe7321e6e2b1c84dafc5a6932a75e54.webp', NULL, 'keyfeatures_lightestFold_PC.webp', 'image/webp', 4060, 5, 0, 170, 120, '2026-11-13 04:08:06', '2026-11-13 04:08:06'),
(37, 290, NULL, 31, 'image', 'f46a74bdb99855547072723eaf32783a.png', NULL, 'africa-en-galaxy-z-fold8-f971-sm-f971blvoafb-553722076.png', 'image/png', 2070168, 6, 0, 171, 120, '2026-11-13 04:08:37', '2026-11-13 04:08:37'),
(38, 290, NULL, 31, 'image', 'b14506097bbe5bff03b76659bf3917a4.png', NULL, 'africa-en-galaxy-z-fold8-f971-sm-f971blvoafb-553722089.png', 'image/png', 4241242, 7, 0, 172, 120, '2026-11-13 04:09:09', '2026-11-13 04:09:09'),
(39, 290, NULL, 31, 'image', '1a991569cb2c99ac5e173bcddee2707d.png', NULL, 'africa-en-galaxy-z-fold8-f971-sm-f971blvoafb-553722076.png', 'image/png', 2070168, 8, 0, 173, 120, '2026-11-13 04:09:45', '2026-11-13 04:09:45'),
(40, 290, NULL, 31, 'image', 'b91153914fde94394983a575f49b8a99.png', NULL, 'africa-en-galaxy-z-fold8-f971-sm-f971blvoafb-553722078.png', 'image/png', 2411173, 9, 0, 174, 120, '2026-11-13 04:10:18', '2026-11-13 04:10:18'),
(41, 290, NULL, 31, 'image', '3d89bad4b94684c994b5f9db92f22516.png', NULL, 'africa-en-galaxy-z-fold8-f971-sm-f971blvoafb-553722080.png', 'image/png', 3599946, 10, 0, 175, 120, '2026-11-13 04:10:41', '2026-11-13 04:10:41'),
(42, 290, NULL, 31, 'image', 'a4e75fde71f312861f1c00f8c6cf79cd.png', NULL, 'africa-en-galaxy-z-fold8-f971-sm-f971blvoafb-553722081.png', 'image/png', 552735, 11, 0, 176, 120, '2026-11-13 04:11:05', '2026-11-13 04:11:05'),
(43, 291, NULL, 31, 'image', '20261113063130_3ddac39c7d6be18b.png', NULL, 'Zap 70g A4 Paper - Featured image', 'image/png', 120989, 0, 1, NULL, 120, '2026-11-13 05:31:30', '2026-11-13 05:31:30'),
(44, 292, NULL, 31, 'image', '20261113065103_121f416c36ca52a9.jpg', NULL, 'Faster 8Gb Flash Drive - Featured image', 'image/jpeg', 88239, 0, 1, NULL, 120, '2026-11-13 05:51:03', '2026-11-13 05:51:03'),
(45, 293, NULL, 31, 'image', '20261113065811_e9144c25179d7c40.png', NULL, 'Faster 16Gb Flash Drive - Featured image', 'image/png', 881607, 0, 1, NULL, 120, '2026-11-13 05:58:11', '2026-11-13 05:58:11'),
(48, 296, NULL, 31, 'image', '20261113140051_54032eccc87c4155.jpg', NULL, 'd1ab0725bbfc8a97b40e543625b514136b813831af2475d0.jpg', 'image/jpeg', 151373, 0, 1, NULL, 216, '2026-11-13 14:00:51', '2026-11-13 14:00:51'),
(49, 296, NULL, 31, 'video', '7a53734f9e65958fce94fad76f801084.mp4', NULL, 'You_are_the_Reason_I_live___viralvideo__lawrenceoyor__viral.mp4', 'video/mp4', 30315553, 1, 0, NULL, 216, '2026-11-13 14:26:56', '2026-11-13 14:26:56'),
(50, 297, NULL, 31, 'image', '20261114221006_370ae2920679c9f2.jpg', NULL, 'africa-en-galaxy-watch8-l330-sm-l330nzsamea-547704308.jpg', 'image/jpeg', 38414, 0, 1, NULL, 120, '2026-11-14 21:10:06', '2026-11-14 21:10:06'),
(51, 297, NULL, 31, 'image', '1ed6dbac929b8ede9b61bbfd93834167.jpg', NULL, 'africa-en-galaxy-watch8-l330-sm-l330nzsamea-547704309.jpg', 'image/jpeg', 49825, 1, 0, 184, 120, '2026-11-14 21:10:59', '2026-11-14 21:10:59'),
(52, 297, NULL, 31, 'image', 'a11e38439d86cba2abc33f5ca13da762.jpg', NULL, 'africa-en-galaxy-watch8-l330-sm-l330nzsamea-547704310.jpg', 'image/jpeg', 33351, 2, 0, 185, 120, '2026-11-14 21:11:14', '2026-11-14 21:11:14'),
(53, 297, NULL, 31, 'image', 'ff89e507be31dad7c2914bb7ab8e3616.jpg', NULL, 'africa-en-galaxy-watch8-l330-sm-l330nzsamea-547704311.jpg', 'image/jpeg', 23310, 3, 0, 186, 120, '2026-11-14 21:11:28', '2026-11-14 21:11:28'),
(54, 297, NULL, 31, 'image', '63048e9f889134f046006730ea7c2e44.jpg', NULL, 'africa-en-galaxy-watch8-l330-sm-l330nzsamea-547704312.jpg', 'image/jpeg', 54006, 4, 0, 187, 120, '2026-11-14 21:11:46', '2026-11-14 21:11:46'),
(55, 297, NULL, 31, 'image', 'a3b1f6db887066998a3ce1a303684166.jpg', NULL, 'africa-en-galaxy-watch8-l330-sm-l330nzsamea-547704319.jpg', 'image/jpeg', 51830, 5, 0, 188, 120, '2026-11-14 21:12:00', '2026-11-14 21:12:00'),
(57, 298, NULL, 31, 'image', '62372c8a2486454ee48dba3752bbbaf7.jpg', NULL, 'Faster_4Gb.jpg', 'image/jpeg', 28458, 1, 0, 190, 120, '2026-11-14 22:18:22', '2026-11-14 22:18:22'),
(58, 298, NULL, 31, 'image', '5a8a02523cac159bb026ca74b9a4fde6.jpg', NULL, 'FASTER_USB_3.1_Flash_Drive_8GB.jpg', 'image/jpeg', 88239, 2, 0, 191, 120, '2026-11-14 22:19:00', '2026-11-14 22:19:00'),
(59, 298, NULL, 31, 'image', 'e8cc57b48557476affd54a2a96eca797.png', NULL, 'Faster_16Gb.png', 'image/png', 881607, 3, 0, 192, 120, '2026-11-14 22:21:37', '2026-11-14 22:21:37'),
(60, 298, NULL, 31, 'image', '7aea49c92f502185e484cc72336f46ad.jpg', NULL, 'Faster_32Gb.jpg', 'image/jpeg', 125220, 4, 0, 193, 120, '2026-11-14 22:22:40', '2026-11-14 22:22:40'),
(61, 298, NULL, 31, 'image', '1680772e1b3206bf6e9841feacfc9983.jpg', NULL, 'Faster_64Gb.jpg', 'image/jpeg', 20373, 5, 0, 194, 120, '2026-11-14 22:25:39', '2026-11-14 22:25:39');

-- --------------------------------------------------------

--
-- Table structure for table `product_serials`
--

CREATE TABLE `product_serials` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `company_id` int(11) NOT NULL,
  `identifier_type` enum('imei','serial','vin') NOT NULL DEFAULT 'imei',
  `identifier_value` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `imei` char(15) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `stock_id` int(11) DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL,
  `status` enum('in_stock','reserved','sold','returned_pending_inspection','under_repair','damaged','written_off') NOT NULL DEFAULT 'in_stock',
  `current_sale_item_id` int(11) DEFAULT NULL,
  `received_at` datetime DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_serial_events`
--

CREATE TABLE `product_serial_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `serial_id` bigint(20) UNSIGNED NOT NULL,
  `company_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `event_type` varchar(40) NOT NULL,
  `from_stock_id` int(11) DEFAULT NULL,
  `to_stock_id` int(11) DEFAULT NULL,
  `from_location_id` int(11) DEFAULT NULL,
  `to_location_id` int(11) DEFAULT NULL,
  `sale_id` int(11) DEFAULT NULL,
  `sale_item_id` int(11) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `variant_name` varchar(255) NOT NULL,
  `cost_price` decimal(14,2) DEFAULT NULL,
  `selling_price` decimal(14,2) DEFAULT NULL,
  `tracking_policy` enum('inherit','none','imei','serial','vin') NOT NULL DEFAULT 'inherit',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`id`, `company_id`, `product_id`, `sku`, `barcode`, `variant_name`, `cost_price`, `selling_price`, `tracking_policy`, `is_default`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 31, 296, NULL, NULL, 'Size: 4gb', 4000.00, 5000.00, 'inherit', 0, 1, '2026-11-13 14:00:51', '2026-11-13 14:00:51'),
(2, 31, 296, NULL, NULL, 'Size: 6gb', 6000.00, 8000.00, 'inherit', 0, 1, '2026-11-13 14:00:51', '2026-11-13 14:00:51'),
(3, 31, 296, NULL, NULL, 'Size: 8gb', 8000.00, 12000.00, 'inherit', 0, 1, '2026-11-13 14:00:51', '2026-11-13 14:00:51'),
(4, 31, 296, NULL, NULL, 'Size: 12gb', 10000.00, 13000.00, 'inherit', 1, 1, '2026-11-13 14:00:51', '2026-11-13 14:00:51'),
(5, 31, 297, NULL, NULL, 'Color: Silver', 391000.00, 391500.00, 'none', 1, 1, '2026-11-14 21:10:06', '2026-11-14 21:10:06'),
(6, 31, 297, NULL, NULL, 'Color: Black', 391200.00, 391800.00, 'none', 0, 1, '2026-11-14 21:10:06', '2026-11-14 21:10:06'),
(7, 31, 297, NULL, NULL, 'Color: White', 391300.00, 392000.00, 'inherit', 0, 1, '2026-11-14 21:10:06', '2026-11-14 21:10:06'),
(8, 31, 298, NULL, NULL, 'Capacity: 4Gb', 3500.00, 4000.00, 'none', 0, 1, '2026-11-14 22:13:19', '2026-11-14 22:13:19'),
(9, 31, 298, NULL, NULL, 'Capacity: 8Gb', 4500.00, 6000.00, 'none', 0, 1, '2026-11-14 22:13:19', '2026-11-14 22:13:19'),
(10, 31, 298, NULL, NULL, 'Capacity: 16Gb', 6500.00, 7500.00, 'none', 1, 1, '2026-11-14 22:13:19', '2026-11-14 22:13:19'),
(11, 31, 298, NULL, NULL, 'Capacity: 32Gb', 7000.00, 8500.00, 'none', 0, 1, '2026-11-14 22:13:19', '2026-11-14 22:13:19'),
(12, 31, 298, NULL, NULL, 'Capacity: 64Gb', 8500.00, 10000.00, 'none', 0, 1, '2026-11-14 22:13:19', '2026-11-14 22:13:19'),
(13, 31, 298, NULL, NULL, 'Capacity: 128Gb', 14000.00, 18000.00, 'none', 0, 1, '2026-11-14 22:13:19', '2026-11-14 22:13:19'),
(14, 31, 298, NULL, NULL, 'Capacity: 256Gb', 18000.00, 23000.00, 'none', 0, 1, '2026-11-14 22:13:19', '2026-11-14 22:13:19');

-- --------------------------------------------------------

--
-- Table structure for table `product_variant_stocks`
--

CREATE TABLE `product_variant_stocks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` bigint(20) UNSIGNED NOT NULL,
  `location_id` int(11) NOT NULL,
  `quantity` decimal(14,2) NOT NULL DEFAULT 0.00,
  `reorder_level` decimal(14,2) NOT NULL DEFAULT 0.00,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_variant_stocks`
--

INSERT INTO `product_variant_stocks` (`id`, `company_id`, `product_id`, `variant_id`, `location_id`, `quantity`, `reorder_level`, `updated_at`) VALUES
(1, 31, 296, 1, 90, 19.00, 0.00, '2026-11-16 07:53:56'),
(2, 31, 296, 1, 91, 7.00, 0.00, '2026-11-13 22:02:35'),
(3, 31, 297, 5, 90, 5.00, 0.00, '2026-11-14 21:14:30'),
(4, 31, 297, 6, 90, 3.00, 0.00, '2026-11-14 21:15:17'),
(5, 31, 297, 7, 90, 7.00, 0.00, '2026-11-14 21:16:11'),
(6, 31, 298, 9, 90, 12.00, 0.00, '2026-11-14 22:30:09'),
(8, 31, 298, 11, 90, 14.00, 0.00, '2026-11-14 22:33:11'),
(9, 31, 298, 12, 90, 8.00, 0.00, '2026-11-14 22:32:21'),
(11, 31, 297, 7, 91, 3.00, 0.00, '2026-11-14 22:44:09');

-- --------------------------------------------------------

--
-- Table structure for table `product_variant_values`
--

CREATE TABLE `product_variant_values` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `variant_id` bigint(20) UNSIGNED NOT NULL,
  `attribute_id` bigint(20) UNSIGNED NOT NULL,
  `attribute_value_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_variant_values`
--

INSERT INTO `product_variant_values` (`id`, `variant_id`, `attribute_id`, `attribute_value_id`) VALUES
(1, 1, 3, 9),
(2, 2, 3, 10),
(3, 3, 3, 11),
(4, 4, 3, 12),
(5, 5, 4, 13),
(6, 6, 4, 14),
(7, 7, 4, 15),
(8, 8, 5, 16),
(9, 9, 5, 17),
(10, 10, 5, 18),
(11, 11, 5, 19),
(12, 12, 5, 20),
(13, 13, 5, 21),
(14, 14, 5, 22);

-- --------------------------------------------------------

--
-- Table structure for table `reg_email`
--

CREATE TABLE `reg_email` (
  `id` int(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `customer_id` int(100) NOT NULL,
  `company_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_phone` varchar(50) DEFAULT NULL,
  `customer_email` varchar(255) NOT NULL,
  `grand_total` decimal(14,2) NOT NULL,
  `discount_percent` int(100) NOT NULL,
  `discount_amount` decimal(14,2) NOT NULL,
  `final_total` decimal(14,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` varchar(100) NOT NULL,
  `payment_status` varchar(20) DEFAULT 'full',
  `amount_paid` decimal(12,2) DEFAULT 0.00,
  `balance` decimal(12,2) DEFAULT 0.00,
  `due_date` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `invoice_number`, `customer_id`, `company_id`, `location_id`, `user_id`, `customer_name`, `customer_phone`, `customer_email`, `grand_total`, `discount_percent`, `discount_amount`, `final_total`, `notes`, `created_at`, `payment_status`, `amount_paid`, `balance`, `due_date`) VALUES
(349, 'EXA-SALES-20261114-000001', 105, 31, 90, 120, 'Baba cally', '08050563014', 'callymus23@yahoo.com', 4000.00, 0, 0.00, 4000.00, 'Allhamdu Lillah', '2026-11-14 22:35:42', 'full', 4000.00, 0.00, '0'),
(350, 'EXA-SALES-20261115-000002', 106, 31, 91, 236, 'usman baba musa', '45678', 'ubmgt@hotmail.com', 784000.00, 10, 78400.00, 705600.00, 'Maa Sha Allah', '2026-11-14 23:01:14', 'partial', 600000.00, 105600.00, '2026-11-25'),
(351, 'EXA-SALES-20261115-000003', 106, 31, 91, 236, 'usman baba musa', '45678', 'ubmgt@hotmail.com', 392000.00, 0, 0.00, 392000.00, 'Maa Sha Allah', '2026-11-14 23:15:48', 'full', 392000.00, 0.00, '0'),
(352, 'UGT-SALES-20261116-000001', 104, 31, 90, 216, 'Omoeheffe', '07045915907', 'henryakiojano671@gmail.com', 10000.00, 0, 0.00, 10000.00, 'Thank You', '2026-11-16 07:53:52', 'full', 10000.00, 0.00, '0'),
(353, 'UGT-SALES-20261116-000002', 104, 31, 90, 216, 'Omoeheffe', '07045915907', 'henryakiojano671@gmail.com', 10000.00, 0, 0.00, 10000.00, 'Thank You', '2026-11-16 07:53:56', 'full', 10000.00, 0.00, '0');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `unit_price` decimal(14,2) NOT NULL,
  `total_price` decimal(14,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `variant_id`, `quantity`, `unit_price`, `total_price`) VALUES
(360, 349, 298, NULL, 1.00, 4000.00, 4000.00),
(361, 350, 297, NULL, 2.00, 392000.00, 784000.00),
(362, 351, 297, NULL, 1.00, 392000.00, 392000.00),
(363, 352, 296, 1, 2.00, 5000.00, 10000.00),
(364, 353, 296, 1, 2.00, 5000.00, 10000.00);

-- --------------------------------------------------------

--
-- Table structure for table `sale_item_serials`
--

CREATE TABLE `sale_item_serials` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sale_item_id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `serial_id` bigint(20) UNSIGNED NOT NULL,
  `imei` char(15) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `identifier_type` enum('imei','serial','vin') NOT NULL DEFAULT 'imei',
  `identifier_value` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `category_id` int(100) NOT NULL,
  `location_id` int(11) NOT NULL,
  `service_number` varchar(100) NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `materials_cost` decimal(18,2) DEFAULT 0.00,
  `external_skill_cost` decimal(18,2) DEFAULT 0.00,
  `total_cost` decimal(18,2) DEFAULT 0.00,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_image`
--

CREATE TABLE `service_image` (
  `id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_items`
--

CREATE TABLE `service_items` (
  `id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_used` decimal(18,2) NOT NULL,
  `unit_cost` decimal(18,2) NOT NULL,
  `total_cost` decimal(18,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_items`
--

INSERT INTO `service_items` (`id`, `service_id`, `product_id`, `quantity_used`, `unit_cost`, `total_cost`, `created_at`) VALUES
(25, 41, 275, 30.00, 4000.00, 120000.00, '2026-09-23 14:32:14'),
(26, 42, 280, 3.00, 8000.00, 24000.00, '2026-10-17 15:28:50'),
(27, 43, 280, 1.00, 8000.00, 8000.00, '2026-10-31 00:02:59'),
(28, 44, 280, 10.00, 8000.00, 80000.00, '2026-10-31 00:31:04'),
(29, 45, 280, 10.00, 8000.00, 80000.00, '2026-10-31 00:52:19');

-- --------------------------------------------------------

--
-- Table structure for table `service_skills`
--

CREATE TABLE `service_skills` (
  `id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `skill_name` varchar(255) NOT NULL,
  `fee` decimal(18,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `slides`
--

CREATE TABLE `slides` (
  `id` int(100) NOT NULL,
  `company_id` int(100) NOT NULL,
  `created_by` varchar(100) NOT NULL,
  `slide_order` int(100) NOT NULL,
  `headline` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `desktop_image` text NOT NULL,
  `mobile_image` text NOT NULL,
  `created_at` int(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `slides`
--

INSERT INTO `slides` (`id`, `company_id`, `created_by`, `slide_order`, `headline`, `description`, `desktop_image`, `mobile_image`, `created_at`) VALUES
(51, 81, 'ubamusglobal@gmail.com', 1, 'Welcome to Excess Led and Sign', 'Coming Soon', 'desktop_81_1_c84f34dd0aa95b8a.jpg', 'mobile_81_1_e572d224d2739b60.jpg', 2026),
(52, 81, 'ubamusglobal@gmail.com', 2, 'Our Show Room', 'Coming Soon', 'desktop_81_2_a54db8535eff58fd.webp', 'mobile_81_2_2ecfa6b51dcf9944.jpg', 2026),
(53, 81, 'ubamusglobal@gmail.com', 3, 'ASAP', 'Coming soon', 'desktop_81_3_3ea4ec4adf85b5e0.jpg', 'mobile_81_3_381b0f5c5f00f4a1.jpg', 2026),
(54, 81, 'ubamusglobal@gmail.com', 4, 'ASAP', 'Coming soon', 'desktop_81_4_d73785668413eb24.jpg', 'mobile_81_4_b0a46a1ceea02444.jpg', 2026);

-- --------------------------------------------------------

--
-- Table structure for table `staffs`
--

CREATE TABLE `staffs` (
  `id` int(100) NOT NULL,
  `company_id` int(100) NOT NULL,
  `location_id` int(100) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(100) NOT NULL,
  `role` varchar(100) NOT NULL,
  `address` varchar(100) NOT NULL,
  `profile` text NOT NULL,
  `refree1name` varchar(100) NOT NULL,
  `refree1phone` varchar(100) NOT NULL,
  `refree2name` varchar(100) NOT NULL,
  `refree2phone` varchar(100) NOT NULL,
  `created_at` timestamp(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  `created_by` varchar(100) NOT NULL,
  `status` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staffs`
--

INSERT INTO `staffs` (`id`, `company_id`, `location_id`, `firstname`, `lastname`, `email`, `phone`, `role`, `address`, `profile`, `refree1name`, `refree1phone`, `refree2name`, `refree2phone`, `created_at`, `created_by`, `status`) VALUES
(98, 31, 90, 'Musa', 'Baba Usman', 'engineermusa01@yahoo.com', '08050563014', 'Head of Administrator', 'sjahdajhdkhdjhsdahkdada', 'uploads/staff/staff_6a94475ebf3603.99602620.png', 'dsjksdhsajkahk', '9848948298', 'dkjfklsfsj`', '932498923', '2026-11-13 17:18:18.309039', 'henry001@ubamus.com', 'active'),
(99, 31, 90, 'Henro', 'Akiojano', 'henry55prince@gmail.com', '08029259733', 'Support', 'zsfdadsaasdadasdsada', 'uploads/staff/staff_6a9457e9744807.78674029.jpg', 'sdaasdada', '2421324242', 'czcxzczxcz', '234223423', '2026-11-13 17:18:22.558658', 'henry55prince@gmail.com', 'active'),
(183, 81, 87, 'Danjuma', 'Usman', 'ubamusglobal@gmail.com', '7860', 'Head of Administrator', 'PH One', 'uploads/staff/staff_6ab0510c8af454.58229510.jpg', 'Baba', '6543', 'Nma', '3456', '2026-09-23 10:43:51.031299', 'Administration', 'active'),
(184, 81, 87, 'ca', 'shaier', 'info@ubamus.com', '56789', 'Cashier', 'Onne, Eleme', 'uploads/staff/staff_6ab14388196de7.27831606.png', 'tuyig', '45678', 'nbm', '5656', '2026-09-21 15:47:36.000000', 'ubamusglobal@gmail.com', 'active'),
(185, 31, 90, 'Henry', 'Omeheffe', 'scripted345@gmail.com', '08038853742', 'Head of Administrator', 'bfjhsdnbmcnbxmbcm', 'uploads/staff/staff_6ad39aa02c2863.51817504.png', 'jksdajhkj', 'jfhdsfdjh', 'jhdfjkshk', 'djhsfhsds', '2026-11-13 17:19:08.063170', 'engineermusa01@yahoo.com', 'active'),
(186, 31, 90, 'Obatala', 'Omads', 'theobatala333@gmail.com', '7283676286328', 'Cashier', 'zhcxbmnzmnzm', 'uploads/staff/staff_6ad4c116a26390.14815631.jpeg', 'sjkdfhjksjkh', '3487289371979', 'jhdkjhj', '11283727', '2026-11-13 17:19:04.345536', 'scripted345@gmail.com', 'active'),
(187, 31, 90, 'Peter', 'sadas', 'pet34@gmail.com', '23132131', 'Branch Manager', 'jksdhasaj', 'uploads/staff/staff_6ad4e318bd33c3.50818519.jpeg', 'kdlsfsjlkfd', '342342', 'kxczkjzjk', '3423423', '2026-11-13 17:19:00.191591', 'scripted345@gmail.com', 'active'),
(188, 31, 90, 'henry', 'operator', 'henryop@gmail.com', '1221311312', 'Operation Manager', 'jkzjhcjkxkjcxz', 'uploads/staff/staff_6ad6156cbf64b8.51488382.jpeg', 'sjkdkjhkjh', '839749789327', 'jdhjkdhdjh', '9832298332', '2026-11-13 17:18:56.062127', 'scripted345@gmail.com', 'active'),
(189, 31, 90, 'Ba', 'Cally', 'manager@ubamus.com', '567898', 'Operation Manager', 'PH', 'uploads/staff/staff_6addc03d344a92.51499846.jpeg', 'Nma', '45684', 'Zee', '6875', '2026-11-13 17:18:52.404083', 'engineermusa01@yahoo.com', 'active'),
(191, 31, 90, 'Great', 'Igbunu', 'igbunu1@gmail.com', '2398279873192', 'Branch Manager', 'jkdhfjsdhkjh', 'uploads/staff/staff_a28eb5d3e4094cfe0934ea666aa9fbbd.jpg', 'sdfhjsdkhfjkh', '3489772387', 'sjfdhkhsdjkfh', '34287642873', '2026-11-13 17:18:48.341568', 'pet34@gmail.com', 'active'),
(192, 31, 91, 'usman', 'musa', 'engineermusa01@hotmail.com', '08050563014', 'Head of Administrator', 'PH', 'uploads/staff/staff_6af8e64cf38157.44794884.jpg', 'Nma', '4556789', 'Kam', '456789', '2026-11-14 23:53:00.000000', 'engineermusa01@yahoo.com', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `stocks`
--

CREATE TABLE `stocks` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `quantity` int(100) DEFAULT NULL,
  `supplier` varchar(100) NOT NULL,
  `created_at` varchar(100) DEFAULT NULL,
  `stocked_by` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stocks`
--

INSERT INTO `stocks` (`id`, `product_id`, `company_id`, `location_id`, `quantity`, `supplier`, `created_at`, `stocked_by`) VALUES
(173, 272, 81, 87, 49, '33', '2026-09-21 06:33:39', '214'),
(174, 273, 81, 87, 55, '33', '2026-09-21 06:34:41', '214'),
(175, 274, 81, 87, 61, '33', '2026-09-21 06:35:19', '214'),
(176, 275, 81, 87, 400, '33', '2026-09-23 14:19:35', '214'),
(186, 296, 31, 90, 25, '37', '2026-11-13 14:52:30', '216'),
(187, 296, 31, 91, 7, 'Transfer', '2026-11-13 22:02:35', '216'),
(188, 297, 31, 90, 15, '38', '2026-11-14 21:16:11', '120'),
(189, 298, 31, 90, 33, '39', '2026-11-14 22:33:11', '120'),
(190, 289, 31, 90, 4, '37', '2026-11-14 22:38:30', '120'),
(191, 297, 31, 91, 0, '37', '2026-11-14 22:44:09', '120');

-- --------------------------------------------------------

--
-- Table structure for table `stocks_assigned`
--

CREATE TABLE `stocks_assigned` (
  `id` int(100) NOT NULL,
  `stock_id` int(100) NOT NULL,
  `company_id` int(100) NOT NULL,
  `location_id` int(100) NOT NULL,
  `assigned_by` int(100) NOT NULL,
  `assigned_to` int(100) NOT NULL,
  `quantity` int(100) NOT NULL,
  `date` varchar(50) NOT NULL,
  `time` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_in`
--

CREATE TABLE `stock_in` (
  `id` int(100) NOT NULL,
  `product_id` int(100) NOT NULL,
  `variant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `stock_id` int(100) NOT NULL,
  `company_id` int(100) NOT NULL,
  `location_id` int(100) NOT NULL,
  `transfer_ID` varchar(100) NOT NULL,
  `quantity` int(100) NOT NULL,
  `type` varchar(100) NOT NULL,
  `supplier` text NOT NULL,
  `created_at` varchar(100) NOT NULL,
  `recorded_by` int(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_in`
--

INSERT INTO `stock_in` (`id`, `product_id`, `variant_id`, `stock_id`, `company_id`, `location_id`, `transfer_ID`, `quantity`, `type`, `supplier`, `created_at`, `recorded_by`) VALUES
(179, 272, NULL, 173, 81, 87, '', 50, 'Initial Stock Entry', '33', '2026-09-21 06:33:39', 214),
(180, 273, NULL, 174, 81, 87, '', 60, 'Initial Stock Entry', '33', '2026-09-21 06:34:41', 214),
(181, 274, NULL, 175, 81, 87, '', 70, 'Initial Stock Entry', '33', '2026-09-21 06:35:19', 214),
(182, 272, NULL, 173, 81, 87, 'RESTOCK-173-20260921074328', 5, 'Restock', '33', '2026-09-21 07:43:28', 214),
(183, 275, NULL, 176, 81, 87, '', 400, 'Initial Stock Entry', '33', '2026-09-23 14:19:35', 214),
(196, 296, 1, 186, 31, 90, 'STOCK-IN-20261113145230-DEB9A36D', 20, 'Initial Stock Entry', '37', '2026-11-13 14:52:30', 216),
(197, 296, 1, 186, 31, 90, 'RESTOCK-186-20261113182053-V1', 10, 'Restock', '37', '2026-11-13 18:20:53', 216),
(198, 296, 1, 187, 31, 91, 'TRANSFER-186-20261113220235-8f6a45', 7, 'Transfer Destination', '37', '2026-11-13 22:02:35', 216),
(199, 296, 1, 186, 31, 90, 'TRANSFER-186-20261113220235-8f6a45', 23, 'Inventory Restocked (via Transfer)', '37', '2026-11-13 22:02:35', 216),
(200, 297, 5, 188, 31, 90, 'STOCK-IN-20261114221430-9FBE200C', 5, 'Initial Stock Entry', '38', '2026-11-14 21:14:30', 120),
(201, 297, 6, 188, 31, 90, 'STOCK-IN-20261114221517-D329BDCB', 3, 'Restock', '38', '2026-11-14 21:15:17', 120),
(202, 297, 7, 188, 31, 90, 'STOCK-IN-20261114221611-AF0BEA5D', 7, 'Restock', '38', '2026-11-14 21:16:11', 120),
(203, 298, 9, 189, 31, 90, 'STOCK-IN-20261114232908-F2C8A095', 5, 'Initial Stock Entry', '39', '2026-11-14 22:29:08', 120),
(204, 298, 9, 189, 31, 90, 'STOCK-IN-20261114233009-63D1002A', 7, 'Restock', '39', '2026-11-14 22:30:09', 120),
(205, 298, 11, 189, 31, 90, 'STOCK-IN-20261114233134-75AC9081', 8, 'Restock', '39', '2026-11-14 22:31:34', 120),
(206, 298, 12, 189, 31, 90, 'STOCK-IN-20261114233221-30011AB6', 8, 'Restock', '39', '2026-11-14 22:32:21', 120),
(207, 298, 11, 189, 31, 90, 'STOCK-IN-20261114233311-DC76831D', 6, 'Restock', '39', '2026-11-14 22:33:11', 120),
(208, 289, NULL, 190, 31, 90, 'STOCK-IN-20261114233830-DA40B187', 3, 'Initial Stock Entry', '37', '2026-11-14 22:38:30', 120),
(209, 289, NULL, 190, 31, 90, 'RESTOCK-190-20261114234106', 1, 'Restock', '37', '2026-11-14 23:41:06', 120),
(210, 296, NULL, 186, 31, 90, 'RESTOCK-186-20261114234223', 2, 'Restock', '37', '2026-11-14 23:42:23', 120),
(211, 297, 7, 191, 31, 91, 'STOCK-IN-20261114234409-A8621238', 3, 'Initial Stock Entry', '37', '2026-11-14 22:44:09', 120);

-- --------------------------------------------------------

--
-- Table structure for table `stock_in_invoices`
--

CREATE TABLE `stock_in_invoices` (
  `id` int(100) NOT NULL,
  `stock_in_id` int(100) NOT NULL,
  `company_id` int(100) NOT NULL,
  `file_path` varchar(100) NOT NULL,
  `original_filename` text NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size` varchar(100) NOT NULL,
  `uploaded_by` varchar(100) NOT NULL,
  `created_at` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_in_invoices`
--

INSERT INTO `stock_in_invoices` (`id`, `stock_in_id`, `company_id`, `file_path`, `original_filename`, `mime_type`, `file_size`, `uploaded_by`, `created_at`) VALUES
(1, 188, 31, '../uploads/stock_in_invoices/1edb81be3837ca874c3571b21cae94d973b203f4df434310.png', 'Safer Operations, Stronger Tomorrow.png', 'image/png', '1987166', '216', '2026-10-29 16:07:45'),
(2, 189, 31, '../uploads/stock_in_invoices/ce7cad569a665068f1e3e4ff46e95ed632bc681023a95780.jpeg', 'WhatsApp Image 2026-10-04 at 7.55.34 PM.jpeg', 'image/jpeg', '117550', '216', '2026-10-29 16:49:21'),
(3, 190, 31, '../uploads/stock_in_invoices/78e033a0ea529fbba56f783d27ca67f52bbe60f50ddbeb9b.png', 'expen.png', 'image/png', '320096', '216', '2026-11-07 14:29:21'),
(4, 191, 31, '../uploads/stock_in_invoices/9002ede67507813389cd239727982b779f8ba87832b1bd17.png', 'Screenshot 2026-11-02 210252.png', 'image/png', '77683', '216', '2026-11-07 18:21:43'),
(5, 192, 31, '../uploads/stock_in_invoices/1e8f5915e5b9edf450203581d7d00696d8df8aed56850ed9.png', 'Screenshot 2026-11-02 211032.png', 'image/png', '207126', '216', '2026-11-07 18:30:10'),
(6, 193, 31, '../uploads/stock_in_invoices/d1ab0725bbfc8a97b40e543625b514136b813831af2475d0.jpg', '2cf1d895905da5e21b3a29be7914208e.jpg', 'image/jpeg', '151373', '216', '2026-11-08 14:57:57'),
(7, 194, 31, '../uploads/stock_in_invoices/840914cdb93593a27eae4202a1b978f5dc06c7d4d95b7199.jpg', 'fdfa2e52c09320ef5bbca426f3cf765d.jpg', 'image/jpeg', '106710', '216', '2026-11-08 15:03:03'),
(8, 195, 31, '../uploads/stock_in_invoices/86be8b73c3aaf636125f2cea310bd1d9554a5139704df4ae.jpg', 'wp2551216-sad-boy-wallpaper.jpg', 'image/jpeg', '210901', '218', '2026-11-09 17:46:23'),
(9, 196, 31, '../uploads/stock_in_invoices/61419ef747a1d440145b33de9b03f8539d50ab6481fd9382.png', 'Falling in Love with Our Father.png', 'image/png', '2455483', '216', '2026-11-13 14:52:30'),
(10, 197, 31, '../uploads/stock_in_invoices/327e4fec347820a50cc80edafaf28895fb88a28d07b173ab.png', 'Falling in Love with Our Father.png', 'image/png', '2455483', '216', '2026-11-13 18:20:53');

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `stock_id` int(100) NOT NULL,
  `from_location_id` int(11) DEFAULT NULL,
  `to_location_id` int(11) DEFAULT NULL,
  `transfer_ID` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `movement_type` varchar(100) NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `company_id`, `product_id`, `variant_id`, `stock_id`, `from_location_id`, `to_location_id`, `transfer_ID`, `quantity`, `movement_type`, `reference`, `created_by`, `created_at`) VALUES
(75, 31, 296, 1, 186, 90, 91, 'TRANSFER-186-20261113220235-8f6a45', 7, 'Transfer', 'TRANSFER-186-20261113220235-8f6a45', 216, '2026-11-13 21:02:35');

-- --------------------------------------------------------

--
-- Table structure for table `stock_production`
--

CREATE TABLE `stock_production` (
  `id` int(100) NOT NULL,
  `product_id` int(100) NOT NULL,
  `company_id` int(100) NOT NULL,
  `location_id` int(100) NOT NULL,
  `batch_number` varchar(100) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `report_number` varchar(100) NOT NULL,
  `category_id` int(100) NOT NULL,
  `quantity_produced` int(100) NOT NULL,
  `description` text NOT NULL,
  `unit_cost` decimal(50,0) NOT NULL,
  `total_cost` decimal(50,0) NOT NULL,
  `profit_margin` varchar(100) NOT NULL,
  `unit_selling_price` decimal(50,0) NOT NULL,
  `unit_profit` decimal(14,2) NOT NULL,
  `total_profit` decimal(14,2) NOT NULL,
  `expenses_breakdown` text NOT NULL,
  `status` varchar(20) NOT NULL,
  `created_by` int(50) NOT NULL,
  `created_at` varchar(50) NOT NULL,
  `updated_at` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_production`
--

INSERT INTO `stock_production` (`id`, `product_id`, `company_id`, `location_id`, `batch_number`, `product_name`, `report_number`, `category_id`, `quantity_produced`, `description`, `unit_cost`, `total_cost`, `profit_margin`, `unit_selling_price`, `unit_profit`, `total_profit`, `expenses_breakdown`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(165, 277, 81, 88, 'ELAS-B260923-670', 'jhkhskj', 'ELAS-PR-260923-0001', 171, 600, 'hjsjhsha', 44828, 26896793, '0', 44828, 0.00, 0.00, '{\"materials\":26868121,\"services\":28672}', 'In Progress', 214, '2026-09-23 14:35:50', '2026-09-23 14:35:50'),
(166, 281, 31, 89, 'UGT-B261017-696', 'Chi Orange Juice', 'UGT-PR-261017-0002', 174, 400, 'jhsajgdsagdjgsdhasd', 138, 55019, '20', 193, 0.00, 0.00, '{\"materials\":15009,\"services\":40010}', '', 120, '2026-10-17 15:39:05', '2026-10-26 21:19:23'),
(167, 282, 31, 89, 'UGT-B261026-219', 'Digital', 'UGT-PR-261026-0003', 174, 5, '', 25, 127, '20', 6, 0.00, 0.00, '{\"materials\":34,\"services\":93}', '', 120, '2026-10-26 21:22:37', '2026-10-26 21:28:18');

-- --------------------------------------------------------

--
-- Table structure for table `stock_production_stages`
--

CREATE TABLE `stock_production_stages` (
  `id` int(100) NOT NULL,
  `stock_production_id` int(100) NOT NULL,
  `product_id` int(100) NOT NULL,
  `company_id` int(100) NOT NULL,
  `location_id` int(100) NOT NULL,
  `batch_number` varchar(100) NOT NULL,
  `report_number` varchar(100) NOT NULL,
  `quantity_produced` int(100) NOT NULL,
  `previous_quantity_produced` int(100) NOT NULL,
  `unit_cost` decimal(14,2) NOT NULL,
  `previous_unit_cost` decimal(14,2) NOT NULL,
  `total_cost` decimal(14,2) NOT NULL,
  `previous_total_cost` decimal(14,2) NOT NULL,
  `profit_margin` int(10) NOT NULL,
  `previous_profit_margin` int(10) NOT NULL,
  `unit_selling_price` decimal(14,2) NOT NULL,
  `previous_unit_selling_price` decimal(14,2) NOT NULL,
  `expenses_break_down` varchar(100) NOT NULL,
  `previous_expenses_break_down` varchar(100) NOT NULL,
  `status` varchar(100) NOT NULL,
  `created_by` int(100) NOT NULL,
  `created_at` varchar(100) NOT NULL,
  `updated_at` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_production_stages`
--

INSERT INTO `stock_production_stages` (`id`, `stock_production_id`, `product_id`, `company_id`, `location_id`, `batch_number`, `report_number`, `quantity_produced`, `previous_quantity_produced`, `unit_cost`, `previous_unit_cost`, `total_cost`, `previous_total_cost`, `profit_margin`, `previous_profit_margin`, `unit_selling_price`, `previous_unit_selling_price`, `expenses_break_down`, `previous_expenses_break_down`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(72, 166, 281, 31, 89, 'UGT-B261017-696', 'UGT-PR-261026-0001', 0, 400, 0.00, 138.00, 19.00, 55000.00, 20, 40, 22.80, 193.00, '{\"materials\":9,\"services\":10}', '{\"materials\":15000,\"services\":40000}', '', 120, '2026-10-17 15:39:05', '2026-10-26 22:19:23'),
(73, 167, 282, 31, 89, 'UGT-B261026-219', 'UGT-PR-261026-0002', 0, 5, 0.00, 6.00, 17.00, 32.00, 0, 0, 17.00, 6.00, '{\"materials\":14,\"services\":3}', '{\"materials\":12,\"services\":20}', 'In Progress', 120, '2026-10-26 21:22:37', '2026-10-26 22:24:10'),
(74, 167, 282, 31, 89, 'UGT-B261026-219', 'UGT-PR-261026-0003', 0, 5, 0.00, 10.00, 78.00, 49.00, 0, 0, 78.00, 6.00, '{\"materials\":8,\"services\":70}', '{\"materials\":26,\"services\":23}', 'In Progress', 120, '2026-10-26 21:22:37', '2026-10-26 22:26:03'),
(75, 167, 282, 31, 89, 'UGT-B261026-219', 'UGT-PR-261026-0004', 0, 5, 0.00, 25.00, 0.00, 127.00, 0, 0, 0.00, 6.00, '{\"materials\":0,\"services\":0}', '{\"materials\":34,\"services\":93}', 'In Progress', 120, '2026-10-26 21:22:37', '2026-10-26 22:27:08'),
(76, 167, 282, 31, 89, 'UGT-B261026-219', 'UGT-PR-261026-0005', 0, 5, 0.00, 25.00, 0.00, 127.00, 20, 0, 0.00, 6.00, '{\"materials\":0,\"services\":0}', '{\"materials\":34,\"services\":93}', '', 120, '2026-10-26 21:22:37', '2026-10-26 22:28:18');

-- --------------------------------------------------------

--
-- Table structure for table `stock_usage`
--

CREATE TABLE `stock_usage` (
  `id` int(100) NOT NULL,
  `product_id` int(100) NOT NULL,
  `quantity_used` varchar(100) NOT NULL,
  `used_for` varchar(100) NOT NULL,
  `reference_id` int(100) NOT NULL,
  `recorded_by` varchar(100) NOT NULL,
  `created_at` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_usage`
--

INSERT INTO `stock_usage` (`id`, `product_id`, `quantity_used`, `used_for`, `reference_id`, `recorded_by`, `created_at`) VALUES
(25, 275, '30', 'Service', 41, '214', '2026-09-23 14:32:14'),
(26, 280, '3', 'Service', 42, '120', '2026-10-17 15:28:50'),
(27, 280, '1', 'Service', 43, '216', '2026-10-31 01:02:59'),
(28, 280, '10', 'Service', 44, '216', '2026-10-31 01:31:04'),
(29, 280, '3', 'Job', 64, '216', '2026-10-31 01:54:15');

-- --------------------------------------------------------

--
-- Table structure for table `terms_conditions`
--

CREATE TABLE `terms_conditions` (
  `id` int(11) NOT NULL,
  `company_id` int(100) NOT NULL,
  `eyebrow` varchar(255) DEFAULT 'Website Terms',
  `title` varchar(255) NOT NULL DEFAULT 'Terms & Conditions',
  `intro` text DEFAULT NULL,
  `acceptance_title` varchar(255) DEFAULT 'Acceptance of Terms',
  `acceptance_content` longtext DEFAULT NULL,
  `website_use_title` varchar(255) DEFAULT 'Use of This Website',
  `website_use_content` longtext DEFAULT NULL,
  `intellectual_property_title` varchar(255) DEFAULT 'Intellectual Property',
  `intellectual_property_content` longtext DEFAULT NULL,
  `website_information_title` varchar(255) DEFAULT 'Website Information',
  `website_information_content` longtext DEFAULT NULL,
  `liability_title` varchar(255) DEFAULT 'Limitation of Liability',
  `liability_content` longtext DEFAULT NULL,
  `third_party_title` varchar(255) DEFAULT 'Third-Party Services',
  `third_party_content` longtext DEFAULT NULL,
  `changes_title` varchar(255) DEFAULT 'Changes to These Terms',
  `changes_content` longtext DEFAULT NULL,
  `contact_title` varchar(255) DEFAULT 'Contact',
  `contact_content` longtext DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `terms_conditions`
--

INSERT INTO `terms_conditions` (`id`, `company_id`, `eyebrow`, `title`, `intro`, `acceptance_title`, `acceptance_content`, `website_use_title`, `website_use_content`, `intellectual_property_title`, `intellectual_property_content`, `website_information_title`, `website_information_content`, `liability_title`, `liability_content`, `third_party_title`, `third_party_content`, `changes_title`, `changes_content`, `contact_title`, `contact_content`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 31, 'Who we are', 'Followup', 'we are one', 'Acceptance of Terms', 'djfkhjfkshs', 'Use of Our Website and Services', 'sdkfjsljfdlkjs', 'Intellectual Property Rights', 's,mdnfs,nfm,nfd', 'Website Information', ',msdfm,nfs,fsmfs,', 'Limitation of Liability', 'dsnfshjksdfjks', 'Third-Party Services and Links', 'asdmshjkajsa', 'Changes to These Terms and Conditions', 'sadkjhkdhajdsa', 'Contact Us', 'dn,manmandm,da', 'Active', '98', '2026-09-03 17:32:39', '2026-09-03 17:07:17');

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` int(100) NOT NULL,
  `company_id` int(100) NOT NULL,
  `unit_name` varchar(100) NOT NULL,
  `unit_type` varchar(100) NOT NULL,
  `created_by` int(100) NOT NULL,
  `created_at` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`id`, `company_id`, `unit_name`, `unit_type`, `created_by`, `created_at`) VALUES
(96, 81, 'LED', 'General / Universal Units', 214, '2026-09-21 06:23:50'),
(97, 31, 'Plot', 'Container', 120, '2026-10-17 14:46:12'),
(98, 31, 'Computer', 'General / Universal Units', 120, '2026-11-13 02:56:53'),
(99, 31, 'Mobile Davice', 'General / Universal Units', 120, '2026-11-13 03:02:55'),
(100, 31, 'Paper', 'Paper / Flat Items', 120, '2026-11-13 05:26:57');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  `company_id` int(100) NOT NULL,
  `location_id` int(50) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `created_at` varchar(100) NOT NULL,
  `created_by` varchar(100) NOT NULL,
  `changed_at` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `company_id`, `location_id`, `status`, `created_at`, `created_by`, `changed_at`) VALUES
(120, 'Musa Baba Usman', 'engineermusa01@yahoo.com', '$2y$10$gfuGl1xiZLlpGPL.1Mesb.WgFEctHX5UfYYH2gjmkHmFF45PYrGAW', 'Head of Administrator', 31, 90, 'active', '2026-07-02 10:06:22', 'engineermusa01@yahoo.com', '2026-10-26 22:16:29'),
(121, 'Henro Akiojano', 'henry55prince@gmail.com', '$2y$10$xCeQZl8s7NkmHr6qvUrO5.RWfLGAkFa66QHOxu2RrxkXEynYoKibG', 'Support', 31, 90, 'active', '2026-07-05 17:33:53', 'nmamus1@gmail.com', '2026-10-17 14:44:31'),
(214, 'Danjuma Usman', 'ubamusglobal@gmail.com', '$2y$10$sWblsPheZwlIDbndW2/rb.3h/Jr9GJvhUxV68io4UZ.UsdXJ19XpC', 'Head of Administrator', 81, 87, 'active', '2026-09-20 22:33:03', 'ubamusglobal@gmail.com', '2026-10-21 09:10:03'),
(215, 'ca shaier', 'info@ubamus.com', '$2y$10$N2S9bDl/6yWfg4PGE/KG8eGK52LNpGmiL/WVLvVjoNZ21zymjhZSK', 'Cashier', 81, 87, 'active', '2026-09-21 15:47:38', 'info@ubamus.com', '2026-09-21 15:50:55'),
(216, 'Henry Omeheffe', 'scripted345@gmail.com', '$2y$10$TK5Y/MjhxKBhzNDt0EERJeGYouR159SJ0E2Xk/tB/Nncrf7nZuTFa', 'Head of Administrator', 31, 90, 'active', '2026-10-17 16:56:20', 'scripted345@gmail.com', '2026-10-17 17:25:11'),
(217, 'Obatala Omads', 'theobatala333@gmail.com', '$2y$10$lQamOCezg7jDrbewmrq8/uF5qpmevx4aEtMHcX5PmFxQkT2OTZo7K', 'Cashier', 31, 90, 'active', '2026-10-18 13:52:40', 'theobatala333@gmail.com', '2026-10-18 13:56:27'),
(218, 'Peter sadas', 'pet34@gmail.com', '$2y$10$OMvmiOnY5RDWcsP8LLAVjuVP5n2tA4BB8ThkV8akym3JwP.7qHvNG', 'Branch Manager', 31, 90, 'active', '2026-10-18 16:17:47', 'pet34@gmail.com', '2026-10-18 16:37:14'),
(219, 'henry operator', 'henryop@gmail.com', '$2y$10$F2WSuqO2n.b7ip8mzz/rse7dz1PV0VeIFE8fMmtVo2reeG8hPsjKu', 'Operation Manager', 31, 90, 'active', '2026-10-19 14:04:46', 'henryop@gmail.com', '2026-10-19 14:06:18'),
(220, 'Ba Cally', 'manager@ubamus.com', '$2y$10$NilXSTK45zOtz5mjcy9H4.cZ90HJXbODKQVShZ.YesaHfmIdZNLze', 'Operation Manager', 31, 90, 'active', '2026-10-25 09:39:27', 'manager@ubamus.com', '2026-10-25 09:42:24'),
(221, 'Great Igbunu', 'igbunu1@gmail.com', '$2y$10$Qq0h41cvPwfisYjWxO1QxuEk0YQJ3F8MME0QaUuyYMYuj7wnvz9ii', 'Branch Manager', 31, 90, 'active', '2026-11-09 23:57:33', 'igbunu1@gmail.com', '2026-11-09 23:57:33'),
(230, 'Henro', 'scripted34@gmail.com', '$2y$10$N2hEwxLm.fU8in5WagZyKus.5Qtf9iIJK9aEX53cooIaa0asr4niK', 'customer', 31, 90, 'active', '2026-11-11 21:18:42', 'Marketplace', '2026-11-11 21:19:04'),
(234, 'Omoeheffe', 'henryakiojano671@gmail.com', '$2y$10$8MqzACXfygxzUn7d5tX7Euie0tR1h5asv0ehrh..WaLQ6IaknNNSa', 'customer', 31, 90, 'active', '2026-11-12 16:35:11', 'Marketplace', '2026-11-12 16:35:34'),
(235, 'Baba cally', 'callymus23@yahoo.com', '$2y$10$o2PZxkSAi9j5PbyHPwjQ.ucUCOWAOWQqmhvCn9v29dD9jFLyHopvu', 'customer', 31, 90, 'active', '2026-11-14 20:52:28', 'Marketplace', '2026-11-14 20:53:50'),
(236, 'usman musa', 'engineermusa01@hotmail.com', '$2y$10$T3lCtqooWKVBEYa8yBm.tOrE/tZsD7Xv3a/NPI2ez8L83AOIRkXEu', 'Head of Administrator', 31, 91, 'active', '2026-11-14 23:53:03', 'engineermusa01@hotmail.com', '2026-11-14 23:56:00');

-- --------------------------------------------------------

--
-- Table structure for table `vendors`
--

CREATE TABLE `vendors` (
  `id` int(100) NOT NULL,
  `company_id` int(100) NOT NULL,
  `location_id` int(100) NOT NULL,
  `business_name` varchar(100) NOT NULL,
  `office_address` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  `created_by` varchar(100) NOT NULL,
  `created_at` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vendors`
--

INSERT INTO `vendors` (`id`, `company_id`, `location_id`, `business_name`, `office_address`, `email`, `mobile`, `description`, `created_by`, `created_at`, `status`) VALUES
(33, 81, 89, 'Cakum', 'Kano Street', 'cakum@hot.com', '4576879', 'salesman', 'ubamusglobal@gmail.com', '2026-09-20 22:39:46', 'active'),
(34, 31, 89, 'Capital City Real Estate', 'sdjfhsjhdksfj', 'capital@gndms.com', '822738732979', 'kjsdfsjhsjkfhjs', 'engineermusa01@yahoo.com', '2026-10-17 16:14:27', 'active'),
(35, 31, 89, 'Chana Clubs', 'dhfkjshkfh', 'chanac@gmail.com', '48789738979', 'fddjkfdhsjkhfksjfd', 'pet34@gmail.com', '2026-11-10 12:38:28', 'active'),
(36, 31, 89, 'Capanu', 'ksjdshfhskj', 'capanu@gmail.com', '2389217231873192', 'sdfsdfsfsd', 'scripted345@gmail.com', '2026-11-10 12:53:02', 'active'),
(37, 31, 89, 'Import', 'USA', 'ubmgt@hotmail.com', '45678', 'Foreign Goods and Services', 'engineermusa01@yahoo.com', '2026-11-13 03:31:55', 'active'),
(38, 31, 89, 'Baba samsung', 'Aba Rd', 'babasam@gmail.com', '45678', 'Phones and Tab dealer', 'engineermusa01@yahoo.com', '2026-11-13 03:53:25', 'active'),
(39, 31, 89, 'Nedu', 'Ikwere', 'nedu@gmail.com', '3456798', 'Computer and Accessories dealer', 'engineermusa01@yahoo.com', '2026-11-13 03:55:54', 'active'),
(40, 31, 89, 'Omoudu', 'Ikwere', 'uma@gmail.com', '345678', 'Stationaries', 'engineermusa01@yahoo.com', '2026-11-13 06:25:53', 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `about`
--
ALTER TABLE `about`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `about_sections`
--
ALTER TABLE `about_sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_about_id` (`about_id`),
  ADD KEY `idx_company_id` (`company_id`),
  ADD KEY `idx_display_order` (`display_order`);

--
-- Indexes for table `chat_conversations`
--
ALTER TABLE `chat_conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_chat_scope` (`company_id`,`location_id`,`updated_at`),
  ADD KEY `idx_chat_creator` (`created_by`);

--
-- Indexes for table `chat_live_activity`
--
ALTER TABLE `chat_live_activity`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_live_activity` (`conversation_id`,`user_id`,`activity_type`),
  ADD KEY `idx_activity_expiry` (`expires_at`),
  ADD KEY `idx_activity_conversation` (`conversation_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_chat_message` (`conversation_id`,`id`),
  ADD KEY `idx_chat_sender` (`sender_id`);

--
-- Indexes for table `chat_message_deletions`
--
ALTER TABLE `chat_message_deletions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_chat_message_delete` (`message_id`,`user_id`,`participant_type`),
  ADD KEY `idx_chat_delete_user` (`user_id`,`participant_type`);

--
-- Indexes for table `chat_message_receipts`
--
ALTER TABLE `chat_message_receipts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_message_recipient` (`message_id`,`user_id`),
  ADD KEY `idx_receipt_user` (`company_id`,`user_id`),
  ADD KEY `idx_receipt_message` (`message_id`);

--
-- Indexes for table `chat_participants`
--
ALTER TABLE `chat_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_chat_typed_participant` (`conversation_id`,`participant_type`,`user_id`),
  ADD KEY `idx_chat_user` (`user_id`,`conversation_id`);

--
-- Indexes for table `chat_user_activity`
--
ALTER TABLE `chat_user_activity`
  ADD PRIMARY KEY (`conversation_id`,`user_id`),
  ADD KEY `idx_chat_activity_recent` (`conversation_id`,`updated_at`);

--
-- Indexes for table `chat_user_presence`
--
ALTER TABLE `chat_user_presence`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_presence_user` (`company_id`,`user_id`),
  ADD KEY `idx_presence_location` (`company_id`,`location_id`);

--
-- Indexes for table `chat_user_profiles`
--
ALTER TABLE `chat_user_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_chat_user` (`company_id`,`user_id`);

--
-- Indexes for table `chat_user_sessions`
--
ALTER TABLE `chat_user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_chat_session_hash` (`session_hash`),
  ADD KEY `idx_chat_sessions_presence` (`company_id`,`user_id`,`ended_at`,`expires_at`),
  ADD KEY `idx_chat_sessions_last_activity` (`company_id`,`user_id`,`last_activity_at`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customer_accounts`
--
ALTER TABLE `customer_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_customer_account` (`company_id`,`customer_id`),
  ADD UNIQUE KEY `uq_customer_login_email` (`company_id`,`login_email`),
  ADD KEY `idx_customer_account_status` (`company_id`,`account_status`),
  ADD KEY `idx_customer_activation` (`activation_token_hash`);

--
-- Indexes for table `customer_awards`
--
ALTER TABLE `customer_awards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `certificate_number` (`certificate_number`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `location_id` (`location_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `customer_debts`
--
ALTER TABLE `customer_debts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deleted_jobs`
--
ALTER TABLE `deleted_jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deleted_products`
--
ALTER TABLE `deleted_products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deleted_product_images`
--
ALTER TABLE `deleted_product_images`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deleted_stocks`
--
ALTER TABLE `deleted_stocks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deleted_stock_in`
--
ALTER TABLE `deleted_stock_in`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deleted_stock_movement`
--
ALTER TABLE `deleted_stock_movement`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `job_categories`
--
ALTER TABLE `job_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `job_items`
--
ALTER TABLE `job_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `marketplace_carts`
--
ALTER TABLE `marketplace_carts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_marketplace_cart_account_company` (`company_id`,`customer_account_id`),
  ADD KEY `idx_marketplace_cart_customer` (`company_id`,`customer_id`,`cart_status`);

--
-- Indexes for table `marketplace_cart_events`
--
ALTER TABLE `marketplace_cart_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cart_event_customer` (`company_id`,`customer_id`),
  ADD KEY `idx_cart_event_cart` (`cart_id`),
  ADD KEY `idx_cart_event_item` (`cart_item_id`),
  ADD KEY `idx_cart_event_date` (`created_at`);

--
-- Indexes for table `marketplace_cart_items`
--
ALTER TABLE `marketplace_cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_marketplace_cart_line_key` (`cart_id`,`line_key`),
  ADD KEY `idx_marketplace_cart_items_cart` (`cart_id`),
  ADD KEY `idx_marketplace_cart_items_catalogue` (`company_id`,`item_type`,`item_id`),
  ADD KEY `idx_cart_variant` (`variant_id`);

--
-- Indexes for table `marketplace_customer_otps`
--
ALTER TABLE `marketplace_customer_otps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_customer_otp` (`company_id`,`email`,`purpose`,`expires_at`);

--
-- Indexes for table `marketplace_orders`
--
ALTER TABLE `marketplace_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_marketplace_order_number` (`order_number`),
  ADD KEY `idx_marketplace_company_customer` (`company_id`,`customer_id`),
  ADD KEY `idx_marketplace_order_status` (`company_id`,`order_status`);

--
-- Indexes for table `marketplace_order_assignments`
--
ALTER TABLE `marketplace_order_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_assignment_company_order` (`company_id`,`order_id`),
  ADD KEY `idx_assignment_location` (`company_id`,`location_id`),
  ADD KEY `idx_assignment_user` (`assigned_user_id`);

--
-- Indexes for table `marketplace_order_items`
--
ALTER TABLE `marketplace_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_marketplace_item_order` (`order_id`),
  ADD KEY `idx_marketplace_item_company` (`company_id`,`item_type`,`item_id`),
  ADD KEY `idx_order_item_variant` (`variant_id`);

--
-- Indexes for table `marketplace_service_settings`
--
ALTER TABLE `marketplace_service_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_marketplace_service_setting` (`company_id`,`service_id`),
  ADD KEY `idx_marketplace_service_availability` (`company_id`,`is_available`);

--
-- Indexes for table `otp_verification`
--
ALTER TABLE `otp_verification`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `paid_dept`
--
ALTER TABLE `paid_dept`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `privacy_policy`
--
ALTER TABLE `privacy_policy`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `production_raw_materials`
--
ALTER TABLE `production_raw_materials`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `production_services`
--
ALTER TABLE `production_services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `production_team`
--
ALTER TABLE `production_team`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `production_team_members`
--
ALTER TABLE `production_team_members`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_attributes`
--
ALTER TABLE `product_attributes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_attribute_product` (`company_id`,`product_id`),
  ADD KEY `idx_attribute_status` (`status`);

--
-- Indexes for table `product_attribute_values`
--
ALTER TABLE `product_attribute_values`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_attribute_value` (`attribute_id`,`value_name`),
  ADD KEY `idx_attribute_value_order` (`attribute_id`,`display_order`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `parent_id` (`unit_type`);

--
-- Indexes for table `product_features`
--
ALTER TABLE `product_features`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_media`
--
ALTER TABLE `product_media`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_product_media_legacy_image` (`legacy_image_id`),
  ADD KEY `idx_product_media_product_order` (`product_id`,`display_order`,`id`),
  ADD KEY `idx_product_media_company_product` (`company_id`,`product_id`),
  ADD KEY `idx_product_media_type` (`media_type`),
  ADD KEY `idx_product_media_variant` (`variant_id`);

--
-- Indexes for table `product_serials`
--
ALTER TABLE `product_serials`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_product_identifier` (`company_id`,`identifier_type`,`identifier_value`),
  ADD UNIQUE KEY `uq_product_serials_imei` (`imei`),
  ADD KEY `idx_serial_product_company` (`product_id`,`company_id`),
  ADD KEY `idx_serial_stock_location` (`stock_id`,`location_id`),
  ADD KEY `idx_serial_status` (`company_id`,`status`),
  ADD KEY `idx_serial_current_sale` (`current_sale_item_id`),
  ADD KEY `idx_serial_variant` (`variant_id`);

--
-- Indexes for table `product_serial_events`
--
ALTER TABLE `product_serial_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_serial_events_serial` (`serial_id`,`created_at`),
  ADD KEY `idx_serial_events_company` (`company_id`,`created_at`),
  ADD KEY `idx_serial_events_sale` (`sale_id`),
  ADD KEY `idx_serial_events_type` (`event_type`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_variant_company_sku` (`company_id`,`sku`),
  ADD UNIQUE KEY `uq_variant_company_barcode` (`company_id`,`barcode`),
  ADD KEY `idx_variant_product` (`company_id`,`product_id`),
  ADD KEY `idx_variant_active` (`product_id`,`is_active`);

--
-- Indexes for table `product_variant_stocks`
--
ALTER TABLE `product_variant_stocks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_variant_location` (`company_id`,`variant_id`,`location_id`),
  ADD KEY `idx_variant_stock_product` (`company_id`,`product_id`),
  ADD KEY `idx_variant_stock_location` (`location_id`),
  ADD KEY `idx_variant_stock_quantity` (`quantity`);

--
-- Indexes for table `product_variant_values`
--
ALTER TABLE `product_variant_values`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_variant_attribute` (`variant_id`,`attribute_id`),
  ADD KEY `idx_variant_value` (`attribute_value_id`),
  ADD KEY `idx_variant_attribute` (`attribute_id`);

--
-- Indexes for table `reg_email`
--
ALTER TABLE `reg_email`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `idx_sale_item_variant` (`variant_id`);

--
-- Indexes for table `sale_item_serials`
--
ALTER TABLE `sale_item_serials`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sale_item_serial` (`sale_item_id`,`serial_id`),
  ADD KEY `idx_sale_serial` (`sale_id`),
  ADD KEY `idx_serial_sale_history` (`serial_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `service_number` (`service_number`);

--
-- Indexes for table `service_image`
--
ALTER TABLE `service_image`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `service_items`
--
ALTER TABLE `service_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `service_skills`
--
ALTER TABLE `service_skills`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `slides`
--
ALTER TABLE `slides`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staffs`
--
ALTER TABLE `staffs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staffs_company_email` (`company_id`,`email`);

--
-- Indexes for table `stocks`
--
ALTER TABLE `stocks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `stocks_assigned`
--
ALTER TABLE `stocks_assigned`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stock_in`
--
ALTER TABLE `stock_in`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_stock_in_variant` (`variant_id`);

--
-- Indexes for table `stock_in_invoices`
--
ALTER TABLE `stock_in_invoices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_stock_movement_variant` (`variant_id`);

--
-- Indexes for table `stock_production`
--
ALTER TABLE `stock_production`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stock_production_stages`
--
ALTER TABLE `stock_production_stages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stock_usage`
--
ALTER TABLE `stock_usage`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `terms_conditions`
--
ALTER TABLE `terms_conditions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_users_messenger_scope` (`company_id`,`location_id`,`status`);

--
-- Indexes for table `vendors`
--
ALTER TABLE `vendors`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `about`
--
ALTER TABLE `about`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `about_sections`
--
ALTER TABLE `about_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `chat_conversations`
--
ALTER TABLE `chat_conversations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `chat_live_activity`
--
ALTER TABLE `chat_live_activity`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `chat_message_deletions`
--
ALTER TABLE `chat_message_deletions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_message_receipts`
--
ALTER TABLE `chat_message_receipts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_participants`
--
ALTER TABLE `chat_participants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `chat_user_presence`
--
ALTER TABLE `chat_user_presence`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=598;

--
-- AUTO_INCREMENT for table `chat_user_profiles`
--
ALTER TABLE `chat_user_profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_user_sessions`
--
ALTER TABLE `chat_user_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT for table `customer_accounts`
--
ALTER TABLE `customer_accounts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `customer_awards`
--
ALTER TABLE `customer_awards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_debts`
--
ALTER TABLE `customer_debts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=173;

--
-- AUTO_INCREMENT for table `deleted_jobs`
--
ALTER TABLE `deleted_jobs`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `deleted_products`
--
ALTER TABLE `deleted_products`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `deleted_product_images`
--
ALTER TABLE `deleted_product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `deleted_stocks`
--
ALTER TABLE `deleted_stocks`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `deleted_stock_in`
--
ALTER TABLE `deleted_stock_in`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `deleted_stock_movement`
--
ALTER TABLE `deleted_stock_movement`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `job_categories`
--
ALTER TABLE `job_categories`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `job_items`
--
ALTER TABLE `job_items`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `marketplace_carts`
--
ALTER TABLE `marketplace_carts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `marketplace_cart_events`
--
ALTER TABLE `marketplace_cart_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `marketplace_cart_items`
--
ALTER TABLE `marketplace_cart_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `marketplace_customer_otps`
--
ALTER TABLE `marketplace_customer_otps`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `marketplace_orders`
--
ALTER TABLE `marketplace_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `marketplace_order_assignments`
--
ALTER TABLE `marketplace_order_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `marketplace_order_items`
--
ALTER TABLE `marketplace_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `marketplace_service_settings`
--
ALTER TABLE `marketplace_service_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `otp_verification`
--
ALTER TABLE `otp_verification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=328;

--
-- AUTO_INCREMENT for table `paid_dept`
--
ALTER TABLE `paid_dept`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `privacy_policy`
--
ALTER TABLE `privacy_policy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `production_raw_materials`
--
ALTER TABLE `production_raw_materials`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=205;

--
-- AUTO_INCREMENT for table `production_services`
--
ALTER TABLE `production_services`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT for table `production_team`
--
ALTER TABLE `production_team`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `production_team_members`
--
ALTER TABLE `production_team_members`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=299;

--
-- AUTO_INCREMENT for table `product_attributes`
--
ALTER TABLE `product_attributes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `product_attribute_values`
--
ALTER TABLE `product_attribute_values`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=181;

--
-- AUTO_INCREMENT for table `product_features`
--
ALTER TABLE `product_features`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=195;

--
-- AUTO_INCREMENT for table `product_media`
--
ALTER TABLE `product_media`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `product_serials`
--
ALTER TABLE `product_serials`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `product_serial_events`
--
ALTER TABLE `product_serial_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `product_variant_stocks`
--
ALTER TABLE `product_variant_stocks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `product_variant_values`
--
ALTER TABLE `product_variant_values`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `reg_email`
--
ALTER TABLE `reg_email`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=354;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=365;

--
-- AUTO_INCREMENT for table `sale_item_serials`
--
ALTER TABLE `sale_item_serials`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `service_image`
--
ALTER TABLE `service_image`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `service_items`
--
ALTER TABLE `service_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `service_skills`
--
ALTER TABLE `service_skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `slides`
--
ALTER TABLE `slides`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `staffs`
--
ALTER TABLE `staffs`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=193;

--
-- AUTO_INCREMENT for table `stocks`
--
ALTER TABLE `stocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=192;

--
-- AUTO_INCREMENT for table `stocks_assigned`
--
ALTER TABLE `stocks_assigned`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `stock_in`
--
ALTER TABLE `stock_in`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=212;

--
-- AUTO_INCREMENT for table `stock_in_invoices`
--
ALTER TABLE `stock_in_invoices`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `stock_production`
--
ALTER TABLE `stock_production`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=168;

--
-- AUTO_INCREMENT for table `stock_production_stages`
--
ALTER TABLE `stock_production_stages`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `stock_usage`
--
ALTER TABLE `stock_usage`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `terms_conditions`
--
ALTER TABLE `terms_conditions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=237;

--
-- AUTO_INCREMENT for table `vendors`
--
ALTER TABLE `vendors`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `service_image`
--
ALTER TABLE `service_image`
  ADD CONSTRAINT `fk_service_image_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

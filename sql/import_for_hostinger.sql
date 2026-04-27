-- Hostinger import for u138580725_cateyes
-- Run install_triggers_procedures.php after import for triggers!

SET FOREIGN_KEY_CHECKS=0;
USE `u138580725_cateyes`;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 14, 2026 at 09:59 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u744799766_cat_eye`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(136, 3, 'admin_login', 'Admin logged in', '2400:adc7:1136:f100:893f:d2eb:24d7:a7e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-01 08:45:28'),
(137, 3, 'admin_logout', 'Admin logged out', '2400:adc7:1136:f100:893f:d2eb:24d7:a7e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-01 08:45:38'),
(138, 3, 'admin_login', 'Admin logged in', '2400:adc7:1136:f100:893f:d2eb:24d7:a7e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-01 08:45:42'),
(139, 3, 'admin_logout', 'Admin logged out', '2400:adc7:1136:f100:893f:d2eb:24d7:a7e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-01 08:45:46'),
(140, 3, 'admin_login', 'Admin logged in', '2400:adc7:1136:f100:893f:d2eb:24d7:a7e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-01 08:46:18'),
(141, 3, 'admin_logout', 'Admin logged out', '2400:adc7:1136:f100:893f:d2eb:24d7:a7e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-01 08:46:34'),
(142, 3, 'admin_login', 'Admin logged in', '2400:adc7:1136:f100:893f:d2eb:24d7:a7e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-01 08:47:23'),
(143, 3, 'admin_login', 'Admin logged in', '185.76.178.217', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Avast/145.0.34271.162', '2026-04-01 08:49:55'),
(144, 3, 'admin_login', 'Admin logged in', '2400:adc7:1136:f100:45b0:c2cf:f995:cb97', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-04-01 08:50:39'),
(145, 3, 'admin_logout', 'Admin logged out', '185.76.178.217', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Avast/145.0.34271.162', '2026-04-01 09:34:03'),
(146, NULL, 'admin_login', 'Admin logged in', '185.76.178.217', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Avast/145.0.34271.162', '2026-04-01 09:34:17'),
(147, NULL, 'admin_logout', 'Admin logged out', '185.76.178.217', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Avast/145.0.34271.162', '2026-04-01 09:36:50'),
(148, 3, 'admin_login', 'Admin logged in', '185.76.178.217', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Avast/145.0.34271.162', '2026-04-01 09:37:02'),
(149, 3, 'admin_logout', 'Admin logged out', '185.76.178.246', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Avast/145.0.34271.162', '2026-04-01 13:44:47'),
(150, 3, 'admin_login', 'Admin logged in', '2400:adc7:1136:f100:e086:941c:5f5f:2fa8', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-01 18:25:23'),
(151, 3, 'admin_login', 'Admin logged in', '185.76.178.226', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Avast/145.0.34271.162', '2026-04-02 17:29:33'),
(152, 3, 'admin_login', 'Admin logged in', '2400:adc7:1136:f100:91ac:923c:8f03:b517', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 17:22:16'),
(153, 3, 'admin_login', 'Admin logged in', '185.76.178.226', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Avast/145.0.34271.162', '2026-04-03 17:41:54'),
(154, 3, 'admin_login', 'Admin logged in', '185.97.95.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Avast/145.0.34271.162', '2026-04-06 14:50:55'),
(155, 3, 'admin_login', 'Admin logged in', '185.97.94.121', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Avast/145.0.34271.162', '2026-04-07 15:34:11'),
(156, 3, 'admin_logout', 'Admin logged out', '185.97.94.121', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Avast/145.0.34271.162', '2026-04-07 15:42:08'),
(157, 3, 'admin_login', 'Admin logged in', '185.97.94.121', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Avast/145.0.34271.162', '2026-04-07 15:42:31'),
(158, 3, 'admin_logout', 'Admin logged out', '185.97.94.121', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Avast/145.0.34271.162', '2026-04-07 15:42:42'),
(159, 3, 'admin_login', 'Admin logged in', '2400:adc7:1136:f100:b8ab:97ce:1977:6529', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 07:18:35'),
(160, 3, 'admin_login', 'Admin logged in', '185.76.178.247', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 09:22:59'),
(161, 3, 'admin_logout', 'Admin logged out', '185.76.178.247', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 10:31:27'),
(162, NULL, 'admin_login', 'Admin logged in', '185.76.178.247', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 10:31:38'),
(163, NULL, 'admin_logout', 'Admin logged out', '185.76.178.247', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 10:31:57'),
(164, 3, 'admin_login', 'Admin logged in', '185.76.178.247', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-13 10:32:08'),
(165, 3, 'admin_login', 'Admin logged in', '182.180.155.210', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-13 11:58:24'),
(166, 3, 'admin_login', 'Admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-13 15:45:57'),
(167, 3, 'admin_logout', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-13 16:15:22'),
(168, 3, 'admin_login', 'Admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-13 16:15:24');

-- --------------------------------------------------------

--
-- Table structure for table `admin_roles`
--

CREATE TABLE `admin_roles` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '[]' CHECK (json_valid(`permissions`)),
  `is_system` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'System roles cannot be deleted',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_roles`
--

INSERT INTO `admin_roles` (`id`, `name`, `slug`, `description`, `permissions`, `is_system`, `created_at`, `updated_at`) VALUES
(1, 'Administrator', 'admin', 'Full access to all features', '[\"dashboard\", \"products\", \"categories\", \"brands\", \"orders\", \"customers\", \"bookings\", \"services\", \"inventory\", \"reports\", \"coupons\", \"newsletter\", \"shipping\", \"settings\", \"users\", \"roles\", \"appearance\", \"payment\", \"languages\", \"currencies\", \"email_settings\", \"social_settings\", \"blogs\", \"banners\"]', 1, '2026-02-27 16:07:03', '2026-03-18 10:34:21'),
(2, 'Shop Manager', 'shop_manager', 'Manages products, orders, inventory and customers', '[\"blogs\",\"banners\"]', 1, '2026-02-27 16:07:03', '2026-04-13 10:30:58'),
(3, 'Editor', 'editor', 'Manages content like blogs, banners, and appearance', '[\"categories\",\"brands\",\"customers\",\"bookings\",\"services\",\"inventory\",\"reports\",\"coupons\",\"newsletter\",\"shipping\",\"settings\",\"appearance\",\"payment\",\"languages\",\"currencies\",\"email_settings\",\"social_settings\",\"users\",\"roles\",\"blogs\",\"banners\"]', 1, '2026-02-27 16:07:03', '2026-03-20 12:42:04'),
(4, 'Manager', 'manager', '', '[\"dashboard\",\"products\",\"categories\",\"brands\",\"orders\",\"customers\",\"bookings\",\"services\",\"inventory\",\"reports\",\"coupons\",\"newsletter\",\"shipping\",\"settings\",\"appearance\",\"payment\",\"languages\",\"currencies\",\"email_settings\",\"social_settings\",\"users\",\"roles\",\"blogs\",\"banners\"]', 0, '2026-03-15 09:40:56', '2026-03-15 09:40:56');

-- --------------------------------------------------------

--
-- Table structure for table `attributes`
--

CREATE TABLE `attributes` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('color','size','text','dropdown') DEFAULT 'text',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attributes`
--

INSERT INTO `attributes` (`id`, `name`, `type`, `is_active`, `created_at`) VALUES
(1, 'Color', 'color', 1, '2026-01-04 07:48:55');

-- --------------------------------------------------------

--
-- Table structure for table `attribute_values`
--

CREATE TABLE `attribute_values` (
  `id` int(11) NOT NULL,
  `attribute_id` int(11) NOT NULL,
  `value` varchar(255) NOT NULL,
  `color_code` varchar(20) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attribute_values`
--

INSERT INTO `attribute_values` (`id`, `attribute_id`, `value`, `color_code`, `sort_order`) VALUES
(1, 1, 'Nude Pink', '#e59f9f', 1),
(2, 1, 'Soft Pink', '#f49999', 2),
(3, 1, 'Rose Pink', '#d77979', 3),
(4, 1, 'Bright Red', '#ff0000', 4),
(9, 1, 'Peach Nude', '#d8775f', 0),
(10, 1, 'Nude Pink - وردي فاتح', '#000000', 0),
(11, 1, 'Rose Pink', '#000000', 0),
(12, 1, 'Bright Red أحمر ناصع', '#000000', 0),
(13, 1, 'Soft Pink', '#000000', 0);

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `id` int(11) NOT NULL,
  `position` enum('home_slider','home_top','home_middle','sidebar') DEFAULT 'home_slider',
  `image` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `banners`
--

INSERT INTO `banners` (`id`, `position`, `image`, `link`, `is_active`, `sort_order`, `start_date`, `end_date`, `created_at`) VALUES
(2, 'home_slider', '1769539761_1767774777_49c6a347208321.5873e043bb323.jpg', 'https://cateyesbeauty.com/shop.php?search=&sort=newest&category=0&brand=46&min_price=0&max_price=0', 1, 2, '2026-01-06 09:04:00', NULL, '2026-01-06 07:04:15'),
(3, 'home_slider', '1767774799_AdobeStock_276301504.webp', 'https://cateyesbeauty.com/blog-detail.php?slug=top-10-beauty-tips-2026', 1, 3, '2026-01-06 09:04:00', NULL, '2026-01-06 07:04:15'),
(12, 'home_slider', '1769544015_depositphotos_6620439-stock-photo-young-women-getting-facial-mask.jpg', 'http://localhost/project/book-service.php?service=13', 1, 1, '2026-01-06 09:04:00', NULL, '2026-01-06 07:04:35');

-- --------------------------------------------------------

--
-- Table structure for table `banner_translations`
--

CREATE TABLE `banner_translations` (
  `id` int(11) NOT NULL,
  `banner_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `button_text` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `banner_translations`
--

INSERT INTO `banner_translations` (`id`, `banner_id`, `language_id`, `title`, `subtitle`, `button_text`) VALUES
(2, 2, 1, 'Cat Eye Special Collection of Beauty Products', 'Especially for you', 'Shop Now'),
(3, 3, 1, 'Beauty Tips & Trends', 'Read our latest blog posts for beauty advice', 'Read Blog'),
(9, 2, 2, '???????????? ?????? ???? ???????????? ???? ???????????? ?????????????? ', '???????????? ?????????? ????', '???????? ????????'),
(10, 3, 2, '?????????? ???????????????? ????????????', '?????????? ???????? ???????????? ?????????????? ???????????? ?????? ?????????? ????????????', '???????? ??????????????'),
(30, 12, 1, 'The future of professional aesthetics is already here', 'MEGAN by SKEYNDOR', 'Book Now'),
(32, 12, 2, '???????????? ?????????????? ?????????????????? ?????????? ???????????? ??????', '?????????? ???? ?????????? ????????????', '???????? ????????');

-- --------------------------------------------------------

--
-- Table structure for table `blog_categories`
--

CREATE TABLE `blog_categories` (
  `id` int(11) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `blog_categories`
--

INSERT INTO `blog_categories` (`id`, `slug`, `is_active`, `created_at`) VALUES
(1, 'beauty-tips', 1, '2026-01-05 19:29:08'),
(2, 'hair-care-guide', 1, '2026-01-05 19:29:08'),
(3, 'skin-care', 1, '2026-01-05 19:29:08'),
(4, 'wellness', 1, '2026-01-05 19:29:08'),
(5, 'trends', 1, '2026-01-05 19:29:08');

-- --------------------------------------------------------

--
-- Table structure for table `blog_category_translations`
--

CREATE TABLE `blog_category_translations` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `blog_category_translations`
--

INSERT INTO `blog_category_translations` (`id`, `category_id`, `language_id`, `name`, `description`) VALUES
(1, 1, 1, 'Beauty Tips', 'Expert beauty advice and tips'),
(2, 2, 1, 'Hair Care Guide', 'Complete guide to hair care and styling'),
(3, 3, 1, 'Skin Care', 'Skin care routines and treatments'),
(4, 4, 1, 'Wellness', 'Health and wellness articles'),
(5, 5, 1, 'Trends', 'Latest beauty and fashion trends'),
(6, 1, 2, '?????????? ????????????', '?????????? ???????????? ???? ???????? ????????????'),
(7, 2, 2, '???????? ?????????????? ????????????', '???????? ???????? ?????????????? ???????????? ??????????????'),
(8, 3, 2, '?????????????? ??????????????', '?????????????? ?????????????? ?????????????? ??????????????????'),
(9, 4, 2, '?????????? ????????????????', '???????????? ?????????? ????????????????'),
(10, 5, 2, '?????????????? ????????????', '???????? ?????????????? ???????????? ??????????????');

-- --------------------------------------------------------

--
-- Table structure for table `blog_posts`
--

CREATE TABLE `blog_posts` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `published_at` datetime DEFAULT NULL,
  `views_count` int(11) DEFAULT 0,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `blog_posts`
--

INSERT INTO `blog_posts` (`id`, `category_id`, `author_id`, `slug`, `featured_image`, `is_published`, `is_featured`, `published_at`, `views_count`, `meta_title`, `meta_description`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 'top-10-beauty-tips-2026', 'blog_69cbc7c44969f.webp', 1, 1, '2025-12-06 21:33:08', 3295, '', '', '2026-01-05 19:29:23', '2026-04-12 21:08:44'),
(2, 2, 3, 'hair-care-winter-guide', 'blog_69d8a93d1773e.png', 1, 0, '2025-05-14 21:33:08', 2404, '', '', '2026-01-05 19:29:23', '2026-04-10 07:39:41'),
(3, 3, 3, 'daily-skin-care-routine', 'skin-care-routine.jpg', 1, 1, '2025-01-08 21:33:08', 4182, '', '', '2026-01-05 19:29:23', '2026-04-11 10:39:09'),
(4, 4, 3, 'meditation-for-beauty', 'meditation-beauty.jpg', 1, 0, '2025-12-28 21:33:08', 3733, '', '', '2026-01-05 19:29:23', '2026-04-08 01:01:47'),
(5, 5, 3, 'summer-beauty-trends-2026', 'summer-trends-2026.jpg', 1, 1, '2025-11-17 21:33:08', 3455, 'Summer Beauty Trends 2026 | What\'s Hot', 'Explore the hottest summer beauty trends for 2026 from makeup to hairstyles.', '2026-01-05 19:29:23', '2026-04-13 02:40:32');

-- --------------------------------------------------------

--
-- Table structure for table `blog_post_translations`
--

CREATE TABLE `blog_post_translations` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `excerpt` text DEFAULT NULL,
  `content` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `blog_post_translations`
--

INSERT INTO `blog_post_translations` (`id`, `post_id`, `language_id`, `title`, `excerpt`, `content`) VALUES
(1, 1, 1, 'Top 10 Beauty Tips for 2026', 'Discover the most effective beauty tips and trends for the upcoming year.', '<p>As we approach 2026, the beauty industry continues to evolve with innovative techniques and products. Here are our top 10 beauty tips for 2026:</p>\r\n<p>1. <strong>Double Cleansing:</strong> Start with an oil-based cleanser followed by a water-based one for perfectly clean skin.</p>\r\n<p>2. <strong>Hyaluronic Acid Serums:</strong> Hydration is key! Use hyaluronic acid serums for plump, moisturized skin.</p>\r\n<p>3. <strong>SPF Every Day:</strong> Never skip sunscreen, even on cloudy days or when indoors.</p>\r\n<p>4. <strong>Customized Skincare:</strong> More brands are offering personalized skincare based on your skin\'s unique needs.</p>\r\n<p>5. <strong>Blue Light Protection:</strong> With increased screen time, protect your skin from blue light damage.</p>\r\n<p>6. <strong>Sustainable Beauty:</strong> Choose eco-friendly packaging and cruelty-free products.</p>\r\n<p>7. <strong>Facial Massage:</strong> Incorporate facial massage into your routine for better circulation.</p>\r\n<p>8. <strong>Probiotic Skincare:</strong> Balance your skin\'s microbiome with probiotic-infused products.</p>\r\n<p>9. <strong>Minimalist Makeup:</strong> The \"skin-first\" approach with lightweight, natural-looking makeup.</p>\r\n<p>10. <strong>Sleeping Masks:</strong> Overnight treatments that work while you sleep for maximum results.</p>'),
(2, 2, 1, 'Complete Winter Hair Care Guide', 'Protect your hair from winter damage with these essential tips.', '<p>Winter can be harsh on your hair, causing dryness, static, and breakage. Follow this comprehensive guide to keep your hair healthy all season long:</p>\r\n<h3>1. Moisture is Key</h3>\r\n<p>Switch to moisturizing shampoos and conditioners during winter. Look for ingredients like argan oil, shea butter, and coconut oil.</p>\r\n<h3>2. Reduce Washing Frequency</h3>\r\n<p>Washing your hair too often can strip natural oils. Try to wash every 2-3 days instead of daily.</p>\r\n<h3>3. Deep Conditioning Treatments</h3>\r\n<p>Use a deep conditioning mask at least once a week to restore moisture and prevent breakage.</p>\r\n<h3>4. Protect from Cold</h3>\r\n<p>Always wear a hat or scarf when going outside to protect your hair from cold winds and temperature changes.</p>\r\n<h3>5. Avoid Hot Tools</h3>\r\n<p>Minimize the use of hot styling tools like straighteners and curling irons during winter months.</p>\r\n<h3>6. Regular Trims</h3>\r\n<p>Get regular trims every 6-8 weeks to prevent split ends and maintain hair health.</p>'),
(3, 3, 1, 'Daily Skin Care Routine for Glowing Skin', 'Achieve radiant skin with this simple daily routine.', '<p>A consistent daily skin care routine is essential for maintaining healthy, glowing skin. Follow these steps morning and night:</p>\r\n<h3>Morning Routine:</h3>\r\n<ol>\r\n<li><strong>Cleanser:</strong> Start with a gentle cleanser to remove overnight impurities.</li>\r\n<li><strong>Toner:</strong> Balance your skin\'s pH with an alcohol-free toner.</li>\r\n<li><strong>Serum:</strong> Apply vitamin C serum for antioxidant protection and brightening.</li>\r\n<li><strong>Eye Cream:</strong> Gently pat eye cream to hydrate the delicate eye area.</li>\r\n<li><strong>Moisturizer:</strong> Lock in moisture with a lightweight, non-comedogenic moisturizer.</li>\r\n<li><strong>Sunscreen:</strong> Always finish with broad-spectrum SPF 30 or higher.</li>\r\n</ol>\r\n<h3>Evening Routine:</h3>\r\n<ol>\r\n<li><strong>Makeup Remover:</strong> Start with an oil-based makeup remover if wearing makeup.</li>\r\n<li><strong>Cleanser:</strong> Use a gentle cleanser to remove dirt and pollution.</li>\r\n<li><strong>Exfoliate (2-3 times weekly):</strong> Use chemical exfoliants like AHAs or BHAs.</li>\r\n<li><strong>Treatment:</strong> Apply retinol or treatment serums as needed.</li>\r\n<li><strong>Moisturizer:</strong> Use a richer moisturizer or night cream.</li>\r\n<li><strong>Facial Oil (optional):</strong> Seal everything in with a few drops of facial oil.</li>\r\n</ol>'),
(4, 4, 1, 'Meditation for Inner Beauty and Wellness', 'How mindfulness practices can enhance your natural beauty.', '<p>True beauty starts from within. Meditation and mindfulness practices can significantly impact your appearance and overall wellness:</p>\r\n<h3>The Mind-Skin Connection</h3>\r\n<p>Stress directly affects your skin by increasing cortisol levels, which can lead to inflammation, acne, and premature aging. Meditation helps reduce stress and its visible effects.</p>\r\n<h3>Benefits of Meditation for Beauty:</h3>\r\n<ul>\r\n<li><strong>Reduced Stress:</strong> Lower cortisol levels mean less inflammation and clearer skin</li>\r\n<li><strong>Better Sleep:</strong> Quality sleep is essential for skin repair and regeneration</li>\r\n<li><strong>Glowing Complexion:</strong> Improved circulation gives you a natural glow</li>\r\n<li><strong>Reduced Puffiness:</strong> Less stress means less fluid retention and puffiness</li>\r\n<li><strong>Anti-Aging:</strong> Meditation can slow down cellular aging</li>\r\n</ul>\r\n<h3>Simple Meditation Techniques:</h3>\r\n<p>Start with just 5-10 minutes daily. Focus on your breath, practice gratitude, or use guided meditation apps. Consistency is key to seeing benefits.</p>'),
(5, 5, 1, 'Summer Beauty Trends 2026', 'What\'s hot in beauty for summer 2026.', '<p>Summer 2026 brings fresh beauty trends that celebrate natural beauty and innovative products:</p>\r\n<h3>1. Glazed Donut Skin</h3>\r\n<p>The dewy, luminous skin trend continues with a \"glazed donut\" finish - skin that looks hydrated, glossy, and fresh.</p>\r\n<h3>2. Sun-Kissed Makeup</h3>\r\n<p>Natural bronzed looks with cream-based products that mimic a natural sun-kissed glow without sun damage.</p>\r\n<h3>3. Waterproof Everything</h3>\r\n<p>From sweat-proof foundations to waterproof mascaras, long-lasting formulas are essential for summer.</p>\r\n<h3>4. Bold Lip Colors</h3>\r\n<p>Vibrant corals, oranges, and reds make a comeback for summer lips that pop.</p>\r\n<h3>5. Beachy Waves</h3>\r\n<p>Effortless, tousled waves that look like you just came from the beach - achieved with sea salt sprays and texturizing products.</p>\r\n<h3>6. UV-Protective Hair Care</h3>\r\n<p>Products with UV filters specifically designed to protect hair color and prevent sun damage.</p>'),
(6, 1, 2, '???????? 10 ?????????? ???????????? ???????? 2026', '???????????? ???????? ?????????? ???????????? ???????????? ?????????? ???????????? ?????????? ????????????.', '???? ???????????????? ???? ?????? 2026?? ?????????? ???????? ?????????????? ???? ???????????? ?????????????? ?????????????? ????????????. ?????????? ???????? 10 ?????????? ?????????????? ???????? 2026:\r\n\r\n1. ?????????????? ??????????????: ?????????? ?????????? ?????????? ???? ?????????????? ???????????? ???????????? ???????????? ?????? ???????? ?????????? ????????????.\r\n\r\n2. ?????????? ?????? ????????????????????????: ?????????????? ??????????! ?????????????? ?????????? ?????? ???????????????????????? ?????????? ???????? ????????????.\r\n\r\n3. ???????? ?????????? ????????????: ???? ?????????????? ?????????????? ???????? ???????????? ?????? ???? ???????????? ?????????????? ???? ?????? ?????????????? ???? ?????????????? ??????????????.\r\n\r\n4. ?????????? ?????????? ??????????????: ???????? ???????????? ???? ???????????????? ???????????????? ???????????? ?????????? ?????????????? ?????????? ???????????? ???????????? ???????????????? ?????????? ??????????????.\r\n\r\n5. ?????????????? ???? ?????????? ????????????: ???? ?????????? ?????? ?????????????? ???????????????? ???????? ?????????? ???? ?????????? ?????????? ????????????.\r\n\r\n6. <strong>???????????? ????????????????:</strong> ???????????? ?????????? ?????????? ???????????? ?????????????? ?????????? ???? ???????????? ?????? ??????????????????.</p>\r\n<p>7. <strong>?????????? ??????????:</strong> ?????????? ?????????? ?????????? ?????? ?????????????? ???????????? ???????????? ??????????????.</p>\r\n<p>8. <strong>?????????????? ?????????????? ????????????????????????:</strong> ?????????? ?????????????????? ???????????? ???????????????? ???????????? ???????? ????????????????????????.</p>\r\n<p>9. <strong>?????????? ????????:</strong> ?????? \"???????????? ??????????\" ???? ?????????? ???????? ?????????? ???????????? ????????????.</p>\r\n<p>10. <strong>?????????? ??????????:</strong> ???????????? ?????????? ???????? ?????????? ?????????? ???????????? ?????? ???????? ??????????????.</p>'),
(7, 2, 2, '???????? ???????? ?????????????? ???????????? ???? ?????? ????????????', '???????? ???????? ???? ?????????? ???????????? ???????????? ?????? ?????????????? ????????????????.', '<p>???? ???????? ???????????? ???????????? ?????? ?????????? ???????????? ???????????? ???????????????? ??????????????. ?????????? ?????? ???????????? ???????????? ???????????? ?????? ?????? ???????? ???????? ?????? ????????????:</p>\r\n<h3>1. ?????????????? ??????????</h3>\r\n<p>?????????????? ?????????? ?????????? ???????????? ???????? ?????? ????????????. ?????????? ???? ???????????? ?????? ?????? ?????????????? ?????????? ?????????? ???????? ?????? ??????????.</p>\r\n<h3>2. ???????? ?????? ???????? ?????? ??????????</h3>\r\n<p>?????? ?????????? ???????????? ???? ?????????? ???????????? ????????????????. ?????????? ???????? ???? ?????????? ???? ?????????? ???????? ?????????? ???? ???????? ????????????.</p>\r\n<h3>3. ???????????? ?????????????? ????????????</h3>\r\n<p>?????????????? ???????? ?????????? ???????? ?????? ?????????? ?????? ?????????? ???????????????? ???????????????? ?????????? ?????????? ???????? ??????????.</p>\r\n<h3>4. ?????????????? ???? ??????????</h3>\r\n<p>?????????? ???????????? ?????? ???????????? ???????? ???? ???????? ?????? ???????????? ???????????? ???????? ???? ???????????? ?????????????? ?????????????? ?????????? ??????????????.</p>\r\n<h3>5. ?????????? ?????????????? ?????????? ?????????????? ????????????????\r\n\r\n???????? ???? ?????????????? ?????????? ?????????????? ???????????????? ?????? ?????????? ?????????? ???????????? ?????????????? ???????? ?????? ????????????.\r\n\r\n6. ???? ?????????? ?????????? ??????????????\r\n\r\n?????? ?????????? ???????? ?????????????? ???? 6-8 ???????????? ???????? ???????? ?????????????? ?????????????? ?????? ?????? ????????.'),
(8, 3, 2, '?????????? ?????????????? ?????????????? ?????????????? ?????????? ????????????', '?????????? ?????? ???????? ?????????? ???? ?????? ?????????????? ???????????? ????????????.', '<p>?????????? ?????????? ?????????? ???????? ?????????? ?????????????? ?????????????? ?????????? ?????????????? ???????????? ?????? ???????? ???????? ??????????. ?????????? ?????? ?????????????? ???????????? ????????????:</p>\r\n\r\n<h3>?????????? ????????????:</h3>\r\n\r\n<ol>\r\n<li><strong>???????? ??????????:</strong> ?????????? ?????????? ???????? ???????????? ?????????????? ?????????????????? ???????? ??????????.</li>\r\n\r\n<li><strong>????????:</strong> ?????????? ???????? ?????????? ???????????? ???????????????? ???????? ???????? ???? ????????????.</li>\r\n\r\n<li><strong>??????????:</strong> ?????? ?????????? ?????????????? ???? ???????????? ???????????? ???? ???????????? ?????????????? ????????????????.</li>\r\n\r\n<li><strong>???????? ??????????:</strong> ???????? ???????? ?????? ???????? ?????????? ???????????? ?????????? ?????????? ??????????????.</li>\r\n\r\n<li><strong>???????? ????????????:</strong> ?????????? ?????? ?????????? ???????????? ???????????????? ???????? ???????? ?????????? ???????? ??????????????????????.</li>\r\n\r\n<li><strong>???????? ??????????:</strong> ???????? ?????????????? ???????????? ???????????????? ???????? ?????? ???????? ?????????? ?????????? ?????????? 30 ???? ????????.</li>\r\n\r\n</ol>\r\n<h3>?????????? ????????????:</h3>\r\n\r\n<ol>\r\n<li><strong>???????? ??????????????:</strong> ?????????? ???????????????? ???????? ?????????? ???????? ?????? ???????? ?????????? ??????????????.\r\n\r\n????????????: ?????????????? ???????????? ???????????? ???????????? ?????????????? ??????????????????.\r\n\r\n?????????????? (2-3 ???????? ????????????????): ?????????????? ???????????? ???????????????? ?????? ?????????? ???????? ???????????????? (AHAs) ???? ?????????? ???????? ???????????????? (BHAs).\r\n\r\n????????????: ?????? ?????????????????? ???? ?????????????? ???????????? ?????? ????????????.\r\n\r\n????????????: ?????????????? ???????????? ?????????? ???? ???????????? ????????????.\r\n\r\n?????? ?????????? (??????????????): ?????? ?????? ?????????? ???? ?????? ?????????? ???????????? ????????????.'),
(9, 4, 2, '???????????? ???? ?????? ???????????? ?????????????? ????????????????', '?????? ???????? ???????????????? ???????????? ?????????????? ???? ???????? ?????????? ??????????????.', '<p>???????????? ?????????????? ???????? ???? ????????????. ???????? ???????????????? ???????????? ?????????????? ?????????????? ???? ???????? ???????? ???????? ?????? ?????????? ?????????? ????????????:</p>\r\n\r\n<h3>?????????????? ?????? ?????????? ??????????????</h3>\r\n\r\n<p>???????? ???????????? ???????? ?????????? ?????? ?????????? ???? ???????? ?????????? ?????????????? ?????????????????????? ?????? ???? ???????? ?????? ???????????????????? ?????? ???????????? ?????????????????? ??????????????.</p> ?????????? ???????????? ?????? ?????????? ???????????? ???????????? ??????????????.</p>\r\n\r\n<h3>?????????? ???????????? ????????????:</h3>\r\n\r\n<ul>\r\n<li><strong>?????????? ????????????:</strong> ???????????? ?????????????? ???????????????????? ???????? ???????????????? ?????? ?????????? ???????? ??????????.</li>\r\n<li><strong>?????? ????????:</strong> ?????????? ?????????? ?????????? ???????????? ???????????? ????????????????.</li>\r\n<li><strong>???????? ????????????:</strong> ?????????? ???????????? ?????????????? ???????????? ???????????? ????????????.</li>\r\n<li><strong>?????????? ????????????????:</strong> ?????????? ???????????? ???????? ???????????? ?????????? ?????? ?????????????????? ??????.</li>\r\n<li><strong>???????????? ????????????????:</strong> ???????? ???????????? ?????????? ???????????? ??????????????.</li>\r\n\r\n</ul>\r\n<h3>???????????? ???????? ??????????:</h3>\r\n\r\n<p>?????????? ???? 5-10 ?????????? ?????? ????????????. ???????? ?????? ?????????????? ?????????? ?????????????????? ???? ?????????????? ?????????????? ???????????? ????????????. ?????????????????????? ???? ?????????? ???????? ??????????????.</p>'),
(10, 5, 2, '?????????????? ???????? ?????????? 2026', '???? ???? ???????????? ???? ???????????? ???????? 2026.', '<p>???????? ?????? 2026 ?????????????? ???????? ?????????? ?????????? ?????????????? ?????????????? ?????????????????? ????????????????:</p>\r\n<h3>1. ???????? ?????????????? ??????????????</h3>\r\n<p>?????????? ?????????? ???????????? ???????????? ?????????????????? ???? ?????????? \"?????????????? ????????????\" - ???????? ???????? ?????????? ???????????? ????????????.</p>\r\n<h3>2. ?????????? ???????? ??????????</h3>\r\n<p>?????????? ?????????????? ???????????? ???? ???????????? ?????????? ?????? ???????????? ?????????? ???????? ???????? ?????????? ?????????????? ?????? ?????????? ??????????.</p>\r\n<h3>3. ???? ?????? ???????? ??????????</h3>\r\n<p>???? ?????????????????? ???????????????? ?????????? ?????? ?????????????????? ???????????????? ???????????? ?????? ?????????????????? ?????????? ?????????? ???????????? ??????????.</p>\r\n<h3>4. ?????????? ???????? ??????????</h3>\r\n<p>???????? ?????????????????? ?????????????????????? ???????????????? ?????????????? ???????????? ?????????????? ???????? ???????? ????????????????.</p>\r\n<h3>5. ???????????? ????????????</h3>\r\n<p>???????????? ???????? ???????????? ???????? ?????????? ?????? ???????? ???? ???????????? - ?????????? ?????????? ?????? ?????????? ?????????????? ???????? ????????????.</p>\r\n<h3>6. ?????????????? ???????????? ?????????????? ???? ???????????? ?????? ??????????????????</h3>\r\n<p>???????????? ?????? ???????????? ???????????? ?????? ?????????????????? ?????????? ???????????? ???????????? ?????? ?????????? ???????? ?????????? ??????????.</p>');

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `name`, `slug`, `logo`, `description`, `website`, `meta_title`, `meta_description`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Nashi', 'nashi', 'brand_69cbbe71b6b96.png', '', NULL, '', '', 1, 7, '2026-01-04 12:19:42', '2026-03-31 12:34:06'),
(2, 'Actyva', 'actyva', 'brand_69cbbfb1a18aa.png', 'South Korean multinational electronics company', NULL, 'Samsung Electronics | Innovation', '?????????????? - ???????? ???????????????????? ?????????? ???????????? ????????????????', 1, 2, '2026-01-04 12:19:42', '2026-03-31 12:36:01'),
(4, 'Loreal', 'loreal', 'brand_69cbf84255577.png', 'Sports apparel and footwear', NULL, '', '', 1, 6, '2026-01-04 12:19:42', '2026-03-31 16:37:22'),
(5, 'Essie', 'essie', 'brand_69cbbf9f77525.png', '', NULL, '', '', 1, 3, '2026-01-04 12:19:42', '2026-03-31 12:35:43'),
(6, 'Kemon', 'kemon', 'brand_69cbbf762f5fc.png', '', NULL, '', '', 1, 5, '2026-01-16 14:09:03', '2026-03-31 12:35:02'),
(7, 'Skeyndor', 'skeyndor', 'brand_69cbbf1210d9c.png', '', NULL, '', '', 1, 8, '2026-01-16 14:09:41', '2026-03-31 12:33:22'),
(8, 'WOW', 'wow', 'brand_69cbf896d2562.png', '', NULL, '', '', 1, 9, '2026-01-16 14:20:07', '2026-03-31 16:38:46'),
(9, 'K18', 'k18', 'brand_69cbf870016f3.png', '', NULL, '', '', 1, 4, '2026-01-16 14:21:57', '2026-03-31 16:38:08'),
(46, 'CatEye', 'cateye', 'brand_69cbbecbd148e.png', '', NULL, 'cateye', 'cateye', 1, 1, '2026-03-31 12:32:11', NULL),
(47, 'Ecosmetics', 'ecosmetics', 'brand_69dcb6f64257a.png', '', NULL, 'ecosmetics', 'ecosmetics', 1, 10, '2026-04-06 16:54:15', '2026-04-13 09:27:18'),
(48, 'Hair Secret', 'hair-secret', 'brand_69dcb70b1174f.png', '', NULL, 'Hair Secret', 'Hair Secret', 1, 11, '2026-04-06 16:56:03', '2026-04-13 09:27:39');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `variation_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `parent_id`, `slug`, `image`, `icon`, `is_active`, `sort_order`, `meta_title`, `meta_description`, `created_at`, `updated_at`) VALUES
(9, NULL, 'hair-care-products', 'category_6978d6b9e3c49.png', NULL, 1, 1, '', '', '2026-01-16 15:57:26', '2026-01-27 15:16:09'),
(13, NULL, 'facial-care-products', 'category_6978d2071d601.png', NULL, 1, 2, '', '', '2026-01-16 16:11:38', '2026-01-27 14:56:07'),
(14, NULL, 'body-care-products', 'category_6977d983ee563.png', NULL, 1, 3, '', '', '2026-01-26 19:48:56', '2026-01-26 21:15:47'),
(15, NULL, 'nails-products', 'category_6977d9cc0a66d.png', NULL, 1, 4, '', '', '2026-01-26 19:49:44', '2026-01-26 21:17:00'),
(16, NULL, 'makeup-products', 'category_6978d876dff8e.png', NULL, 1, 5, '', '', '2026-01-26 19:50:32', '2026-01-27 15:23:34');

-- --------------------------------------------------------

--
-- Table structure for table `category_translations`
--

CREATE TABLE `category_translations` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `category_translations`
--

INSERT INTO `category_translations` (`id`, `category_id`, `language_id`, `name`, `description`) VALUES
(18, 9, 1, 'Hair Products', ''),
(19, 9, 2, '???????????? ??????????', ''),
(25, 13, 1, 'Facial Products', ''),
(26, 13, 2, '???????????? ??????????', ''),
(27, 14, 1, 'Body Products', ''),
(28, 14, 2, '???????????? ??????????', ''),
(29, 15, 1, 'Nails Products', ''),
(30, 15, 2, '???????????? ??????????????', ''),
(31, 16, 1, 'Makeup Products', ''),
(32, 16, 2, '???????????? ??????????????', '');

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `title_ar` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `description_ar` text DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `certificates`
--

INSERT INTO `certificates` (`id`, `title`, `title_ar`, `description`, `description_ar`, `image`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'PHILIP MARTIN', '?????????? ??????????', 'Successfully Participated in The PHILIPMARTIN???S Training<br/>\r\n\r\nAwarded to: Amaal Awadh Saeed Alrashedi<br/>\r\n\r\nLocation: Khobar<br/>\r\n\r\nDate: 5/6/7/8 Feb. 2026<br/>', '???????????????? ?????????????? ???? ?????????? ?????????? ?????????? <br/>\r\n\r\n???????? ??????:<br/>\r\n\r\n?????? ?????? ???????? ??????????????<br/>\r\n<br/>\r\n????????????: ??????????\r\n<br/>\r\n??????????????: 5/6/7/8 ???????????? 2026', 'cert_69cbf8011209d.jpeg', 6, 1, '2026-03-02 19:53:47', '2026-03-31 16:36:17'),
(3, 'Kholo Beauty', '???????? ????????????', 'Kholo Beauty - ITALY<br/>\r\n\r\nOnline training course<br/>\r\n\r\nAwarded to: Amaal Awadh Saeed Alrashedi<br/>\r\n\r\nDesignation: Professional', '???????? ???????????? -  ??????????????<br/>\r\n\r\n?????????? ?????????? ??????????????<br/>\r\n\r\n???????? ???????????? ???????? ?????? ???????? ??????????????<br/>\r\n\r\n?????????? - ??????????????', 'cert_69b57c4c80925.jpeg', 5, 1, '2026-03-14 15:18:36', '2026-03-31 16:30:27'),
(4, 'NUBEA Essential oil therapy', '???????????? ?????????????? ??????????', 'This is to certify that <br/>\r\n\r\nAmaal Awadh Saeed Alrashedi <br/>\r\n\r\nhas successfully attended nube?? training <br/>\r\n\r\nKhobar - 5/6/7/8 Feb. 2026 <br/>\r\n\r\nnubea.it', '???????? ?????? ?????????????? ??????<br/>\r\n\r\n?????? ?????? ???????? ??????????????<br/>\r\n\r\n???? ???????? ?????????? ?????????? ??????????<br/>\r\n\r\n?????????? - 5/6/7/8 ???????????? 2026<br/>\r\n\r\nnubea.it', 'cert_69b57c6d68fda.jpeg', 3, 1, '2026-03-14 15:19:09', '2026-03-31 16:13:18'),
(5, 'KH PH ??? Hair Professional', '?? ?? ?????????????? ?????????????????? ??????????', 'Golden Of Amazon Brasil</br>\r\nEscola De Cabeleireiros</br>\r\n\r\nTraining Participation Certificate</br>\r\n\r\nParticipation grants Mrs.</br>\r\n\r\nAmaal Awadh Saeed Alrashedi</br>\r\n\r\nKhobar ??? 5/6/7/8 Feb. 2026</br>\r\n\r\nCertificate of participation and training in the course held in the Kingdom of Saudi Arabia in cooperation between the Fuel Training Center Academy and the Academy Golden of Amazon Brasil escola de cabeleireiro ltda to study and apply KH PH products in a professional scientific manner.</br>\r\n\r\nSignature</br>\r\n\r\nDra. Khloud H. M. Aljorayed</br>\r\n\r\nKH PH ??? Hair Professional</br>', '?????????? ?????? ???????????? ????????????????</br>\r\n?????????? ?????????? ??????????</br>\r\n\r\n?????????? ???????????? ???? ??????????????</br>\r\n\r\n?????????? ?????? ?????????????? ????????????:</br>\r\n\r\n?????? ?????? ???????? ??????????????</br>\r\n\r\n?????????? ??? 5/6/7/8 ???????????? 2026</br>\r\n\r\n?????????? ???????????? ???????????? ???? ???????????? ?????????????? ???? ?????????????? ?????????????? ?????????????????? ???????????????? ?????? ???????????????? ???????? ???????? ?????????????? ?????????????????? ?????????? ?????? ???????????? ???????????????? (?????????? ?????????? ?????????? ????????????????)?? ???????? ???????????? ???????????? ???????????? KH PH ???????????? ?????????? ??????????????????.</br>\r\n\r\n??????????????</br>\r\n\r\n??. ???????? ?????? ???????? ????????????</br>\r\n\r\nKH PH ??? ???????????? ?????????????? ????????????', 'cert_69b57c931a525.jpeg', 2, 1, '2026-03-14 15:19:47', '2026-03-31 15:08:08'),
(6, 'iAM???U', 'IAM  ?????? ????', 'THIS CERTIFICATE IS PRESENTED TO : <br/>\r\n\r\nAmaal Awadh Saeed Alrashedi<br/>\r\n\r\nFor Having Successfully Participated in The iAM???U Training<br/>\r\n\r\nKhobar - 5/6/7/8 Feb. 2026', '?????????????? ?????? ?????????????? ??????:<br/>\r\n\r\n?????? ?????? ???????? ??????????????<br/>\r\n\r\n???????? ?????????????????? ?????????? ???? ?????????? iAM???U \r\n<br/>\r\n?????????? - 5/6/7/8 ???????????? 2026', 'cert_69b57cad84e47.jpeg', 4, 1, '2026-03-14 15:20:13', '2026-03-31 16:24:02'),
(7, 'Professional Hair Coloring', '?????????? ?????????? ????????????????????', 'Professional Khulood Aljrayed Center.\r\nCertificate Of Attending A Workshop\r\nThis Certificate That : Amaal Awadh saeed AlRashedi.\r\nWorkshop For Professional Hair Color.\r\nWorkshop for professional hair color & treatment.\r\nKhobar - 5 / 6 / 7 / 8 Feb. 2026', '???????? ???????? ???????????? ????????????<br/> \r\n\r\n?????????? ???????? ???????? ??????<br/>\r\n\r\n???????? ?????? ?????????????? ??????: ?????? ?????? ???????? ??????????????<br/>\r\n\r\n???????? ?????? ???????????????? ???????? ??????????<br/>\r\n\r\n???????? ?????? ???????????????? ???????? ?????????? ???????????????? ????<br/>\r\n\r\n?????????? - 5 / 6 / 7 / 8 ???????????? 2026', 'cert_69b57cbf04512.jpeg', 1, 1, '2026-03-14 15:20:31', '2026-04-03 17:29:35');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('fixed','percentage') NOT NULL DEFAULT 'fixed',
  `value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `minimum_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `max_discount_amount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `usage_count` int(11) NOT NULL DEFAULT 0,
  `usage_limit_per_user` int(11) DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `coupons`
--

INSERT INTO `coupons` (`id`, `code`, `description`, `type`, `value`, `minimum_amount`, `max_discount_amount`, `usage_limit`, `usage_count`, `usage_limit_per_user`, `start_date`, `end_date`, `is_active`, `created_at`) VALUES
(2, 'SAVEF40Y', NULL, 'fixed', 40.00, 600.00, NULL, 100, 2, 1, '2026-04-04 09:41:00', '2026-09-01 09:41:00', 1, '2026-01-11 07:41:35');

-- --------------------------------------------------------

--
-- Table structure for table `coupon_usage`
--

CREATE TABLE `coupon_usage` (
  `id` int(11) NOT NULL,
  `coupon_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `coupon_usage`
--

INSERT INTO `coupon_usage` (`id`, `coupon_id`, `user_id`, `order_id`, `booking_id`, `discount_amount`, `used_at`) VALUES
(37, 2, 21, NULL, 21, 40.00, '2026-04-01 14:11:05');

-- --------------------------------------------------------

--
-- Table structure for table `currencies`
--

CREATE TABLE `currencies` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) NOT NULL,
  `symbol` varchar(20) NOT NULL,
  `exchange_rate` decimal(10,4) NOT NULL DEFAULT 1.0000,
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `currencies`
--

INSERT INTO `currencies` (`id`, `name`, `code`, `symbol`, `exchange_rate`, `is_default`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(4, 'UAE Dirham', 'AED', 'AED', 1.0000, 1, 1, 4, '2026-01-04 07:48:55', '2026-03-15 08:19:35');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'Link to users table if customer has account',
  `customer_group_id` int(11) DEFAULT 1,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `tax_id` varchar(50) DEFAULT NULL,
  `newsletter_subscribed` tinyint(1) DEFAULT 0,
  `total_orders` int(11) DEFAULT 0,
  `total_spent` decimal(10,2) DEFAULT 0.00,
  `last_order_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL COMMENT 'Internal notes about customer',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_addresses`
--

CREATE TABLE `customer_addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address_type` enum('billing','shipping') NOT NULL DEFAULT 'shipping',
  `is_default` tinyint(1) DEFAULT 0,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `company` varchar(100) DEFAULT NULL,
  `address_line1` varchar(200) NOT NULL,
  `address_line2` varchar(200) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) NOT NULL,
  `country` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_groups`
--

CREATE TABLE `customer_groups` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customer_groups`
--

INSERT INTO `customer_groups` (`id`, `name`, `description`, `discount_percentage`, `is_active`, `created_at`) VALUES
(1, 'Regular', 'Regular customers', 0.00, 1, '2026-01-04 12:19:41'),
(2, 'VIP', 'VIP customers with special discounts', 10.00, 1, '2026-01-04 12:19:41'),
(3, 'Wholesale', 'Wholesale customers', 20.00, 1, '2026-01-04 12:19:41'),
(4, 'Regular', 'Regular customers', 0.00, 1, '2026-01-04 12:34:10'),
(5, 'VIP', 'VIP customers with special discounts', 10.00, 1, '2026-01-04 12:34:10'),
(6, 'Wholesale', 'Wholesale customers', 20.00, 1, '2026-01-04 12:34:10');

-- --------------------------------------------------------

--
-- Table structure for table `digital_downloads`
--

CREATE TABLE `digital_downloads` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `digital_file_id` int(11) NOT NULL,
  `download_token` varchar(255) NOT NULL,
  `download_count` int(11) DEFAULT 0,
  `download_limit` int(11) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_templates`
--

CREATE TABLE `email_templates` (
  `id` int(11) NOT NULL,
  `template_key` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` longtext NOT NULL,
  `variables` text DEFAULT NULL COMMENT 'JSON array of available variables',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faqs`
--

CREATE TABLE `faqs` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faq_categories`
--

CREATE TABLE `faq_categories` (
  `id` int(11) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faq_category_translations`
--

CREATE TABLE `faq_category_translations` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faq_translations`
--

CREATE TABLE `faq_translations` (
  `id` int(11) NOT NULL,
  `faq_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `footer_links`
--

CREATE TABLE `footer_links` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `url` varchar(500) NOT NULL,
  `column_number` int(11) DEFAULT 1 COMMENT '1=Company, 2=Help, 3=Legal, 4=Other',
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `target` varchar(20) DEFAULT '_self',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `footer_links`
--

INSERT INTO `footer_links` (`id`, `title`, `url`, `column_number`, `is_active`, `sort_order`, `target`, `created_at`, `updated_at`) VALUES
(1, 'About Us', 'about.php', 1, 1, 1, '_self', '2026-01-20 14:28:30', NULL),
(2, 'Our Story', 'about.php#story', 1, 1, 2, '_self', '2026-01-20 14:28:30', NULL),
(3, 'Careers', 'careers.php', 1, 1, 3, '_self', '2026-01-20 14:28:30', NULL),
(4, 'Contact', 'contact.php', 1, 1, 4, '_self', '2026-01-20 14:28:30', NULL),
(5, 'Help Center', 'help.php', 2, 1, 1, '_self', '2026-01-20 14:28:30', NULL),
(6, 'FAQs', 'faq.php', 2, 1, 2, '_self', '2026-01-20 14:28:30', NULL),
(7, 'Shipping Info', 'shipping.php', 2, 1, 3, '_self', '2026-01-20 14:28:30', NULL),
(8, 'Returns', 'returns.php', 2, 1, 4, '_self', '2026-01-20 14:28:30', NULL),
(9, 'Privacy Policy', 'privacy.php', 3, 1, 1, '_self', '2026-01-20 14:28:30', NULL),
(10, 'Terms of Service', 'terms.php', 3, 1, 2, '_self', '2026-01-20 14:28:30', NULL),
(11, 'Cookie Policy', 'cookies.php', 3, 1, 3, '_self', '2026-01-20 14:28:30', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_transactions`
--

CREATE TABLE `inventory_transactions` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `transaction_type` enum('purchase','sale','sales_return','supplier_return','adjustment','transfer_in','transfer_out','production','return') DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `previous_quantity` int(11) NOT NULL,
  `new_quantity` int(11) NOT NULL,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL COMMENT 'order, purchase_order, stock_adjustment, etc',
  `reference_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_transactions`
--

INSERT INTO `inventory_transactions` (`id`, `product_id`, `warehouse_id`, `variant_id`, `transaction_type`, `quantity`, `previous_quantity`, `new_quantity`, `unit_cost`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES
(40, 7, 1, NULL, 'adjustment', 15, 0, 15, 50.00, 'stock_adjustment', 61, 'Adjustment: ADJ-20260401130021-3131 - ', 3, '2026-04-01 09:00:21'),
(41, 8, 1, 67, 'production', 20, 0, 20, 12.00, 'production_order', 8, 'Production order: PROD-20260401165932-1892-1', 3, '2026-04-01 13:01:28'),
(42, 8, 1, 70, 'purchase', 44, 0, 44, 12.00, 'purchase_order', 16, 'Purchase order received: PO-20260401171317-3349', 3, '2026-04-01 13:13:40'),
(43, 7, 1, NULL, 'sale', -2, 15, 13, 50.00, 'order', 27, 'Order delivered: ORD-20260401131313-7651 - High Light x 2', NULL, '2026-04-01 13:35:23'),
(44, 7, 1, NULL, 'production', 22, 13, 35, 50.00, 'production_order', 9, 'Production received: dfgdfgdfg', 3, '2026-04-02 17:35:01'),
(45, 7, 1, NULL, 'production', 33, 35, 68, 50.00, 'production_order', 9, 'Production order: PROD-20260402213419-6545-1', 3, '2026-04-02 17:35:42'),
(46, 7, 4, NULL, 'production', 1, 0, 1, 50.00, 'production_order', 10, 'Production received: PROD-20260403214644-8817-1', 3, '2026-04-03 17:48:13'),
(47, 7, 4, NULL, 'production', 2, 1, 3, 50.00, 'production_order', 10, 'Production order: PROD-20260403214644-8817-1', 3, '2026-04-03 17:49:15'),
(48, 7, 1, NULL, 'adjustment', -1, 68, 67, 50.00, 'stock_adjustment', 62, 'Adjustment: ADJ-20260406192634-4389 - test gify', 3, '2026-04-06 15:26:34'),
(49, 8, 1, NULL, 'sale', -2, 0, -2, 12.00, 'order', 29, 'Order Sale: ORD-20260413145516-1728 - Velvet Lip Liquid x 2', 24, '2026-04-13 10:55:16'),
(50, 7, 1, NULL, 'sale', -3, 67, 64, 50.00, 'order', 29, 'Order Sale: ORD-20260413145516-1728 - High Light x 3', 24, '2026-04-13 10:55:16'),
(51, 7, 1, NULL, 'production', 22, 64, 86, 50.00, 'production_order', 11, 'Production auto-completed: PROD-20260413155649-8653-1', 3, '2026-04-13 11:57:04'),
(52, 7, 1, NULL, 'production', 22, 86, 108, 50.00, 'production_order', 11, 'Production auto-completed: PROD-20260413155649-8653-1', 3, '2026-04-13 11:57:10'),
(53, 7, 1, NULL, 'production', 22, 108, 130, 50.00, 'production_order', 11, 'Production auto-completed: PROD-20260413155649-8653-1', 3, '2026-04-13 11:57:56'),
(54, 7, 1, NULL, 'production', 22, 130, 152, 50.00, 'production_order', 11, 'Production order: PROD-20260413155649-8653-1', 3, '2026-04-13 12:51:36'),
(55, 9, 1, NULL, 'production', 10, 0, 10, 30.00, 'production_order', 12, 'Production auto-completed: PROD-20260413194730-3082-1', 3, '2026-04-13 15:47:34'),
(56, 9, 1, NULL, 'production', 10, 10, 20, 30.00, 'production_order', 12, 'Production auto-completed: PROD-20260413194730-3082-1', 3, '2026-04-13 15:47:38'),
(57, 9, 1, NULL, 'production', 10, 20, 30, 30.00, 'production_order', 12, 'Production auto-completed: PROD-20260413194730-3082-1', 3, '2026-04-13 15:47:42'),
(58, 9, 1, NULL, 'production', 10, 30, 40, 30.00, 'production_order', 12, 'Production auto-completed: PROD-20260413194730-3082-1', 3, '2026-04-13 15:47:46'),
(59, 9, 1, NULL, 'production', 10, 40, 50, 30.00, 'production_order', 12, 'Production auto-completed: PROD-20260413194730-3082-1', 3, '2026-04-13 15:48:46'),
(60, 9, 3, NULL, 'production', 121, 0, 121, 30.00, 'production_order', 14, 'Production auto-completed: PROD-20260413195537-6854-1', 3, '2026-04-13 15:55:53'),
(61, 9, 3, NULL, 'production', 121, 121, 242, 30.00, 'production_order', 14, 'Production auto-completed: PROD-20260413195537-6854-1', 3, '2026-04-13 15:55:58'),
(62, 9, 3, NULL, 'production', 121, 242, 363, 30.00, 'production_order', 14, 'Production auto-completed: PROD-20260413195537-6854-1', 3, '2026-04-13 15:56:14'),
(63, 9, 3, NULL, 'production', 121, 363, 484, 30.00, 'production_order', 14, 'Production auto-completed: PROD-20260413195537-6854-1', 3, '2026-04-13 15:56:59'),
(64, 9, 1, NULL, 'production', 12, 50, 62, 30.00, 'production_order', 15, 'Production auto-completed: PROD-20260413195843-4570-1', 3, '2026-04-13 15:58:49'),
(65, 7, 3, NULL, 'production', 12, 0, 12, 50.00, 'production_order', 16, 'Production auto-completed: PROD-20260413195958-2572-1', 3, '2026-04-13 16:00:14'),
(66, 8, 1, NULL, 'sale', -1, -2, -3, 12.00, 'order', 30, 'Order Sale: ORD-20260413201708-8322 - Velvet Lip Liquid x 1', 26, '2026-04-13 16:17:08'),
(67, 9, 1, NULL, 'sale', -1, 62, 61, 30.00, 'order', 30, 'Order Sale: ORD-20260413201708-8322 - Lenses x 1', 26, '2026-04-13 16:17:08'),
(68, 8, 1, NULL, 'sale', -1, -3, -4, 12.00, 'order', 31, 'Order Sale: ORD-20260413202733-5835 - Velvet Lip Liquid x 1', 26, '2026-04-13 16:27:33'),
(69, 7, 1, NULL, 'sale', -1, 152, 151, 50.00, 'order', 32, 'Order Sale: ORD-20260413203316-1700 - High Light x 1', 26, '2026-04-13 16:33:16'),
(70, 9, 1, NULL, 'sale', -1, 61, 60, 30.00, 'order', 32, 'Order Sale: ORD-20260413203316-1700 - Lenses x 1', 26, '2026-04-13 16:33:16'),
(71, 8, 1, NULL, 'sale', -1, -4, -5, 12.00, 'order', 33, 'Order Sale: ORD-20260413205200-5611 - Velvet Lip Liquid x 1', 26, '2026-04-13 16:52:00'),
(72, 9, 1, NULL, 'sale', -1, 60, 59, 30.00, 'order', 33, 'Order Sale: ORD-20260413205200-5611 - Lenses x 1', 26, '2026-04-13 16:52:00'),
(73, 8, 1, 70, 'supplier_return', -44, 44, 0, 12.00, 'supplier_return', 39, 'Supplier return: SUPR-20260413224659-4903', 3, '2026-04-13 18:47:19'),
(74, 9, 1, NULL, 'sale', -4, 59, 55, 30.00, 'order', 34, 'Order Sale: ORD-20260414085727-2568 - Lenses x 4', 26, '2026-04-14 04:57:27'),
(75, 9, 1, NULL, 'sale', -1, 55, 54, 30.00, 'order', 35, 'Order Sale: ORD-20260414114318-4241 - Lenses x 1', 26, '2026-04-14 07:43:18'),
(76, 9, 4, NULL, 'production', 12, 0, 12, 30.00, 'production_order', 17, 'Production auto-completed: PROD-20260414114909-7810-1', 3, '2026-04-14 07:49:16'),
(77, 9, 1, NULL, 'purchase', 19, 54, 73, 30.00, 'purchase_order', 18, 'Purchase order received: PO-20260414115035-7814', 3, '2026-04-14 07:50:48'),
(78, 9, 1, NULL, 'supplier_return', -19, 73, 54, 30.00, 'supplier_return', 40, 'Supplier return: SUPR-20260414115131-8962', 3, '2026-04-14 07:51:39');

--
-- Triggers `inventory_transactions`
--
-- (Procedures/Triggers removed - run install_triggers_procedures.php after import)


-- --------------------------------------------------------

--
-- Table structure for table `languages`
--

CREATE TABLE `languages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) NOT NULL,
  `direction` enum('ltr','rtl') DEFAULT 'ltr',
  `flag` varchar(50) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `languages`
--

INSERT INTO `languages` (`id`, `name`, `code`, `direction`, `flag`, `is_default`, `is_active`, `sort_order`, `created_at`) VALUES
(1, 'English', 'en', 'ltr', 'gb.png', 1, 1, 1, '2026-01-04 07:48:55'),
(2, 'Arabic', 'ar', 'rtl', 'sa.png', 0, 1, 2, '2026-01-04 07:48:55');

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `id` int(11) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `views` int(11) DEFAULT 0,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_campaigns`
--

CREATE TABLE `newsletter_campaigns` (
  `id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `sent_count` int(11) DEFAULT 0,
  `failed_count` int(11) DEFAULT 0,
  `total_subscribers` int(11) DEFAULT 0,
  `status` enum('draft','sending','sent','failed') DEFAULT 'draft',
  `sent_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sent_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `newsletter_campaigns`
--

INSERT INTO `newsletter_campaigns` (`id`, `subject`, `content`, `sent_count`, `failed_count`, `total_subscribers`, `status`, `sent_by`, `created_at`, `sent_at`) VALUES
(4, 'Services Offer from CatEyes', '<p>Hello</p>\r\n<p>Come and Enjoy <span style=\"color: rgb(185, 106, 217); font-size: 18pt;\">50%</span> off this Summer at <span style=\"color: rgb(185, 106, 217); font-size: 24pt;\"><strong>Cateyes</strong></span></p>\r\n<p><span style=\"color: rgb(185, 106, 217); font-size: 24pt;\"><strong><img src=\"../assets/uploads/services/service_6979f13e35025.jpg\" alt=\"\" width=\"300\" height=\"300\"></strong></span></p>\r\n<p><span style=\"color: rgb(185, 106, 217); font-size: 10pt;\"><strong><a href=\"mailto:info@cateyesbeauty.com\">info@cateyesbeauty.com</a></strong></span></p>\r\n<p>&nbsp;</p>', 3, 0, 3, 'sent', NULL, '2026-04-13 14:14:04', '2026-04-13 14:14:10');

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_subscribers`
--

CREATE TABLE `newsletter_subscribers` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `status` enum('subscribed','unsubscribed') DEFAULT 'subscribed',
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `unsubscribed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `newsletter_subscribers`
--

INSERT INTO `newsletter_subscribers` (`id`, `email`, `status`, `subscribed_at`, `unsubscribed_at`) VALUES
(13, 'albara.bitar@gmail.com', 'subscribed', '2026-04-01 13:06:04', NULL),
(14, 'xwmmxvtx@immenseignite.info', 'subscribed', '2026-04-06 06:33:21', NULL),
(15, 'support@bundlewp.com', 'subscribed', '2026-04-11 21:01:01', NULL),
(16, 'info@ssproviders.com', 'subscribed', '2026-04-13 14:35:21', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `news_translations`
--

CREATE TABLE `news_translations` (
  `id` int(11) NOT NULL,
  `news_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `summary` text DEFAULT NULL,
  `content` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `warehouse_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `guest_email` varchar(255) DEFAULT NULL,
  `currency_code` varchar(10) NOT NULL,
  `exchange_rate` decimal(10,4) NOT NULL DEFAULT 1.0000,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `order_status` enum('pending','confirmed','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `payment_date` date DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_transaction_id` varchar(255) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shipping_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shipping_method_id` int(11) DEFAULT NULL,
  `shipping_date` date DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `shipping_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `billing_first_name` varchar(100) NOT NULL,
  `billing_last_name` varchar(100) NOT NULL,
  `billing_company` varchar(255) DEFAULT NULL,
  `billing_address_line1` varchar(255) NOT NULL,
  `billing_address_line2` varchar(255) DEFAULT NULL,
  `billing_city` varchar(100) NOT NULL,
  `billing_state` varchar(100) DEFAULT NULL,
  `billing_postal_code` varchar(20) NOT NULL,
  `billing_country` varchar(100) NOT NULL,
  `billing_phone` varchar(20) NOT NULL,
  `billing_email` varchar(255) NOT NULL,
  `shipping_first_name` varchar(100) NOT NULL,
  `shipping_last_name` varchar(100) NOT NULL,
  `shipping_company` varchar(255) DEFAULT NULL,
  `shipping_address_line1` varchar(255) NOT NULL,
  `shipping_address_line2` varchar(255) DEFAULT NULL,
  `shipping_city` varchar(100) NOT NULL,
  `shipping_state` varchar(100) DEFAULT NULL,
  `shipping_postal_code` varchar(20) NOT NULL,
  `shipping_country` varchar(100) NOT NULL,
  `shipping_phone` varchar(20) NOT NULL,
  `notes` text DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `billing_address` text DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `admin_viewed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `user_id`, `warehouse_id`, `customer_id`, `guest_email`, `currency_code`, `exchange_rate`, `currency`, `order_status`, `payment_status`, `payment_date`, `payment_method`, `payment_transaction_id`, `subtotal`, `tax_amount`, `shipping_cost`, `shipping_method_id`, `shipping_date`, `delivery_date`, `shipping_amount`, `discount_amount`, `total_amount`, `billing_first_name`, `billing_last_name`, `billing_company`, `billing_address_line1`, `billing_address_line2`, `billing_city`, `billing_state`, `billing_postal_code`, `billing_country`, `billing_phone`, `billing_email`, `shipping_first_name`, `shipping_last_name`, `shipping_company`, `shipping_address_line1`, `shipping_address_line2`, `shipping_city`, `shipping_state`, `shipping_postal_code`, `shipping_country`, `shipping_phone`, `notes`, `shipping_address`, `billing_address`, `tracking_number`, `ip_address`, `user_agent`, `created_at`, `updated_at`, `admin_viewed_at`) VALUES
(27, 'ORD-20260401131313-7651', 21, 1, NULL, '', 'AED', 1.0000, 'USD', 'delivered', 'paid', NULL, NULL, NULL, 200.00, 10.00, 15.00, 5, '2026-04-01', '2026-04-01', 0.00, 0.00, 225.00, 'Albaraa', 'Bitar', '', 'Dubai, Internet City', 'Dubai, Internet City', 'Dubai', '', '971', 'United Arab Emirates', '+971552969432', 'albara.bitar@gmail.com', 'Albaraa', 'Bitar', '', 'Dubai, Internet City', 'Dubai, Internet City', 'Dubai', '', '971', 'United Arab Emirates', '+971552969432', NULL, '{\"first_name\":\"Albaraa\",\"last_name\":\"Bitar\",\"company\":\"\",\"address_line1\":\"Dubai, Internet City\",\"address_line2\":\"Dubai, Internet City\",\"city\":\"Dubai\",\"state\":\"\",\"postal_code\":\"971\",\"country\":\"United Arab Emirates\",\"phone\":\"+971552969432\"}', '{\"first_name\":\"Albaraa\",\"last_name\":\"Bitar\",\"company\":\"\",\"address_line1\":\"Dubai, Internet City\",\"address_line2\":\"Dubai, Internet City\",\"city\":\"Dubai\",\"state\":\"\",\"postal_code\":\"971\",\"country\":\"United Arab Emirates\",\"phone\":\"+971552969432\",\"email\":\"albara.bitar@gmail.com\"}', NULL, NULL, NULL, '2026-04-01 09:13:13', '2026-04-06 14:54:19', '2026-04-06 18:54:19'),
(28, 'ORD-20260403221100-5293', NULL, NULL, NULL, '', 'AED', 1.0000, 'USD', 'confirmed', 'paid', NULL, NULL, NULL, 90.00, 4.50, 15.00, NULL, NULL, NULL, 0.00, 0.00, 109.50, 'Al Baraa', 'Bitar', '', 'Dubau', 'Dubai', 'Dubai', 'Dubai', '971', 'United Arab Emirates', '89789789789', 'albara.bitar@gmail.com', 'Al Baraa', 'Bitar', '', 'Dubau', 'Dubai', 'Dubai', 'Dubai', '971', 'United Arab Emirates', '89789789789', NULL, '{\"first_name\":\"Al Baraa\",\"last_name\":\"Bitar\",\"company\":\"\",\"address_line1\":\"Dubau\",\"address_line2\":\"Dubai\",\"city\":\"Dubai\",\"state\":\"Dubai\",\"postal_code\":\"971\",\"country\":\"United Arab Emirates\",\"phone\":\"89789789789\"}', '{\"first_name\":\"Al Baraa\",\"last_name\":\"Bitar\",\"company\":\"\",\"address_line1\":\"Dubau\",\"address_line2\":\"Dubai\",\"city\":\"Dubai\",\"state\":\"Dubai\",\"postal_code\":\"971\",\"country\":\"United Arab Emirates\",\"phone\":\"89789789789\",\"email\":\"albara.bitar@gmail.com\"}', NULL, NULL, NULL, '2026-04-03 18:11:00', '2026-04-06 14:54:02', '2026-04-06 18:54:02'),
(29, 'ORD-20260413145516-1728', 24, 1, NULL, '', 'AED', 1.0000, 'USD', 'confirmed', 'paid', '2026-04-13', NULL, NULL, 390.00, 19.50, 15.00, NULL, NULL, '2026-04-13', 0.00, 0.00, 424.50, 'Marco', 'Marco', '', 'add1', 'add2', 'Dubai', 'Dubai', '99', 'United Arab Emirates', '52525252525', 'info@ssproviders.com', 'Marco', 'Marco', '', 'add1', 'add2', 'Dubai', 'Dubai', '99', 'United Arab Emirates', '52525252525', NULL, '{\"first_name\":\"Marco\",\"last_name\":\"Marco\",\"company\":\"\",\"address_line1\":\"add1\",\"address_line2\":\"add2\",\"city\":\"Dubai\",\"state\":\"Dubai\",\"postal_code\":\"99\",\"country\":\"United Arab Emirates\",\"phone\":\"52525252525\"}', '{\"first_name\":\"Marco\",\"last_name\":\"Marco\",\"company\":\"\",\"address_line1\":\"add1\",\"address_line2\":\"add2\",\"city\":\"Dubai\",\"state\":\"Dubai\",\"postal_code\":\"99\",\"country\":\"United Arab Emirates\",\"phone\":\"52525252525\",\"email\":\"info@ssproviders.com\"}', NULL, NULL, NULL, '2026-04-13 10:55:16', '2026-04-13 12:05:37', '2026-04-13 16:05:37'),
(30, 'ORD-20260413201708-8322', 26, 1, NULL, '', 'AED', 1.0000, 'USD', 'delivered', 'paid', '2026-04-13', NULL, NULL, 165.00, 8.25, 15.00, 5, '2026-04-13', '2026-04-13', 0.00, 0.00, 188.25, '123', '123', '', '1111111111', '111111111111', '1111111111', '11111111', '1111111111', 'United Arab Emirates', '111111111111', 'artofgallery19@gmail.com', '123', '123', '', '1111111111', '111111111111', '1111111111', '11111111', '1111111111', 'United Arab Emirates', '111111111111', NULL, '{\"first_name\":\"123\",\"last_name\":\"123\",\"company\":\"\",\"address_line1\":\"1111111111\",\"address_line2\":\"111111111111\",\"city\":\"1111111111\",\"state\":\"11111111\",\"postal_code\":\"1111111111\",\"country\":\"United Arab Emirates\",\"phone\":\"111111111111\"}', '{\"first_name\":\"123\",\"last_name\":\"123\",\"company\":\"\",\"address_line1\":\"1111111111\",\"address_line2\":\"111111111111\",\"city\":\"1111111111\",\"state\":\"11111111\",\"postal_code\":\"1111111111\",\"country\":\"United Arab Emirates\",\"phone\":\"111111111111\",\"email\":\"artofgallery19@gmail.com\"}', NULL, NULL, NULL, '2026-04-13 16:17:08', '2026-04-13 16:33:56', '2026-04-13 15:33:56'),
(31, 'ORD-20260413202733-5835', 26, 1, NULL, '', 'AED', 1.0000, 'USD', 'confirmed', 'paid', NULL, NULL, NULL, 45.00, 2.25, 15.00, NULL, NULL, NULL, 0.00, 0.00, 62.25, '123', '123', '', '1111111111', '111111111111', 'Gujranwala', '11111111', '50250', 'United Arab Emirates', '111111111111', 'artofgallery19@gmail.com', '123', '123', '', '1111111111', '111111111111', 'Gujranwala', '11111111', '50250', 'United Arab Emirates', '111111111111', NULL, '{\"first_name\":\"123\",\"last_name\":\"123\",\"company\":\"\",\"address_line1\":\"1111111111\",\"address_line2\":\"111111111111\",\"city\":\"Gujranwala\",\"state\":\"11111111\",\"postal_code\":\"50250\",\"country\":\"United Arab Emirates\",\"phone\":\"111111111111\"}', '{\"first_name\":\"123\",\"last_name\":\"123\",\"company\":\"\",\"address_line1\":\"1111111111\",\"address_line2\":\"111111111111\",\"city\":\"Gujranwala\",\"state\":\"11111111\",\"postal_code\":\"50250\",\"country\":\"United Arab Emirates\",\"phone\":\"111111111111\",\"email\":\"artofgallery19@gmail.com\"}', NULL, NULL, NULL, '2026-04-13 16:27:33', '2026-04-13 16:27:33', NULL),
(32, 'ORD-20260413203316-1700', 26, 1, NULL, '', 'AED', 1.0000, 'USD', 'delivered', 'paid', NULL, NULL, NULL, 220.00, 11.00, 15.00, NULL, NULL, '2026-04-13', 0.00, 0.00, 246.00, '123', '123', '', '1111111111', '111111111111', '1111111111', '11111111', '50250', 'United Arab Emirates', '111111111111', 'artofgallery19@gmail.com', '123', '123', '', '1111111111', '111111111111', '1111111111', '11111111', '50250', 'United Arab Emirates', '111111111111', NULL, '{\"first_name\":\"123\",\"last_name\":\"123\",\"company\":\"\",\"address_line1\":\"1111111111\",\"address_line2\":\"111111111111\",\"city\":\"1111111111\",\"state\":\"11111111\",\"postal_code\":\"50250\",\"country\":\"United Arab Emirates\",\"phone\":\"111111111111\"}', '{\"first_name\":\"123\",\"last_name\":\"123\",\"company\":\"\",\"address_line1\":\"1111111111\",\"address_line2\":\"111111111111\",\"city\":\"1111111111\",\"state\":\"11111111\",\"postal_code\":\"50250\",\"country\":\"United Arab Emirates\",\"phone\":\"111111111111\",\"email\":\"artofgallery19@gmail.com\"}', NULL, NULL, NULL, '2026-04-13 16:33:16', '2026-04-13 16:39:41', '2026-04-13 15:39:41'),
(33, 'ORD-20260413205200-5611', 26, 1, NULL, '', 'AED', 1.0000, 'USD', 'delivered', 'paid', NULL, NULL, NULL, 165.00, 8.25, 15.00, NULL, NULL, '2026-04-13', 0.00, 0.00, 188.25, '1111111111', '11111111111', '', '11111111111111', '1111111111111', '111111111111111111111', '11111111111111', '1111111111', '111111111111', '11111111111', 'artofgallery19@gmail.com', '1111111111', '11111111111', '', '11111111111111', '1111111111111', '111111111111111111111', '11111111111111', '1111111111', 'United Arab Emirates', '11111111111', NULL, '{\"first_name\":\"1111111111\",\"last_name\":\"11111111111\",\"company\":\"\",\"address_line1\":\"11111111111111\",\"address_line2\":\"1111111111111\",\"city\":\"111111111111111111111\",\"state\":\"11111111111111\",\"postal_code\":\"1111111111\",\"country\":\"United Arab Emirates\",\"phone\":\"11111111111\"}', '{\"first_name\":\"1111111111\",\"last_name\":\"11111111111\",\"company\":\"\",\"address_line1\":\"11111111111111\",\"address_line2\":\"1111111111111\",\"city\":\"111111111111111111111\",\"state\":\"11111111111111\",\"postal_code\":\"1111111111\",\"country\":\"111111111111\",\"phone\":\"11111111111\",\"email\":\"artofgallery19@gmail.com\"}', NULL, NULL, NULL, '2026-04-13 16:52:00', '2026-04-13 19:14:06', '2026-04-13 18:14:06'),
(34, 'ORD-20260414085727-2568', 26, 1, NULL, '', 'AED', 1.0000, 'USD', 'confirmed', 'paid', NULL, NULL, NULL, 480.00, 24.00, 15.00, NULL, NULL, NULL, 0.00, 0.00, 519.00, '123', '123', '', '111111111111111', '111111111111111', '1111111111111', '11111111', '50250', 'United Arab Emirates', '11111111111111111', 'artofgallery19@gmail.com', '123', '123', '', '111111111111111', '111111111111111', '1111111111111', '11111111', '50250', 'United Arab Emirates', '11111111111111111', NULL, '{\"first_name\":\"123\",\"last_name\":\"123\",\"company\":\"\",\"address_line1\":\"111111111111111\",\"address_line2\":\"111111111111111\",\"city\":\"1111111111111\",\"state\":\"11111111\",\"postal_code\":\"50250\",\"country\":\"United Arab Emirates\",\"phone\":\"11111111111111111\"}', '{\"first_name\":\"123\",\"last_name\":\"123\",\"company\":\"\",\"address_line1\":\"111111111111111\",\"address_line2\":\"111111111111111\",\"city\":\"1111111111111\",\"state\":\"11111111\",\"postal_code\":\"50250\",\"country\":\"United Arab Emirates\",\"phone\":\"11111111111111111\",\"email\":\"artofgallery19@gmail.com\"}', NULL, NULL, NULL, '2026-04-14 04:57:27', '2026-04-14 04:57:27', NULL),
(35, 'ORD-20260414114318-4241', 26, 1, NULL, '', 'AED', 1.0000, 'USD', 'confirmed', 'paid', NULL, NULL, NULL, 120.00, 6.00, 15.00, NULL, NULL, NULL, 0.00, 0.00, 141.00, '123', '123', '', '1111111111', '111111111111', '11111111111', '11111111', '111111111111', 'United Arab Emirates', '111111111111', 'artofgallery19@gmail.com', '123', '123', '', '1111111111', '111111111111', '11111111111', '11111111', '111111111111', 'United Arab Emirates', '111111111111', NULL, '{\"first_name\":\"123\",\"last_name\":\"123\",\"company\":\"\",\"address_line1\":\"1111111111\",\"address_line2\":\"111111111111\",\"city\":\"11111111111\",\"state\":\"11111111\",\"postal_code\":\"111111111111\",\"country\":\"United Arab Emirates\",\"phone\":\"111111111111\"}', '{\"first_name\":\"123\",\"last_name\":\"123\",\"company\":\"\",\"address_line1\":\"1111111111\",\"address_line2\":\"111111111111\",\"city\":\"11111111111\",\"state\":\"11111111\",\"postal_code\":\"111111111111\",\"country\":\"United Arab Emirates\",\"phone\":\"111111111111\",\"email\":\"artofgallery19@gmail.com\"}', NULL, NULL, NULL, '2026-04-14 07:43:18', '2026-04-14 07:43:18', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variation_id` int(11) DEFAULT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_price` decimal(10,2) DEFAULT 0.00,
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `product_sku` varchar(100) NOT NULL,
  `variant_details` text DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `total_price` decimal(10,2) NOT NULL,
  `warehouse_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `variation_id`, `variant_id`, `product_name`, `product_price`, `unit_cost`, `product_sku`, `variant_details`, `quantity`, `unit_price`, `tax_rate`, `tax_amount`, `total_price`, `warehouse_id`) VALUES
(29, 27, 7, 0, NULL, 'High Light', 100.00, 50.00, 'SKU-111001', NULL, 2, 100.00, 0.00, 0.00, 200.00, NULL),
(30, 28, 8, 0, NULL, 'Velvet Lip Liquid', 45.00, 12.00, 'SKU-111002', NULL, 2, 45.00, 0.00, 0.00, 90.00, NULL),
(31, 29, 8, 70, NULL, 'Velvet Lip Liquid', 45.00, 12.00, 'SKU-111002', NULL, 2, 45.00, 0.00, 0.00, 90.00, NULL),
(32, 29, 7, 0, NULL, 'High Light', 100.00, 50.00, 'SKU-111001', NULL, 3, 100.00, 0.00, 0.00, 300.00, NULL),
(33, 30, 8, 0, NULL, 'Velvet Lip Liquid', 45.00, 12.00, 'SKU-111002', NULL, 1, 45.00, 0.00, 0.00, 45.00, NULL),
(34, 30, 9, 0, NULL, 'Lenses', 120.00, 30.00, 'SKU-111003', NULL, 1, 120.00, 0.00, 0.00, 120.00, NULL),
(35, 31, 8, 0, NULL, 'Velvet Lip Liquid', 45.00, 12.00, 'SKU-111002', NULL, 1, 45.00, 0.00, 0.00, 45.00, NULL),
(36, 32, 7, 0, NULL, 'High Light', 100.00, 50.00, 'SKU-111001', NULL, 1, 100.00, 0.00, 0.00, 100.00, NULL),
(37, 32, 9, 0, NULL, 'Lenses', 120.00, 30.00, 'SKU-111003', NULL, 1, 120.00, 0.00, 0.00, 120.00, NULL),
(38, 33, 8, 0, NULL, 'Velvet Lip Liquid', 45.00, 12.00, 'SKU-111002', NULL, 1, 45.00, 0.00, 0.00, 45.00, NULL),
(39, 33, 9, 0, NULL, 'Lenses', 120.00, 30.00, 'SKU-111003', NULL, 1, 120.00, 0.00, 0.00, 120.00, NULL),
(40, 34, 9, 0, NULL, 'Lenses', 120.00, 30.00, 'SKU-111003', NULL, 4, 120.00, 0.00, 0.00, 480.00, NULL),
(41, 35, 9, 0, NULL, 'Lenses', 120.00, 30.00, 'SKU-111003', NULL, 1, 120.00, 0.00, 0.00, 120.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

CREATE TABLE `order_status_history` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` enum('pending','confirmed','processing','shipped','delivered','cancelled') NOT NULL,
  `comment` text DEFAULT NULL,
  `notify_customer` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_status_history`
--

INSERT INTO `order_status_history` (`id`, `order_id`, `status`, `comment`, `notify_customer`, `created_by`, `created_at`) VALUES
(59, 27, 'confirmed', 'Order confirmed - payment received', 0, NULL, '2026-04-01 09:13:13'),
(60, 27, 'processing', '', 0, 3, '2026-04-01 13:32:21'),
(61, 27, 'shipped', '', 0, NULL, '2026-04-01 13:32:36'),
(62, 27, 'delivered', '', 0, NULL, '2026-04-01 13:35:23'),
(63, 28, 'confirmed', 'Order confirmed - payment received', 0, NULL, '2026-04-03 18:11:00'),
(64, 29, 'confirmed', 'Order confirmed - payment received', 0, NULL, '2026-04-13 10:55:16'),
(65, 29, 'delivered', '', 0, NULL, '2026-04-13 12:05:00'),
(66, 29, 'confirmed', '', 0, 3, '2026-04-13 12:05:35'),
(67, 30, 'confirmed', 'Order confirmed - payment received', 0, NULL, '2026-04-13 16:17:08'),
(68, 30, 'shipped', '', 0, NULL, '2026-04-13 16:24:58'),
(69, 30, 'delivered', '', 0, NULL, '2026-04-13 16:25:28'),
(70, 31, 'confirmed', 'Order confirmed - payment received', 0, NULL, '2026-04-13 16:27:33'),
(71, 32, 'confirmed', 'Order confirmed - payment received', 0, NULL, '2026-04-13 16:33:16'),
(72, 32, 'delivered', '', 0, NULL, '2026-04-13 16:39:19'),
(73, 33, 'confirmed', 'Order confirmed - payment received', 0, NULL, '2026-04-13 16:52:00'),
(74, 33, 'delivered', '', 0, NULL, '2026-04-13 19:14:01'),
(75, 34, 'confirmed', 'Order confirmed - payment received', 0, NULL, '2026-04-14 04:57:27'),
(76, 35, 'confirmed', 'Order confirmed - payment received', 0, NULL, '2026-04-14 07:43:18');

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `id` int(11) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `page_type` enum('about','faq','terms','privacy','contact','custom') DEFAULT 'custom',
  `is_active` tinyint(1) DEFAULT 1,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `page_translations`
--

CREATE TABLE `page_translations` (
  `id` int(11) NOT NULL,
  `page_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_method` enum('credit_card','paypal','cash_on_delivery','bank_transfer','googlepay','applepay') NOT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `payment_data` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods_config`
--

CREATE TABLE `payment_methods_config` (
  `id` int(11) NOT NULL,
  `method_code` varchar(50) NOT NULL,
  `method_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `icon_class` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `settings` text DEFAULT NULL COMMENT 'JSON settings for the payment method',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_methods_config`
--

INSERT INTO `payment_methods_config` (`id`, `method_code`, `method_name`, `is_active`, `display_order`, `icon_class`, `description`, `settings`, `created_at`, `updated_at`) VALUES
(1, 'card', 'Credit/Debit Card', 1, 1, 'fas fa-credit-card', 'Pay securely with your credit or debit card', NULL, '2026-02-01 16:58:43', NULL),
(2, 'paypal', 'PayPal', 1, 2, 'fab fa-paypal', 'Fast and secure payment via PayPal', NULL, '2026-02-01 16:58:43', NULL),
(3, 'cash', 'Cash on Arrival', 1, 3, 'fas fa-money-bill-wave', 'Pay cash when you arrive for your appointment', NULL, '2026-02-01 16:58:43', NULL),
(4, 'bank_transfer', 'Bank Transfer', 0, 4, 'fas fa-university', 'Direct bank transfer payment', NULL, '2026-02-01 16:58:43', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `production_orders`
--

CREATE TABLE `production_orders` (
  `id` int(11) NOT NULL,
  `production_number` varchar(50) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `warehouse_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `produced_quantity` int(11) DEFAULT 0,
  `production_date` date NOT NULL,
  `expected_completion` date DEFAULT NULL,
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `production_orders`
--

INSERT INTO `production_orders` (`id`, `production_number`, `product_id`, `variant_id`, `warehouse_id`, `quantity`, `produced_quantity`, `production_date`, `expected_completion`, `status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(8, 'PROD-20260401165932-1892-1', 8, 67, 1, 20, 20, '2026-04-01', '2026-04-01', 'completed', 'Test Production', 3, '2026-04-01 12:59:32', '2026-04-01 13:01:28'),
(9, 'PROD-20260402213419-6545-1', 7, NULL, 1, 33, 33, '2026-04-02', '2026-04-02', 'completed', 'fgdgd', 3, '2026-04-02 17:34:19', '2026-04-02 17:35:42'),
(10, 'PROD-20260403214644-8817-1', 7, NULL, 4, 2, 2, '2026-04-03', '2026-04-03', 'completed', 'Test', 3, '2026-04-03 17:46:44', '2026-04-03 17:49:15'),
(11, 'PROD-20260413155649-8653-1', 7, NULL, 1, 22, 22, '2026-04-12', '2026-04-13', 'completed', 'ghgjg', 3, '2026-04-13 11:56:49', '2026-04-13 12:51:36'),
(15, 'PROD-20260413195843-4570-1', 9, NULL, 1, 12, 12, '2026-04-13', '2026-04-14', 'completed', NULL, 3, '2026-04-13 15:58:43', '2026-04-13 15:58:49'),
(16, 'PROD-20260413195958-2572-1', 7, NULL, 3, 12, 12, '2026-04-13', '2026-04-15', 'completed', NULL, 3, '2026-04-13 15:59:58', '2026-04-13 16:00:14'),
(17, 'PROD-20260414114909-7810-1', 9, NULL, 4, 12, 12, '2026-04-14', '2026-04-23', 'completed', NULL, 3, '2026-04-14 07:49:09', '2026-04-14 07:49:16');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `sku` varchar(100) NOT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `product_type` enum('simple','variable','digital','physical','both') DEFAULT 'simple',
  `slug` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `compare_price` decimal(10,2) DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `weight` decimal(10,2) DEFAULT NULL,
  `weight_unit` varchar(10) DEFAULT 'kg',
  `stock_quantity` int(11) DEFAULT 0,
  `low_stock_threshold` int(11) DEFAULT 5,
  `stock_status` enum('in_stock','out_of_stock','on_backorder') DEFAULT 'in_stock',
  `track_inventory` tinyint(1) DEFAULT 1,
  `main_image` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `views_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `views` int(11) DEFAULT 0,
  `sales_count` int(11) DEFAULT 0,
  `average_rating` decimal(3,2) DEFAULT 0.00,
  `review_count` int(11) DEFAULT 0,
  `is_new` tinyint(1) DEFAULT 0,
  `is_bestseller` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `sku`, `brand_id`, `product_type`, `slug`, `price`, `compare_price`, `cost_price`, `weight`, `weight_unit`, `stock_quantity`, `low_stock_threshold`, `stock_status`, `track_inventory`, `main_image`, `is_featured`, `is_active`, `meta_title`, `meta_description`, `meta_keywords`, `views_count`, `created_at`, `updated_at`, `views`, `sales_count`, `average_rating`, `review_count`, `is_new`, `is_bestseller`) VALUES
(7, 'SKU-111001', 46, 'physical', 'high-light', 100.00, NULL, 50.00, 0.10, 'kg', 166, 10, 'in_stock', 1, 'product_69d8a90e596c6.png', 1, 1, 'High Light', 'High Light', 'High Light', 60, '2026-04-01 12:56:00', '2026-04-14 07:47:42', 0, 0, 0.00, 0, 1, 0),
(8, 'SKU-111002', 46, 'variable', 'velvet-lip-liquid', 45.00, NULL, 12.00, 0.10, 'kg', 15, 10, 'in_stock', 1, 'product_69d8a8daa42d8.jpeg', 1, 1, '', '', '', 75, '2026-04-01 14:20:33', '2026-04-14 05:15:11', 0, 0, 0.00, 0, 1, 0),
(9, 'SKU-111003', 46, 'physical', 'lenses', 120.00, NULL, 30.00, 0.10, 'kg', 550, 10, 'in_stock', 1, 'product_69d8a8bb76670.jpeg', 1, 1, 'Cat Eye Lenses', 'Cat Eye Lenses', 'Cat Eye Lenses', 48, '2026-04-01 17:09:24', '2026-04-14 07:51:47', 0, 0, 0.00, 0, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`product_id`, `category_id`) VALUES
(7, 13),
(8, 16),
(9, 16);

-- --------------------------------------------------------

--
-- Table structure for table `product_digital_files`
--

CREATE TABLE `product_digital_files` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `download_limit` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `sort_order`, `created_at`) VALUES
(25, 9, 'product_69d8a8bb76670.jpeg', 0, '2026-04-01 13:09:24'),
(31, 8, 'product_69d8a8daa42d8.jpeg', 0, '2026-04-06 17:15:49'),
(32, 7, 'product_69d8a90e596c6.png', 0, '2026-04-06 17:20:43'),
(38, 7, 'product_img_69d8a9801d753.png', 1, '2026-04-10 07:40:48');

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `rating` tinyint(1) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `title` varchar(255) DEFAULT NULL,
  `review` text DEFAULT NULL,
  `is_verified_purchase` tinyint(1) DEFAULT 0,
  `is_approved` tinyint(1) DEFAULT 0,
  `helpful_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_translations`
--

CREATE TABLE `product_translations` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `short_description` text DEFAULT NULL,
  `description` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_translations`
--

INSERT INTO `product_translations` (`id`, `product_id`, `language_id`, `name`, `short_description`, `description`) VALUES
(14, 7, 1, 'High Light', 'By Cateye', 'By Cateye'),
(15, 7, 2, '??????????????', '???????????? ?????? ????', '???????????? ?????? ????'),
(16, 8, 1, 'Velvet Lip Liquid', 'Velvet Lip Liquid', 'Velvet Lip Liquid'),
(17, 8, 2, 'سائل شفاه مخملي', 'سائل شفاه مخملي', 'سائل شفاه مخملي'),
(18, 9, 1, 'Lenses', 'Cat Eye Lenses', 'Cat Eye Lenses'),
(19, 9, 2, 'عدسات', 'عدسات عين القطة', 'عدسات عين القطة');

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `sku` varchar(100) NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `compare_price` decimal(10,2) DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `sku`, `price`, `compare_price`, `stock_quantity`, `image`, `is_active`, `created_at`) VALUES
(67, 8, 'SKU-111002SoftPink', 50.00, NULL, 20, NULL, 1, '2026-04-01 10:20:33'),
(70, 8, 'SKU-111002BrightRed', 45.00, NULL, 0, NULL, 1, '2026-04-01 10:26:07');

-- --------------------------------------------------------

--
-- Table structure for table `product_variant_attributes`
--

CREATE TABLE `product_variant_attributes` (
  `variant_id` int(11) NOT NULL,
  `attribute_value_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_variant_attributes`
--

INSERT INTO `product_variant_attributes` (`variant_id`, `attribute_value_id`) VALUES
(67, 12),
(70, 10);

-- --------------------------------------------------------

--
-- Table structure for table `product_warehouse_stock`
--

CREATE TABLE `product_warehouse_stock` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `warehouse_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 0,
  `reserved_quantity` int(11) DEFAULT 0,
  `available_quantity` int(11) GENERATED ALWAYS AS (`quantity` - `reserved_quantity`) STORED,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_warehouse_stock`
--

INSERT INTO `product_warehouse_stock` (`id`, `product_id`, `variant_id`, `warehouse_id`, `quantity`, `reserved_quantity`, `updated_at`) VALUES
(25, 7, NULL, 1, 151, 0, '2026-04-13 16:33:16'),
(26, 8, 67, 1, 20, 0, '2026-04-01 13:01:28'),
(27, 8, 70, 1, 0, 0, '2026-04-13 18:47:19'),
(28, 7, NULL, 4, 3, 0, '2026-04-03 17:49:15'),
(29, 8, NULL, 1, -5, 0, '2026-04-13 16:52:00'),
(30, 9, NULL, 1, 54, 0, '2026-04-14 07:51:39'),
(31, 9, NULL, 3, 484, 0, '2026-04-13 15:56:59'),
(32, 7, NULL, 3, 12, 0, '2026-04-13 16:00:14'),
(33, 9, NULL, 4, 12, 0, '2026-04-14 07:49:16');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL,
  `po_number` varchar(50) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `expected_delivery` date DEFAULT NULL,
  `status` enum('draft','pending','confirmed','received','cancelled') DEFAULT 'draft',
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shipping_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`id`, `po_number`, `supplier_id`, `warehouse_id`, `order_date`, `expected_delivery`, `status`, `subtotal`, `tax_amount`, `shipping_cost`, `total_amount`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(16, 'PO-20260401171317-3349', 5, 1, '2026-04-01', '2026-04-01', 'received', 528.00, 12.00, 23.00, 563.00, 'dfsdf', 3, '2026-04-01 13:13:17', '2026-04-01 13:13:40'),
(17, 'PO-20260401171619-3556', 5, 1, '2026-04-01', '2026-04-01', 'draft', 30000.00, 500.00, 123.00, 30623.00, 'gjg', 3, '2026-04-01 13:16:19', '2026-04-01 13:16:19'),
(18, 'PO-20260414115035-7814', 5, 1, '2026-04-14', '2026-04-14', 'received', 570.00, 0.00, 0.00, 570.00, NULL, 3, '2026-04-14 07:50:35', '2026-04-14 07:50:48');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `id` int(11) NOT NULL,
  `purchase_order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `received_quantity` int(11) DEFAULT 0,
  `unit_price` decimal(10,2) NOT NULL,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `total_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_order_items`
--

INSERT INTO `purchase_order_items` (`id`, `purchase_order_id`, `product_id`, `variant_id`, `quantity`, `received_quantity`, `unit_price`, `tax_rate`, `total_price`) VALUES
(21, 16, 8, 70, 44, 44, 12.00, 0.00, 528.00),
(22, 17, 9, NULL, 1000, 0, 30.00, 0.00, 30000.00),
(23, 18, 9, NULL, 19, 19, 30.00, 0.00, 570.00);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_returns`
--

CREATE TABLE `purchase_returns` (
  `id` int(11) NOT NULL,
  `return_number` varchar(50) NOT NULL,
  `purchase_order_id` int(11) DEFAULT NULL,
  `return_date` date NOT NULL,
  `status` enum('pending','approved','rejected','refunded') DEFAULT 'pending',
  `reason` text DEFAULT NULL,
  `refund_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_return_items`
--

CREATE TABLE `purchase_return_items` (
  `id` int(11) NOT NULL,
  `purchase_return_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales_returns`
--

CREATE TABLE `sales_returns` (
  `id` int(11) NOT NULL,
  `return_number` varchar(50) NOT NULL,
  `order_id` int(11) NOT NULL,
  `return_date` date NOT NULL,
  `status` enum('pending','approved','rejected','refunded') DEFAULT 'pending',
  `reason` text DEFAULT NULL,
  `refund_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `refund_method` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales_return_items`
--

CREATE TABLE `sales_return_items` (
  `id` int(11) NOT NULL,
  `sales_return_id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL COMMENT 'Duration in minutes',
  `price` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_package` tinyint(2) NOT NULL DEFAULT 0,
  `compare_price` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `category_id`, `slug`, `image`, `duration`, `price`, `is_active`, `created_at`, `is_package`, `compare_price`) VALUES
(3, 3, 'deep-cleaning', 'service_697f095b1411b.jpg', 45, 450.00, 1, '2026-01-05 17:30:42', 0, NULL),
(4, 3, 'normal-cleaning', 'service_696b43bb86239.jpg', 30, 350.00, 1, '2026-01-05 17:30:42', 0, NULL),
(5, 2, 'makeup-normal', 'service_6979f32a02177.webp', 60, 350.00, 1, '2026-01-05 17:30:42', 0, NULL),
(6, 2, 'makeup -special', 'service_6979f42db742d.jpg', 90, 500.00, 1, '2026-01-05 17:30:42', 0, NULL),
(12, 1, 'body-and-feet', 'service_6979ef3d6487b.webp', 60, 150.00, 1, '2026-01-10 07:35:49', 0, NULL),
(13, 1, 'cleaning-uterus', 'service_6979f13e35025.jpg', 60, 150.00, 1, '2026-01-10 10:55:52', 0, NULL),
(14, 3, 'power-c-cleaning', 'service_697f0b821329c.jpg', 30, 500.00, 1, '2026-02-01 08:14:58', 0, NULL),
(15, 3, 'hydronic-cleaning', 'service_697f0c60e8ebc.jpg', 45, 450.00, 1, '2026-02-01 08:18:40', 0, NULL),
(16, 3, 'retinol-skin-cleaning', 'service_697f0d36e9f06.jpg', 40, 450.00, 1, '2026-02-01 08:22:14', 0, NULL),
(17, 4, 'normal-scrub', 'service_697f173ee91ff.jpg', 30, 150.00, 1, '2026-02-01 09:05:03', 0, NULL),
(18, 7, 'swedish-massage', 'service_698492b252d37.webp', 45, 200.00, 1, '2026-02-05 12:53:06', 0, NULL),
(19, 7, 'lymphatic-massage', 'service_69849404f1b86.jpg', 45, 300.00, 1, '2026-02-05 12:58:44', 0, NULL),
(20, 7, 'deep-tissues-massage', 'service_69849539c49aa.jpg', 60, 300.00, 1, '2026-02-05 13:03:53', 0, NULL),
(21, 7, 'thai-swedish-massage', 'service_69849617e476b.jpg', 60, 400.00, 1, '2026-02-05 13:07:35', 0, 450),
(22, 6, 'normal-korean-spa', 'service_6984c5793a0fc.jpg', 40, 250.00, 1, '2026-02-05 16:29:45', 0, NULL),
(23, 6, 'korean-spa-nashi', 'service_6984c6c9352a3.jpg', 45, 350.00, 1, '2026-02-05 16:35:21', 0, NULL),
(24, 6, 'korean-spa-loreal', 'service_6984c7a7ecf6e.jpg', 60, 350.00, 1, '2026-02-05 16:39:03', 0, NULL),
(25, 6, 'korean-spa-deep-vip', 'service_6984c8156087b.jpg', 60, 500.00, 1, '2026-02-05 16:40:53', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `service_bookings`
--

CREATE TABLE `service_bookings` (
  `id` int(11) NOT NULL,
  `booking_number` varchar(50) NOT NULL,
  `service_id` int(11) NOT NULL,
  `time_slot_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `booking_date` date NOT NULL,
  `booking_time` time NOT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL COMMENT 'Selected payment method: cod, card, paypal, googlepay, applepay',
  `payment_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `confirmed_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancelled_by` int(11) DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `coupon_code` varchar(50) DEFAULT NULL COMMENT 'Coupon code used if any',
  `coupon_discount_amount` decimal(10,2) DEFAULT NULL COMMENT 'Discount amount applied from coupon',
  `coupon_discount_type` varchar(20) DEFAULT NULL COMMENT 'Coupon discount type: fixed or percentage',
  `coupon_discount_percentage` decimal(5,2) DEFAULT NULL COMMENT 'Discount percentage if percentage type',
  `original_amount` decimal(10,2) DEFAULT NULL COMMENT 'Original amount before discount',
  `currency_code` varchar(10) DEFAULT 'AED',
  `exchange_rate` decimal(10,4) DEFAULT 1.0000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores all service bookings with strict slot management (one booking per slot)';

--
-- Dumping data for table `service_bookings`
--

INSERT INTO `service_bookings` (`id`, `booking_number`, `service_id`, `time_slot_id`, `user_id`, `customer_name`, `customer_email`, `customer_phone`, `booking_date`, `booking_time`, `status`, `payment_status`, `payment_method`, `payment_id`, `notes`, `admin_notes`, `total_amount`, `created_at`, `confirmed_at`, `confirmed_by`, `updated_at`, `cancelled_at`, `cancelled_by`, `cancellation_reason`, `completed_at`, `completed_by`, `coupon_code`, `coupon_discount_amount`, `coupon_discount_type`, `coupon_discount_percentage`, `original_amount`, `currency_code`, `exchange_rate`) VALUES
(21, 'BK-1775038264-8678', 13, 317, 21, 'Albaraa Bitar', 'albara.bitar@gmail.com', '+971552969432', '2026-04-08', '09:00:00', 'confirmed', 'paid', 'card', NULL, '', NULL, 110.00, '2026-04-01 14:11:04', '2026-04-01 14:11:04', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'SAVE40', 40.00, 'fixed', 26.67, 150.00, 'AED', 1.0000),
(22, 'BK-1775238856-4993', 12, 378, NULL, 'Hamza Mughal', 'hm8620730@gmail.com', '03245932916', '2026-04-07', '09:00:00', 'confirmed', 'paid', 'card', NULL, 'Test', NULL, 150.00, '2026-04-03 21:54:16', '2026-04-03 21:54:16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 150.00, 'AED', 1.0000),
(23, 'BK-1776075419-3617', 22, 359, NULL, 'Albaraa', 'albara.bitar@gmail.com', '989898989', '2026-04-23', '15:00:00', 'confirmed', 'paid', 'card', NULL, 'sdfs', NULL, 250.00, '2026-04-13 14:16:59', '2026-04-13 14:16:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 250.00, 'AED', 1.0000),
(25, 'BK-1776075672-9333', 12, 375, NULL, 'Albaraa', 'albara.bitar@gmail.com', '989898989', '2026-04-19', '00:00:00', 'confirmed', 'paid', 'card', NULL, 'sdsdss', NULL, 150.00, '2026-04-13 14:21:12', '2026-04-13 14:21:12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 150.00, 'AED', 1.0000),
(27, 'BK-1776075847-6559', 5, 310, NULL, 'Albaraa', 'albara.bitar@gmail.com', '423242', '2026-04-21', '09:00:00', 'confirmed', 'paid', 'card', NULL, '23', NULL, 350.00, '2026-04-13 14:24:07', '2026-04-13 14:24:07', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 350.00, 'AED', 1.0000),
(29, 'BK-1776096457-696-1', 17, 348, NULL, 'Test', '123@mail.com', 'test', '2026-04-22', '09:00:00', 'confirmed', 'paid', 'card', NULL, '123', NULL, 150.00, '2026-04-13 15:07:37', '2026-04-13 15:07:37', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 150.00, 'AED', 1.0000),
(30, 'BK-1776096457-696-2', 14, 341, NULL, 'Test', '123@mail.com', 'test', '2026-04-21', '09:00:00', 'confirmed', 'paid', 'card', NULL, '123', NULL, 500.00, '2026-04-13 15:07:45', '2026-04-13 15:07:45', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 500.00, 'AED', 1.0000),
(31, 'BK-1776096671-756-1', 12, 377, 26, '123 123', 'artofgallery19@gmail.com', '1222211', '2026-04-20', '09:00:00', 'confirmed', 'paid', 'card', NULL, '', NULL, 150.00, '2026-04-13 15:11:11', '2026-04-13 15:11:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 150.00, 'AED', 1.0000),
(32, 'BK-1776096671-756-2', 5, 311, 26, '123 123', 'artofgallery19@gmail.com', '1222211', '2026-04-22', '09:00:00', 'confirmed', 'paid', 'card', NULL, '', NULL, 350.00, '2026-04-13 15:11:19', '2026-04-13 15:11:19', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 350.00, 'AED', 1.0000),
(33, 'BK-1776142363-833-1', 4, 338, 26, '123 123', 'artofgallery19@gmail.com', '1222211', '2026-04-24', '09:00:00', 'confirmed', 'paid', 'card', NULL, 'iuhuih', NULL, 350.00, '2026-04-14 03:52:43', '2026-04-14 03:52:43', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 350.00, 'AED', 1.0000),
(34, 'BK-1776143074-628-1', 6, 308, 26, '123 123', 'artofgallery19@gmail.com', '1222211', '2026-04-24', '09:00:00', 'confirmed', 'paid', 'card', NULL, '11111111111111', NULL, 500.00, '2026-04-14 04:04:34', '2026-04-14 04:04:34', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 500.00, 'AED', 1.0000),
(35, 'BK-1776151788-650-1', 23, 361, 26, '123 123', 'artofgallery19@gmail.com', '1222211', '2026-04-22', '11:00:00', 'confirmed', 'paid', 'card', NULL, '', NULL, 350.00, '2026-04-14 06:29:48', '2026-04-14 06:29:48', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 350.00, 'AED', 1.0000),
(36, 'BK-1776151788-650-2', 5, 313, 26, '123 123', 'artofgallery19@gmail.com', '1222211', '2026-04-24', '09:00:00', 'confirmed', 'paid', 'card', NULL, '', NULL, 350.00, '2026-04-14 06:29:55', '2026-04-14 06:29:55', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 350.00, 'AED', 1.0000);

--
-- Triggers `service_bookings`
--
-- (Procedures/Triggers removed - run install_triggers_procedures.php after import)


-- --------------------------------------------------------

--
-- Table structure for table `service_booking_reminders`
--

CREATE TABLE `service_booking_reminders` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `reminder_type` enum('24_hours','1_hour','custom') DEFAULT '24_hours',
  `reminder_method` enum('email','sms','both') DEFAULT 'email',
  `sent_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_booking_reviews`
--

CREATE TABLE `service_booking_reviews` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `rating` int(1) NOT NULL COMMENT '1 to 5 stars',
  `review_text` text DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `admin_response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_categories`
--

CREATE TABLE `service_categories` (
  `id` int(11) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `service_categories`
--

INSERT INTO `service_categories` (`id`, `slug`, `image`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'chinese-treatment', NULL, 1, 1, '2026-01-16 19:22:37'),
(2, 'makeup', NULL, 2, 1, '2026-01-16 19:22:37'),
(3, 'skin-cleaning', NULL, 3, 1, '2026-01-16 19:22:37'),
(4, 'skin-scrub', NULL, 4, 1, '2026-02-01 08:57:51'),
(5, 'moroccan-bath', NULL, 5, 1, '2026-02-05 12:42:40'),
(6, 'korean-spa', NULL, 6, 1, '2026-02-05 12:44:10'),
(7, 'massage', NULL, 7, 1, '2026-02-05 12:46:31');

-- --------------------------------------------------------

--
-- Table structure for table `service_category_translations`
--

CREATE TABLE `service_category_translations` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `service_category_translations`
--

INSERT INTO `service_category_translations` (`id`, `category_id`, `language_id`, `name`, `description`, `meta_title`, `meta_description`, `created_at`) VALUES
(1, 1, 1, 'Chinese Treatment', 'Chinese Treatment', 'Chinese Treatment', 'Chinese Treatment', '2026-01-16 19:22:37'),
(2, 1, 2, '???????????? ????????????', '???????????? ????????????', '???????????? ????????????', '???????????? ????????????', '2026-01-16 19:22:37'),
(3, 2, 1, 'Makeup', 'Makeup', 'Makeup', 'Makeup', '2026-01-16 19:22:37'),
(4, 2, 2, '??????????????', '??????????????', '??????????????', '??????????????', '2026-01-16 19:22:37'),
(5, 3, 1, 'Skin Cleaning', 'All type of professional Skin Cleaning', 'Skin Cleaning', 'All type of professional Skin Cleaning', '2026-01-16 19:22:37'),
(6, 3, 2, '?????????? ????????????', '???????? ?????????? ?????????? ????????????', '?????????? ????????????', '???????? ?????????? ?????????? ????????????', '2026-01-16 19:22:37'),
(9, 4, 1, 'Skin Scrub', 'Skin Scrub', 'Skin Scrub', 'Skin Scrub', '2026-02-01 08:57:51'),
(10, 4, 2, '????????????', '????????????', '????????????', '????????????', '2026-02-01 08:57:51'),
(11, 5, 1, 'Moroccan bath', 'Moroccan bath', 'Moroccan bath', 'Moroccan bath', '2026-02-05 12:42:40'),
(12, 5, 2, '???????????? ??????????????', '???????????? ??????????????', '???????????? ??????????????', '???????????? ??????????????', '2026-02-05 12:42:40'),
(13, 6, 1, 'Korean Spa', 'Korean Spa', 'Korean Spa', 'Korean Spa', '2026-02-05 12:44:10'),
(14, 6, 2, '?????????? ?????????? ??????????????', '?????????? ?????????? ??????????????', '?????????? ?????????? ??????????????', '?????????? ?????????? ??????????????', '2026-02-05 12:44:10'),
(15, 7, 1, 'Massage', 'Massage', 'Massage', 'Massage', '2026-02-05 12:46:31'),
(16, 7, 2, '??????????????', '??????????????', '??????????????', '??????????????', '2026-02-05 12:46:31');

-- --------------------------------------------------------

--
-- Table structure for table `service_payments`
--

CREATE TABLE `service_payments` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `payment_method` varchar(50) NOT NULL COMMENT 'card, paypal, cash, bank_transfer',
  `payment_gateway` varchar(50) DEFAULT NULL COMMENT 'stripe, paypal, etc',
  `transaction_id` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'USD',
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `payment_data` text DEFAULT NULL COMMENT 'JSON data for payment details',
  `paid_at` timestamp NULL DEFAULT NULL,
  `refunded_at` timestamp NULL DEFAULT NULL,
  `refund_amount` decimal(10,2) DEFAULT NULL,
  `refund_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks all payment transactions for service bookings';

-- --------------------------------------------------------

--
-- Table structure for table `service_payment_logs`
--

CREATE TABLE `service_payment_logs` (
  `id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `event_type` varchar(50) NOT NULL COMMENT 'payment_initiated, payment_completed, payment_failed, refund_initiated, refund_completed',
  `event_data` text DEFAULT NULL COMMENT 'JSON data for event details',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit log for all payment events and activities';

-- --------------------------------------------------------

--
-- Table structure for table `service_requests`
--

CREATE TABLE `service_requests` (
  `id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `request_details` text NOT NULL,
  `preferred_date` date DEFAULT NULL,
  `preferred_time` time DEFAULT NULL,
  `status` enum('pending','confirmed','in_progress','completed','cancelled') DEFAULT 'pending',
  `assigned_to` int(11) DEFAULT NULL,
  `completion_date` datetime DEFAULT NULL,
  `rating` tinyint(1) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_time_slots`
--

CREATE TABLE `service_time_slots` (
  `id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `day_of_week` tinyint(1) NOT NULL COMMENT '0=Sunday, 1=Monday, ... 6=Saturday',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `max_bookings` int(11) DEFAULT 1 COMMENT 'Maximum concurrent bookings for this slot',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `service_time_slots`
--

INSERT INTO `service_time_slots` (`id`, `service_id`, `day_of_week`, `start_time`, `end_time`, `max_bookings`, `is_active`, `created_at`) VALUES
(304, 6, 1, '09:00:00', '10:00:00', 1, 1, '2026-01-28 11:34:05'),
(305, 6, 2, '09:00:00', '10:00:00', 1, 1, '2026-01-28 11:34:05'),
(306, 6, 3, '09:00:00', '10:00:00', 1, 1, '2026-01-28 11:34:05'),
(307, 6, 4, '09:00:00', '10:00:00', 1, 1, '2026-01-28 11:34:05'),
(308, 6, 5, '09:00:00', '10:00:00', 1, 1, '2026-01-28 11:34:05'),
(309, 5, 1, '09:00:00', '10:00:00', 1, 1, '2026-01-28 11:34:22'),
(310, 5, 2, '09:00:00', '10:00:00', 1, 1, '2026-01-28 11:34:22'),
(311, 5, 3, '09:00:00', '10:00:00', 1, 1, '2026-01-28 11:34:22'),
(312, 5, 4, '09:00:00', '10:00:00', 1, 1, '2026-01-28 11:34:22'),
(313, 5, 5, '09:00:00', '10:00:00', 1, 1, '2026-01-28 11:34:22'),
(314, 13, 0, '00:00:00', '13:00:00', 1, 1, '2026-02-01 08:01:28'),
(315, 13, 1, '09:00:00', '10:00:00', 1, 1, '2026-02-01 08:01:28'),
(316, 13, 2, '09:00:00', '10:00:00', 1, 1, '2026-02-01 08:01:28'),
(317, 13, 3, '09:00:00', '10:00:00', 1, 1, '2026-02-01 08:01:28'),
(318, 13, 4, '09:00:00', '10:00:00', 1, 1, '2026-02-01 08:01:28'),
(319, 13, 5, '09:00:00', '10:00:00', 1, 1, '2026-02-01 08:01:28'),
(320, 13, 6, '09:00:00', '10:00:00', 1, 1, '2026-02-01 08:01:28'),
(328, 3, 1, '09:00:00', '10:00:00', 1, 1, '2026-02-01 08:05:47'),
(329, 3, 2, '09:00:00', '10:00:00', 1, 1, '2026-02-01 08:05:47'),
(330, 3, 3, '09:00:00', '10:00:00', 1, 1, '2026-02-01 08:05:47'),
(331, 3, 4, '09:00:00', '10:00:00', 1, 1, '2026-02-01 08:05:47'),
(332, 3, 5, '09:00:00', '10:00:00', 1, 1, '2026-02-01 08:05:47'),
(333, 4, 0, '09:00:00', '10:00:00', 1, 1, '2026-02-01 08:06:08'),
(334, 4, 1, '09:00:00', '10:00:00', 1, 1, '2026-02-01 08:06:08'),
(335, 4, 2, '09:00:00', '10:00:00', 1, 1, '2026-02-01 08:06:08'),
(336, 4, 3, '09:00:00', '10:00:00', 1, 1, '2026-02-01 08:06:08'),
(337, 4, 4, '09:00:00', '10:00:00', 1, 1, '2026-02-01 08:06:08'),
(338, 4, 5, '09:00:00', '10:00:00', 1, 1, '2026-02-01 08:06:08'),
(339, 4, 6, '09:00:00', '10:00:00', 1, 1, '2026-02-01 08:06:08'),
(340, 14, 1, '09:00:00', '10:00:00', 1, 1, '2026-02-01 08:14:58'),
(341, 14, 2, '09:00:00', '10:00:00', 1, 1, '2026-02-01 08:14:58'),
(342, 16, 0, '09:00:00', '10:00:00', 1, 1, '2026-02-01 08:22:14'),
(343, 16, 1, '09:00:00', '10:00:00', 1, 1, '2026-02-01 08:22:14'),
(346, 17, 2, '09:00:00', '09:30:00', 1, 1, '2026-02-03 18:24:54'),
(347, 17, 2, '13:00:00', '13:30:00', 1, 1, '2026-02-03 18:24:54'),
(348, 17, 3, '09:00:00', '09:30:00', 1, 1, '2026-02-03 18:24:54'),
(349, 18, 1, '00:00:00', '12:45:00', 1, 1, '2026-02-05 12:53:06'),
(350, 18, 2, '00:00:00', '12:45:00', 1, 1, '2026-02-05 12:53:06'),
(351, 19, 3, '09:00:00', '09:45:00', 1, 1, '2026-02-05 12:58:45'),
(352, 19, 5, '09:00:00', '09:45:00', 1, 1, '2026-02-05 12:58:45'),
(353, 20, 1, '09:00:00', '10:00:00', 1, 1, '2026-02-05 13:03:53'),
(354, 20, 1, '11:00:00', '00:00:00', 1, 1, '2026-02-05 13:03:53'),
(355, 20, 1, '13:00:00', '14:00:00', 1, 1, '2026-02-05 13:03:53'),
(356, 20, 3, '09:00:00', '10:00:00', 1, 1, '2026-02-05 13:03:53'),
(358, 21, 4, '00:00:00', '13:00:00', 1, 1, '2026-02-05 14:02:11'),
(359, 22, 4, '15:00:00', '15:40:00', 1, 1, '2026-02-05 16:29:45'),
(360, 22, 6, '14:00:00', '14:40:00', 1, 1, '2026-02-05 16:29:45'),
(361, 23, 3, '11:00:00', '11:45:00', 1, 1, '2026-02-05 16:35:21'),
(362, 24, 1, '15:00:00', '16:00:00', 1, 1, '2026-02-05 16:39:03'),
(364, 25, 3, '10:00:00', '11:00:00', 1, 1, '2026-03-16 18:17:28'),
(374, 12, 0, '11:00:00', '12:00:00', 1, 1, '2026-03-31 18:47:38'),
(375, 12, 0, '00:00:00', '13:00:00', 1, 1, '2026-03-31 18:47:38'),
(376, 12, 0, '13:00:00', '14:00:00', 1, 1, '2026-03-31 18:47:38'),
(377, 12, 1, '09:00:00', '10:00:00', 1, 1, '2026-03-31 18:47:38'),
(378, 12, 2, '09:00:00', '10:00:00', 1, 1, '2026-03-31 18:47:38'),
(379, 12, 3, '09:00:00', '10:00:00', 1, 1, '2026-03-31 18:47:38'),
(380, 12, 4, '09:00:00', '10:00:00', 1, 1, '2026-03-31 18:47:38'),
(381, 12, 5, '09:00:00', '10:00:00', 1, 1, '2026-03-31 18:47:38'),
(382, 12, 6, '09:00:00', '10:00:00', 1, 1, '2026-03-31 18:47:38');

-- --------------------------------------------------------

--
-- Table structure for table `service_translations`
--

CREATE TABLE `service_translations` (
  `id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `long_description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `service_translations`
--

INSERT INTO `service_translations` (`id`, `service_id`, `language_id`, `name`, `description`, `long_description`) VALUES
(5, 3, 1, 'Deep Cleaning', 'Deep Cleaning', 'Deep Cleaning'),
(6, 4, 1, 'Normal Cleaning', 'Normal Cleaning', 'Normal Cleaning'),
(7, 5, 1, 'Makeup - Normal', 'Makeup - Normal', 'Makeup - Normal'),
(8, 6, 1, 'Makeup - Special', 'Makeup - Special', 'Professional makeup application for special occasions'),
(18, 3, 2, '?????????????? ????????????', '?????????????? ????????????', '?????????????? ????????????'),
(19, 4, 2, '?????????? ????????', '?????????? ????????', '?????????? ????????'),
(20, 5, 2, '?????????????? ????????????', '?????????????? ????????????', '?????????????? ????????????'),
(21, 6, 2, '?????????????? ??????????', '?????????????? ??????????', '?????????? ?????????????? ?????????????????? ????????????'),
(24, 12, 1, 'Body and Feet', 'Chinese treatment for Body and Feet', 'Chinese treatment for Body and Feet'),
(25, 12, 2, '?????????????? ?? ??????????', '???????????? ???????????? ?????????????? ?? ??????????', '???????????? ???????????? ?????????????? ?? ??????????'),
(26, 13, 1, 'Cleaning the uterus', 'Chinese treatment cleansing the uterus', 'Chinese treatment cleansing the uterus'),
(27, 13, 2, '?????????? ??????????', '???????????? ???????????? ?????????? ??????????', '???????????? ???????????? ?????????? ??????????'),
(28, 14, 1, 'Power C Cleaning', 'Power C Cleaning', 'Power C Cleaning'),
(29, 14, 2, '?????????? ???? ?????????????? C', '?????????? ???? ?????????????? C', '?????????? ???? ?????????????? C'),
(30, 15, 1, 'Hydronic Cleaning', 'Hydronic Cleaning', 'Hydronic Cleaning'),
(31, 15, 2, '?????????? ????????????????', '?????????? ????????????????', '?????????? ????????????????'),
(32, 16, 1, 'Retinol Skin Cleaning', 'Retinol Skin Cleaning', 'Retinol Skin Cleaning'),
(33, 16, 2, '?????????? ??????????????????', '?????????? ??????????????????', '?????????? ??????????????????'),
(34, 17, 1, 'Normal Scrub', 'Normal Scrub', 'Normal Scrub'),
(35, 17, 2, '???????? ????????', '???????? ????????', '???????? ????????'),
(36, 18, 1, 'Swedish Massage', 'Swedish Massage', 'Swedish Massage'),
(37, 18, 2, '???????????? ??????????????', '???????????? ??????????????', '???????????? ??????????????'),
(38, 19, 1, 'Lymphatic Massage', 'Lymphatic Massage', 'Lymphatic Massage'),
(39, 19, 2, '???????????? ????????????????', '???????????? ????????????????', '???????????? ????????????????'),
(40, 20, 1, 'Deep Tissues Massage', 'Deep Tissues Massage', 'Deep Tissues Massage'),
(41, 20, 2, '???????? ?????????????? ??????????????', '???????? ?????????????? ??????????????', '???????? ?????????????? ??????????????'),
(42, 21, 1, 'Thai-Swedish Massage', 'Thai-Swedish Massage', 'Thai-Swedish Massage'),
(43, 21, 2, '???????????? ????????????????????-??????????????', '???????????? ????????????????????-??????????????', '???????????? ????????????????????-??????????????'),
(44, 22, 1, 'Normal Korean Spa', 'Normal Korean Spa', 'Normal Korean Spa'),
(45, 22, 2, '?????????? ???????????? ????????????', '?????????? ???????????? ????????????', '?????????? ???????????? ????????????'),
(46, 23, 1, 'Korean Spa Nashi', 'Korean Spa Nashi', 'Korean Spa Nashi'),
(47, 23, 2, '?????????? ???????????? ????????', '?????????? ???????????? ????????', '?????????? ???????????? ????????'),
(48, 24, 1, 'Korean Spa Loreal', 'Korean Spa Loreal', 'Korean Spa Loreal'),
(49, 24, 2, '?????????? ???????????? ????????????', '?????????? ???????????? ????????????', '?????????? ???????????? ????????????'),
(50, 25, 1, 'Korean Spa Deep VIP', 'Korean Spa Deep VIP', 'Korean Spa Deep VIP'),
(51, 25, 2, '?????????? ???????????? ???????????? VIP', '?????? ???????? VIP', '?????? ???????? ?????????? ????????????');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `group` varchar(50) DEFAULT 'general',
  `type` varchar(20) DEFAULT 'text',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `group`, `type`, `updated_at`) VALUES
(1, 'site_name', 'Cat Eye Beauty', 'general', 'text', '2026-03-31 12:09:53'),
(2, 'site_tagline', 'Best Beauty Care Experience', 'general', 'text', '2026-01-05 13:44:44'),
(3, 'site_description', 'Online Portal of Cat Eyes', 'general', 'textarea', '2026-01-05 13:44:44'),
(4, 'site_logo', 'logo_69cbb991f1d86.png', 'general', 'file', '2026-03-31 12:09:53'),
(5, 'site_favicon', 'favicon_69b66a0c54aee.png', 'general', 'file', '2026-03-15 08:13:00'),
(6, 'contact_email', 'info@cateyesbeauty.com', 'general', 'email', '2026-03-31 12:10:27'),
(7, 'contact_phone', '971-55-1273327', 'general', 'text', '2026-01-31 20:51:22'),
(8, 'contact_address', 'Bani Yas, Abu Dhabi, UAE', 'general', 'textarea', '2026-01-31 20:51:22'),
(9, 'timezone', 'Asia/Dubai', 'general', 'select', '2026-03-15 08:13:00'),
(10, 'date_format', 'Y-m-d', 'general', 'select', '2026-01-04 17:28:14'),
(11, 'time_format', 'H:i:s', 'general', 'select', '2026-01-04 17:28:14'),
(12, 'primary_color', '#604d5e', 'appearance', 'color', '2026-02-05 15:52:05'),
(13, 'secondary_color', '#968b98', 'appearance', 'color', '2026-02-05 17:23:11'),
(14, 'success_color', '#465a62', 'appearance', 'color', '2026-02-05 15:48:41'),
(15, 'danger_color', '#ff5490', 'appearance', 'color', '2026-02-05 15:43:05'),
(16, 'warning_color', '#efe6ea', 'appearance', 'color', '2026-02-05 17:36:00'),
(17, 'info_color', '#fbdfeb', 'appearance', 'color', '2026-02-05 15:43:05'),
(18, 'font_family', '\'Vollkorn\', serif', 'appearance', 'select', '2026-03-15 08:26:20'),
(19, 'font_size', '16', 'appearance', 'number', '2026-03-02 17:13:04'),
(20, 'header_style', 'default', 'appearance', 'select', '2026-01-04 17:28:14'),
(21, 'footer_style', 'default', 'appearance', 'select', '2026-01-04 17:28:14'),
(22, 'smtp_host', 'smtp.gmail.com', 'email', 'text', '2026-01-04 17:28:14'),
(23, 'smtp_port', '587', 'email', 'number', '2026-01-04 17:28:14'),
(24, 'smtp_username', '221400016@gift.edu.pk', 'email', 'text', '2026-04-14 05:03:55'),
(25, 'smtp_password', 'uyxcceaxmtdqegvl', 'email', 'password', '2026-04-14 05:02:42'),
(26, 'smtp_encryption', 'tls', 'email', 'select', '2026-01-04 17:28:14'),
(27, 'mail_from_address', '221400016@gift.edu.pk', 'email', 'email', '2026-04-14 05:03:22'),
(28, 'mail_from_name', 'Cat Eyes', 'email', 'text', '2026-03-15 08:21:13'),
(29, 'payment_cod_enabled', '0', 'payment', 'checkbox', '2026-04-13 09:37:11'),
(30, 'payment_stripe_enabled', '1', 'payment', 'checkbox', '2026-03-31 12:08:00'),
(31, 'payment_stripe_public_key', '', 'payment', 'text', '2026-01-04 17:28:14'),
(32, 'payment_stripe_secret_key', '', 'payment', 'password', '2026-01-04 17:28:14'),
(33, 'payment_paypal_enabled', '0', 'payment', 'checkbox', '2026-04-13 09:37:11'),
(34, 'payment_paypal_mode', 'sandbox', 'payment', 'select', '2026-01-04 17:28:14'),
(35, 'payment_paypal_client_id', '', 'payment', 'text', '2026-01-04 17:28:14'),
(36, 'payment_paypal_secret', '', 'payment', 'password', '2026-01-04 17:28:14'),
(37, 'payment_googlepay_enabled', '0', 'payment', 'checkbox', '2026-04-13 09:37:11'),
(38, 'payment_googlepay_merchant_id', '', 'payment', 'text', '2026-01-04 17:28:14'),
(39, 'payment_applepay_enabled', '0', 'payment', 'checkbox', '2026-04-13 09:37:11'),
(40, 'payment_applepay_merchant_id', '', 'payment', 'text', '2026-01-04 17:28:14'),
(41, 'social_facebook', 'https://facebook.com/testpage', 'social', 'text', '2026-01-06 17:56:01'),
(42, 'social_twitter', 'https://www.snapchat.com/add/cateyes.beauty', 'social', 'text', '2026-03-31 15:38:03'),
(43, 'social_instagram', 'https://www.instagram.com/cateyes.beauty/', 'social', 'text', '2026-03-31 15:22:09'),
(44, 'social_youtube', 'https://www.youtube.com/@cateyes.beauty', 'social', 'text', '2026-01-31 20:32:05'),
(45, 'social_linkedin', 'https://www.tiktok.com/@cateye.beauty', 'social', 'text', '2026-01-31 20:32:48'),
(46, 'seo_meta_title', '', 'seo', 'text', '2026-01-04 17:28:14'),
(47, 'seo_meta_description', '', 'seo', 'textarea', '2026-01-04 17:28:14'),
(48, 'seo_meta_keywords', '', 'seo', 'textarea', '2026-01-04 17:28:14'),
(49, 'seo_google_analytics', '', 'seo', 'textarea', '2026-01-04 17:28:14'),
(50, 'seo_facebook_pixel', '', 'seo', 'textarea', '2026-01-04 17:28:14'),
(51, 'social_tiktok', NULL, 'social', 'text', '2026-01-06 18:19:46'),
(52, 'shop_country', 'United Arab Emirates', 'shipping', 'text', '2026-01-13 14:09:03'),
(53, 'free_shipping_enabled', '1', 'shipping', 'text', '2026-01-11 10:01:00'),
(54, 'free_shipping_limit', '5000', 'shipping', 'text', '2026-01-11 10:01:33'),
(55, 'flat_rate_enabled', '1', 'shipping', 'text', '2026-03-31 12:19:09'),
(56, 'flat_rate_amount', '15', 'shipping', 'text', '2026-03-31 12:19:43'),
(57, 'by_destination_enabled', '0', 'shipping', 'text', '2026-03-31 12:19:09'),
(58, 'by_weight_enabled', '0', 'shipping', 'text', '2026-01-11 10:01:00'),
(59, 'weight_rate_per_kg', '5', 'shipping', 'text', '2026-01-13 14:10:54'),
(60, 'tax_rate', '5', 'shipping', 'text', '2026-01-13 14:10:27'),
(67, 'heading_font', '\'Indie Flower\', cursive', 'appearance', 'text', '2026-03-15 08:27:18'),
(68, 'paragraph_font', '\'Vollkorn\', serif', 'appearance', 'text', '2026-03-15 08:26:20'),
(69, 'line_height', '1.4', 'appearance', 'text', '2026-02-27 15:55:42'),
(70, 'payment_googlepay_merchant_name', '&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;lt;br /&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;gt;&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;lt;b&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;gt;Deprecated&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;lt;/b&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;gt;:  htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in &amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;lt;b&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;gt;C:xampphtdocsprojectadminsettingspayment.php&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;lt;/b&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;gt; on line &amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;lt;b&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;gt;193&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;lt;/b&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;gt;&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;lt;br /&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;gt;', 'general', 'text', '2026-04-13 09:37:11'),
(71, 'payment_applepay_merchant_name', '&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;lt;br /&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;gt;&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;lt;b&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;gt;Deprecated&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;lt;/b&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;gt;:  htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in &amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;lt;b&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;gt;C:xampphtdocsprojectadminsettingspayment.php&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;lt;/b&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;gt; on line &amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;lt;b&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;gt;229&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;lt;/b&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;gt;&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;lt;br /&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;gt;', 'general', 'text', '2026-04-13 09:37:11'),
(72, 'payment_card_visa_enabled', '1', 'general', 'text', '2026-04-02 17:44:21'),
(73, 'payment_card_mastercard_enabled', '1', 'general', 'text', '2026-04-02 17:44:21'),
(74, 'payment_card_tabby_enabled', '1', 'general', 'text', '2026-04-02 17:44:21'),
(75, 'payment_card_networks_enabled', '1', 'general', 'text', '2026-04-02 17:44:21');

-- --------------------------------------------------------

--
-- Table structure for table `shipping_cities`
--

CREATE TABLE `shipping_cities` (
  `id` int(11) NOT NULL,
  `city_name` varchar(100) NOT NULL,
  `country_code` varchar(2) NOT NULL,
  `shipping_charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipping_countries`
--

CREATE TABLE `shipping_countries` (
  `id` int(11) NOT NULL,
  `country_code` varchar(2) NOT NULL,
  `country_name` varchar(100) NOT NULL,
  `shipping_charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shipping_countries`
--

INSERT INTO `shipping_countries` (`id`, `country_code`, `country_name`, `shipping_charge`, `is_active`, `created_at`, `updated_at`) VALUES
(3, 'AE', 'United Arab Emirates', 15.00, 1, '2026-01-11 18:05:32', '2026-02-18 12:50:43');

-- --------------------------------------------------------

--
-- Table structure for table `shipping_methods`
--

CREATE TABLE `shipping_methods` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `calculation_type` enum('flat_rate','weight_based','location_based','free') DEFAULT 'flat_rate',
  `flat_rate_cost` decimal(10,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shipping_methods`
--

INSERT INTO `shipping_methods` (`id`, `name`, `code`, `description`, `calculation_type`, `flat_rate_cost`, `is_active`, `sort_order`, `created_at`) VALUES
(5, 'zajel', 'standard', 'Standard delivery service', 'flat_rate', 15.00, 1, 1, '2026-01-17 13:46:56');

-- --------------------------------------------------------

--
-- Table structure for table `shipping_rates`
--

CREATE TABLE `shipping_rates` (
  `id` int(11) NOT NULL,
  `shipping_method_id` int(11) NOT NULL,
  `shipping_zone_id` int(11) DEFAULT NULL,
  `min_weight` decimal(10,2) DEFAULT NULL,
  `max_weight` decimal(10,2) DEFAULT NULL,
  `cost` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipping_zones`
--

CREATE TABLE `shipping_zones` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('domestic','international') DEFAULT 'domestic',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipping_zone_countries`
--

CREATE TABLE `shipping_zone_countries` (
  `id` int(11) NOT NULL,
  `shipping_zone_id` int(11) NOT NULL,
  `country_code` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `language_id` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `social_links`
--

CREATE TABLE `social_links` (
  `id` int(11) NOT NULL,
  `platform` varchar(50) NOT NULL COMMENT 'facebook, twitter, instagram, etc.',
  `url` varchar(500) NOT NULL,
  `icon_class` varchar(100) DEFAULT NULL COMMENT 'Font Awesome class',
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `social_links`
--

INSERT INTO `social_links` (`id`, `platform`, `url`, `icon_class`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'facebook', 'https://facebook.com/yourpage', 'fab fa-facebook-f', 1, 1, '2026-01-20 14:28:30', NULL),
(2, 'twitter', 'https://twitter.com/yourhandle', 'fab fa-twitter', 1, 2, '2026-01-20 14:28:30', NULL),
(3, 'instagram', 'https://instagram.com/yourprofile', 'fab fa-instagram', 1, 3, '2026-01-20 14:28:30', NULL),
(4, 'Tiktok', 'https://www.tiktok.com/@cateye.beauty', 'fab fa-tiktok', 1, 4, '2026-01-20 14:28:30', '2026-03-31 12:59:58'),
(5, 'youtube', 'https://www.youtube.com/@cateyes.beauty', 'fab fa-youtube', 1, 5, '2026-01-20 14:28:30', '2026-03-31 12:58:10'),
(6, 'whatsapp', 'https://wa.me/1234567890', 'fab fa-whatsapp', 1, 6, '2026-01-20 14:28:30', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `stock_adjustments`
--

CREATE TABLE `stock_adjustments` (
  `id` int(11) NOT NULL,
  `adjustment_number` varchar(50) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `adjustment_date` date NOT NULL,
  `adjustment_type` enum('add','subtract','set') NOT NULL,
  `reason` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_adjustments`
--

INSERT INTO `stock_adjustments` (`id`, `adjustment_number`, `warehouse_id`, `adjustment_date`, `adjustment_type`, `reason`, `created_by`, `created_at`) VALUES
(61, 'ADJ-20260401130021-3131', 1, '2026-04-01', 'add', NULL, 3, '2026-04-01 09:00:21'),
(62, 'ADJ-20260406192634-4389', 1, '2026-04-06', 'subtract', 'test gify', 3, '2026-04-06 15:26:34');

-- --------------------------------------------------------

--
-- Table structure for table `stock_adjustment_items`
--

CREATE TABLE `stock_adjustment_items` (
  `id` int(11) NOT NULL,
  `stock_adjustment_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `old_quantity` int(11) NOT NULL,
  `adjustment_quantity` int(11) NOT NULL,
  `new_quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_adjustment_items`
--

INSERT INTO `stock_adjustment_items` (`id`, `stock_adjustment_id`, `product_id`, `variant_id`, `old_quantity`, `adjustment_quantity`, `new_quantity`) VALUES
(65, 61, 7, NULL, 0, 15, 15),
(66, 62, 7, NULL, 68, 1, 67);

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `movement_type` enum('sale','purchase','adjustment','transfer_in','transfer_out') NOT NULL DEFAULT 'sale',
  `quantity` int(11) NOT NULL COMMENT 'Positive for stock in, negative for stock out',
  `reference_type` varchar(50) NOT NULL DEFAULT 'order' COMMENT 'order, purchase_order, stock_adjustment, etc',
  `reference_id` int(11) DEFAULT NULL COMMENT 'ID of the reference record',
  `previous_quantity` int(11) DEFAULT NULL COMMENT 'Stock quantity before movement',
  `new_quantity` int(11) DEFAULT NULL COMMENT 'Stock quantity after movement',
  `unit_cost` decimal(10,2) DEFAULT NULL COMMENT 'Cost per unit for purchases/adjustments',
  `total_cost` decimal(15,2) DEFAULT NULL COMMENT 'Total cost of movement',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL COMMENT 'User who created the movement',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_transfers`
--

CREATE TABLE `stock_transfers` (
  `id` int(11) NOT NULL,
  `transfer_number` varchar(50) NOT NULL,
  `from_warehouse_id` int(11) NOT NULL,
  `to_warehouse_id` int(11) NOT NULL,
  `transfer_date` date NOT NULL,
  `status` enum('pending','in_transit','completed','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_transfer_items`
--

CREATE TABLE `stock_transfer_items` (
  `id` int(11) NOT NULL,
  `stock_transfer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `company` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `tax_number` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `company`, `email`, `phone`, `address`, `city`, `country`, `tax_number`, `is_active`, `created_at`) VALUES
(5, 'test supp', 'Compnay Name', 'admin@training.com', '24242', 'dfgd', 'Dubai', 'UAE', '4353', 1, '2026-04-01 17:12:04');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_returns`
--

CREATE TABLE `supplier_returns` (
  `id` int(11) NOT NULL,
  `return_number` varchar(50) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `return_date` date NOT NULL,
  `reason` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `shipping_cost` decimal(10,2) DEFAULT 0.00,
  `status` enum('pending','approved','rejected','completed') DEFAULT 'pending',
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `supplier_returns`
--

INSERT INTO `supplier_returns` (`id`, `return_number`, `supplier_id`, `warehouse_id`, `return_date`, `reason`, `notes`, `tax_amount`, `shipping_cost`, `status`, `total_amount`, `created_by`, `created_at`, `updated_at`) VALUES
(39, 'SUPR-20260413224659-4903', 5, 1, '2026-04-13', 'defective', NULL, 0.00, 0.00, 'completed', 528.00, 3, '2026-04-13 18:46:59', '2026-04-13 17:47:19'),
(40, 'SUPR-20260414115131-8962', 5, 1, '2026-04-14', 'defective', NULL, 0.00, 0.00, 'completed', 570.00, 3, '2026-04-14 07:51:31', '2026-04-14 06:51:39');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_return_items`
--

CREATE TABLE `supplier_return_items` (
  `id` int(11) NOT NULL,
  `supplier_return_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `supplier_return_items`
--

INSERT INTO `supplier_return_items` (`id`, `supplier_return_id`, `product_id`, `variant_id`, `quantity`, `unit_price`, `total_price`, `reason`, `created_at`, `updated_at`) VALUES
(38, 39, 8, 70, 44.00, 12.00, 528.00, NULL, '2026-04-13 18:46:59', '2026-04-13 18:46:59'),
(39, 40, 9, NULL, 19.00, 30.00, 570.00, NULL, '2026-04-14 07:51:31', '2026-04-14 07:51:31');

-- --------------------------------------------------------

--
-- Table structure for table `tax_settings`
--

CREATE TABLE `tax_settings` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `rate` decimal(5,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tax_settings`
--

INSERT INTO `tax_settings` (`id`, `name`, `rate`, `is_active`, `created_at`) VALUES
(1, 'VAT', 5.00, 1, '2026-01-04 07:48:55');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','shop_manager','editor','customer','vendor') NOT NULL DEFAULT 'customer',
  `admin_role_id` int(11) DEFAULT NULL,
  `permissions` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `email_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expire` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `customer_group_id` int(11) DEFAULT 1,
  `company` varchar(100) DEFAULT NULL,
  `tax_id` varchar(50) DEFAULT NULL,
  `newsletter_subscribed` tinyint(1) DEFAULT 0,
  `total_orders` int(11) DEFAULT 0,
  `total_spent` decimal(10,2) DEFAULT 0.00,
  `last_order_date` datetime DEFAULT NULL,
  `customer_notes` text DEFAULT NULL,
  `failed_attempts` int(11) DEFAULT 0,
  `lockout_until` datetime DEFAULT NULL,
  `last_failed_login` datetime DEFAULT NULL,
  `last_failed_ip` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password`, `phone`, `role`, `admin_role_id`, `permissions`, `is_active`, `email_verified`, `verification_token`, `reset_token`, `reset_token_expire`, `last_login`, `last_login_ip`, `created_at`, `updated_at`, `customer_group_id`, `company`, `tax_id`, `newsletter_subscribed`, `total_orders`, `total_spent`, `last_order_date`, `customer_notes`, `failed_attempts`, `lockout_until`, `last_failed_login`, `last_failed_ip`) VALUES
(3, 'Admin', 'User', 'info@advancedpromedia.com', '$2y$12$co3FGzvPoGKs059KN7MCM.9CLX/SOCSsfzgiSZQ2KLd73qbbINIua', NULL, 'admin', 1, NULL, 1, 1, NULL, NULL, NULL, '2026-04-13 20:15:24', NULL, '2026-01-04 09:23:19', '2026-04-13 16:15:24', 1, NULL, NULL, 0, 0, 0.00, NULL, NULL, 0, NULL, '2026-03-30 21:36:23', '185.97.95.52'),
(21, 'Albaraa', 'Bitar', 'albara.bitar@gmail.com', '$2y$10$ADmX/X.XI0JuhSZGg249gu/rcRZH/uVg9X9QWapGzYvunTE7v6cKS', '+971552969432', 'customer', NULL, NULL, 1, 0, 'afdf58998a504c260b2d2f67030031a01e5ee4e6900e9bef6ed5ee0a2a420e76', NULL, NULL, '2026-04-01 13:08:56', '185.76.178.217', '2026-04-01 13:06:02', '2026-04-01 09:08:56', 1, NULL, NULL, 1, 0, 0.00, NULL, NULL, 0, NULL, NULL, NULL),
(24, 'Marco', 'Marco', 'info@ssproviders.com', '$2y$10$qJzJGBgOVsx5Woh67yEW..8bLAcowhHbDtMQxy4Ljpo8wR5loeuDm', '9715656564', 'customer', NULL, NULL, 1, 0, '908c019a41bc143a06ed069f65ac379e7c6192b91b7da241ceab601a38347de1', '601319a388f2d301a9af7a5fd5089a5c3a1e0b25e0bda61541cd451a3dc0f0e8', '2026-04-13 15:45:22', '2026-04-13 14:52:12', '185.76.178.247', '2026-04-13 14:35:17', '2026-04-13 10:52:12', 1, NULL, NULL, 1, 0, 0.00, NULL, NULL, 0, NULL, NULL, NULL),
(25, 'Sleek', 'Creations', 'sleekstudiocreations@gmail.com', '$2y$10$Zqn9upgXk/8x51YSUc1xIuB6LXrV2FUhL/OfGfNHZY0ISeVJb38qy', '+92-324-5932916', 'customer', NULL, NULL, 1, 0, 'ed015e4ca10bd8f8591f91ea59c625076958f504309f2fd894ba6c9e60f24a6a', NULL, NULL, '2026-04-13 15:23:36', '182.180.155.210', '2026-04-13 15:23:21', '2026-04-13 11:23:36', 1, NULL, NULL, 0, 0, 0.00, NULL, NULL, 0, NULL, NULL, NULL),
(26, '123', '123', 'artofgallery19@gmail.com', '$2y$10$1tyoJ1f//t6I1UwMX4WeS.NGvNHQQEMNaZQYGvXwauGtvr1275wU2', '1222211', 'customer', NULL, NULL, 1, 0, '24eadf902143241f746764c610594fe9266072ad04b267103d975363625ca8ff', NULL, NULL, '2026-04-14 11:41:31', '::1', '2026-04-13 15:09:56', '2026-04-14 07:41:31', 1, NULL, NULL, 0, 0, 0.00, NULL, NULL, 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address_type` enum('billing','shipping','both') DEFAULT 'both',
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `company` varchar(255) DEFAULT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) NOT NULL,
  `country` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_customer_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_customer_summary` (
`id` int(11)
,`customer_name` varchar(201)
,`email` varchar(255)
,`phone` varchar(20)
,`company` varchar(100)
,`customer_group` varchar(100)
,`discount_percentage` decimal(5,2)
,`total_orders` int(11)
,`total_spent` decimal(10,2)
,`last_order_date` datetime
,`member_since` timestamp
,`days_as_customer` int(7)
,`is_active` tinyint(1)
,`customer_tier` varchar(7)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_inventory_status`
-- (See below for the actual view)
--
CREATE TABLE `v_inventory_status` (
`id` int(11)
,`sku` varchar(100)
,`name` varchar(255)
,`stock_quantity` int(11)
,`low_stock_threshold` int(11)
,`stock_status` varchar(12)
,`cost_price` decimal(10,2)
,`total_inventory_value` decimal(20,2)
,`is_active` tinyint(1)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_reviews_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_reviews_summary` (
`id` int(11)
,`product_id` int(11)
,`product_name` varchar(255)
,`customer_name` varchar(201)
,`rating` tinyint(1)
,`title` varchar(255)
,`review` text
,`is_verified_purchase` tinyint(1)
,`is_approved` tinyint(1)
,`helpful_count` int(11)
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_service_bookings_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_service_bookings_summary` (
`id` int(11)
,`booking_number` varchar(50)
,`service_name` varchar(255)
,`customer_name` varchar(255)
,`customer_email` varchar(255)
,`customer_phone` varchar(20)
,`booking_date` date
,`booking_time` time
,`status` enum('pending','confirmed','completed','cancelled')
,`total_amount` decimal(10,2)
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_service_bookings_with_payments`
-- (See below for the actual view)
--
CREATE TABLE `v_service_bookings_with_payments` (
`id` int(11)
,`booking_number` varchar(50)
,`service_id` int(11)
,`time_slot_id` int(11)
,`user_id` int(11)
,`customer_name` varchar(255)
,`customer_email` varchar(255)
,`customer_phone` varchar(20)
,`booking_date` date
,`booking_time` time
,`status` enum('pending','confirmed','completed','cancelled')
,`payment_status` enum('pending','paid','failed','refunded')
,`payment_method` varchar(50)
,`payment_id` int(11)
,`notes` text
,`admin_notes` text
,`total_amount` decimal(10,2)
,`created_at` timestamp
,`confirmed_at` timestamp
,`confirmed_by` int(11)
,`updated_at` timestamp
,`cancelled_at` timestamp
,`cancelled_by` int(11)
,`cancellation_reason` text
,`completed_at` timestamp
,`completed_by` int(11)
,`service_name` varchar(255)
,`service_price` decimal(10,2)
,`service_duration` int(11)
,`service_image` varchar(255)
,`payment_record_id` int(11)
,`payment_method_used` varchar(50)
,`payment_gateway` varchar(50)
,`transaction_id` varchar(255)
,`paid_at` timestamp
,`booking_status_display` varchar(27)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_stock_integrity`
-- (See below for the actual view)
--
CREATE TABLE `v_stock_integrity` (
`product_id` int(11)
,`sku` varchar(100)
,`products_stock` int(11)
,`warehouse_total_stock` decimal(32,0)
,`difference` decimal(33,0)
,`status` varchar(8)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_transaction_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_transaction_summary` (
`product_id` int(11)
,`sku` varchar(100)
,`warehouse_id` int(11)
,`transaction_type` enum('purchase','sale','sales_return','supplier_return','adjustment','transfer_in','transfer_out','production','return')
,`transaction_count` bigint(21)
,`total_quantity` decimal(32,0)
,`first_transaction` timestamp
,`last_transaction` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `warehouses`
--

CREATE TABLE `warehouses` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `manager_name` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `warehouses`
--

INSERT INTO `warehouses` (`id`, `name`, `code`, `address`, `city`, `country`, `manager_name`, `phone`, `email`, `is_active`, `created_at`) VALUES
(1, 'CatEye Abu Dhabi', '001', 'Abu Dabi', 'Abu Dhabi', 'United Arab Emirates', 'Amal Rashidy', '0551273327', 'info@cateyesbeauty.com', 1, '2026-03-10 20:25:15'),
(3, 'Dubai', '002', 'Dubai', 'Dubai', 'UAE', 'Baraa', '998989', 'mario@mario.com', 1, '2026-04-01 13:38:19'),
(4, 'Test', 'Test-01', 'Test', 'Test', 'TEst', 'Test', '1111111111', '123456@gmail.com', 1, '2026-04-03 17:26:32');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wishlists`
--

CREATE TABLE `wishlists` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure for view `v_customer_summary`
--
DROP TABLE IF EXISTS `v_customer_summary`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `v_customer_summary`  AS SELECT `u`.`id` AS `id`, concat(`u`.`first_name`,' ',`u`.`last_name`) AS `customer_name`, `u`.`email` AS `email`, `u`.`phone` AS `phone`, `u`.`company` AS `company`, `cg`.`name` AS `customer_group`, `cg`.`discount_percentage` AS `discount_percentage`, `u`.`total_orders` AS `total_orders`, `u`.`total_spent` AS `total_spent`, `u`.`last_order_date` AS `last_order_date`, `u`.`created_at` AS `member_since`, to_days(current_timestamp()) - to_days(`u`.`created_at`) AS `days_as_customer`, `u`.`is_active` AS `is_active`, CASE WHEN `u`.`total_orders` = 0 THEN 'New' WHEN `u`.`total_orders` < 5 THEN 'Regular' WHEN `u`.`total_orders` < 20 THEN 'Loyal' ELSE 'VIP' END AS `customer_tier` FROM (`users` `u` left join `customer_groups` `cg` on(`u`.`customer_group_id` = `cg`.`id`)) WHERE `u`.`role` = 'customer' ;

-- --------------------------------------------------------

--
-- Structure for view `v_inventory_status`
--
DROP TABLE IF EXISTS `v_inventory_status`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `v_inventory_status`  AS SELECT `p`.`id` AS `id`, `p`.`sku` AS `sku`, coalesce(`pt`.`name`,'Untitled') AS `name`, `p`.`stock_quantity` AS `stock_quantity`, `p`.`low_stock_threshold` AS `low_stock_threshold`, CASE WHEN `p`.`stock_quantity` = 0 THEN 'Out of Stock' WHEN `p`.`stock_quantity` <= `p`.`low_stock_threshold` THEN 'Low Stock' ELSE 'In Stock' END AS `stock_status`, `p`.`cost_price` AS `cost_price`, `p`.`stock_quantity`* `p`.`cost_price` AS `total_inventory_value`, `p`.`is_active` AS `is_active` FROM (`products` `p` left join `product_translations` `pt` on(`p`.`id` = `pt`.`product_id` and `pt`.`language_id` = 1)) WHERE `p`.`is_active` = 1 ;

-- --------------------------------------------------------

--
-- Structure for view `v_reviews_summary`
--
DROP TABLE IF EXISTS `v_reviews_summary`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `v_reviews_summary`  AS SELECT `pr`.`id` AS `id`, `pr`.`product_id` AS `product_id`, coalesce(`pt`.`name`,'Untitled') AS `product_name`, concat(coalesce(`u`.`first_name`,'Guest'),' ',coalesce(`u`.`last_name`,'')) AS `customer_name`, `pr`.`rating` AS `rating`, `pr`.`title` AS `title`, `pr`.`review` AS `review`, `pr`.`is_verified_purchase` AS `is_verified_purchase`, `pr`.`is_approved` AS `is_approved`, `pr`.`helpful_count` AS `helpful_count`, `pr`.`created_at` AS `created_at` FROM (((`product_reviews` `pr` left join `products` `p` on(`pr`.`product_id` = `p`.`id`)) left join `product_translations` `pt` on(`p`.`id` = `pt`.`product_id` and `pt`.`language_id` = 1)) left join `users` `u` on(`pr`.`user_id` = `u`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `v_service_bookings_summary`
--
DROP TABLE IF EXISTS `v_service_bookings_summary`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `v_service_bookings_summary`  AS SELECT `sb`.`id` AS `id`, `sb`.`booking_number` AS `booking_number`, coalesce(`st`.`name`,'Untitled') AS `service_name`, `sb`.`customer_name` AS `customer_name`, `sb`.`customer_email` AS `customer_email`, `sb`.`customer_phone` AS `customer_phone`, `sb`.`booking_date` AS `booking_date`, `sb`.`booking_time` AS `booking_time`, `sb`.`status` AS `status`, `sb`.`total_amount` AS `total_amount`, `sb`.`created_at` AS `created_at` FROM ((`service_bookings` `sb` left join `services` `s` on(`sb`.`service_id` = `s`.`id`)) left join `service_translations` `st` on(`s`.`id` = `st`.`service_id` and `st`.`language_id` = 1)) ;

-- --------------------------------------------------------

--
-- Structure for view `v_service_bookings_with_payments`
--
DROP TABLE IF EXISTS `v_service_bookings_with_payments`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `v_service_bookings_with_payments`  AS SELECT `sb`.`id` AS `id`, `sb`.`booking_number` AS `booking_number`, `sb`.`service_id` AS `service_id`, `sb`.`time_slot_id` AS `time_slot_id`, `sb`.`user_id` AS `user_id`, `sb`.`customer_name` AS `customer_name`, `sb`.`customer_email` AS `customer_email`, `sb`.`customer_phone` AS `customer_phone`, `sb`.`booking_date` AS `booking_date`, `sb`.`booking_time` AS `booking_time`, `sb`.`status` AS `status`, `sb`.`payment_status` AS `payment_status`, `sb`.`payment_method` AS `payment_method`, `sb`.`payment_id` AS `payment_id`, `sb`.`notes` AS `notes`, `sb`.`admin_notes` AS `admin_notes`, `sb`.`total_amount` AS `total_amount`, `sb`.`created_at` AS `created_at`, `sb`.`confirmed_at` AS `confirmed_at`, `sb`.`confirmed_by` AS `confirmed_by`, `sb`.`updated_at` AS `updated_at`, `sb`.`cancelled_at` AS `cancelled_at`, `sb`.`cancelled_by` AS `cancelled_by`, `sb`.`cancellation_reason` AS `cancellation_reason`, `sb`.`completed_at` AS `completed_at`, `sb`.`completed_by` AS `completed_by`, `st`.`name` AS `service_name`, `s`.`price` AS `service_price`, `s`.`duration` AS `service_duration`, `s`.`image` AS `service_image`, `sp`.`id` AS `payment_record_id`, `sp`.`payment_method` AS `payment_method_used`, `sp`.`payment_gateway` AS `payment_gateway`, `sp`.`transaction_id` AS `transaction_id`, `sp`.`paid_at` AS `paid_at`, CASE WHEN `sb`.`status` = 'completed' THEN 'Completed' WHEN `sb`.`status` = 'confirmed' THEN 'Confirmed' WHEN `sb`.`status` = 'pending' AND `sb`.`payment_status` = 'paid' THEN 'Pending Confirmation (Paid)' WHEN `sb`.`status` = 'pending' THEN 'Pending Confirmation' WHEN `sb`.`status` = 'cancelled' THEN 'Cancelled' ELSE 'Unknown' END AS `booking_status_display` FROM (((`service_bookings` `sb` left join `services` `s` on(`sb`.`service_id` = `s`.`id`)) left join `service_translations` `st` on(`s`.`id` = `st`.`service_id` and `st`.`language_id` = 1)) left join `service_payments` `sp` on(`sb`.`payment_id` = `sp`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `v_stock_integrity`
--
DROP TABLE IF EXISTS `v_stock_integrity`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `v_stock_integrity`  AS SELECT `p`.`id` AS `product_id`, `p`.`sku` AS `sku`, `p`.`stock_quantity` AS `products_stock`, coalesce(sum(`pws`.`quantity`),0) AS `warehouse_total_stock`, `p`.`stock_quantity`- coalesce(sum(`pws`.`quantity`),0) AS `difference`, CASE WHEN abs(`p`.`stock_quantity` - coalesce(sum(`pws`.`quantity`),0)) < 0.01 THEN 'OK' ELSE 'MISMATCH' END AS `status` FROM (`products` `p` left join `product_warehouse_stock` `pws` on(`p`.`id` = `pws`.`product_id`)) GROUP BY `p`.`id`, `p`.`sku`, `p`.`stock_quantity` HAVING abs(`p`.`stock_quantity` - coalesce(sum(`pws`.`quantity`),0)) >= 0.01 ORDER BY abs(`p`.`stock_quantity` - coalesce(sum(`pws`.`quantity`),0)) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `v_transaction_summary`
--
DROP TABLE IF EXISTS `v_transaction_summary`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `v_transaction_summary`  AS SELECT `it`.`product_id` AS `product_id`, `p`.`sku` AS `sku`, `it`.`warehouse_id` AS `warehouse_id`, `it`.`transaction_type` AS `transaction_type`, count(0) AS `transaction_count`, sum(`it`.`quantity`) AS `total_quantity`, min(`it`.`created_at`) AS `first_transaction`, max(`it`.`created_at`) AS `last_transaction` FROM ((`inventory_transactions` `it` join `products` `p` on(`p`.`id` = `it`.`product_id`)) left join `warehouses` `w` on(`w`.`id` = `it`.`warehouse_id`)) WHERE `it`.`created_at` >= current_timestamp() - interval 30 day GROUP BY `it`.`product_id`, `p`.`sku`, `it`.`warehouse_id`, `it`.`transaction_type` ORDER BY max(`it`.`created_at`) DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `action` (`action`);

--
-- Indexes for table `admin_roles`
--
ALTER TABLE `admin_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `attributes`
--
ALTER TABLE `attributes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attribute_values`
--
ALTER TABLE `attribute_values`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attribute_id` (`attribute_id`);

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `banner_translations`
--
ALTER TABLE `banner_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `banner_lang` (`banner_id`,`language_id`),
  ADD KEY `language_id` (`language_id`);

--
-- Indexes for table `blog_categories`
--
ALTER TABLE `blog_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `blog_category_translations`
--
ALTER TABLE `blog_category_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_lang` (`category_id`,`language_id`),
  ADD KEY `language_id` (`language_id`);

--
-- Indexes for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `author_id` (`author_id`),
  ADD KEY `is_published` (`is_published`);

--
-- Indexes for table `blog_post_translations`
--
ALTER TABLE `blog_post_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `post_lang` (`post_id`,`language_id`),
  ADD KEY `language_id` (`language_id`);

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_brands_active` (`is_active`),
  ADD KEY `idx_brands_slug` (`slug`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `idx_categories_parent` (`parent_id`),
  ADD KEY `idx_categories_active` (`is_active`),
  ADD KEY `idx_categories_sort` (`sort_order`);

--
-- Indexes for table `category_translations`
--
ALTER TABLE `category_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_lang` (`category_id`,`language_id`),
  ADD KEY `language_id` (`language_id`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `sort_order` (`sort_order`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `is_read` (`is_read`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coupon_id` (`coupon_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `currencies`
--
ALTER TABLE `currencies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `customer_group_id` (`customer_group_id`),
  ADD KEY `email_index` (`email`);

--
-- Indexes for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `customer_groups`
--
ALTER TABLE `customer_groups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `digital_downloads`
--
ALTER TABLE `digital_downloads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `download_token` (`download_token`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `order_item_id` (`order_item_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `digital_file_id` (`digital_file_id`);

--
-- Indexes for table `email_templates`
--
ALTER TABLE `email_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `template_key` (`template_key`);

--
-- Indexes for table `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `faq_categories`
--
ALTER TABLE `faq_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `faq_category_translations`
--
ALTER TABLE `faq_category_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_lang` (`category_id`,`language_id`),
  ADD KEY `language_id` (`language_id`);

--
-- Indexes for table `faq_translations`
--
ALTER TABLE `faq_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `faq_lang` (`faq_id`,`language_id`),
  ADD KEY `language_id` (`language_id`);

--
-- Indexes for table `footer_links`
--
ALTER TABLE `footer_links`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active_order` (`is_active`,`sort_order`);

--
-- Indexes for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `warehouse_id` (`warehouse_id`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_inventory_product_warehouse` (`product_id`,`warehouse_id`),
  ADD KEY `idx_inventory_type` (`transaction_type`),
  ADD KEY `idx_inventory_date` (`created_at`),
  ADD KEY `idx_inventory_transactions_lookup` (`product_id`,`warehouse_id`,`variant_id`,`created_at`,`id`),
  ADD KEY `idx_inventory_transactions_reference` (`reference_type`,`reference_id`);

--
-- Indexes for table `languages`
--
ALTER TABLE `languages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `is_published` (`is_published`),
  ADD KEY `is_featured` (`is_featured`),
  ADD KEY `published_at` (`published_at`);

--
-- Indexes for table `newsletter_campaigns`
--
ALTER TABLE `newsletter_campaigns`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `news_translations`
--
ALTER TABLE `news_translations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `news_id` (`news_id`),
  ADD KEY `language_id` (`language_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order_status` (`order_status`),
  ADD KEY `payment_status` (`payment_status`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `idx_orders_user` (`user_id`),
  ADD KEY `idx_orders_status` (`order_status`),
  ADD KEY `idx_orders_payment` (`payment_status`),
  ADD KEY `idx_orders_date` (`created_at`),
  ADD KEY `idx_orders_payment_status` (`payment_status`),
  ADD KEY `idx_orders_user_id` (`user_id`),
  ADD KEY `fk_orders_shipping_method` (`shipping_method_id`),
  ADD KEY `idx_orders_warehouse` (`warehouse_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `variant_id` (`variant_id`),
  ADD KEY `warehouse_id` (`warehouse_id`),
  ADD KEY `idx_order_items_order_id` (`order_id`),
  ADD KEY `idx_order_items_product_id` (`product_id`);

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `page_translations`
--
ALTER TABLE `page_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `page_lang` (`page_id`,`language_id`),
  ADD KEY `language_id` (`language_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_transaction_id` (`transaction_id`);

--
-- Indexes for table `payment_methods_config`
--
ALTER TABLE `payment_methods_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `method_code` (`method_code`);

--
-- Indexes for table `production_orders`
--
ALTER TABLE `production_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `production_number` (`production_number`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `variant_id` (`variant_id`),
  ADD KEY `warehouse_id` (`warehouse_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `brand_id` (`brand_id`),
  ADD KEY `product_type` (`product_type`),
  ADD KEY `is_featured` (`is_featured`),
  ADD KEY `idx_products_featured` (`is_featured`),
  ADD KEY `idx_products_bestseller` (`is_bestseller`),
  ADD KEY `idx_products_new` (`is_new`),
  ADD KEY `idx_products_views` (`views`),
  ADD KEY `idx_products_sales` (`sales_count`),
  ADD KEY `idx_products_brand` (`brand_id`),
  ADD KEY `idx_products_active` (`is_active`),
  ADD KEY `idx_products_stock` (`stock_quantity`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`product_id`,`category_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_digital_files`
--
ALTER TABLE `product_digital_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `is_approved` (`is_approved`),
  ADD KEY `idx_reviews_product_approved` (`product_id`,`is_approved`),
  ADD KEY `idx_reviews_rating` (`rating`);

--
-- Indexes for table `product_translations`
--
ALTER TABLE `product_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_lang` (`product_id`,`language_id`),
  ADD KEY `language_id` (`language_id`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_variant_attributes`
--
ALTER TABLE `product_variant_attributes`
  ADD PRIMARY KEY (`variant_id`,`attribute_value_id`),
  ADD KEY `attribute_value_id` (`attribute_value_id`);

--
-- Indexes for table `product_warehouse_stock`
--
ALTER TABLE `product_warehouse_stock`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_unique_stock` (`product_id`,`warehouse_id`,`variant_id`),
  ADD UNIQUE KEY `unique_stock_index` (`product_id`,`warehouse_id`,`variant_id`),
  ADD KEY `warehouse_id` (`warehouse_id`),
  ADD KEY `variant_id` (`variant_id`),
  ADD KEY `idx_product_warehouse_stock_lookup` (`product_id`,`warehouse_id`,`variant_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `po_number` (`po_number`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `warehouse_id` (`warehouse_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_order_id` (`purchase_order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `variant_id` (`variant_id`);

--
-- Indexes for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchase_return_items`
--
ALTER TABLE `purchase_return_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_return_id` (`purchase_return_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `variant_id` (`variant_id`);

--
-- Indexes for table `sales_returns`
--
ALTER TABLE `sales_returns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `return_number` (`return_number`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `sales_return_items`
--
ALTER TABLE `sales_return_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sales_return_id` (`sales_return_id`),
  ADD KEY `order_item_id` (`order_item_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `variant_id` (`variant_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_services_active` (`is_active`),
  ADD KEY `idx_services_slug` (`slug`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `service_bookings`
--
ALTER TABLE `service_bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_number` (`booking_number`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_bookings_service` (`service_id`),
  ADD KEY `idx_bookings_user` (`user_id`),
  ADD KEY `idx_bookings_status` (`status`),
  ADD KEY `idx_bookings_date` (`booking_date`),
  ADD KEY `time_slot_id` (`time_slot_id`),
  ADD KEY `confirmed_by` (`confirmed_by`),
  ADD KEY `idx_booking_status` (`status`,`booking_date`),
  ADD KEY `idx_booking_date` (`booking_date`,`booking_time`),
  ADD KEY `idx_booking_date_status` (`booking_date`,`status`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_customer_email` (`customer_email`),
  ADD KEY `idx_booking_number` (`booking_number`),
  ADD KEY `idx_slot_availability` (`service_id`,`booking_date`,`booking_time`,`status`);

--
-- Indexes for table `service_booking_reminders`
--
ALTER TABLE `service_booking_reminders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `service_booking_reviews`
--
ALTER TABLE `service_booking_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `rating` (`rating`),
  ADD KEY `is_approved` (`is_approved`);

--
-- Indexes for table `service_categories`
--
ALTER TABLE `service_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `service_category_translations`
--
ALTER TABLE `service_category_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_lang` (`category_id`,`language_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `language_id` (`language_id`);

--
-- Indexes for table `service_payments`
--
ALTER TABLE `service_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `status` (`status`),
  ADD KEY `payment_method` (`payment_method`);

--
-- Indexes for table `service_payment_logs`
--
ALTER TABLE `service_payment_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_id` (`payment_id`),
  ADD KEY `event_type` (`event_type`);

--
-- Indexes for table `service_requests`
--
ALTER TABLE `service_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `service_time_slots`
--
ALTER TABLE `service_time_slots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `day_of_week` (`day_of_week`);

--
-- Indexes for table `service_translations`
--
ALTER TABLE `service_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `service_lang` (`service_id`,`language_id`),
  ADD KEY `language_id` (`language_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`),
  ADD KEY `group` (`group`);

--
-- Indexes for table `shipping_cities`
--
ALTER TABLE `shipping_cities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_country_code` (`country_code`);

--
-- Indexes for table `shipping_countries`
--
ALTER TABLE `shipping_countries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_country_code` (`country_code`);

--
-- Indexes for table `shipping_methods`
--
ALTER TABLE `shipping_methods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `shipping_rates`
--
ALTER TABLE `shipping_rates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shipping_method_id` (`shipping_method_id`),
  ADD KEY `shipping_zone_id` (`shipping_zone_id`);

--
-- Indexes for table `shipping_zones`
--
ALTER TABLE `shipping_zones`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shipping_zone_countries`
--
ALTER TABLE `shipping_zone_countries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shipping_zone_id` (`shipping_zone_id`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key_lang` (`setting_key`,`language_id`),
  ADD KEY `language_id` (`language_id`);

--
-- Indexes for table `social_links`
--
ALTER TABLE `social_links`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `platform` (`platform`),
  ADD KEY `idx_active_order` (`is_active`,`sort_order`);

--
-- Indexes for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `adjustment_number` (`adjustment_number`),
  ADD KEY `warehouse_id` (`warehouse_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `stock_adjustment_items`
--
ALTER TABLE `stock_adjustment_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_adjustment_id` (`stock_adjustment_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `variant_id` (`variant_id`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `variant_id` (`variant_id`),
  ADD KEY `reference` (`reference_type`,`reference_id`),
  ADD KEY `movement_type` (`movement_type`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transfer_number` (`transfer_number`),
  ADD KEY `from_warehouse_id` (`from_warehouse_id`),
  ADD KEY `to_warehouse_id` (`to_warehouse_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `stock_transfer_items`
--
ALTER TABLE `stock_transfer_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_transfer_id` (`stock_transfer_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `variant_id` (`variant_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `supplier_returns`
--
ALTER TABLE `supplier_returns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `return_number` (`return_number`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `warehouse_id` (`warehouse_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `supplier_return_items`
--
ALTER TABLE `supplier_return_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_return_id` (`supplier_return_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `variant_id` (`variant_id`);

--
-- Indexes for table `tax_settings`
--
ALTER TABLE `tax_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role` (`role`),
  ADD KEY `customer_group_id` (`customer_group_id`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_active` (`is_active`),
  ADD KEY `idx_users_group` (`customer_group_id`),
  ADD KEY `fk_user_admin_role` (`admin_role_id`);

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `warehouses`
--
ALTER TABLE `warehouses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_product` (`user_id`,`product_id`,`variant_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `variant_id` (`variant_id`);

--
-- Indexes for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=169;

--
-- AUTO_INCREMENT for table `admin_roles`
--
ALTER TABLE `admin_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `attributes`
--
ALTER TABLE `attributes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `attribute_values`
--
ALTER TABLE `attribute_values`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `banner_translations`
--
ALTER TABLE `banner_translations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `blog_categories`
--
ALTER TABLE `blog_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `blog_category_translations`
--
ALTER TABLE `blog_category_translations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `blog_posts`
--
ALTER TABLE `blog_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `blog_post_translations`
--
ALTER TABLE `blog_post_translations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=179;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `category_translations`
--
ALTER TABLE `category_translations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `currencies`
--
ALTER TABLE `currencies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_groups`
--
ALTER TABLE `customer_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `digital_downloads`
--
ALTER TABLE `digital_downloads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_templates`
--
ALTER TABLE `email_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faqs`
--
ALTER TABLE `faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faq_categories`
--
ALTER TABLE `faq_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faq_category_translations`
--
ALTER TABLE `faq_category_translations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faq_translations`
--
ALTER TABLE `faq_translations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `footer_links`
--
ALTER TABLE `footer_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `languages`
--
ALTER TABLE `languages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `newsletter_campaigns`
--
ALTER TABLE `newsletter_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `news_translations`
--
ALTER TABLE `news_translations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `page_translations`
--
ALTER TABLE `page_translations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `payment_methods_config`
--
ALTER TABLE `payment_methods_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `production_orders`
--
ALTER TABLE `production_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `product_digital_files`
--
ALTER TABLE `product_digital_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `product_translations`
--
ALTER TABLE `product_translations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `product_warehouse_stock`
--
ALTER TABLE `product_warehouse_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `purchase_return_items`
--
ALTER TABLE `purchase_return_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales_returns`
--
ALTER TABLE `sales_returns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `sales_return_items`
--
ALTER TABLE `sales_return_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `service_bookings`
--
ALTER TABLE `service_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `service_booking_reminders`
--
ALTER TABLE `service_booking_reminders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_booking_reviews`
--
ALTER TABLE `service_booking_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_categories`
--
ALTER TABLE `service_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `service_category_translations`
--
ALTER TABLE `service_category_translations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `service_payments`
--
ALTER TABLE `service_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `service_payment_logs`
--
ALTER TABLE `service_payment_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_requests`
--
ALTER TABLE `service_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_time_slots`
--
ALTER TABLE `service_time_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=383;

--
-- AUTO_INCREMENT for table `service_translations`
--
ALTER TABLE `service_translations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `shipping_cities`
--
ALTER TABLE `shipping_cities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `shipping_countries`
--
ALTER TABLE `shipping_countries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `shipping_methods`
--
ALTER TABLE `shipping_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `shipping_rates`
--
ALTER TABLE `shipping_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipping_zones`
--
ALTER TABLE `shipping_zones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipping_zone_countries`
--
ALTER TABLE `shipping_zone_countries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `social_links`
--
ALTER TABLE `social_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `stock_adjustment_items`
--
ALTER TABLE `stock_adjustment_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `stock_transfer_items`
--
ALTER TABLE `stock_transfer_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `supplier_returns`
--
ALTER TABLE `supplier_returns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `supplier_return_items`
--
ALTER TABLE `supplier_return_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `tax_settings`
--
ALTER TABLE `tax_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `warehouses`
--
ALTER TABLE `warehouses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `attribute_values`
--
ALTER TABLE `attribute_values`
  ADD CONSTRAINT `attribute_values_ibfk_1` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `banner_translations`
--
ALTER TABLE `banner_translations`
  ADD CONSTRAINT `banner_translations_ibfk_1` FOREIGN KEY (`banner_id`) REFERENCES `banners` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `banner_translations_ibfk_2` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `blog_category_translations`
--
ALTER TABLE `blog_category_translations`
  ADD CONSTRAINT `blog_category_translations_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `blog_category_translations_ibfk_2` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD CONSTRAINT `blog_posts_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `blog_posts_ibfk_2` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `blog_post_translations`
--
ALTER TABLE `blog_post_translations`
  ADD CONSTRAINT `blog_post_translations_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `blog_post_translations_ibfk_2` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `category_translations`
--
ALTER TABLE `category_translations`
  ADD CONSTRAINT `category_translations_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `category_translations_ibfk_2` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  ADD CONSTRAINT `fk_coupon_usage_coupon_id` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `customers_ibfk_2` FOREIGN KEY (`customer_group_id`) REFERENCES `customer_groups` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD CONSTRAINT `customer_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `digital_downloads`
--
ALTER TABLE `digital_downloads`
  ADD CONSTRAINT `digital_downloads_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `digital_downloads_ibfk_2` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `digital_downloads_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `digital_downloads_ibfk_4` FOREIGN KEY (`digital_file_id`) REFERENCES `product_digital_files` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `faqs`
--
ALTER TABLE `faqs`
  ADD CONSTRAINT `faqs_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `faq_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `faq_category_translations`
--
ALTER TABLE `faq_category_translations`
  ADD CONSTRAINT `faq_category_translations_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `faq_categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `faq_category_translations_ibfk_2` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `faq_translations`
--
ALTER TABLE `faq_translations`
  ADD CONSTRAINT `faq_translations_ibfk_1` FOREIGN KEY (`faq_id`) REFERENCES `faqs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `faq_translations_ibfk_2` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD CONSTRAINT `inventory_transactions_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_transactions_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_transactions_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `news_translations`
--
ALTER TABLE `news_translations`
  ADD CONSTRAINT `news_translations_ibfk_1` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `news_translations_ibfk_2` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_shipping_method` FOREIGN KEY (`shipping_method_id`) REFERENCES `shipping_methods` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_4` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `order_items_ibfk_3` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`),
  ADD CONSTRAINT `order_items_ibfk_4` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `fk_order_history_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_status_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_status_history_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `page_translations`
--
ALTER TABLE `page_translations`
  ADD CONSTRAINT `page_translations_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `page_translations_ibfk_2` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `production_orders`
--
ALTER TABLE `production_orders`
  ADD CONSTRAINT `production_orders_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `production_orders_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`),
  ADD CONSTRAINT `production_orders_ibfk_3` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`),
  ADD CONSTRAINT `production_orders_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD CONSTRAINT `product_categories_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_digital_files`
--
ALTER TABLE `product_digital_files`
  ADD CONSTRAINT `product_digital_files_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `product_reviews_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_translations`
--
ALTER TABLE `product_translations`
  ADD CONSTRAINT `product_translations_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_translations_ibfk_2` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variant_attributes`
--
ALTER TABLE `product_variant_attributes`
  ADD CONSTRAINT `product_variant_attributes_ibfk_1` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_variant_attributes_ibfk_2` FOREIGN KEY (`attribute_value_id`) REFERENCES `attribute_values` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_warehouse_stock`
--
ALTER TABLE `product_warehouse_stock`
  ADD CONSTRAINT `product_warehouse_stock_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_warehouse_stock_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_warehouse_stock_ibfk_3` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `purchase_orders_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`),
  ADD CONSTRAINT `purchase_orders_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `purchase_order_items_ibfk_1` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `purchase_order_items_ibfk_3` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`);

--
-- Constraints for table `purchase_return_items`
--
ALTER TABLE `purchase_return_items`
  ADD CONSTRAINT `purchase_return_items_ibfk_1` FOREIGN KEY (`purchase_return_id`) REFERENCES `purchase_returns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_return_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `purchase_return_items_ibfk_3` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`);

--
-- Constraints for table `sales_returns`
--
ALTER TABLE `sales_returns`
  ADD CONSTRAINT `sales_returns_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Constraints for table `sales_return_items`
--
ALTER TABLE `sales_return_items`
  ADD CONSTRAINT `sales_return_items_ibfk_1` FOREIGN KEY (`sales_return_id`) REFERENCES `sales_returns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_return_items_ibfk_2` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`),
  ADD CONSTRAINT `sales_return_items_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `sales_return_items_ibfk_4` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`);

--
-- Constraints for table `service_bookings`
--
ALTER TABLE `service_bookings`
  ADD CONSTRAINT `service_bookings_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
  ADD CONSTRAINT `service_bookings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `service_bookings_ibfk_3` FOREIGN KEY (`time_slot_id`) REFERENCES `service_time_slots` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `service_bookings_ibfk_4` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `service_booking_reminders`
--
ALTER TABLE `service_booking_reminders`
  ADD CONSTRAINT `fk_reminder_booking` FOREIGN KEY (`booking_id`) REFERENCES `service_bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_booking_reviews`
--
ALTER TABLE `service_booking_reviews`
  ADD CONSTRAINT `fk_review_booking` FOREIGN KEY (`booking_id`) REFERENCES `service_bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_payments`
--
ALTER TABLE `service_payments`
  ADD CONSTRAINT `fk_payment_booking` FOREIGN KEY (`booking_id`) REFERENCES `service_bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_payment_logs`
--
ALTER TABLE `service_payment_logs`
  ADD CONSTRAINT `fk_log_payment` FOREIGN KEY (`payment_id`) REFERENCES `service_payments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_requests`
--
ALTER TABLE `service_requests`
  ADD CONSTRAINT `service_requests_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_requests_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `service_requests_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `service_time_slots`
--
ALTER TABLE `service_time_slots`
  ADD CONSTRAINT `service_time_slots_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_translations`
--
ALTER TABLE `service_translations`
  ADD CONSTRAINT `service_translations_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_translations_ibfk_2` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipping_rates`
--
ALTER TABLE `shipping_rates`
  ADD CONSTRAINT `shipping_rates_ibfk_1` FOREIGN KEY (`shipping_method_id`) REFERENCES `shipping_methods` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shipping_rates_ibfk_2` FOREIGN KEY (`shipping_zone_id`) REFERENCES `shipping_zones` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipping_zone_countries`
--
ALTER TABLE `shipping_zone_countries`
  ADD CONSTRAINT `shipping_zone_countries_ibfk_1` FOREIGN KEY (`shipping_zone_id`) REFERENCES `shipping_zones` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD CONSTRAINT `site_settings_ibfk_1` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  ADD CONSTRAINT `stock_adjustments_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`),
  ADD CONSTRAINT `stock_adjustments_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `stock_adjustment_items`
--
ALTER TABLE `stock_adjustment_items`
  ADD CONSTRAINT `stock_adjustment_items_ibfk_1` FOREIGN KEY (`stock_adjustment_id`) REFERENCES `stock_adjustments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_adjustment_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `stock_adjustment_items_ibfk_3` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`);

--
-- Constraints for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  ADD CONSTRAINT `stock_transfers_ibfk_1` FOREIGN KEY (`from_warehouse_id`) REFERENCES `warehouses` (`id`),
  ADD CONSTRAINT `stock_transfers_ibfk_2` FOREIGN KEY (`to_warehouse_id`) REFERENCES `warehouses` (`id`),
  ADD CONSTRAINT `stock_transfers_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `stock_transfer_items`
--
ALTER TABLE `stock_transfer_items`
  ADD CONSTRAINT `stock_transfer_items_ibfk_1` FOREIGN KEY (`stock_transfer_id`) REFERENCES `stock_transfers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_transfer_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `stock_transfer_items_ibfk_3` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`);

--
-- Constraints for table `supplier_returns`
--
ALTER TABLE `supplier_returns`
  ADD CONSTRAINT `supplier_returns_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supplier_returns_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supplier_returns_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_return_items`
--
ALTER TABLE `supplier_return_items`
  ADD CONSTRAINT `supplier_return_items_ibfk_1` FOREIGN KEY (`supplier_return_id`) REFERENCES `supplier_returns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supplier_return_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supplier_return_items_ibfk_3` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_admin_role` FOREIGN KEY (`admin_role_id`) REFERENCES `admin_roles` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_3` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `fk_wishlists_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_wishlists_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

SET FOREIGN_KEY_CHECKS=1;

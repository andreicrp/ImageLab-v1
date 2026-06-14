-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 14, 2026 at 10:21 PM
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
-- Database: `imagelab`
--

-- --------------------------------------------------------

--
-- Table structure for table `ai_jobs`
--

CREATE TABLE `ai_jobs` (
  `id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `operation` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'queued',
  `result_path` varchar(255) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ai_jobs`
--

INSERT INTO `ai_jobs` (`id`, `image_path`, `operation`, `status`, `result_path`, `error_message`, `created_at`, `updated_at`) VALUES
(16, 'b366bf10d98c94a17ad242e0b217610d.jpg', 'upscale_2x', 'completed', 'b366bf10d98c94a17ad242e0b217610d_upscaled_2x_1781462050.jpg', NULL, '2026-06-14 18:34:10', '2026-06-14 18:34:10');

-- --------------------------------------------------------

--
-- Table structure for table `api_keys`
--

CREATE TABLE `api_keys` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `api_key` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL DEFAULT 'Default Key',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_used_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `api_keys`
--

INSERT INTO `api_keys` (`id`, `user_id`, `api_key`, `name`, `status`, `created_at`, `last_used_at`) VALUES
(1, 2, 'il_a83b8e9281f3895abf8a1a5a8df5f98c134837112613f3f4', 'Test Gateway Link', 'active', '2026-06-14 15:41:28', '2026-06-14 15:41:28'),
(2, 2, 'il_76157010dd9a830225b3e796519ec22546b01d422598488f', 'Test Gateway Link', 'active', '2026-06-14 15:41:38', '2026-06-14 15:41:38'),
(3, 2, 'il_188fef9133c0af5209c7ac90e7507d804901d760d41b4126', 'Test Gateway Link', 'active', '2026-06-14 15:41:48', '2026-06-14 15:41:48'),
(4, 2, 'il_e3bf01ba39e6a736ba7787fae13dbbe3d80cde6dff2f6a84', 'Test Gateway Link', 'active', '2026-06-14 15:47:57', '2026-06-14 15:47:57');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `ip_address`, `user_agent`, `details`, `created_at`) VALUES
(1, 2, 'login_success', '127.0.0.1', NULL, NULL, '2026-06-10 09:36:56'),
(2, 1, 'login_success', '127.0.0.1', NULL, NULL, '2026-06-10 09:36:56'),
(3, 2, 'login_success', '127.0.0.1', NULL, NULL, '2026-06-11 09:36:56'),
(4, 2, 'login_success', '127.0.0.1', NULL, NULL, '2026-06-12 09:36:56'),
(5, 1, 'login_success', '127.0.0.1', NULL, NULL, '2026-06-12 09:36:56'),
(6, 2, 'login_success', '127.0.0.1', NULL, NULL, '2026-06-13 09:36:56'),
(7, 2, 'login_success', '127.0.0.1', NULL, NULL, '2026-06-14 09:36:56'),
(8, 1, 'login_success', '127.0.0.1', NULL, NULL, '2026-06-14 09:36:56'),
(9, NULL, 'register', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'New user registration', '2026-06-14 15:40:26'),
(10, 2, 'subscription_change', '127.0.0.1', NULL, 'Upgraded/Downgraded plan to starter', '2026-06-14 15:41:48'),
(11, 1, 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'Successful login', '2026-06-14 15:46:56'),
(12, 1, 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'Successful login', '2026-06-14 15:47:00'),
(13, 1, 'subscription_change', '::1', NULL, 'Upgraded/Downgraded plan to starter', '2026-06-14 15:48:18'),
(14, 1, 'delete_user', '::1', NULL, 'Deleted user ID 3', '2026-06-14 15:49:27'),
(15, 1, 'subscription_change', '::1', NULL, 'Upgraded/Downgraded plan to starter', '2026-06-14 16:38:12'),
(16, 1, 'subscription_change', '::1', NULL, 'Upgraded/Downgraded plan to enterprise', '2026-06-14 16:38:31'),
(17, 1, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'User logged out', '2026-06-14 20:17:05'),
(18, 1, 'login_success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'Successful login', '2026-06-14 20:17:37');

-- --------------------------------------------------------

--
-- Table structure for table `conversion_history`
--

CREATE TABLE `conversion_history` (
  `id` int(11) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `processed_filename` varchar(255) NOT NULL,
  `operation` varchar(50) NOT NULL,
  `file_size_before` int(11) NOT NULL,
  `file_size_after` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `conversion_history`
--

INSERT INTO `conversion_history` (`id`, `original_filename`, `processed_filename`, `operation`, `file_size_before`, `file_size_after`, `created_at`) VALUES
(1, '2dd7c67d64c1987602471f2486616797.png', '2dd7c67d64c1987602471f2486616797_enhanced.png', 'Enhance', 81503, 81503, '2026-06-14 09:40:00'),
(2, '2dd7c67d64c1987602471f2486616797.png', '2dd7c67d64c1987602471f2486616797_enhanced.png', 'Enhance', 81503, 81503, '2026-06-14 09:40:07'),
(3, '2dd7c67d64c1987602471f2486616797.png', '2dd7c67d64c1987602471f2486616797_enhanced.png', 'Enhance', 81503, 81503, '2026-06-14 09:40:08'),
(4, '2dd7c67d64c1987602471f2486616797.png', '2dd7c67d64c1987602471f2486616797_enhanced.png', 'Enhance', 81503, 81503, '2026-06-14 09:40:08'),
(5, '2dd7c67d64c1987602471f2486616797.png', '2dd7c67d64c1987602471f2486616797_enhanced.png', 'Enhance', 81503, 81503, '2026-06-14 09:40:08'),
(6, '2dd7c67d64c1987602471f2486616797.png', '2dd7c67d64c1987602471f2486616797_enhanced.png', 'Enhance', 81503, 81503, '2026-06-14 09:40:08'),
(7, '2bafc690cf05681e472c21a149913b0c.png', '2bafc690cf05681e472c21a149913b0c_resized.png', 'Resize', 81503, 82796, '2026-06-14 10:23:34'),
(8, '2bafc690cf05681e472c21a149913b0c.png', '2bafc690cf05681e472c21a149913b0c_resized.png', 'Resize', 81503, 82796, '2026-06-14 10:23:38'),
(9, '2bafc690cf05681e472c21a149913b0c.png', '2bafc690cf05681e472c21a149913b0c_resized.png', 'Resize', 81503, 82796, '2026-06-14 10:23:39'),
(10, '2bafc690cf05681e472c21a149913b0c.png', '2bafc690cf05681e472c21a149913b0c_resized.png', 'Resize', 81503, 82796, '2026-06-14 10:23:40'),
(11, '2bafc690cf05681e472c21a149913b0c.png', '2bafc690cf05681e472c21a149913b0c_compressed.png', 'Compress', 81503, 82796, '2026-06-14 10:23:46'),
(12, '2bafc690cf05681e472c21a149913b0c.png', '2bafc690cf05681e472c21a149913b0c_compressed.png', 'Compress', 81503, 82796, '2026-06-14 10:24:02'),
(13, '2bafc690cf05681e472c21a149913b0c.png', '2bafc690cf05681e472c21a149913b0c_compressed.png', 'Compress', 81503, 82796, '2026-06-14 10:24:03'),
(14, '2bafc690cf05681e472c21a149913b0c.png', '2bafc690cf05681e472c21a149913b0c_compressed.png', 'Compress', 81503, 82796, '2026-06-14 10:24:06'),
(15, '2bafc690cf05681e472c21a149913b0c.png', '2bafc690cf05681e472c21a149913b0c_compressed.png', 'Compress', 81503, 82796, '2026-06-14 10:24:07'),
(16, 'bcb265e3f6e61e55fedf4614b1436b36.png', 'bcb265e3f6e61e55fedf4614b1436b36_resized.png', 'Resize', 81503, 82796, '2026-06-14 10:33:32'),
(17, '28f177849b1f20221c4ac2ad2b24ffaa.png', '28f177849b1f20221c4ac2ad2b24ffaa_enhanced_1781433388.png', 'Custom Enhancement', 81503, 56735, '2026-06-14 10:36:28'),
(18, '28f177849b1f20221c4ac2ad2b24ffaa.png', '28f177849b1f20221c4ac2ad2b24ffaa_enhanced_1781433392.png', 'Custom Enhancement', 81503, 38527, '2026-06-14 10:36:32'),
(19, '28f177849b1f20221c4ac2ad2b24ffaa.png', '28f177849b1f20221c4ac2ad2b24ffaa_compressed.png', 'Compress', 81503, 82796, '2026-06-14 10:37:29'),
(20, '28f177849b1f20221c4ac2ad2b24ffaa.png', '28f177849b1f20221c4ac2ad2b24ffaa_resized.png', 'Resize', 81503, 619485, '2026-06-14 10:37:40'),
(21, '28f177849b1f20221c4ac2ad2b24ffaa.png', '28f177849b1f20221c4ac2ad2b24ffaa_resized.png', 'Resize', 81503, 619485, '2026-06-14 10:37:53'),
(22, '28f177849b1f20221c4ac2ad2b24ffaa.png', '28f177849b1f20221c4ac2ad2b24ffaa_resized.png', 'Resize', 81503, 619485, '2026-06-14 10:37:54'),
(23, '28f177849b1f20221c4ac2ad2b24ffaa.png', '28f177849b1f20221c4ac2ad2b24ffaa_resized.png', 'Resize', 81503, 619485, '2026-06-14 10:37:57'),
(24, '28f177849b1f20221c4ac2ad2b24ffaa.png', '28f177849b1f20221c4ac2ad2b24ffaa_resized.png', 'Resize', 81503, 619485, '2026-06-14 10:38:00'),
(25, 'bfd4c0997e53992256d08b3fbbd68665.png', 'bfd4c0997e53992256d08b3fbbd68665_resized.png', 'Resize', 119891, 121775, '2026-06-14 10:38:11'),
(26, '33a497da5762526419f204cbe7af2906.png', '33a497da5762526419f204cbe7af2906.jpg', 'Convert', 119891, 62168, '2026-06-14 10:39:30'),
(27, '096a897ce93e770e8bde8a716ecf7952.png', '096a897ce93e770e8bde8a716ecf7952.jpg', 'Convert', 573781, 96082, '2026-06-14 10:39:43'),
(28, '862732efdfdf15762b162d643dac7cf8.png', '862732efdfdf15762b162d643dac7cf8.jpg', 'Convert', 2357219, 329087, '2026-06-14 10:39:44'),
(29, '2f4b2c7592691b61d1cb2443f2fd934b.png', '2f4b2c7592691b61d1cb2443f2fd934b_resized.png', 'Resize', 119891, 133628, '2026-06-14 14:40:35'),
(30, '2f4b2c7592691b61d1cb2443f2fd934b.png', '2f4b2c7592691b61d1cb2443f2fd934b_resized.png', 'Resize', 119891, 37755, '2026-06-14 14:40:44'),
(31, 'bfbdbc625e5eaf220a03f2682355e688.png', 'bfbdbc625e5eaf220a03f2682355e688_resized.png', 'Resize', 81503, 621460, '2026-06-14 15:42:23'),
(32, 'fe82d53a6c089ddc904fd8987c76c16f.png', 'fe82d53a6c089ddc904fd8987c76c16f_resized.png', 'Resize', 119891, 133628, '2026-06-14 16:19:13'),
(33, 'ab098d1acdf8f14ef03764ae922eabdd.png', 'ab098d1acdf8f14ef03764ae922eabdd_enhanced_1781454751.png', 'Custom Enhancement', 119891, 280936, '2026-06-14 16:32:32'),
(34, 'ab098d1acdf8f14ef03764ae922eabdd.png', 'ab098d1acdf8f14ef03764ae922eabdd_enhanced_1781454759.png', 'Custom Enhancement', 119891, 162125, '2026-06-14 16:32:39'),
(35, 'ab098d1acdf8f14ef03764ae922eabdd.png', 'ab098d1acdf8f14ef03764ae922eabdd_enhanced_1781454829.png', 'Custom Enhancement', 119891, 153952, '2026-06-14 16:33:49'),
(36, 'ab098d1acdf8f14ef03764ae922eabdd.png', 'ab098d1acdf8f14ef03764ae922eabdd_enhanced_1781454908.png', 'Custom Enhancement', 119891, 153952, '2026-06-14 16:35:09'),
(37, 'ab098d1acdf8f14ef03764ae922eabdd.png', 'ab098d1acdf8f14ef03764ae922eabdd_enhanced_1781454911.png', 'Custom Enhancement', 119891, 303908, '2026-06-14 16:35:11'),
(38, 'ab098d1acdf8f14ef03764ae922eabdd.png', 'ab098d1acdf8f14ef03764ae922eabdd_resized.png', 'Resize', 119891, 510566, '2026-06-14 16:35:30'),
(39, 'ab098d1acdf8f14ef03764ae922eabdd.png', 'ab098d1acdf8f14ef03764ae922eabdd_resized.png', 'Resize', 119891, 425903, '2026-06-14 16:35:38'),
(40, '82a791c367f4c2a8da9e84369072b6ef.png', '82a791c367f4c2a8da9e84369072b6ef_enhanced_1781454975.png', 'Custom Enhancement', 2357219, 2579336, '2026-06-14 16:36:16'),
(41, '82a791c367f4c2a8da9e84369072b6ef.png', '82a791c367f4c2a8da9e84369072b6ef_enhanced_1781454975.png', 'Custom Enhancement', 2357219, 2579336, '2026-06-14 16:36:16'),
(42, '82a791c367f4c2a8da9e84369072b6ef.png', '82a791c367f4c2a8da9e84369072b6ef_enhanced_1781454982.png', 'Custom Enhancement', 2357219, 8208, '2026-06-14 16:36:22'),
(43, '82a791c367f4c2a8da9e84369072b6ef.png', '82a791c367f4c2a8da9e84369072b6ef_enhanced_1781454984.png', 'Custom Enhancement', 2357219, 8208, '2026-06-14 16:36:24'),
(44, '82a791c367f4c2a8da9e84369072b6ef.png', '82a791c367f4c2a8da9e84369072b6ef_enhanced_1781455009.png', 'Custom Enhancement', 2357219, 1964565, '2026-06-14 16:36:50'),
(45, '82a791c367f4c2a8da9e84369072b6ef.png', '82a791c367f4c2a8da9e84369072b6ef_enhanced_1781455010.png', 'Custom Enhancement', 2357219, 1964565, '2026-06-14 16:36:50'),
(46, '46cbe983cfe1b622b640154496710a8b.png', '46cbe983cfe1b622b640154496710a8b_enhanced_1781455394.png', 'Custom Enhancement', 2357219, 2562194, '2026-06-14 16:43:15'),
(47, '46cbe983cfe1b622b640154496710a8b.png', '46cbe983cfe1b622b640154496710a8b_enhanced_1781455396.png', 'Custom Enhancement', 2357219, 2562194, '2026-06-14 16:43:16'),
(48, '46cbe983cfe1b622b640154496710a8b.png', '46cbe983cfe1b622b640154496710a8b_enhanced_1781455398.png', 'Custom Enhancement', 2357219, 2748075, '2026-06-14 16:43:18'),
(49, '46cbe983cfe1b622b640154496710a8b.png', '46cbe983cfe1b622b640154496710a8b_enhanced_1781455399.png', 'Custom Enhancement', 2357219, 2513154, '2026-06-14 16:43:19'),
(50, '46cbe983cfe1b622b640154496710a8b.png', '46cbe983cfe1b622b640154496710a8b_enhanced_1781455401.png', 'Custom Enhancement', 2357219, 2474951, '2026-06-14 16:43:22'),
(51, '46cbe983cfe1b622b640154496710a8b.png', '46cbe983cfe1b622b640154496710a8b_enhanced_1781455402.png', 'Custom Enhancement', 2357219, 2474951, '2026-06-14 16:43:23'),
(52, '46cbe983cfe1b622b640154496710a8b.png', '46cbe983cfe1b622b640154496710a8b_enhanced_1781455405.png', 'Custom Enhancement', 2357219, 2712274, '2026-06-14 16:43:25'),
(53, '46cbe983cfe1b622b640154496710a8b.png', '46cbe983cfe1b622b640154496710a8b_enhanced_1781455405.png', 'Custom Enhancement', 2357219, 2712274, '2026-06-14 16:43:26'),
(54, '640f26064f19bc7fec32409a62939122.png', '640f26064f19bc7fec32409a62939122_enhanced_1781455642.png', 'Custom Enhancement', 119891, 303908, '2026-06-14 16:47:22'),
(55, '6bf5206d3bbca6b444548204bcf14222.png', '6bf5206d3bbca6b444548204bcf14222_filter_vintage_1781455739.png', 'Filter: Vintage', 119891, 263790, '2026-06-14 16:48:59'),
(56, '6bf5206d3bbca6b444548204bcf14222.png', '6bf5206d3bbca6b444548204bcf14222_filter_vintage_1781455740.png', 'Filter: Vintage', 119891, 263790, '2026-06-14 16:49:00'),
(57, '6bf5206d3bbca6b444548204bcf14222.png', '6bf5206d3bbca6b444548204bcf14222_filter_vintage_1781455741.png', 'Filter: Vintage', 119891, 263790, '2026-06-14 16:49:01'),
(58, '6bf5206d3bbca6b444548204bcf14222.png', '6bf5206d3bbca6b444548204bcf14222_filter_vivid_1781455741.png', 'Filter: Vivid', 119891, 274629, '2026-06-14 16:49:01'),
(59, '6bf5206d3bbca6b444548204bcf14222.png', '6bf5206d3bbca6b444548204bcf14222_filter_bw_1781455742.png', 'Filter: Bw', 119891, 110371, '2026-06-14 16:49:02'),
(60, '6bf5206d3bbca6b444548204bcf14222.png', '6bf5206d3bbca6b444548204bcf14222_filter_cinema_1781455743.png', 'Filter: Cinema', 119891, 271898, '2026-06-14 16:49:03'),
(61, '6bf5206d3bbca6b444548204bcf14222.png', '6bf5206d3bbca6b444548204bcf14222_filter_hdr_1781455744.png', 'Filter: Hdr', 119891, 338796, '2026-06-14 16:49:04'),
(62, '6bf5206d3bbca6b444548204bcf14222.png', '6bf5206d3bbca6b444548204bcf14222_filter_warm_1781455744.png', 'Filter: Warm', 119891, 298240, '2026-06-14 16:49:05'),
(63, '6bf5206d3bbca6b444548204bcf14222.png', '6bf5206d3bbca6b444548204bcf14222_filter_cool_1781455745.png', 'Filter: Cool', 119891, 300129, '2026-06-14 16:49:06'),
(64, '6bf5206d3bbca6b444548204bcf14222.png', '6bf5206d3bbca6b444548204bcf14222_filter_original_1781455749.png', 'Filter: Original', 119891, 119891, '2026-06-14 16:49:09'),
(65, '54becf4000615063e392dd2155bca08c.png', '54becf4000615063e392dd2155bca08c_resized.png', 'Resize', 81503, 619485, '2026-06-14 17:33:28'),
(66, '2dd7c67d64c1987602471f2486616797_resized.png', '2dd7c67d64c1987602471f2486616797_resized_resized.png', 'Resize', 29236, 4118, '2026-06-14 17:36:42'),
(67, 'b366bf10d98c94a17ad242e0b217610d.jpg', 'b366bf10d98c94a17ad242e0b217610d_compressed.jpg', 'Compress', 57280, 22636, '2026-06-14 18:34:22'),
(68, '2dd7c67d64c1987602471f2486616797.png', '2dd7c67d64c1987602471f2486616797_resized.png', 'Resize', 81503, 29236, '2026-06-14 20:14:14'),
(69, '2dd7c67d64c1987602471f2486616797_resized.png', '2dd7c67d64c1987602471f2486616797_resized_resized.png', 'Resize', 29236, 4118, '2026-06-14 20:14:17'),
(70, '2dd7c67d64c1987602471f2486616797_resized.png', '2dd7c67d64c1987602471f2486616797_resized_resized.png', 'Resize', 29236, 4118, '2026-06-14 20:15:09');

-- --------------------------------------------------------

--
-- Table structure for table `enhancement_history`
--

CREATE TABLE `enhancement_history` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `operation` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `tax` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'unpaid',
  `billing_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `user_id`, `invoice_number`, `amount`, `tax`, `status`, `billing_date`, `created_at`) VALUES
(1, 2, 'INV-8D9DA2-202603', 49.99, 0.00, 'paid', '2026-03-10 04:00:00', '2026-06-14 15:36:56'),
(2, 2, 'INV-3168E7-202604', 99.98, 0.00, 'paid', '2026-04-10 04:00:00', '2026-06-14 15:36:56'),
(3, 2, 'INV-9ADDBB-202605', 149.97, 0.00, 'paid', '2026-05-10 04:00:00', '2026-06-14 15:36:56'),
(4, 2, 'INV-1A9CF9-202606', 199.96, 0.00, 'paid', '2026-06-10 04:00:00', '2026-06-14 15:36:56'),
(6, 2, 'INV-F2FE29B7-2026', 10.99, 1.00, 'paid', '2026-06-14 15:41:48', '2026-06-14 15:41:48'),
(7, 1, 'INV-4D9C3E99-2026', 10.99, 1.00, 'paid', '2026-06-14 15:48:18', '2026-06-14 15:48:18'),
(8, 1, 'INV-5A3C765A-2026', 10.99, 1.00, 'paid', '2026-06-14 16:38:12', '2026-06-14 16:38:12'),
(9, 1, 'INV-DBC16C72-2026', 109.99, 10.00, 'paid', '2026-06-14 16:38:31', '2026-06-14 16:38:31');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 2, 'system', 'Subscription Updated', 'Your plan has been updated to STARTER. Enjoy your new limits!', 0, '2026-06-14 15:41:48'),
(2, 2, 'system', 'Payment Received', 'We received your payment of $9.99 for the STARTER plan. Invoice INV-F2FE29B7-2026 has been generated.', 0, '2026-06-14 15:41:48'),
(3, 1, 'system', 'Subscription Updated', 'Your plan has been updated to STARTER. Enjoy your new limits!', 0, '2026-06-14 15:48:18'),
(4, 1, 'system', 'Payment Received', 'We received your payment of $9.99 for the STARTER plan. Invoice INV-4D9C3E99-2026 has been generated.', 0, '2026-06-14 15:48:18'),
(5, 1, 'system', 'Subscription Updated', 'Your plan has been updated to STARTER. Enjoy your new limits!', 0, '2026-06-14 16:38:12'),
(6, 1, 'system', 'Payment Received', 'We received your payment of $9.99 for the STARTER plan. Invoice INV-5A3C765A-2026 has been generated.', 0, '2026-06-14 16:38:12'),
(7, 1, 'system', 'Subscription Updated', 'Your plan has been updated to ENTERPRISE. Enjoy your new limits!', 0, '2026-06-14 16:38:31'),
(8, 1, 'system', 'Payment Received', 'We received your payment of $99.99 for the ENTERPRISE plan. Invoice INV-DBC16C72-2026 has been generated.', 0, '2026-06-14 16:38:31');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `project_data` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `queue_jobs`
--

CREATE TABLE `queue_jobs` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `operation` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'waiting',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `saved_presets`
--

CREATE TABLE `saved_presets` (
  `id` int(11) NOT NULL,
  `preset_name` varchar(255) NOT NULL,
  `preset_data` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan` varchar(50) NOT NULL DEFAULT 'free',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `credits` int(11) NOT NULL DEFAULT 5,
  `starts_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ends_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscriptions`
--

INSERT INTO `subscriptions` (`id`, `user_id`, `plan`, `status`, `credits`, `starts_at`, `ends_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'enterprise', 'expired', 9999, '2026-06-14 15:36:56', '2026-06-14 15:48:18', '2026-06-14 15:36:56', '2026-06-14 15:48:18'),
(2, 2, 'free', 'expired', 5, '2026-06-14 15:36:56', '2026-06-14 15:41:48', '2026-06-14 15:36:56', '2026-06-14 15:41:48'),
(4, 2, 'starter', 'active', 100, '2026-06-14 15:41:48', '2026-07-14 09:41:48', '2026-06-14 15:41:48', '2026-06-14 15:41:48'),
(5, 1, 'starter', 'expired', 100, '2026-06-14 15:48:18', '2026-06-14 16:38:12', '2026-06-14 15:48:18', '2026-06-14 16:38:12'),
(6, 1, 'starter', 'expired', 100, '2026-06-14 16:38:12', '2026-06-14 16:38:31', '2026-06-14 16:38:12', '2026-06-14 16:38:31'),
(7, 1, 'enterprise', 'active', 99999, '2026-06-14 16:38:31', '2027-06-14 10:38:31', '2026-06-14 16:38:31', '2026-06-14 16:38:31');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `transaction_id` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  `status` varchar(20) NOT NULL,
  `provider` varchar(50) NOT NULL DEFAULT 'paypal',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `transaction_id`, `amount`, `currency`, `status`, `provider`, `created_at`) VALUES
(2, 2, 'PAYID-MOCKTEST123456', 9.99, 'USD', 'completed', 'paypal', '2026-06-14 15:41:48'),
(4, 1, 'PAYID-BF4266A1A1E42371E866', 9.99, 'USD', 'completed', 'paypal', '2026-06-14 15:48:18'),
(5, 1, 'PAYID-2476B702D1E898B6DA2D', 9.99, 'USD', 'completed', 'paypal', '2026-06-14 16:38:12'),
(6, 1, 'PAYID-0F15D7AF3DD75687985A', 99.99, 'USD', 'completed', 'paypal', '2026-06-14 16:38:31');

-- --------------------------------------------------------

--
-- Table structure for table `usage_logs`
--

CREATE TABLE `usage_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `api_key_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `bytes` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `usage_logs`
--

INSERT INTO `usage_logs` (`id`, `user_id`, `api_key_id`, `action`, `details`, `bytes`, `created_at`) VALUES
(1, 2, NULL, 'upload', NULL, 1048576, '2026-06-14 15:36:56'),
(2, 2, NULL, 'upload', NULL, 524288, '2026-06-14 15:36:56'),
(3, 2, NULL, 'convert', NULL, 204857, '2026-06-14 15:36:56'),
(4, 2, NULL, 'resize', NULL, 304857, '2026-06-14 15:36:56'),
(5, 2, NULL, 'enhance', NULL, 404857, '2026-06-14 15:36:56'),
(6, 2, NULL, 'ai_request', NULL, 2097152, '2026-06-14 15:36:56'),
(7, 2, NULL, 'ai_request', NULL, 1572864, '2026-06-14 15:36:56'),
(8, 2, 3, 'convert', 'Converted 096a897ce93e770e8bde8a716ecf7952.png to webp', 66616, '2026-06-14 15:41:50');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'user',
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_token` varchar(100) DEFAULT NULL,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_token_expires` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `failed_attempts` int(11) NOT NULL DEFAULT 0,
  `lockout_until` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `email_verified`, `verification_token`, `reset_token`, `reset_token_expires`, `remember_token`, `failed_attempts`, `lockout_until`, `created_at`, `updated_at`) VALUES
(1, 'System Admin', 'admin@imagelab.com', '$2y$10$JqoRteXXRARyE3mWARi9i.zzWRii7gfRdC23LtYjBn691zcAbMOEK', 'admin', 1, NULL, NULL, NULL, NULL, 0, NULL, '2026-06-14 15:36:56', '2026-06-14 20:17:05'),
(2, 'Standard User', 'user@imagelab.com', '$2y$10$CYITNBSUTmzwGUJVztpX6.wjyheGUQNavN0.CH1WpkiCDsD41ATna', 'premium', 1, NULL, NULL, NULL, NULL, 0, NULL, '2026-06-14 15:36:56', '2026-06-14 15:41:48');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ai_jobs`
--
ALTER TABLE `ai_jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `api_keys`
--
ALTER TABLE `api_keys`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `api_key` (`api_key`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `conversion_history`
--
ALTER TABLE `conversion_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `enhancement_history`
--
ALTER TABLE `enhancement_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `queue_jobs`
--
ALTER TABLE `queue_jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `saved_presets`
--
ALTER TABLE `saved_presets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_id` (`transaction_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `usage_logs`
--
ALTER TABLE `usage_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `api_key_id` (`api_key_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ai_jobs`
--
ALTER TABLE `ai_jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `api_keys`
--
ALTER TABLE `api_keys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `conversion_history`
--
ALTER TABLE `conversion_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `enhancement_history`
--
ALTER TABLE `enhancement_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `queue_jobs`
--
ALTER TABLE `queue_jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `saved_presets`
--
ALTER TABLE `saved_presets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `usage_logs`
--
ALTER TABLE `usage_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `api_keys`
--
ALTER TABLE `api_keys`
  ADD CONSTRAINT `api_keys_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `usage_logs`
--
ALTER TABLE `usage_logs`
  ADD CONSTRAINT `usage_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `usage_logs_ibfk_2` FOREIGN KEY (`api_key_id`) REFERENCES `api_keys` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

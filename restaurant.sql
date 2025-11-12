-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 07, 2025 at 12:39 PM
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
-- Database: `agan_cafe`
--
USE agan_cafe;

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `email`, `password`, `name`, `last_login_at`, `login_attempts`, `locked_until`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@agancafe.com', '1111', 'System Administrator', '2025-11-07 10:37:08', 0, NULL, 1, '2025-09-20 04:04:51', '2025-11-07 10:37:08'),
(2, 'anish', 'anish@123', '11111', 'Anish', NULL, 0, NULL, 1, '2025-09-20 04:33:20', '2025-09-20 04:33:20'),
(3, 'sarun', 'sarun@gmail.com', 'password', 'System Administrator', '2025-11-07 10:37:08', 0, NULL, 1, '2025-09-20 04:04:51', '2025-11-07 10:37:08');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `image`, `created_at`, `updated_at`) VALUES
(1, 'Appetizers', 'Start your meal with our delicious appetizers', NULL, '2025-09-20 04:04:51', '2025-09-20 04:04:51'),
(2, 'Main Courses', 'Hearty and satisfying main dishes', NULL, '2025-09-20 04:04:51', '2025-09-20 04:04:51'),
(3, 'Beverages', 'Refreshing drinks to complement your meal', NULL, '2025-09-20 04:04:51', '2025-09-20 04:04:51'),
(4, 'Desserts', 'Sweet treats to end your meal perfectly', NULL, '2025-09-20 04:04:51', '2025-09-20 04:04:51'),
(5, 'Salads', 'Fresh and healthy salad options', NULL, '2025-09-20 04:04:51', '2025-09-20 04:04:51');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `order_number` varchar(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `delivery_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `status` enum('Pending','Confirmed','Preparing','Out for Delivery','Delivered','Cancelled') DEFAULT 'Pending',
  `payment_method` enum('Cash on Delivery','Online Payment') DEFAULT 'Cash on Delivery',
  `payment_status` enum('Pending','Paid','Failed','Refunded') DEFAULT 'Pending',
  `estimated_delivery_time` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_number`, `name`, `phone`, `email`, `address`, `subtotal`, `delivery_fee`, `tax_amount`, `total`, `status`, `payment_method`, `payment_status`, `estimated_delivery_time`, `delivered_at`, `created_at`, `updated_at`) VALUES
(13, NULL, 'ORD000001', 'Anish Maharjan', '9860815803', '', 'Imadole', 490.00, 50.00, 63.70, 603.70, 'Pending', 'Cash on Delivery', 'Pending', NULL, NULL, '2025-09-21 07:26:28', '2025-09-21 07:26:28'),
(14, NULL, 'ORD000002', 'Anish Maharjan', '9860815803', 'anishmaharjan553@gmail.com', 'Imadole', 700.00, 50.00, 91.00, 841.00, 'Delivered', 'Cash on Delivery', 'Pending', NULL, '2025-09-21 07:28:38', '2025-09-21 07:26:45', '2025-09-21 07:28:38'),
(15, 3, 'ORD000003', 'Subash', '9847377373', 'subash@gmail.com', 'Langakhel, Lalitpur', 490.00, 50.00, 63.70, 603.70, 'Pending', 'Cash on Delivery', 'Pending', NULL, NULL, '2025-11-07 10:50:23', '2025-11-07 10:50:23');

--
-- Triggers `orders`
--
DELIMITER $$
CREATE TRIGGER `generate_order_number` BEFORE INSERT ON `orders` FOR EACH ROW BEGIN
    DECLARE next_number INT;
    SELECT COALESCE(MAX(CAST(SUBSTRING(order_number, 4) AS UNSIGNED)), 0) + 1 
    INTO next_number 
    FROM orders 
    WHERE order_number LIKE CONCAT('ORD', '%');
    
    SET NEW.order_number = CONCAT('ORD', LPAD(next_number, 6, '0'));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `unit_price`, `total_price`, `created_at`) VALUES
(1, 13, 4, 1, 350.00, 350.00, '2025-09-21 07:26:28'),
(2, 13, 1, 1, 140.00, 140.00, '2025-09-21 07:26:28'),
(3, 14, 4, 1, 350.00, 350.00, '2025-09-21 07:26:45'),
(4, 14, 3, 1, 350.00, 350.00, '2025-09-21 07:26:45'),
(5, 15, 4, 1, 350.00, 350.00, '2025-11-07 10:50:23'),
(6, 15, 1, 1, 140.00, 140.00, '2025-11-07 10:50:23');

--
-- Triggers `order_items`
--
DELIMITER $$
CREATE TRIGGER `update_order_item_total` BEFORE INSERT ON `order_items` FOR EACH ROW BEGIN
    SET NEW.total_price = NEW.quantity * NEW.unit_price;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

CREATE TABLE `order_status_history` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `changed_by_admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_status_history`
--

INSERT INTO `order_status_history` (`id`, `order_id`, `status`, `notes`, `changed_by_admin_id`, `created_at`) VALUES
(1, 13, 'Pending', 'Order placed successfully', NULL, '2025-09-21 07:26:29'),
(2, 14, 'Pending', 'Order placed successfully', NULL, '2025-09-21 07:26:45'),
(3, 14, 'Confirmed', '', 1, '2025-09-21 07:27:22'),
(4, 14, 'Delivered', '', 1, '2025-09-21 07:28:12'),
(5, 14, 'Delivered', '', 1, '2025-09-21 07:28:38'),
(6, 15, 'Pending', 'Order placed successfully', NULL, '2025-11-07 10:50:23');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `image`, `created_at`, `updated_at`) VALUES
(1, 1, 'Mo:Mo (Chicken)', 'Traditional Nepali dumplings filled with seasoned chicken', 140.00, NULL, '2025-09-20 04:04:51', '2025-09-20 15:30:25'),
(3, 2, 'Dal Bhat Set', 'Traditional Nepali meal with lentils, rice, vegetables and pickle', 350.00, NULL, '2025-09-20 04:04:51', '2025-09-20 04:04:51'),
(4, 2, 'Chicken Curry', 'Tender chicken cooked in aromatic spices with rice', 350.00, NULL, '2025-09-20 04:04:51', '2025-09-20 09:35:35'),
(5, 3, 'Masala Chai', 'Traditional spiced tea', 80.00, NULL, '2025-09-20 04:04:51', '2025-09-20 04:04:51'),
(6, 3, 'Lassi (Sweet)', 'Refreshing yogurt-based drink', 120.00, NULL, '2025-09-20 04:04:51', '2025-09-20 04:04:51'),
(7, 4, 'Gulab Jamun', 'Soft milk dumplings in sugar syrup', 150.00, NULL, '2025-09-20 04:04:51', '2025-09-20 04:04:51'),
(8, 1, 'Mo:Mo (Vegetable)', 'Steamed dumplings filled with fresh vegetables.', 150.00, NULL, '2025-11-06 15:17:09', '2025-11-06 15:17:27');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'site_name', 'Agan Cafe', 'Website name', '2025-09-20 04:04:51'),
(2, 'delivery_fee', '50.00', 'Standard delivery fee', '2025-09-20 04:04:51'),
(3, 'min_order_amount', '200.00', 'Minimum order amount for delivery', '2025-09-20 04:04:51'),
(4, 'tax_rate', '13.00', 'Tax rate percentage', '2025-09-20 04:04:51'),
(5, 'restaurant_phone', '+977-1-4567890', 'Restaurant contact number', '2025-09-20 04:04:51'),
(6, 'restaurant_email', 'info@agancafe.com', 'Restaurant email', '2025-09-20 04:04:51'),
(7, 'restaurant_address', '123 Food Street, Kathmandu, Nepal', 'Restaurant address', '2025-09-20 04:04:51'),
(8, 'opening_hours', 'Mon-Fri: 9AM-10PM, Sat: 10AM-11PM, Sun: 10AM-9PM', 'Restaurant opening hours', '2025-09-20 04:04:51');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `last_login_at`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Anish Maharjan', 'anishmaharjan553@gmail.com', '9860815803', '$2y$10$pyqgnsdxaQF4HtmRv8AF7esJJyQSVQA2FL7dXWVsn3Xahmyn43lpK', '2025-09-21 06:35:57', 1, '2025-09-20 05:07:48', '2025-09-21 06:35:57'),
(2, 'Anish Maharjan', 'aaaaa@asd.com', '9860815803', '$2y$10$xjxjrQ8XDDiCvDAbDRIAJeVPav9OKgLyg9JBCmHqslFr.DpnXAozS', NULL, 1, '2025-09-20 09:36:59', '2025-09-20 09:36:59'),
(3, 'Subash', 'subash@gmail.com', '9847377373', '$2y$10$KB8zWfoupSelS7ZwGNSJj.1QR4259ueJPVaQylRym49LGZ2aTXa/q', '2025-11-07 10:49:33', 1, '2025-11-07 10:48:19', '2025-11-07 10:49:33');

-- --------------------------------------------------------

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_order_number` (`order_number`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `changed_by_admin_id` (`changed_by_admin_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category_id` (`category_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_phone` (`phone`);

-- --------------------------------------------------------

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

-- --------------------------------------------------------

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `order_status_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_status_history_ibfk_2` FOREIGN KEY (`changed_by_admin_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
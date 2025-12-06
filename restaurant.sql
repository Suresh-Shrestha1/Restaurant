-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 04, 2025 at 01:33 PM
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
-- Database: `restaurant`
--

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
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `email`, `password`, `name`, `last_login_at`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin_rest@gmail.com', 'Admin123', 'Main Admin', '2025-12-04 11:31:05', 1, '2025-11-12 08:46:09', '2025-12-04 11:31:05');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Appetizers', 'Small, bite-sized dishes served before the main course to stimulate appetite.', '2025-09-20 04:04:51', '2025-12-04 03:37:33'),
(2, 'Main Courses', 'The primary, hearty dish of a meal, typically featuring protein, vegetables, and starch.', '2025-09-20 04:04:51', '2025-12-04 03:38:21'),
(3, 'Tandoori Specialties', 'Dishes cooked in a traditional clay oven (tandoor), often marinated with spices and yogurt, giving them a smoky flavor.', '2025-09-20 04:04:51', '2025-12-04 03:39:50'),
(4, 'Curries & Stews', 'Hearty, slow-cooked dishes with rich, spiced sauces or broths, perfect with rice or bread.', '2025-12-04 05:00:38', '2025-12-04 05:00:38'),
(5, 'Rice & Breads', 'Steamed rice and various types of flatbreads, often used to complement curries and dishes. ', '2025-09-20 04:04:51', '2025-12-04 04:57:42'),
(6, 'Salads & Sides', 'Fresh vegetables, often with dressing, served as a light side or appetizer.', '2025-09-20 04:04:51', '2025-12-04 12:10:13'),
(7, 'Desserts', 'Sweet dishes, typically served at the end of a meal to satisfy the palate.', '2025-09-24 04:02:51', '2025-12-04 04:57:22'),
(8, 'Beverages', 'Drinks, both hot and cold, served to refresh or complement meals.', '2025-12-04 03:44:14', '2025-12-04 04:57:16');

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
  `payment_method` enum('Cash on Delivery','') DEFAULT 'Cash on Delivery',
  `delivered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_number`, `name`, `phone`, `email`, `address`, `subtotal`, `delivery_fee`, `tax_amount`, `total`, `status`, `payment_method`, `delivered_at`, `created_at`, `updated_at`) VALUES
(23, 2, 'ORD000001', 'Sujan Shakya', '9848838858', 'sujan12@gmail.com', 'Gwarko, Lalitpur', 300.00, 50.00, 39.00, 389.00, 'Pending', 'Cash on Delivery', NULL, '2025-12-04 12:31:41', '2025-12-04 12:31:41');

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
(21, 23, 17, 1, 210.00, 210.00, '2025-12-04 12:31:41'),
(22, 23, 30, 2, 45.00, 90.00, '2025-12-04 12:31:41');

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
(24, 23, 'Pending', 'Order placed successfully', NULL, '2025-12-04 12:31:41');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(20) NOT NULL,
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
(1, 1, 'Mo:Mo(Chicken)', 'Soft dumplings filled with spiced, tender chicken, served with a tangy sauce.', 250.00, '693174d2dfbfa_1764848850.jpg', '2025-09-20 04:04:51', '2025-12-04 11:47:30'),
(2, 1, 'Mo:Mo(Vegetable)', 'Steamed dumplings filled with a mix of spiced vegetables, served with a tangy sauce.', 180.00, '693174e068471_1764848864.jpg', '2025-09-20 04:04:51', '2025-12-04 11:47:44'),
(3, 1, 'Mo:Mo(Buffalo)', 'Traditional dumplings with spiced buffalo meat, served with a flavorful dipping sauce.', 220.00, '693174c892520_1764848840.jpg', '2025-09-20 04:04:51', '2025-12-04 11:47:20'),
(4, 1, 'Allo Tikka', 'Crispy potato patties spiced with cumin, coriander, and turmeric, served with fresh mint chutney.', 150.00, '6931749a3f8c4_1764848794.jpg', '2025-09-20 04:04:51', '2025-12-04 11:46:34'),
(5, 1, 'Samosa', 'Flaky pastry filled with spiced potatoes, peas, and peas, served with tamarind chutney.', 80.00, '693175058ba3a_1764848901.jpg', '2025-09-20 04:04:51', '2025-12-04 11:48:21'),
(6, 1, 'Chhoila', 'Charcoal-grilled marinated buffalo, served with onion, coriander, and a spicy sesame-based dressing.', 120.00, '693174aded473_1764848813.jpg', '2025-09-20 04:04:51', '2025-12-04 11:46:53'),
(8, 1, 'Pani Puri', 'Puris filled with spiced potatoes, chickpeas, and tangy tamarind water, creating a burst of flavor in every bite.', 100.00, '693174f811491_1764848888.webp', '2025-09-24 04:11:15', '2025-12-04 11:48:08'),
(9, 2, 'Dal Bhat', 'The heart of Nepali cuisine – lentil soup served with steamed rice, accompanied by seasonal vegetables, pickles, and gundruk (fermented greens).', 180.00, '693174b70cd9a_1764848823.jpg', '2025-12-02 16:34:54', '2025-12-04 11:47:03'),
(10, 2, 'Thakali Thali', 'A traditional Nepali platter featuring dal, rice, vegetables, pickles, and meat mutton, offering a taste of the Himalayas in every bite.', 250.00, '6931756dddd69_1764849005.jpg', '2025-12-04 11:50:05', '2025-12-04 11:50:43'),
(11, 2, 'Sel Roti', 'Homemade Nepali rice doughnut served with a side of yogurt or chutney.', 60.00, '6931760618c29_1764849158.webp', '2025-12-04 11:51:33', '2025-12-04 11:52:38'),
(12, 2, 'Gundruk', 'Fermented leafy greens, a traditional Nepali dish, served as a side or salad, bringing out the authentic flavors of rural Nepal.', 100.00, '6931762f29c04_1764849199.jpg', '2025-12-04 11:53:19', '2025-12-04 11:53:19'),
(13, 3, 'Tandoori Chicken', 'Chicken marinated in a rich blend of spices, yogurt, and herbs, grilled to perfection in the tandoor oven.', 500.00, '6931767ac1356_1764849274.jpg', '2025-12-04 11:54:34', '2025-12-04 11:54:34'),
(14, 3, 'Seekh Kebab', 'Spiced minced meat buffalo shaped into skewers and cooked in the tandoor.', 150.00, '693176af77f7f_1764849327.jpg', '2025-12-04 11:55:27', '2025-12-04 11:55:27'),
(15, 3, 'Paneer Tikka', 'Cubes of cottage cheese marinated in aromatic spices and grilled to perfection, served with a fresh mint chutney.', 130.00, '693176de0e5a9_1764849374.jpg', '2025-12-04 11:56:14', '2025-12-04 11:56:14'),
(16, 3, 'Tandoori Fish', 'Fresh fish marinated in a delicate mix of Nepali spices, grilled in the tandoor for a smoky flavor.', 350.00, '693177037ff0b_1764849411.jpg', '2025-12-04 11:56:51', '2025-12-04 11:56:51'),
(17, 4, 'Chicken Curry', 'Tender chicken cooked in a rich, aromatic curry sauce with traditional Nepali spices, perfect with a side of steamed rice or naan.', 210.00, '69317750acec5_1764849488.jpg', '2025-12-04 11:58:08', '2025-12-04 11:58:08'),
(18, 4, 'Mutton Curry', 'Slow-cooked mutton simmered in a hearty, flavorful gravy with a mix of spices and herbs.', 300.00, '693177790fb42_1764849529.jpg', '2025-12-04 11:58:49', '2025-12-04 11:58:49'),
(19, 4, 'Vegetable Curry', 'Seasonal vegetables cooked in a fragrant curry sauce with cumin, turmeric, and garam masala.', 170.00, '693177a40d45b_1764849572.jpg', '2025-12-04 11:59:32', '2025-12-04 11:59:32'),
(20, 4, 'Buffalo Curry', 'A traditional Nepali favorite, buffalo meat simmered in a rich, spicy gravy, infused with Nepali herbs and spices.', 200.00, '693177fc01f15_1764849660.jpg', '2025-12-04 12:01:00', '2025-12-04 12:01:00'),
(21, 4, 'Dal(Lentil Soup)', 'Hearty, comforting lentil soup cooked with turmeric and served with a side of steamed rice.', 120.00, '6931782c750e3_1764849708.webp', '2025-12-04 12:01:48', '2025-12-04 12:01:48'),
(22, 5, 'Chicken Fried Rice', 'Fragrant rice stir-fried with vegetables, spices, and a hint of soy sauce, perfect for pairing with any curry.', 160.00, '69317887129e4_1764849799.webp', '2025-12-04 12:03:19', '2025-12-04 12:05:32'),
(23, 5, 'Butter Naan', 'Soft, pillowy flatbread baked in the tandoor, perfect for scooping up curry or dal.', 100.00, '69317957aa7a3_1764850007.jpg', '2025-12-04 12:06:47', '2025-12-04 12:06:47'),
(24, 5, 'Tandoori Roti', 'Whole wheat flatbread baked in the tandoor, giving it a crispy texture and smoky flavor.', 140.00, '69317986bbd6d_1764850054.jpg', '2025-12-04 12:07:34', '2025-12-04 12:07:34'),
(25, 5, 'Matar Pulao', 'Fragrant basmati rice cooked with peas and mild spices, an aromatic side to complement your curry.', 120.00, '693179b01d5a4_1764850096.jpg', '2025-12-04 12:08:16', '2025-12-04 12:08:16'),
(26, 6, 'Kachumber Salad', 'Freshly chopped cucumbers, tomatoes, onions, and cilantro tossed with a tangy lemon dressing.', 80.00, '693179d4f01ee_1764850132.jpg', '2025-12-04 12:08:52', '2025-12-04 12:08:52'),
(27, 6, 'Raita', 'Creamy yogurt mixed with cucumbers, mint, and spices, the perfect cooling side dish to balance the heat of curries.', 80.00, '69317abe6e1bd_1764850366.jpg', '2025-12-04 12:12:46', '2025-12-04 12:12:46'),
(28, 6, 'Pickles(Achar)', 'Traditional Nepali pickles made from radish, adding a flavorful kick to your meal.', 50.00, '69317af1da16c_1764850417.jpg', '2025-12-04 12:13:37', '2025-12-04 12:13:37'),
(29, 7, 'Ras Malai', 'Soft, spongy milk-based dumplings served in sweetened, flavored cream – a royal treat to end your meal.', 75.00, '69317b2420314_1764850468.jpg', '2025-12-04 12:14:28', '2025-12-04 12:14:28'),
(30, 7, 'Gulab Jamun', 'Warm, syrup-soaked dumplings made from milk solids, offering a sweet and indulgent finish.', 45.00, '69317b4c1483d_1764850508.jpg', '2025-12-04 12:15:08', '2025-12-04 12:15:08'),
(31, 7, 'Kheer', 'Traditional Nepali rice pudding made with milk, sugar, and cardamom, topped with pistachios and almonds.', 100.00, '69317b732946a_1764850547.jpg', '2025-12-04 12:15:47', '2025-12-04 12:15:47'),
(32, 8, 'Lassi(Sweet', 'Creamy yogurt drink, available sweet or savory, flavored with fruit or spices.', 60.00, '69317b9083050_1764850576.webp', '2025-12-04 12:16:16', '2025-12-04 12:16:16'),
(33, 8, 'Nepali Tea(Chiya)', 'Traditional spiced tea brewed with milk, cardamom, and a touch of cinnamon.', 40.00, '69317bd820acc_1764850648.jpg', '2025-12-04 12:17:28', '2025-12-04 12:17:28'),
(34, 8, 'Mango Lassi', 'A refreshing blend of mango and yogurt, perfect for cooling down after a spicy meal.', 80.00, '69317c12940cb_1764850706.jpg', '2025-12-04 12:18:26', '2025-12-04 12:18:26'),
(35, 8, 'Mixed Fruit Juices', 'Freshly squeezed juices including orange, pineapple, and pomegranate.', 90.00, '69317c34a8f53_1764850740.jpg', '2025-12-04 12:19:00', '2025-12-04 12:19:00'),
(36, 8, 'Nepali Masala Chai', 'A fragrant, spiced tea, brewed with cinnamon, cloves, cardamom, and ginger.', 50.00, '69317c565ac28_1764850774.jpg', '2025-12-04 12:19:34', '2025-12-04 12:19:34');

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
(1, 'Subash', 'subash@gmail.com', '9848484838', '$2y$10$xn8x4NnbXXG3MFdZqqet9umB..cjwi3VO.6yUwuYl6N8EIMKBCzfG', '2025-12-04 03:57:11', 1, '2025-12-01 01:17:26', '2025-12-04 04:09:29'),
(2, 'Sujan Shakya', 'sujan12@gmail.com', '9848838858', '$2y$10$18zqfCTXr3ueunLcYI/ysesFxfs2nKhfYqwldYFoCtQcbxd91L7Wu', '2025-12-04 12:30:39', 1, '2025-12-04 12:25:07', '2025-12-04 12:30:39');

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
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_phone` (`phone`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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

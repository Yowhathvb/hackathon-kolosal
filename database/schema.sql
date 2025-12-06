-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 06, 2025 at 01:53 PM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ecommerce_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`, `created_at`, `updated_at`) VALUES
(1, 2, 2, 1, '2025-12-06 10:53:18', '2025-12-06 13:35:48'),
(2, 2, 1, 1, '2025-12-06 13:35:57', '2025-12-06 13:35:57');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `parent_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `parent_id`, `created_at`) VALUES
(1, 'Fashion', 'fashion', 'Pakaian dan aksesoris fashion', NULL, '2025-12-01 04:33:02'),
(2, 'Elektronik', 'elektronik', 'Produk elektronik dan gadget', NULL, '2025-12-01 04:33:02'),
(3, 'Kesehatan & Kecantikan', 'kesehatan-kecantikan', 'Produk kesehatan dan kecantikan', NULL, '2025-12-01 04:33:02'),
(4, 'Rumah Tangga', 'rumah-tangga', 'Peralatan rumah tangga', NULL, '2025-12-01 04:33:02'),
(5, 'Olahraga', 'olahraga', 'Perlengkapan olahraga', NULL, '2025-12-01 04:33:02');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int NOT NULL,
  `shop_id` int NOT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int NOT NULL DEFAULT '0',
  `images` json NOT NULL,
  `category_id` int DEFAULT NULL,
  `tags` text COLLATE utf8mb4_unicode_ci,
  `ai_generated` tinyint(1) DEFAULT '0',
  `seo_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `seo_description` text COLLATE utf8mb4_unicode_ci,
  `seo_keywords` text COLLATE utf8mb4_unicode_ci,
  `status` enum('draft','published','archived') COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `views` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_featured` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `shop_id`, `product_name`, `product_slug`, `description`, `price`, `stock`, `images`, `category_id`, `tags`, `ai_generated`, `seo_title`, `seo_description`, `seo_keywords`, `status`, `views`, `created_at`, `updated_at`, `is_featured`) VALUES
(1, 1, 'Produk 20251205152511', 'produk-20251205152511-6932f96272482', 'Deskripsi untuk Produk 20251205152511. Produk berkualitas dengan harga terjangkau. Cocok untuk berbagai kebutuhan.', '10000.00', 100, '[\"1764948322_6932f96271bd0.jpg\"]', 1, '[\"produk\",\"online\",\"ecommerce\",\"terbaru\"]', 1, 'Produk 20251205152511', 'Beli Produk 20251205152511 dengan kualitas terbaik. Harga murah, bergaransi, dan pengiriman cepat.', '', 'published', 2, '2025-12-05 15:25:22', '2025-12-06 13:35:57', 0),
(2, 1, 'Buku Belajar Anak dengan Topi Wisuda Edukatif', 'buku-belajar-anak-dengan-topi-wisuda-edukatif-6933fd7831737', 'Buku belajar anak yang dirancang menarik dengan ilustrasi topi wisuda dan buku terbuka, simbol pendidikan dan pencapaian akademik. Produk ini ideal sebagai hadiah belajar, motivasi belajar, atau alat bantu pendidikan anak. Desain modern dan warna cerah membuatnya mudah diterima anak-anak, sambil mengajarkan pentingnya pendidikan sejak dini. Cocok untuk anak usia dini hingga sekolah dasar, membantu mengembangkan minat baca dan rasa ingin tahu. Produk edukatif yang praktis dan bermakna.', '10000.00', 100, '[\"1765014904_6933fd78301cd.jpg\"]', NULL, '[\"buku belajar\",\"pendidikan anak\",\"hadiah anak\",\"topi wisuda\",\"belajar\",\"edukatif\",\"anak usia dini\"]', 1, 'Buku Belajar Anak dengan Topi Wisuda - Edukatif & Menarik', 'Temukan buku belajar anak dengan desain topi wisuda yang menginspirasi. Produk edukatif untuk mengembangkan minat baca dan motivasi belajar anak. Ideal sebagai hadiah atau alat pendidikan. Beli sekarang!', '', 'published', 4, '2025-12-06 09:55:04', '2025-12-06 13:42:23', 0);

-- --------------------------------------------------------

--
-- Table structure for table `shops`
--

CREATE TABLE `shops` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `shop_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shop_slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shop_description` text COLLATE utf8mb4_unicode_ci,
  `shop_logo` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shop_banner` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shop_address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `shop_phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shop_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','verified','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `verification_documents` json DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shops`
--

INSERT INTO `shops` (`id`, `user_id`, `shop_name`, `shop_slug`, `shop_description`, `shop_logo`, `shop_banner`, `shop_address`, `shop_phone`, `shop_email`, `status`, `verification_documents`, `verified_at`, `created_at`) VALUES
(1, 2, 'jars', 'jars', 'jars', NULL, NULL, 'ada', '545454454545', '', 'verified', '[\"1764569290_ecommerce_db.sql\"]', '2025-12-01 06:21:35', '2025-12-01 06:08:10');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('customer','seller','admin') COLLATE utf8mb4_unicode_ci DEFAULT 'customer',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `phone`, `avatar`, `role`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@myshopee.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', NULL, NULL, 'admin', '2025-12-01 04:33:02', '2025-12-01 04:33:02'),
(2, 'jars', 'jars@gmail.com', '$2y$10$zW3U6/QjyxKQWup0Yno7re7T22ybMF5lTZCGhHfpWGgyGYsRFFQu2', 'jars', NULL, NULL, 'seller', '2025-12-01 05:21:47', '2025-12-01 06:27:15'),
(3, 'user1', 'user1@gmail.com', '$2y$10$2waJCFYXjBMyQIOkzMxi8OyTzppJDES4sSn0lGazQFUmYW46AAgBe', 'user', NULL, NULL, 'customer', '2025-12-06 13:46:27', '2025-12-06 13:46:27');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_slug` (`product_slug`),
  ADD KEY `shop_id` (`shop_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `shops`
--
ALTER TABLE `shops`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `shop_name` (`shop_name`),
  ADD UNIQUE KEY `shop_slug` (`shop_slug`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `shops`
--
ALTER TABLE `shops`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `shops`
--
ALTER TABLE `shops`
  ADD CONSTRAINT `shops_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

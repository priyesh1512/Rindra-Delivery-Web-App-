-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.30 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for rindra_delivery
CREATE DATABASE IF NOT EXISTS `rindra_delivery` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `rindra_delivery`;

-- Dumping structure for table rindra_delivery.drivers
CREATE TABLE IF NOT EXISTS `drivers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `license_number` varchar(50) NOT NULL,
  `vehicle_info` varchar(100) NOT NULL,
  `availability` enum('available','unavailable') DEFAULT 'available',
  `driver_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `drivers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table rindra_delivery.drivers: ~1 rows (approximately)
INSERT IGNORE INTO `drivers` (`id`, `user_id`, `license_number`, `vehicle_info`, `availability`, `driver_name`) VALUES
	(12, 16, '6506', 'Volvo 189', 'available', 'Fam'),
	(13, 22, '0789', 'Honda City', 'available', 'Test');

-- Dumping structure for table rindra_delivery.orders
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `client_id` int unsigned NOT NULL,
  `driver_id` int unsigned DEFAULT NULL,
  `status` enum('pending','picked_up','delivered') DEFAULT 'pending',
  `address` varchar(255) NOT NULL,
  `contact_info` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `driver_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `orders_ibfk_2` (`driver_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table rindra_delivery.orders: ~6 rows (approximately)
INSERT IGNORE INTO `orders` (`id`, `client_id`, `driver_id`, `status`, `address`, `contact_info`, `created_at`, `updated_at`, `driver_name`) VALUES
	(3, 8, 12, 'delivered', 'AIU', '789987657', '2024-09-19 20:41:26', '2024-09-23 03:27:07', NULL),
	(6, 8, 12, 'delivered', 'Solomon Hall', '987654456', '2024-09-20 13:31:30', '2024-09-23 03:27:04', NULL),
	(7, 8, 12, 'delivered', 'Solomon Hall', '7689086', '2024-09-20 16:51:11', '2024-09-23 03:27:01', NULL),
	(8, 8, 12, 'delivered', 'Solomon Hall', '987654456', '2024-09-22 17:03:55', '2024-09-24 16:50:16', NULL),
	(9, 8, 13, 'pending', '123', '123', '2024-09-24 16:50:35', '2024-09-25 20:52:31', NULL),
	(10, 19, 12, 'delivered', 'AIU', '12345', '2024-09-25 20:32:20', '2024-09-25 20:33:38', NULL);

-- Dumping structure for table rindra_delivery.order_history
CREATE TABLE IF NOT EXISTS `order_history` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned NOT NULL,
  `status` enum('pending','picked_up','delivered') NOT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `order_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table rindra_delivery.order_history: ~0 rows (approximately)

-- Dumping structure for table rindra_delivery.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','driver','client') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table rindra_delivery.users: ~9 rows (approximately)
INSERT IGNORE INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
	(8, 'Priyesh', 'priyesh@123', '$2y$10$PMyyNv2qEBHBrxDCLphzBupqV/0k2vSyzSQ559DxP43ykio8LcYhO', 'client', '2024-09-19 07:44:48'),
	(10, 'admin', 'admin@123', '$2y$10$OKM8KV0tIYk8gQWYEAYFrOrap8lgPaz9Ne1F5vYsimg3wQ9XNRhdS', 'admin', '2024-09-19 19:14:28'),
	(16, 'Fam', 'fam@driver', '$2y$10$pm24ThpzuHJl65WGi9ZPyOd1ErvEUEknk9rmT/sQ4aGYUQC9SOH1K', 'driver', '2024-09-19 21:04:41'),
	(17, 'Priyesh kumar', 'Priyesh@1', '$2y$10$1FbZ3pL3HoUG48YBh0kMVOBavhavEDrigytm9YUUEU7KAdoy1GLam', 'client', '2024-09-24 16:54:51'),
	(18, 'phillip', 'phillip23@gmail.com', '$2y$10$msfc957DAnnvSv1e/gET9eWDWn8bIPMk8BPETn76QUlqYbHHL.tdq', 'client', '2024-09-24 16:56:58'),
	(19, 'Enowell', 'test@enowell', '$2y$10$SxOj6Zs2ueJ05SPfKW89M.KC6rphwtLRCdEOhsARr2u33BevhTdGK', 'client', '2024-09-25 20:31:48'),
	(20, 'Fan', 'fan@123', '$2y$10$P1YF4qipyCYlmkXgP8H/2umNKdXHDglDyRFN9MHbNncrD66Lo5.n6', 'driver', '2024-09-25 20:41:31'),
	(21, 'Fan', 'fan@test', '$2y$10$XvhPbRIQrlCqI2EzqaSNmuN609JtyAbO/HD0.KdkqHP4OGYCO9X66', 'driver', '2024-09-25 20:44:19'),
	(22, 'test', 'test@1', '$2y$10$8gS8rlC8mCzXYf3e1bh1ze/6W15sE9rs2Cpp3fYl0VWr.nE2N1Xmi', 'driver', '2024-09-25 20:52:12');

-- Dumping structure for table rindra_delivery.vehicles
CREATE TABLE IF NOT EXISTS `vehicles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` enum('car','motorcycle') NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table rindra_delivery.vehicles: ~4 rows (approximately)
INSERT IGNORE INTO `vehicles` (`id`, `type`, `name`, `price`, `image_url`, `description`) VALUES
	(1, 'car', 'Toyota Camry', 24000.00, 'https://example.com/images/toyota_camry.jpg', 'A reliable and fuel-efficient sedan.'),
	(2, 'car', 'Honda Accord', 26000.00, 'https://example.com/images/honda_accord.jpg', 'A spacious and comfortable sedan with advanced features.'),
	(3, 'motorcycle', 'Yamaha YZF-R3', 5000.00, 'https://example.com/images/yamaha_r3.jpg', 'A lightweight sport bike with excellent handling.'),
	(4, 'motorcycle', 'Harley Davidson Sportster', 12000.00, 'https://example.com/images/harley_sportster.jpg', 'A classic cruiser motorcycle with a powerful engine.');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;

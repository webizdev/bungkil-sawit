-- Bungkil Sawit ID - Database Schema (MySQL)
-- Use this for Shared Hosting migration

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------

-- Table structure for `settings`
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_name` varchar(255) DEFAULT 'Bungkil Sawit ID',
  `hero_image` text DEFAULT NULL,
  `admin_password` varchar(255) DEFAULT 'admin',
  `email` varchar(255) DEFAULT NULL,
  `whatsapp` varchar(255) DEFAULT NULL,
  `about_text` text DEFAULT NULL,
  `tiktok` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Initial settings data
INSERT INTO `settings` (`id`, `site_name`, `admin_password`, `email`, `whatsapp`, `about_text`) VALUES
(1, 'Bungkil Sawit ID', 'admin', 'hello@bungkilsawit.id', '628123456789', 'Founded in the heart of Indonesia...');

-- --------------------------------------------------------

-- Table structure for `products`
CREATE TABLE `products` (
  `id` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `short_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image_url` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for `variants`
CREATE TABLE `variants` (
  `id` varchar(50) NOT NULL,
  `product_id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `protein` varchar(50) DEFAULT NULL,
  `fat` varchar(50) DEFAULT NULL,
  `moisture` varchar(50) DEFAULT NULL,
  `shell_content` varchar(50) DEFAULT NULL,
  `dirt` varchar(50) DEFAULT NULL,
  `coa_url` text DEFAULT NULL,
  `image_url` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for `leads`
CREATE TABLE `leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `interest` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for `stats`
CREATE TABLE `stats` (
  `stat_key` varchar(50) NOT NULL,
  `stat_value` int(11) DEFAULT 0,
  PRIMARY KEY (`stat_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Initial stats
INSERT INTO `stats` (`stat_key`, `stat_value`) VALUES ('visits', 0);

COMMIT;

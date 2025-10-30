
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `product_variants`;
DROP TABLE IF EXISTS `product_variant_options`;
DROP TABLE IF EXISTS `attribute_values`;
DROP TABLE IF EXISTS `product_attributes`;
DROP TABLE IF EXISTS `product_reviews`;
DROP TABLE IF EXISTS `product_images`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `site_settings`;
DROP TABLE IF EXISTS `pages`;
DROP TABLE IF EXISTS `banners`;
DROP TABLE IF EXISTS `hero_settings`;
DROP TABLE IF EXISTS `modal_ads`;
DROP TABLE IF EXISTS `product_downloads`;
DROP TABLE IF EXISTS `customer_downloads`;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `role_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `onesignal_player_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT 'default.jpg',
  `stock` int(11) DEFAULT 0,
  `has_variants` tinyint(1) DEFAULT 0,
  `is_downloadable` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `order_notes` text,
  `payment_proof` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `product_attributes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `attribute_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `attribute_id` int(11) NOT NULL,
  `value` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `attribute_id` (`attribute_id`),
  CONSTRAINT `attribute_values_ibfk_1` FOREIGN KEY (`attribute_id`) REFERENCES `product_attributes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `product_variant_options` (
  `variant_id` int(11) NOT NULL,
  `attribute_id` int(11) NOT NULL,
  `value_id` int(11) NOT NULL,
  PRIMARY KEY (`variant_id`,`attribute_id`),
  KEY `attribute_id` (`attribute_id`),
  KEY `value_id` (`value_id`),
  CONSTRAINT `product_variant_options_ibfk_1` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_variant_options_ibfk_2` FOREIGN KEY (`attribute_id`) REFERENCES `product_attributes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_variant_options_ibfk_3` FOREIGN KEY (`value_id`) REFERENCES `attribute_values` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `product_downloads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `download_limit` int(11) DEFAULT 5,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_downloads_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `customer_downloads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_download_id` int(11) NOT NULL,
  `download_token` varchar(255) NOT NULL,
  `downloads_remaining` int(11) DEFAULT 5,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `download_token` (`download_token`),
  KEY `order_id` (`order_id`),
  KEY `user_id` (`user_id`),
  KEY `product_download_id` (`product_download_id`),
  CONSTRAINT `customer_downloads_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_downloads_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_downloads_ibfk_3` FOREIGN KEY (`product_download_id`) REFERENCES `product_downloads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `users` (id, username, password, email, phone, role_id) VALUES
(1, 'admin', '$2y$10$E.gOfI4/gOfI4.gOfI4.gOfI4.gOfI4.gOfI4.gOfI4.gOfI4.gOfI4.g', 'admin@example.com', '1234567890', 1),
(2, 'testuser', '$2y$10$7R.gJ.9jZ/v5R.3jX.1m..d2Q.t7s.Z/p3.H.a1g.I/qO.e7w.EaG', 'testuser@example.com', '0987654321', 2);

INSERT INTO `categories` (id, `name`) VALUES
(1, 'Electronics');

INSERT INTO `products` (id, category_id, `name`, description, price, stock, has_variants) VALUES
(1, 1, 'Test Product', 'A test product with variants.', '10.00', 0, 1);

INSERT INTO `product_attributes` (id, `name`) VALUES
(1, 'Color'),
(2, 'Size');

INSERT INTO `attribute_values` (id, attribute_id, `value`) VALUES
(1, 1, 'Red'),
(2, 1, 'Blue'),
(3, 2, 'S'),
(4, 2, 'M');

INSERT INTO `product_variants` (id, product_id, sku, price, stock) VALUES
(1, 1, 'TP-RED-S', '12.00', 10),
(2, 1, 'TP-BLUE-M', '15.00', 5);

INSERT INTO `product_variant_options` (variant_id, attribute_id, value_id) VALUES
(1, 1, 1),
(1, 2, 3),
(2, 1, 2),
(2, 2, 4);

INSERT INTO `orders` (id, user_id, total_amount, status, payment_method, transaction_id) VALUES
(1, 2, '27.00', 'Pending', 'cod', 'TX12345');

INSERT INTO `order_items` (order_id, product_id, variant_id, quantity, price) VALUES
(1, 1, 1, 1, '12.00'),
(1, 1, 2, 1, '15.00');

-- ============================================================
-- VESTIA COUTURE — Database Schema
-- Compatible with MySQL 5.7+ / MariaDB 10.3+
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `vestia_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `vestia_db`;

-- ──────────────────────────────────────────────
-- ADMINS
-- ──────────────────────────────────────────────
CREATE TABLE `admins` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(150) NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default admin: admin@vestia.com / Admin@1234
INSERT INTO `admins` (`name`, `email`, `password`) VALUES
('Admin Vestia', 'admin@vestia.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- ──────────────────────────────────────────────
-- USERS
-- ──────────────────────────────────────────────
CREATE TABLE `users` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(150) NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,
  `avatar`     VARCHAR(500) DEFAULT NULL,
  `is_active`  TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ──────────────────────────────────────────────
-- AUTH TOKENS  (simple token table — no JWT lib needed)
-- ──────────────────────────────────────────────
CREATE TABLE `auth_tokens` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `token`      VARCHAR(64) NOT NULL UNIQUE,
  `expires_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ──────────────────────────────────────────────
-- CATEGORIES
-- ──────────────────────────────────────────────
CREATE TABLE `categories` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100) NOT NULL,
  `slug`       VARCHAR(120) NOT NULL UNIQUE,
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO `categories` (`name`, `slug`, `sort_order`) VALUES
('All',     'all',     0),
('Tshirts', 'tshirts', 1),
('Jeans',   'jeans',   2),
('Shoes',   'shoes',   3),
('Jackets', 'jackets', 4);

-- ──────────────────────────────────────────────
-- PRODUCTS
-- ──────────────────────────────────────────────
CREATE TABLE `products` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT UNSIGNED DEFAULT NULL,
  `name`        VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `price`       DECIMAL(10,2) NOT NULL,
  `old_price`   DECIMAL(10,2) DEFAULT NULL,
  `image_url`   VARCHAR(500) DEFAULT NULL,
  `sizes`       VARCHAR(100) DEFAULT 'S,M,L,XL,XXL',
  `is_active`   TINYINT(1) DEFAULT 1,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO `products` (`category_id`, `name`, `description`, `price`, `old_price`, `image_url`) VALUES
(2, 'Regular Fit Slogan', 'The name says it all, the right size slightly snugs the body leaving enough room for comfort in the sleeves and waist.', 1190.00, NULL, 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=400&h=480&fit=crop&auto=format'),
(2, 'Regular Fit Polo',   'The name says it all, the right size slightly snugs the body leaving enough room for comfort in the sleeves and waist.', 1100.00, 2291.00, 'https://images.unsplash.com/photo-1586790170083-2f9ceadc732d?w=400&h=480&fit=crop&auto=format'),
(2, 'Regular Fit Black',  'The name says it all, the right size slightly snugs the body leaving enough room for comfort in the sleeves and waist.', 1690.00, NULL, 'https://images.unsplash.com/photo-1503341455253-b2e723bb3dbb?w=400&h=480&fit=crop&auto=format'),
(2, 'Regular Fit V-Neck', 'The name says it all, the right size slightly snugs the body leaving enough room for comfort in the sleeves and waist.', 1290.00, NULL, 'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=400&h=480&fit=crop&auto=format'),
(2, 'Regular Fit Slogan Pink', 'The name says it all, the right size slightly snugs the body leaving enough room for comfort in the sleeves and waist.', 1190.00, NULL, 'https://images.unsplash.com/photo-1618354691373-d851c5c3a990?w=400&h=480&fit=crop&auto=format'),
(2, 'Regular Fit Striped', 'The name says it all, the right size slightly snugs the body leaving enough room for comfort in the sleeves and waist.', 1190.00, NULL, 'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?w=400&h=480&fit=crop&auto=format'),
(2, 'Regular Fit Crew',  'The name says it all, the right size slightly snugs the body leaving enough room for comfort in the sleeves and waist.', 990.00,  NULL, 'https://images.unsplash.com/photo-1576566588028-4147f3842f27?w=400&h=480&fit=crop&auto=format'),
(2, 'Regular Fit Striped Blue', 'The name says it all, the right size slightly snugs the body leaving enough room for comfort in the sleeves and waist.', 1390.00, NULL, 'https://images.unsplash.com/photo-1571945153237-4929e783af4a?w=400&h=480&fit=crop&auto=format');

-- ──────────────────────────────────────────────
-- SAVED ITEMS (Wishlist)
-- ──────────────────────────────────────────────
CREATE TABLE `saved_items` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_saved` (`user_id`, `product_id`),
  FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ──────────────────────────────────────────────
-- CART ITEMS
-- ──────────────────────────────────────────────
CREATE TABLE `cart_items` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity`   INT UNSIGNED DEFAULT 1,
  `size`       VARCHAR(10) DEFAULT 'M',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_cart` (`user_id`, `product_id`, `size`),
  FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ──────────────────────────────────────────────
-- ORDERS
-- ──────────────────────────────────────────────
CREATE TABLE `orders` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`      INT UNSIGNED NOT NULL,
  `status`       ENUM('Packing','Picked','In Transit','Completed','Cancelled') DEFAULT 'Packing',
  `subtotal`     DECIMAL(10,2) NOT NULL,
  `shipping_fee` DECIMAL(10,2) NOT NULL DEFAULT 80.00,
  `vat`          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total`        DECIMAL(10,2) NOT NULL,
  `notes`        TEXT DEFAULT NULL,
  `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ──────────────────────────────────────────────
-- ORDER ITEMS
-- ──────────────────────────────────────────────
CREATE TABLE `order_items` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id`   INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED DEFAULT NULL,
  `name`       VARCHAR(200) NOT NULL,
  `image_url`  VARCHAR(500) DEFAULT NULL,
  `price`      DECIMAL(10,2) NOT NULL,
  `quantity`   INT UNSIGNED NOT NULL DEFAULT 1,
  `size`       VARCHAR(10) DEFAULT 'M',
  FOREIGN KEY (`order_id`)   REFERENCES `orders`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ──────────────────────────────────────────────
-- REVIEWS
-- ──────────────────────────────────────────────
CREATE TABLE `reviews` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `order_id`   INT UNSIGNED DEFAULT NULL,
  `rating`     TINYINT UNSIGNED NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `text`       TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_review` (`user_id`, `product_id`),
  FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`order_id`)   REFERENCES `orders`(`id`)   ON DELETE SET NULL
) ENGINE=InnoDB;

-- Sample reviews
INSERT INTO `reviews` (`user_id`, `product_id`, `order_id`, `rating`, `text`) VALUES
(1, 1, NULL, 5, 'The item is very good, I like it very much.'),
(1, 2, NULL, 4, 'The seller is very fast in sending packet, arrived in just 1 day!'),
(1, 3, NULL, 4, 'Really good quality! I highly recommend it!');

SET FOREIGN_KEY_CHECKS = 1;

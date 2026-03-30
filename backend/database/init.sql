-- ============================================================
-- E-Commerce Database Schema
-- ============================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(150) NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,
  `role`       ENUM('admin','user') NOT NULL DEFAULT 'user',
  `phone`      VARCHAR(20)  DEFAULT NULL,
  `address`    TEXT         DEFAULT NULL,
  `avatar_url` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin account (password: admin123)
INSERT IGNORE INTO `users` (`name`, `email`, `password`, `role`)
VALUES ('Admin', 'admin@shop.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Default test user (password: user123)
INSERT IGNORE INTO `users` (`name`, `email`, `password`, `role`)
VALUES ('Nguyen Van A', 'user@shop.com', '$2y$12$JFKJBMsrqBkRbw0fgEMH7evzWK5bDXSUFfm4A8PBFY4H9cI7L4OAm', 'user');

-- ------------------------------------------------------------
-- 2. categories
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100) NOT NULL,
  `slug`        VARCHAR(120) NOT NULL UNIQUE,
  `description` TEXT         DEFAULT NULL,
  `created_at`  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `categories` (`name`, `slug`) VALUES
  ('Điện thoại', 'dien-thoai'),
  ('Tai nghe', 'tai-nghe'),
  ('Laptop', 'laptop'),
  ('Phụ kiện', 'phu-kien');

-- ------------------------------------------------------------
-- 3. products
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `name`        VARCHAR(200) NOT NULL,
  `slug`        VARCHAR(220) NOT NULL UNIQUE,
  `description` TEXT         DEFAULT NULL,
  `price`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `stock`       INT UNSIGNED    NOT NULL DEFAULT 0,
  `image_url`   VARCHAR(500)    DEFAULT NULL,
  `is_active`   TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_category` (`category_id`),
  INDEX `idx_active`   (`is_active`),
  CONSTRAINT `fk_product_category`
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `products` (`category_id`,`name`,`slug`,`description`,`price`,`stock`,`image_url`) VALUES
  (1, 'iPhone 15 Pro Max 256GB', 'iphone-15-pro-max-256gb', 'Chip A17 Pro, camera 48MP', 32990000, 50, 'https://picsum.photos/seed/iphone/400/400'),
  (2, 'AirPods Pro Gen 2',       'airpods-pro-gen-2',       'ANC, MagSafe Case',         5990000, 100, 'https://picsum.photos/seed/airpods/400/400'),
  (3, 'MacBook Air M3 15"',      'macbook-air-m3-15',       'Apple M3, 8GB RAM, 256GB',  34990000,  20, 'https://picsum.photos/seed/macbook/400/400');

-- ------------------------------------------------------------
-- 4. vouchers
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `vouchers` (
  `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`                  VARCHAR(50)  NOT NULL UNIQUE,
  `discount_amount`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `min_order_value`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `applicable_product_id` INT UNSIGNED DEFAULT NULL,
  `expires_at`            DATETIME     DEFAULT NULL,
  `is_active`             TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`            TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_code`     (`code`),
  INDEX `idx_active`   (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `vouchers` (`code`, `discount_amount`, `min_order_value`, `expires_at`) VALUES
  ('HELLO2026', 500000,   10000000, '2026-12-31 23:59:59'),
  ('FREESHIP',   30000,       0,    '2026-12-31 23:59:59'),
  ('TECHFAN',  1000000,  20000000, '2026-12-31 23:59:59');

UPDATE `vouchers` SET `applicable_product_id` = 1 WHERE `code` = 'TECHFAN';

-- ------------------------------------------------------------
-- 5. orders
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
  `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`              INT UNSIGNED NOT NULL,
  `voucher_id`           INT UNSIGNED DEFAULT NULL,
  `shipping_address`     TEXT         NOT NULL,
  `shipping_region`      ENUM('hanoi','hcm','other') NOT NULL DEFAULT 'other',
  `shipping_cost`        BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `subtotal`             BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `discount`             BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `total_amount`         BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `status`               ENUM('pending','confirmed','shipping','completed','disputed','cancelled')
                         NOT NULL DEFAULT 'pending',
  `payment_receipt_url`  VARCHAR(500)  DEFAULT NULL,
  `note`                 TEXT          DEFAULT NULL,
  `created_at`           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user`   (`user_id`),
  INDEX `idx_status` (`status`),
  CONSTRAINT `fk_order_user`
    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE RESTRICT,
  CONSTRAINT `fk_order_voucher`
    FOREIGN KEY (`voucher_id`) REFERENCES `vouchers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. order_items
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_items` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`      INT UNSIGNED NOT NULL,
  `product_id`    INT UNSIGNED NOT NULL,
  `product_name`  VARCHAR(200) NOT NULL,
  `product_price` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `quantity`      INT UNSIGNED    NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  INDEX `idx_order`   (`order_id`),
  INDEX `idx_product` (`product_id`),
  CONSTRAINT `fk_item_order`
    FOREIGN KEY (`order_id`)   REFERENCES `orders`(`id`)   ON DELETE CASCADE,
  CONSTRAINT `fk_item_product`
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 7. wishlists
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wishlists` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_product` (`user_id`, `product_id`),
  CONSTRAINT `fk_wish_user`
    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
  CONSTRAINT `fk_wish_product`
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 8. dispute_chats
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `dispute_chats` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`       INT UNSIGNED NOT NULL,
  `user_id`        INT UNSIGNED NOT NULL,
  `sender_role`    ENUM('admin','user') NOT NULL,
  `message`        TEXT         NOT NULL,
  `attachment_url` VARCHAR(500) DEFAULT NULL,
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_order` (`order_id`),
  CONSTRAINT `fk_chat_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_chat_user`
    FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

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
  `status`     ENUM('active','locked') NOT NULL DEFAULT 'active',
  `avatar_url` VARCHAR(500) DEFAULT NULL,
  `reset_token` VARCHAR(255) DEFAULT NULL,
  `reset_token_expires_at` DATETIME DEFAULT NULL,
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

-- ------------------------------------------------------------
-- 9. product_reviews
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `product_reviews` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `user_id`    INT UNSIGNED NOT NULL,
  `order_id`   INT UNSIGNED NOT NULL,
  `rating`     TINYINT UNSIGNED NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `comment`    TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_review_order` (`user_id`, `product_id`, `order_id`),
  INDEX `idx_product_rating` (`product_id`, `rating`),
  CONSTRAINT `fk_review_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
  CONSTRAINT `fk_review_order`   FOREIGN KEY (`order_id`)   REFERENCES `orders`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 10. notifications
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `title`      VARCHAR(255) NOT NULL,
  `message`    TEXT NOT NULL,
  `type`       VARCHAR(50) DEFAULT 'info',
  `is_read`    TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_read` (`user_id`, `is_read`),
  CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- MOCK DATA INJECTION (50+ records)
-- ============================================================

-- 1. Additional Users
INSERT IGNORE INTO `users` (`name`, `email`, `password`, `role`, `phone`, `address`) VALUES
('Trần Thị B', 'khachhang1@shop.com', '$2y$12$JFKJBMsrqBkRbw0fgEMH7evzWK5bDXSUFfm4A8PBFY4H9cI7L4OAm', 'user', '0912345678', 'TP.HCM'),
('Lê Văn C', 'khachhang2@shop.com', '$2y$12$JFKJBMsrqBkRbw0fgEMH7evzWK5bDXSUFfm4A8PBFY4H9cI7L4OAm', 'user', '0988888888', 'Đà Nẵng'),
('Phạm Thị D', 'khachhang3@shop.com', '$2y$12$JFKJBMsrqBkRbw0fgEMH7evzWK5bDXSUFfm4A8PBFY4H9cI7L4OAm', 'user', '0977777777', 'Hải Phòng'),
('Hoàng Văn E', 'khachhang4@shop.com', '$2y$12$JFKJBMsrqBkRbw0fgEMH7evzWK5bDXSUFfm4A8PBFY4H9cI7L4OAm', 'user', '0966666666', 'Cần Thơ'),
('Vũ Thị F', 'khachhang5@shop.com', '$2y$12$JFKJBMsrqBkRbw0fgEMH7evzWK5bDXSUFfm4A8PBFY4H9cI7L4OAm', 'user', '0955555555', 'Bình Dương');

-- 2. Additional Categories
INSERT IGNORE INTO `categories` (`name`, `slug`) VALUES
('Đồng hồ thông minh', 'dong-ho-thong-minh'),
('Màn hình', 'man-hinh'),
('Bàn phím - Chuột', 'ban-phim-chuot');

-- 3. Additional Products
INSERT IGNORE INTO `products` (`category_id`,`name`,`slug`,`description`,`price`,`stock`,`image_url`) VALUES
(1, 'Samsung Galaxy S24 Ultra 256GB', 'samsung-galaxy-s24-ultra', 'Snapdragon 8 Gen 3, Camera 200MP', 33990000, 30, 'https://picsum.photos/seed/s24u/400/400'),
(1, 'iPhone 14 Pro Max 256GB', 'iphone-14-pro-max-256gb', 'Chip A16 Bionic, Dynamic Island', 26990000, 40, 'https://picsum.photos/seed/ip14pm/400/400'),
(1, 'Xiaomi 14 Pro 5G', 'xiaomi-14-pro', 'Leica Summilux, HyperOS', 22990000, 20, 'https://picsum.photos/seed/mi14pro/400/400'),
(1, 'OPPO Find X7 Ultra', 'oppo-find-x7-ultra', 'Hasselblad Camera, sạc siêu nhanh', 24990000, 15, 'https://picsum.photos/seed/oppox7/400/400'),
(1, 'Vivo X100 Pro', 'vivo-x100-pro', 'Zeiss Optics, Dimensity 9300', 23990000, 10, 'https://picsum.photos/seed/vivox100/400/400'),
(1, 'Google Pixel 8 Pro', 'pixel-8-pro', 'Camera AI đỉnh cao', 21990000, 25, 'https://picsum.photos/seed/pixel8/400/400'),
(1, 'Samsung Galaxy Z Fold5', 'z-fold-5', 'Màn hình gập cao cấp', 40990000, 10, 'https://picsum.photos/seed/fold5/400/400'),
(1, 'Samsung Galaxy Z Flip5', 'z-flip-5', 'Gập vỏ sò thời trang', 25990000, 15, 'https://picsum.photos/seed/flip5/400/400'),
(1, 'iPhone 15 128GB', 'iphone-15-128gb', 'Màu sắc trẻ trung, Dynamic Island', 19990000, 60, 'https://picsum.photos/seed/ip15/400/400'),
(1, 'iPhone 13 128GB', 'iphone-13-128gb', 'Hiệu năng vẫn rất tốt', 13990000, 80, 'https://picsum.photos/seed/ip13/400/400'),
(2, 'Sony WH-1000XM5', 'sony-wh-1000xm5', 'Chống ồn chủ động tốt nhất', 7990000, 60, 'https://picsum.photos/seed/sonyxm5/400/400'),
(2, 'AirPods 3', 'airpods-3', 'Âm thanh không gian, pin trâu', 4290000, 100, 'https://picsum.photos/seed/airpods3/400/400'),
(2, 'Marshall Minor III', 'marshall-minor-iii', 'Thiết kế cổ điển, âm bass mạnh', 3590000, 40, 'https://picsum.photos/seed/marshall/400/400'),
(2, 'Samsung Galaxy Buds 2 Pro', 'galaxy-buds-2-pro', 'Âm thanh Hi-Fi 24bit', 3990000, 50, 'https://picsum.photos/seed/buds2pro/400/400'),
(2, 'Sennheiser Momentum True Wireless 3', 'sennheiser-mtw3', 'Chất âm Audiophile', 6490000, 30, 'https://picsum.photos/seed/senmtw3/400/400'),
(2, 'Jabra Elite 85t', 'jabra-elite-85t', 'Chống ồn linh hoạt', 4990000, 20, 'https://picsum.photos/seed/jabra/400/400'),
(2, 'Beats Fit Pro', 'beats-fit-pro', 'Tai nghe thể thao chuyên dụng', 4590000, 35, 'https://picsum.photos/seed/beats/400/400'),
(3, 'Dell XPS 13 Plus', 'dell-xps-13-plus', 'Thiết kế tràn viền, Intel Gen 13', 42990000, 10, 'https://picsum.photos/seed/xps13/400/400'),
(3, 'ASUS ROG Strix G15', 'asus-rog-strix-g15', 'Laptop Gaming cấu hình khủng', 35990000, 25, 'https://picsum.photos/seed/rog/400/400'),
(3, 'Lenovo Legion 5 Pro', 'lenovo-legion-5-pro', 'Màn hình 165Hz, RTX 4070', 38990000, 20, 'https://picsum.photos/seed/legion/400/400'),
(3, 'HP Spectre x360', 'hp-spectre-x360', 'Thiết kế gập 360 độ', 41990000, 12, 'https://picsum.photos/seed/spectre/400/400'),
(3, 'Acer Zenbook 14 OLED', 'acer-zenbook-14', 'Màn hình OLED rực rỡ', 25990000, 30, 'https://picsum.photos/seed/zenbook/400/400'),
(3, 'LG Gram 16', 'lg-gram-16', 'Siêu nhẹ, pin cực trâu', 31990000, 15, 'https://picsum.photos/seed/lggram/400/400'),
(3, 'MSI Predator Helios 300', 'msi-predator', 'Tản nhiệt siêu cấp', 37990000, 18, 'https://picsum.photos/seed/msi/400/400'),
(4, 'Củ sạc Anker 65W', 'cu-sac-anker-65w', 'Sạc nhanh 2 cổng Type-C', 890000, 200, 'https://picsum.photos/seed/anker/400/400'),
(4, 'Cáp sạc Baseus Type-C', 'cap-sac-baseus', 'Dây bọc dù, siêu bền', 250000, 300, 'https://picsum.photos/seed/baseus/400/400'),
(4, 'Ốp lưng iPhone 15 Pro Max UAG', 'op-lung-uag-ip15pm', 'Chống sốc quân đội', 1200000, 80, 'https://picsum.photos/seed/uag/400/400'),
(4, 'Sạc dự phòng Mophie 10000mAh', 'mophie-10000mah', 'Sạc không dây MagSafe', 1490000, 60, 'https://picsum.photos/seed/mophie/400/400'),
(4, 'Chuột Logitech MX Master 3S', 'logitech-mx-master-3s', 'Chuột công thái học cao cấp', 2490000, 40, 'https://picsum.photos/seed/logitech/400/400'),
(5, 'Apple Watch Series 9', 'apple-watch-s9', 'Chip S9 SiP, Double Tap', 10490000, 50, 'https://picsum.photos/seed/aw9/400/400'),
(5, 'Samsung Galaxy Watch 6', 'galaxy-watch-6', 'Đo huyết áp, nhịp tim', 6990000, 45, 'https://picsum.photos/seed/gw6/400/400'),
(5, 'Garmin Fenix 7', 'garmin-fenix-7', 'Đồng hồ thể thao chuyên nghiệp', 18990000, 20, 'https://picsum.photos/seed/garmin/400/400'),
(6, 'Dell UltraSharp 27 4K', 'dell-u2723qe', 'Màn hình 4K IPS Black', 14990000, 15, 'https://picsum.photos/seed/dellu27/400/400'),
(6, 'LG 27GP850-B', 'lg-27gp850', 'Màn hình Gaming Nano IPS 165Hz', 9990000, 25, 'https://picsum.photos/seed/lg27/400/400'),
(7, 'Bàn phím cơ Keychron K2', 'keychron-k2', 'Hỗ trợ Mac/Windows', 2190000, 70, 'https://picsum.photos/seed/keychron/400/400'),
(7, 'Chuột Razer DeathAdder V3 Pro', 'razer-deathadder-v3', 'Chuột Gaming siêu nhẹ', 3490000, 35, 'https://picsum.photos/seed/razer/400/400');

-- 4. Mock Orders
INSERT IGNORE INTO `orders` (`user_id`, `shipping_address`, `shipping_region`, `shipping_cost`, `subtotal`, `discount`, `total_amount`, `status`) VALUES
(3, '123 Đường A, Quận 1, TP.HCM', 'hcm', 30000, 32990000, 0, 33020000, 'completed'),
(4, '456 Đường B, Cầu Giấy, Hà Nội', 'hanoi', 20000, 5990000, 0, 6010000, 'shipping'),
(5, '789 Đường C, Hải Châu, Đà Nẵng', 'other', 40000, 34990000, 500000, 34530000, 'pending'),
(6, '321 Đường D, Ninh Kiều, Cần Thơ', 'other', 40000, 26990000, 0, 27030000, 'confirmed'),
(7, '654 Đường E, Ngô Quyền, Hải Phòng', 'other', 40000, 7990000, 30000, 8000000, 'completed'),
(7, '987 Đường F, Dĩ An, Bình Dương', 'other', 40000, 10490000, 0, 10530000, 'cancelled');

-- 5. Mock Order Items
INSERT IGNORE INTO `order_items` (`order_id`, `product_id`, `product_name`, `product_price`, `quantity`) VALUES
(1, 1, 'iPhone 15 Pro Max 256GB', 32990000, 1),
(2, 2, 'AirPods Pro Gen 2', 5990000, 1),
(3, 3, 'MacBook Air M3 15"', 34990000, 1),
(4, 5, 'iPhone 14 Pro Max 256GB', 26990000, 1),
(5, 14, 'Sony WH-1000XM5', 7990000, 1),
(6, 34, 'Apple Watch Series 9', 10490000, 1);

-- 6. Mock Reviews
INSERT IGNORE INTO `product_reviews` (`product_id`, `user_id`, `order_id`, `rating`, `comment`) VALUES
(1, 3, 1, 5, 'Sản phẩm tuyệt vời, thiết kế sang trọng và cầm rất nhẹ tay.'),
(14, 7, 5, 4, 'Chống ồn cực kỳ đỉnh cao, nhưng đeo lâu hơi nóng tai một chút.');

-- ============================================================
-- MORE MOCK DATA INJECTION (50+ records)
-- ============================================================

-- 7. More Users
INSERT IGNORE INTO `users` (`name`, `email`, `password`, `role`, `phone`, `address`) VALUES
('Bùi Văn M', 'khachhang11@shop.com', '$2y$12$JFKJBMsrqBkRbw0fgEMH7evzWK5bDXSUFfm4A8PBFY4H9cI7L4OAm', 'user', '0812345678', 'Hà Nội'),
('Đỗ Thị N', 'khachhang12@shop.com', '$2y$12$JFKJBMsrqBkRbw0fgEMH7evzWK5bDXSUFfm4A8PBFY4H9cI7L4OAm', 'user', '0823456789', 'Hồ Chí Minh'),
('Hồ Văn O', 'khachhang13@shop.com', '$2y$12$JFKJBMsrqBkRbw0fgEMH7evzWK5bDXSUFfm4A8PBFY4H9cI7L4OAm', 'user', '0834567890', 'Đà Nẵng'),
('Dương Thị P', 'khachhang14@shop.com', '$2y$12$JFKJBMsrqBkRbw0fgEMH7evzWK5bDXSUFfm4A8PBFY4H9cI7L4OAm', 'user', '0845678901', 'Hải Phòng'),
('Ngô Văn Q', 'khachhang15@shop.com', '$2y$12$JFKJBMsrqBkRbw0fgEMH7evzWK5bDXSUFfm4A8PBFY4H9cI7L4OAm', 'user', '0856789012', 'Cần Thơ'),
('Đào Thị R', 'khachhang16@shop.com', '$2y$12$JFKJBMsrqBkRbw0fgEMH7evzWK5bDXSUFfm4A8PBFY4H9cI7L4OAm', 'user', '0867890123', 'Bình Dương'),
('Đoàn Văn S', 'khachhang17@shop.com', '$2y$12$JFKJBMsrqBkRbw0fgEMH7evzWK5bDXSUFfm4A8PBFY4H9cI7L4OAm', 'user', '0878901234', 'Đồng Nai'),
('Vương Thị T', 'khachhang18@shop.com', '$2y$12$JFKJBMsrqBkRbw0fgEMH7evzWK5bDXSUFfm4A8PBFY4H9cI7L4OAm', 'user', '0889012345', 'Bắc Ninh'),
('Trịnh Văn U', 'khachhang19@shop.com', '$2y$12$JFKJBMsrqBkRbw0fgEMH7evzWK5bDXSUFfm4A8PBFY4H9cI7L4OAm', 'user', '0890123456', 'Thanh Hóa'),
('Lâm Thị V', 'khachhang20@shop.com', '$2y$12$JFKJBMsrqBkRbw0fgEMH7evzWK5bDXSUFfm4A8PBFY4H9cI7L4OAm', 'user', '0801234567', 'Nghệ An');

-- 8. More Products
INSERT IGNORE INTO `products` (`category_id`,`name`,`slug`,`description`,`price`,`stock`,`image_url`) VALUES
(1, 'Oppo Reno 10 Pro', 'oppo-reno-10-pro', 'Camera chân dung xuất sắc', 15990000, 30, 'https://picsum.photos/seed/opporeno10/400/400'),
(1, 'Xiaomi Redmi Note 13', 'xiaomi-redmi-note-13', 'Màn hình AMOLED 120Hz', 5990000, 100, 'https://picsum.photos/seed/redmi13/400/400'),
(1, 'Realme 11 Pro', 'realme-11-pro', 'Thiết kế mặt lưng da sang trọng', 8990000, 45, 'https://picsum.photos/seed/realme11/400/400'),
(1, 'Samsung Galaxy A54', 'samsung-galaxy-a54', 'Chống nước IP67, pin 5000mAh', 8490000, 60, 'https://picsum.photos/seed/a54/400/400'),
(1, 'Vivo V29 5G', 'vivo-v29-5g', 'Aura Light Portrait', 12990000, 25, 'https://picsum.photos/seed/vivov29/400/400'),
(2, 'JBL Flip 6', 'jbl-flip-6', 'Loa Bluetooth di động chống nước', 2490000, 80, 'https://picsum.photos/seed/jblflip6/400/400'),
(2, 'Harman Kardon Onyx Studio 8', 'harman-kardon-onyx-8', 'Âm thanh vòm chất lượng cao', 5990000, 30, 'https://picsum.photos/seed/onyx8/400/400'),
(2, 'Bose Charge 5', 'bose-charge-5', 'Loa công suất lớn, pin 20h', 3490000, 40, 'https://picsum.photos/seed/bosecharge5/400/400'),
(2, 'Sony SRS-XB13', 'sony-srs-xb13', 'Extra Bass nhỏ gọn', 1290000, 120, 'https://picsum.photos/seed/sonyx13/400/400'),
(2, 'Marshall Emberton II', 'marshall-emberton-ii', 'Thiết kế classic, âm thanh 360', 4290000, 35, 'https://picsum.photos/seed/emberton2/400/400'),
(3, 'MacBook Pro 14 M3 Pro', 'macbook-pro-14-m3-pro', 'Sức mạnh vượt trội cho Creator', 49990000, 15, 'https://picsum.photos/seed/macpro14m3/400/400'),
(3, 'Asus Vivobook 15 OLED', 'asus-vivobook-15-oled', 'Màn hình OLED màu sắc rực rỡ', 18990000, 40, 'https://picsum.photos/seed/vivobook15/400/400'),
(3, 'Lenovo ThinkPad X1 Carbon Gen 11', 'thinkpad-x1-carbon-gen11', 'Laptop doanh nhân mỏng nhẹ', 45990000, 10, 'https://picsum.photos/seed/thinkpadx1/400/400'),
(3, 'Dell Inspiron 15', 'dell-inspiron-15', 'Laptop sinh viên văn phòng', 14990000, 60, 'https://picsum.photos/seed/inspiron15/400/400'),
(3, 'HP Pavilion 14', 'hp-pavilion-14', 'Thiết kế thanh lịch, hiệu năng ổn định', 15990000, 50, 'https://picsum.photos/seed/pavilion14/400/400'),
(4, 'Bàn phím cơ Logitech MX Mechanical', 'logitech-mx-mechanical', 'Bàn phím cơ không dây cho Mac/Win', 3990000, 25, 'https://picsum.photos/seed/mxmechanical/400/400'),
(4, 'Chuột Apple Magic Mouse 3', 'apple-magic-mouse-3', 'Thiết kế tối giản, hỗ trợ đa điểm', 2490000, 40, 'https://picsum.photos/seed/magicmouse3/400/400'),
(4, 'Hub chuyển đổi UGREEN 6 in 1', 'hub-ugreen-6-in-1', 'Cổng Type-C đa năng', 690000, 150, 'https://picsum.photos/seed/ugreenhub/400/400'),
(4, 'Tai nghe HyperX Cloud II', 'hyperx-cloud-ii', 'Tai nghe Gaming giả lập 7.1', 2190000, 60, 'https://picsum.photos/seed/hyperxcloud2/400/400'),
(4, 'Balo Laptop Targus 15.6"', 'balo-targus-15', 'Chống sốc, chống nước nhẹ', 890000, 100, 'https://picsum.photos/seed/targus/400/400');

-- 9. More Orders
INSERT IGNORE INTO `orders` (`user_id`, `shipping_address`, `shipping_region`, `shipping_cost`, `subtotal`, `discount`, `total_amount`, `status`) VALUES
(8, '111 Đường X, Hà Nội', 'hanoi', 20000, 15990000, 0, 16010000, 'completed'),
(9, '222 Đường Y, TP.HCM', 'hcm', 30000, 5990000, 0, 6020000, 'completed'),
(10, '333 Đường Z, Đà Nẵng', 'other', 40000, 8990000, 0, 9030000, 'pending'),
(11, '444 Đường W, Hải Phòng', 'other', 40000, 8490000, 0, 8530000, 'confirmed'),
(12, '555 Đường V, Cần Thơ', 'other', 40000, 12990000, 0, 13030000, 'shipping'),
(13, '666 Đường U, Bình Dương', 'other', 40000, 2490000, 0, 2530000, 'completed'),
(14, '777 Đường T, Đồng Nai', 'other', 40000, 5990000, 0, 6030000, 'completed'),
(15, '888 Đường S, Bắc Ninh', 'other', 40000, 3490000, 0, 3530000, 'cancelled'),
(16, '999 Đường R, Thanh Hóa', 'other', 40000, 1290000, 0, 1330000, 'completed'),
(17, '000 Đường Q, Nghệ An', 'other', 40000, 4290000, 0, 4330000, 'pending');

-- 10. More Order Items
INSERT IGNORE INTO `order_items` (`order_id`, `product_id`, `product_name`, `product_price`, `quantity`) VALUES
(7, 38, 'Oppo Reno 10 Pro', 15990000, 1),
(8, 39, 'Xiaomi Redmi Note 13', 5990000, 1),
(9, 40, 'Realme 11 Pro', 8990000, 1),
(10, 41, 'Samsung Galaxy A54', 8490000, 1),
(11, 42, 'Vivo V29 5G', 12990000, 1),
(12, 43, 'JBL Flip 6', 2490000, 1),
(13, 44, 'Harman Kardon Onyx Studio 8', 5990000, 1),
(14, 45, 'Bose Charge 5', 3490000, 1),
(15, 46, 'Sony SRS-XB13', 1290000, 1),
(16, 47, 'Marshall Emberton II', 4290000, 1);

-- 11. More Reviews
INSERT IGNORE INTO `product_reviews` (`product_id`, `user_id`, `order_id`, `rating`, `comment`) VALUES
(38, 8, 7, 5, 'Điện thoại chụp ảnh rất đẹp, sạc nhanh.'),
(39, 9, 8, 4, 'Màn hình mượt, pin trâu, chơi game tốt trong tầm giá.'),
(43, 13, 12, 5, 'Loa nghe nhạc cực kỳ hay, bass chắc.'),
(44, 14, 13, 5, 'Thiết kế đẹp, âm thanh vòm nghe phòng khách rất hợp.'),
(46, 16, 15, 4, 'Nhỏ gọn tiện mang đi du lịch, âm lượng khá to.'),
(2, 4, 2, 5, 'Tai nghe chống ồn tuyệt vời, đáng tiền mua.'),
(3, 5, 3, 5, 'Máy mỏng nhẹ, pin trâu, code rất mượt không bị nóng.'),
(5, 6, 4, 4, 'Giao hàng nhanh, máy đẹp, giá hợp lý ở thời điểm này.');

SET FOREIGN_KEY_CHECKS = 1;

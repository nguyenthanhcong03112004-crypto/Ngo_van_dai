-- ============================================================
-- ElectroHub E-Commerce Database Schema + Seed Data
-- Charset: utf8mb4 | Engine: InnoDB
-- ============================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `ecommerce_db`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `ecommerce_db`;

-- ============================================================
-- TABLE DEFINITIONS
-- ============================================================

-- 1. users
CREATE TABLE IF NOT EXISTS `users` (
  `id`                     INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `name`                   VARCHAR(100)  NOT NULL,
  `email`                  VARCHAR(150)  NOT NULL UNIQUE,
  `password`               VARCHAR(255)  NOT NULL,
  `role`                   ENUM('admin','user') NOT NULL DEFAULT 'user',
  `phone`                  VARCHAR(20)   DEFAULT NULL,
  `address`                TEXT          DEFAULT NULL,
  `status`                 ENUM('active','locked') NOT NULL DEFAULT 'active',
  `avatar_url`             VARCHAR(500)  DEFAULT NULL,
  `reset_token`            VARCHAR(255)  DEFAULT NULL,
  `reset_token_expires_at` DATETIME      DEFAULT NULL,
  `created_at`             TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`             TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. categories
CREATE TABLE IF NOT EXISTS `categories` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100) NOT NULL,
  `slug`        VARCHAR(120) NOT NULL UNIQUE,
  `description` TEXT         DEFAULT NULL,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. products
CREATE TABLE IF NOT EXISTS `products` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED    NOT NULL,
  `name`        VARCHAR(200)    NOT NULL,
  `slug`        VARCHAR(220)    NOT NULL UNIQUE,
  `description` TEXT            DEFAULT NULL,
  `price`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `stock`       INT UNSIGNED    NOT NULL DEFAULT 0,
  `image_url`   VARCHAR(500)    DEFAULT NULL,
  `is_active`   TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_category` (`category_id`),
  INDEX `idx_active`   (`is_active`),
  CONSTRAINT `fk_product_category`
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. vouchers
CREATE TABLE IF NOT EXISTS `vouchers` (
  `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `code`                  VARCHAR(50)     NOT NULL UNIQUE,
  `discount_amount`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `min_order_value`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `applicable_product_id` INT UNSIGNED    DEFAULT NULL,
  `expires_at`            DATETIME        DEFAULT NULL,
  `is_active`             TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`            TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_code`   (`code`),
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. orders
CREATE TABLE IF NOT EXISTS `orders` (
  `id`                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`             INT UNSIGNED    NOT NULL,
  `voucher_id`          INT UNSIGNED    DEFAULT NULL,
  `shipping_address`    TEXT            NOT NULL,
  `shipping_region`     ENUM('hanoi','hcm','other') NOT NULL DEFAULT 'other',
  `shipping_cost`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `subtotal`            BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `discount`            BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `total_amount`        BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `status`              ENUM('pending','confirmed','shipping','completed','disputed','cancelled')
                        NOT NULL DEFAULT 'pending',
  `payment_receipt_url` VARCHAR(500)    DEFAULT NULL,
  `note`                TEXT            DEFAULT NULL,
  `created_at`          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user`   (`user_id`),
  INDEX `idx_status` (`status`),
  CONSTRAINT `fk_order_user`
    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE RESTRICT,
  CONSTRAINT `fk_order_voucher`
    FOREIGN KEY (`voucher_id`) REFERENCES `vouchers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. order_items
CREATE TABLE IF NOT EXISTS `order_items` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `order_id`      INT UNSIGNED    NOT NULL,
  `product_id`    INT UNSIGNED    NOT NULL,
  `product_name`  VARCHAR(200)    NOT NULL,
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

-- 7. wishlists
CREATE TABLE IF NOT EXISTS `wishlists` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_product` (`user_id`, `product_id`),
  CONSTRAINT `fk_wish_user`
    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
  CONSTRAINT `fk_wish_product`
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. dispute_chats
CREATE TABLE IF NOT EXISTS `dispute_chats` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`       INT UNSIGNED NOT NULL,
  `user_id`        INT UNSIGNED NOT NULL,
  `sender_role`    ENUM('admin','user') NOT NULL,
  `message`        TEXT         NOT NULL,
  `attachment_url` VARCHAR(500) DEFAULT NULL,
  `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_order` (`order_id`),
  CONSTRAINT `fk_chat_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_chat_user`
    FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. product_reviews
CREATE TABLE IF NOT EXISTS `product_reviews` (
  `id`         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED     NOT NULL,
  `user_id`    INT UNSIGNED     NOT NULL,
  `order_id`   INT UNSIGNED     NOT NULL,
  `rating`     TINYINT UNSIGNED NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `comment`    TEXT             NOT NULL,
  `created_at` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_review_order` (`user_id`, `product_id`, `order_id`),
  INDEX `idx_product_rating` (`product_id`, `rating`),
  CONSTRAINT `fk_review_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
  CONSTRAINT `fk_review_order`   FOREIGN KEY (`order_id`)   REFERENCES `orders`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `title`      VARCHAR(255) NOT NULL,
  `message`    TEXT         NOT NULL,
  `type`       VARCHAR(50)  NOT NULL DEFAULT 'info',
  `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_read` (`user_id`, `is_read`),
  CONSTRAINT `fk_notification_user`
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA
-- ============================================================

-- ── 1. Users ─────────────────────────────────────────────────
-- password for all accounts: user123  → hash below
-- password for admin: admin123        → hash below
INSERT IGNORE INTO `users` (`name`, `email`, `password`, `role`, `phone`, `address`) VALUES
('Admin',        'admin@shop.com',         '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '0900000000', 'Hà Nội'),
('Nguyễn Văn A', 'user@shop.com',          '$2y$12$JFKJBMsrqBkRbw0fgEMH7evzWK5bDXSUFfm4A8PBFY4H9cI7L4OAm', 'user',  '0901111111', 'TP.HCM'),
('Trần Thị B',   'khachhang1@shop.com',    '$2y$12$JFKJBMsrqBkRbw0fgEMH7evzWK5bDXSUFfm4A8PBFY4H9cI7L4OAm', 'user',  '0912345678', 'Hà Nội'),
('Lê Văn C',     'khachhang2@shop.com',    '$2y$12$JFKJBMsrqBkRbw0fgEMH7evzWK5bDXSUFfm4A8PBFY4H9cI7L4OAm', 'user',  '0923456789', 'TP.HCM'),
('Phạm Thị D',   'khachhang3@shop.com',    '$2y$12$JFKJBMsrqBkRbw0fgEMH7evzWK5bDXSUFfm4A8PBFY4H9cI7L4OAm', 'user',  '0934567890', 'Đà Nẵng'),
('Hoàng Văn E',  'khachhang4@shop.com',    '$2y$12$JFKJBMsrqBkRbw0fgEMH7evzWK5bDXSUFfm4A8PBFY4H9cI7L4OAm', 'user',  '0945678901', 'Hải Phòng'),
('Vũ Thị F',     'khachhang5@shop.com',    '$2y$12$JFKJBMsrqBkRbw0fgEMH7evzWK5bDXSUFfm4A8PBFY4H9cI7L4OAm', 'user',  '0956789012', 'Cần Thơ'),
('Bùi Văn G',    'khachhang6@shop.com',    '$2y$12$JFKJBMsrqBkRbw0fgEMH7evzWK5bDXSUFfm4A8PBFY4H9cI7L4OAm', 'user',  '0967890123', 'Bình Dương'),
('Đỗ Thị H',     'khachhang7@shop.com',    '$2y$12$JFKJBMsrqBkRbw0fgEMH7evzWK5bDXSUFfm4A8PBFY4H9cI7L4OAm', 'user',  '0978901234', 'Đồng Nai'),
('Hồ Văn I',     'khachhang8@shop.com',    '$2y$12$JFKJBMsrqBkRbw0fgEMH7evzWK5bDXSUFfm4A8PBFY4H9cI7L4OAm', 'user',  '0989012345', 'Bắc Ninh'),
('Dương Thị K',  'khachhang9@shop.com',    '$2y$12$JFKJBMsrqBkRbw0fgEMH7evzWK5bDXSUFfm4A8PBFY4H9cI7L4OAm', 'user',  '0990123456', 'Thanh Hóa'),
('Ngô Văn L',    'khachhang10@shop.com',   '$2y$12$JFKJBMsrqBkRbw0fgEMH7evzWK5bDXSUFfm4A8PBFY4H9cI7L4OAm', 'user',  '0901234567', 'Nghệ An');

-- ── 2. Categories ─────────────────────────────────────────────
INSERT IGNORE INTO `categories` (`name`, `slug`, `description`) VALUES
('Điện thoại',          'dien-thoai',      'Điện thoại thông minh các hãng'),
('Tai nghe & Loa',      'tai-nghe-loa',    'Tai nghe và loa Bluetooth'),
('Laptop',              'laptop',          'Máy tính xách tay'),
('Phụ kiện',            'phu-kien',        'Phụ kiện điện tử đa dạng'),
('Đồng hồ thông minh',  'dong-ho-thong-minh', 'Smartwatch các hãng'),
('Màn hình',            'man-hinh',        'Màn hình máy tính'),
('Bàn phím & Chuột',    'ban-phim-chuot',  'Bàn phím cơ và chuột gaming');

-- ── 3. Products ───────────────────────────────────────────────
INSERT IGNORE INTO `products` (`category_id`,`name`,`slug`,`description`,`price`,`stock`,`image_url`) VALUES
-- Điện thoại (cat 1)
(1,'iPhone 15 Pro Max 256GB',     'iphone-15-pro-max-256gb',   'Chip A17 Pro, camera 48MP, khung titan',                        32990000, 50, 'https://picsum.photos/seed/iphone15pm/400/400'),
(1,'iPhone 15 128GB',             'iphone-15-128gb',           'Dynamic Island, sạc USB-C',                                     19990000, 60, 'https://picsum.photos/seed/ip15/400/400'),
(1,'iPhone 14 Pro Max 256GB',     'iphone-14-pro-max-256gb',   'Chip A16 Bionic, Dynamic Island',                               26990000, 40, 'https://picsum.photos/seed/ip14pm/400/400'),
(1,'iPhone 13 128GB',             'iphone-13-128gb',           'Hiệu năng ổn định, giá tốt',                                   13990000, 80, 'https://picsum.photos/seed/ip13/400/400'),
(1,'Samsung Galaxy S24 Ultra',    'samsung-galaxy-s24-ultra',  'Snapdragon 8 Gen 3, Camera 200MP, bút S-Pen',                  33990000, 30, 'https://picsum.photos/seed/s24u/400/400'),
(1,'Samsung Galaxy Z Fold5',      'z-fold-5',                  'Màn hình gập cao cấp, Snapdragon 8 Gen 2',                     40990000, 10, 'https://picsum.photos/seed/fold5/400/400'),
(1,'Xiaomi 14 Pro 5G',            'xiaomi-14-pro',             'Leica Summilux camera, HyperOS',                               22990000, 20, 'https://picsum.photos/seed/mi14pro/400/400'),
(1,'OPPO Find X7 Ultra',          'oppo-find-x7-ultra',        'Hasselblad Camera, sạc siêu nhanh 100W',                       24990000, 15, 'https://picsum.photos/seed/oppox7/400/400'),
(1,'Google Pixel 8 Pro',          'pixel-8-pro',               'AI Camera hàng đầu, Android gốc',                              21990000, 25, 'https://picsum.photos/seed/pixel8/400/400'),
(1,'Samsung Galaxy A54 5G',       'samsung-galaxy-a54',        'Chống nước IP67, pin 5000mAh, màn AMOLED',                      8490000, 60, 'https://picsum.photos/seed/a54/400/400'),
-- Tai nghe & Loa (cat 2)
(2,'AirPods Pro Gen 2',           'airpods-pro-gen-2',         'ANC thế hệ 2, MagSafe Case, chip H2',                          5990000,100, 'https://picsum.photos/seed/airpods/400/400'),
(2,'AirPods 3',                   'airpods-3',                 'Âm thanh không gian, pin 6h',                                  4290000,100, 'https://picsum.photos/seed/airpods3/400/400'),
(2,'Sony WH-1000XM5',             'sony-wh-1000xm5',           'Chống ồn chủ động tốt nhất, pin 30h',                          7990000, 60, 'https://picsum.photos/seed/sonyxm5/400/400'),
(2,'Samsung Galaxy Buds 2 Pro',   'galaxy-buds-2-pro',         'Âm thanh Hi-Fi 24bit, ANC thông minh',                         3990000, 50, 'https://picsum.photos/seed/buds2pro/400/400'),
(2,'JBL Flip 6',                  'jbl-flip-6',                'Loa Bluetooth di động chống nước IP67',                         2490000, 80, 'https://picsum.photos/seed/jblflip6/400/400'),
(2,'Sony SRS-XB13',               'sony-srs-xb13',             'Extra Bass nhỏ gọn, cắm cổng Type-C',                          1290000,120, 'https://picsum.photos/seed/sonyx13/400/400'),
(2,'Marshall Emberton II',        'marshall-emberton-ii',      'Thiết kế classic, âm thanh 360 độ, IP67',                      4290000, 35, 'https://picsum.photos/seed/emberton2/400/400'),
(2,'Bose QuietComfort 45',        'bose-qc45',                 'Over-ear ANC huyền thoại, pin 24h',                             6990000, 30, 'https://picsum.photos/seed/boseqc45/400/400'),
-- Laptop (cat 3)
(3,'MacBook Air M3 15"',          'macbook-air-m3-15',         'Apple M3, 8GB RAM, 256GB SSD, 18h pin',                       34990000, 20, 'https://picsum.photos/seed/macbook/400/400'),
(3,'MacBook Pro 14 M3 Pro',       'macbook-pro-14-m3-pro',     'M3 Pro 11-core, 18GB, Liquid XDR display',                    49990000, 15, 'https://picsum.photos/seed/macpro14m3/400/400'),
(3,'Dell XPS 13 Plus',            'dell-xps-13-plus',          'Intel Gen 13, thiết kế tràn viền, OLED',                      42990000, 10, 'https://picsum.photos/seed/xps13/400/400'),
(3,'ASUS ROG Strix G15',          'asus-rog-strix-g15',        'RTX 4070, 165Hz, Ryzen 9, cấu hình Gaming đỉnh',              35990000, 25, 'https://picsum.photos/seed/rog/400/400'),
(3,'Lenovo Legion 5 Pro',         'lenovo-legion-5-pro',       'RTX 4070, màn 165Hz, tản nhiệt vượt trội',                    38990000, 20, 'https://picsum.photos/seed/legion/400/400'),
(3,'Dell Inspiron 15',            'dell-inspiron-15',          'Laptop sinh viên – văn phòng, Core i5',                       14990000, 60, 'https://picsum.photos/seed/inspiron15/400/400'),
(3,'Acer Zenbook 14 OLED',        'acer-zenbook-14',           'Màn OLED 2.8K 90Hz, Core Ultra, siêu mỏng',                  25990000, 30, 'https://picsum.photos/seed/zenbook/400/400'),
-- Phụ kiện (cat 4)
(4,'Củ sạc Anker 65W GaN',        'cu-sac-anker-65w',          '2 cổng Type-C + USB-A, GaN, nhỏ gọn',                           890000,200, 'https://picsum.photos/seed/anker/400/400'),
(4,'Cáp Baseus Type-C 100W',      'cap-baseus-type-c-100w',    'Dây bọc dù, sạc nhanh 100W, 2m',                                290000,300, 'https://picsum.photos/seed/baseus/400/400'),
(4,'Chuột Logitech MX Master 3S', 'logitech-mx-master-3s',     'Công thái học cao cấp, 8K DPI, silent click',                  2490000, 40, 'https://picsum.photos/seed/logitech/400/400'),
(4,'Hub UGREEN 6 in 1 Type-C',    'hub-ugreen-6-in-1',         'HDMI 4K, USB 3.0 x3, SD/TF, 100W PD',                           690000,150, 'https://picsum.photos/seed/ugreenhub/400/400'),
(4,'Sạc dự phòng Mophie 10000mAh','mophie-10000mah',           'MagSafe, sạc không dây 15W, siêu mỏng',                        1490000, 60, 'https://picsum.photos/seed/mophie/400/400'),
-- Đồng hồ thông minh (cat 5)
(5,'Apple Watch Series 9 45mm',   'apple-watch-s9-45mm',       'Chip S9 SiP, Double Tap, luôn hiển thị',                      10490000, 50, 'https://picsum.photos/seed/aw9/400/400'),
(5,'Samsung Galaxy Watch 6 Classic','galaxy-watch-6-classic',  'Viền xoay, đo huyết áp, ECG',                                  8990000, 45, 'https://picsum.photos/seed/gw6c/400/400'),
(5,'Garmin Forerunner 265',        'garmin-forerunner-265',     'Màn AMOLED, GPS chính xác, theo dõi sức khoẻ toàn diện',      10990000, 20, 'https://picsum.photos/seed/garmin265/400/400'),
-- Màn hình (cat 6)
(6,'Dell UltraSharp 27 4K U2723QE','dell-u2723qe',             '4K IPS Black, USB-C 90W, màu Delta-E<2',                      14990000, 15, 'https://picsum.photos/seed/dellu27/400/400'),
(6,'LG 27GP850-B 165Hz',           'lg-27gp850',               'Nano IPS 165Hz, G-Sync Compatible, 1ms',                       9990000, 25, 'https://picsum.photos/seed/lg27/400/400'),
-- Bàn phím & Chuột (cat 7)
(7,'Bàn phím cơ Keychron K2 v2',  'keychron-k2-v2',            'Hot-swap, RGB, Bluetooth, hỗ trợ Mac/Win',                     2190000, 70, 'https://picsum.photos/seed/keychron/400/400'),
(7,'Chuột Razer DeathAdder V3',    'razer-deathadder-v3',       'Sensor Focus 30K, 59g siêu nhẹ, Speedflex',                   3490000, 35, 'https://picsum.photos/seed/razer/400/400');

-- ── 4. Vouchers ───────────────────────────────────────────────
INSERT IGNORE INTO `vouchers` (`code`, `discount_amount`, `min_order_value`, `expires_at`) VALUES
('HELLO2026',  500000,  10000000, '2026-12-31 23:59:59'),
('FREESHIP',    30000,         0, '2026-12-31 23:59:59'),
('TECHFAN',   1000000,  20000000, '2026-12-31 23:59:59'),
('SUMMER10',   200000,   5000000, '2026-09-30 23:59:59'),
('NEWUSER',    100000,   1000000, '2026-12-31 23:59:59');

UPDATE `vouchers` SET `applicable_product_id` = 1 WHERE `code` = 'TECHFAN';

-- ── 5. Orders ─────────────────────────────────────────────────
INSERT IGNORE INTO `orders` (`user_id`,`shipping_address`,`shipping_region`,`shipping_cost`,`subtotal`,`discount`,`total_amount`,`status`) VALUES
(2,  '12 Hoàng Hoa Thám, Ba Đình, Hà Nội',         'hanoi', 20000, 32990000,       0, 33010000, 'completed'),
(3,  '45 Nguyễn Thị Nhung, Thủ Đức, TP.HCM',       'hcm',   30000,  5990000,       0,  6020000, 'completed'),
(4,  '78 Lê Lợi, Hải Châu, Đà Nẵng',               'other', 40000, 34990000,  500000, 34530000, 'completed'),
(5,  '99 Trần Phú, Hồng Bàng, Hải Phòng',          'other', 40000, 26990000,       0, 27030000, 'shipping'),
(6,  '200 Nguyễn Huệ, Ninh Kiều, Cần Thơ',         'other', 40000,  7990000,   30000,  8000000, 'completed'),
(7,  '15 Đường 3/2, Thủ Dầu Một, Bình Dương',      'other', 40000, 10490000,       0, 10530000, 'cancelled'),
(8,  '33 Bà Triệu, Hoàn Kiếm, Hà Nội',             'hanoi', 20000, 13990000,       0, 14010000, 'completed'),
(9,  '88 Võ Văn Tần, Quận 3, TP.HCM',              'hcm',   30000,  2490000,       0,  2520000, 'completed'),
(10, '11 Lý Thường Kiệt, Đà Nẵng',                 'other', 40000, 19990000,       0, 20030000, 'confirmed'),
(11, '22 Hai Bà Trưng, Hải Phòng',                 'other', 40000,  4290000,       0,  4330000, 'pending'),
(3,  '45 Nguyễn Thị Nhung, Thủ Đức, TP.HCM',       'hcm',   30000,  8490000,  100000,  8420000, 'completed'),
(4,  '78 Lê Lợi, Hải Châu, Đà Nẵng',               'other', 40000,  3990000,       0,  4030000, 'disputed');

-- ── 6. Order Items ────────────────────────────────────────────
INSERT IGNORE INTO `order_items` (`order_id`,`product_id`,`product_name`,`product_price`,`quantity`) VALUES
(1,  1,  'iPhone 15 Pro Max 256GB',    32990000, 1),
(2,  11, 'AirPods Pro Gen 2',           5990000, 1),
(3,  19, 'MacBook Air M3 15"',         34990000, 1),
(4,  3,  'iPhone 14 Pro Max 256GB',    26990000, 1),
(5,  13, 'Sony WH-1000XM5',            7990000, 1),
(6,  31, 'Apple Watch Series 9 45mm', 10490000, 1),
(7,  4,  'iPhone 13 128GB',            13990000, 1),
(8,  15, 'JBL Flip 6',                 2490000, 1),
(9,  2,  'iPhone 15 128GB',            19990000, 1),
(10, 12, 'AirPods 3',                  4290000, 1),
(11, 10, 'Samsung Galaxy A54 5G',      8490000, 1),
(12, 23, 'Lenovo Legion 5 Pro',       38990000, 1),
-- Order 1 thêm sản phẩm phụ kiện
(1,  27, 'Cáp Baseus Type-C 100W',       290000, 2),
-- Order 3 thêm sản phẩm
(3,  26, 'Củ sạc Anker 65W GaN',         890000, 1);

-- ── 7. Wishlists ──────────────────────────────────────────────
INSERT IGNORE INTO `wishlists` (`user_id`,`product_id`) VALUES
(2, 5), (2, 13), (2, 19),
(3, 1), (3, 20), (3, 31),
(4, 11),(4, 14), (4, 25),
(5, 6), (5, 33), (5, 34),
(6, 3), (6, 22), (6, 36),
(7, 28),(7, 37), (7, 38),
(8, 7), (8, 15), (8, 32);

-- ── 8. Product Reviews ────────────────────────────────────────
INSERT IGNORE INTO `product_reviews` (`product_id`,`user_id`,`order_id`,`rating`,`comment`) VALUES
(1,  2, 1, 5, 'Sản phẩm tuyệt vời! Khung titan cứng cáp, camera 48MP siêu nét, rất hài lòng.'),
(11, 3, 2, 5, 'AirPods Pro 2 chống ồn cực đỉnh, âm thanh trong trẻo, đeo thoải mái cả ngày.'),
(19, 4, 3, 4, 'MacBook mỏng nhẹ, pin trâu cả ngày, nhưng cần dongle khá bất tiện.'),
(3,  5, 4, 4, 'Máy đẹp, chụp ảnh tốt, nhưng giá hơi cao so với thị trường hiện tại.'),
(13, 6, 5, 5, 'Chống ồn tốt nhất tôi từng dùng, pin bền, đео đi làm rất hợp.'),
(4,  8, 7, 4, 'Hiệu năng vẫn rất tốt dù là máy cũ, giá hợp lý, pin dùng cả ngày ok.'),
(15, 9, 8, 5, 'Loa nhỏ gọn âm thanh rất ổn, bass chắc, chống nước tốt đi biển thích lắm.'),
(10,11,11, 4, 'Màn AMOLED đẹp, pin trâu, camera ổn trong tầm giá. Giao hàng nhanh.');

-- ── 9. Notifications ──────────────────────────────────────────
INSERT IGNORE INTO `notifications` (`user_id`,`title`,`message`,`type`,`is_read`) VALUES
(2, 'Đơn hàng đã hoàn tất', 'Đơn hàng #1 của bạn đã được giao thành công. Cảm ơn bạn đã mua sắm!', 'success', 1),
(2, 'Khuyến mãi đặc biệt',  'Giảm đến 30% cho laptop cao cấp. Chỉ còn hôm nay!',                   'info',    0),
(3, 'Đơn hàng đã hoàn tất', 'Đơn hàng #2 (AirPods Pro Gen 2) đã giao thành công.',                 'success', 1),
(3, 'Đánh giá sản phẩm',    'Bạn có muốn đánh giá AirPods Pro Gen 2 vừa mua không?',               'info',    0),
(4, 'Đơn hàng đang giao',   'Đơn hàng #4 đang được vận chuyển đến địa chỉ của bạn.',               'info',    0),
(4, 'Voucher mới',          'Bạn nhận được voucher SUMMER10 trị giá 200.000đ.',                     'success', 0),
(5, 'Đơn hàng hoàn tất',    'Đơn hàng #5 đã giao thành công. Mời bạn đánh giá sản phẩm.',         'success', 1),
(6, 'Đơn hàng đã huỷ',      'Đơn hàng #6 đã được huỷ theo yêu cầu của bạn.',                       'warning', 1),
(7, 'Đơn hàng hoàn tất',    'iPhone 13 của bạn đã được giao. Cảm ơn bạn!',                         'success', 1),
(9, 'Đơn hàng hoàn tất',    'JBL Flip 6 đã được giao thành công.',                                  'success', 1),
(1, 'Đơn hàng mới',         'Có đơn hàng mới #12 cần xử lý — trạng thái: Tranh chấp.',             'warning', 0),
(1, 'Hệ thống',             'Backup database định kỳ đã hoàn tất lúc 02:00 AM.',                   'info',    1);

-- ── 10. Dispute Chats ──────────────────────────────────────────
INSERT IGNORE INTO `dispute_chats` (`order_id`,`user_id`,`sender_role`,`message`) VALUES
(12, 4,  'user',  'Tôi đặt Lenovo Legion 5 Pro nhưng nhận được hàng bị lỗi màn hình, có vạch ngang. Yêu cầu đổi máy mới.'),
(12, 1,  'admin', 'Chào bạn, chúng tôi rất tiếc về sự bất tiện này. Bạn có thể gửi ảnh/video lỗi để chúng tôi kiểm tra không?'),
(12, 4,  'user',  'Tôi đã gửi ảnh qua email rồi ạ. Máy bị vạch từ lúc mới bật lên, chưa dùng gì cả.'),
(12, 1,  'admin', 'Cảm ơn bạn. Chúng tôi đã nhận được ảnh và sẽ xử lý đổi trả trong 3–5 ngày làm việc. Xin lỗi vì sự cố này.');

-- ============================================================
SET FOREIGN_KEY_CHECKS = 1;
-- ============================================================
-- Tài khoản mẫu:
--   Admin   : admin@shop.com   / admin123
--   User    : user@shop.com    / user123
--   Khách   : khachhang1..10@shop.com / user123
-- Vouchers : HELLO2026 | FREESHIP | TECHFAN | SUMMER10 | NEWUSER
-- ============================================================

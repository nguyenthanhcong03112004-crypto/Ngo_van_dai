-- ============================================================
-- ElectroHub E-Commerce Database
-- Auto-initialized on first Docker container start
-- Passwords: Admin@123 (admin) | User@123 (users) | Sales@123 (sales)
-- Generated: 2026-04-06
-- ============================================================

CREATE DATABASE IF NOT EXISTS `ecommerce_db`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `ecommerce_db`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- TABLES
-- ============================================================

CREATE TABLE IF NOT EXISTS `users` (
  `id`                     INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `name`                   VARCHAR(100)   NOT NULL,
  `email`                  VARCHAR(150)   NOT NULL UNIQUE,
  `password`               VARCHAR(255)   NOT NULL,
  `role`                   ENUM('admin','user','sales','staff') NOT NULL DEFAULT 'user',
  `phone`                  VARCHAR(20)    DEFAULT NULL,
  `address`                TEXT           DEFAULT NULL,
  `status`                 ENUM('active','locked') NOT NULL DEFAULT 'active',
  `avatar_url`             VARCHAR(500)   DEFAULT NULL,
  `bank_account`           VARCHAR(50)    DEFAULT NULL,
  `bank_name`              VARCHAR(100)   DEFAULT NULL,
  `bank_bin`               VARCHAR(20)    DEFAULT NULL,
  `reset_token`            VARCHAR(255)   DEFAULT NULL,
  `reset_token_expires_at` DATETIME       DEFAULT NULL,
  `created_at`             TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`             TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `categories` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100) NOT NULL,
  `slug`        VARCHAR(120) NOT NULL UNIQUE,
  `description` TEXT         DEFAULT NULL,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_category` (`category_id`),
  INDEX `idx_active`   (`is_active`),
  CONSTRAINT `fk_product_category`
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vouchers` (
  `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`                  VARCHAR(50)  NOT NULL UNIQUE,
  `discount_amount`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `min_order_value`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `applicable_product_id` INT UNSIGNED DEFAULT NULL,
  `expires_at`            DATETIME     DEFAULT NULL,
  `is_active`             TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_code`   (`code`),
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `orders` (
  `id`                     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`                INT UNSIGNED NOT NULL,
  `voucher_id`             INT UNSIGNED DEFAULT NULL,
  `shipping_address`       TEXT         NOT NULL,
  `shipping_region`        ENUM('hanoi','hcm','other') NOT NULL DEFAULT 'other',
  `shipping_cost`          BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `subtotal`               BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `discount`               BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `total_amount`           BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `status`                 ENUM('pending','confirmed','shipping','completed','disputed','cancelled') NOT NULL DEFAULT 'pending',
  `payment_receipt_url`    VARCHAR(500) DEFAULT NULL,
  `dispute_resolution_url` VARCHAR(500) DEFAULT NULL,
  `note`                   TEXT         DEFAULT NULL,
  `created_at`             TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`             TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user`   (`user_id`),
  INDEX `idx_status` (`status`),
  CONSTRAINT `fk_order_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE RESTRICT,
  CONSTRAINT `fk_order_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  CONSTRAINT `fk_item_order`   FOREIGN KEY (`order_id`)   REFERENCES `orders`(`id`)   ON DELETE CASCADE,
  CONSTRAINT `fk_item_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wishlists` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_product` (`user_id`, `product_id`),
  CONSTRAINT `fk_wish_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
  CONSTRAINT `fk_wish_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  CONSTRAINT `fk_chat_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_chat_user`  FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS `notifications` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `title`      VARCHAR(255) NOT NULL,
  `message`    TEXT         NOT NULL,
  `type`       VARCHAR(50)  DEFAULT 'info',
  `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_read` (`user_id`, `is_read`),
  CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cabinets` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `rows`       TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `cols`       TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cabinet_slots` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cabinet_id` INT UNSIGNED NOT NULL,
  `row`        TINYINT UNSIGNED NOT NULL,
  `col`        TINYINT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `status`     ENUM('available','sold','reserved') NOT NULL DEFAULT 'available',
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_cabinet_slot` (`cabinet_id`, `row`, `col`),
  CONSTRAINT `fk_slot_cabinet` FOREIGN KEY (`cabinet_id`) REFERENCES `cabinets`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_slot_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `offline_orders` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id`     INT UNSIGNED NOT NULL,
  `slot_id`        INT UNSIGNED DEFAULT NULL,
  `user_id`        INT UNSIGNED DEFAULT NULL COMMENT 'NULL neu khach vang lai',
  `customer_phone` VARCHAR(20)  NOT NULL,
  `customer_name`  VARCHAR(200) DEFAULT 'Khach vang lai',
  `sale_price`     BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `staff_id`       INT UNSIGNED DEFAULT NULL COMMENT 'ID nhan vien ban hang',
  `confirmed_by`   INT UNSIGNED DEFAULT NULL,
  `confirmed_at`   DATETIME     DEFAULT NULL,
  `status`         ENUM('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
  `payment_method` ENUM('cash','transfer') NOT NULL DEFAULT 'transfer',
  `note`           TEXT         DEFAULT NULL,
  `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_product` (`product_id`),
  INDEX `idx_user`    (`user_id`),
  INDEX `idx_status`  (`status`),
  CONSTRAINT `fk_offline_product`      FOREIGN KEY (`product_id`)  REFERENCES `products`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_offline_confirmed_by` FOREIGN KEY (`confirmed_by`) REFERENCES `users`(`id`)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA
-- ============================================================
-- Passwords:
--   Admin@123 → $2y$10$jdskyMF3HX3/lgIg/kBYH.b6KbgfJXjfyJ4N2WUziNZBbCosiboEy
--   User@123  → $2y$10$Nq31mtIqWIOr0OgiUuDxYe58j3f263WCjbo8sbczJAuY1a4QOBAL6
--   Sales@123 → $2y$10$QK5oMHqBLYm3gTNRzB4mfuC3N8VDqWoL1TZ8Y6nZpH2TmRkbNa8Tq
-- ============================================================

-- 1. USERS
INSERT IGNORE INTO `users` (`id`,`name`,`email`,`password`,`role`,`phone`,`address`,`status`) VALUES
-- Admin
(1,  'Quản Trị Viên',    'admin@shop.com',           '$2y$10$jdskyMF3HX3/lgIg/kBYH.b6KbgfJXjfyJ4N2WUziNZBbCosiboEy', 'admin',  '0900000001', '1 Lý Thái Tổ, Hoàn Kiếm, Hà Nội', 'active'),
-- Sales Staff
(2,  'Nguyễn Văn Sales',   'sales@shop.com',   '$2y$10$mUBvOf7rpl6E8dzgH6.duu.EH8p66nLxWZIWcPAQcD6oaY67aT5nS', 'sales',  '0900000002', 'Showroom Hà Nội - 123 Trần Duy Hưng, Cầu Giấy', 'active'),
(3,  'Trần Thị Bán Hàng',  'sales2@shop.com',  '$2y$10$mUBvOf7rpl6E8dzgH6.duu.EH8p66nLxWZIWcPAQcD6oaY67aT5nS', 'sales',  '0900000003', 'Showroom HCM - 456 Nguyễn Huệ, Quận 1',         'active'),
(4,  'Lê Văn POS',         'sales3@shop.com',  '$2y$10$mUBvOf7rpl6E8dzgH6.duu.EH8p66nLxWZIWcPAQcD6oaY67aT5nS', 'sales',  '0900000004', 'Showroom Đà Nẵng - 78 Bạch Đằng, Hải Châu',    'active'),
-- Regular Users
(5,  'Nguyễn Văn An',    'user@shop.com',            '$2y$10$Nq31mtIqWIOr0OgiUuDxYe58j3f263WCjbo8sbczJAuY1a4QOBAL6', 'user',   '0912345678', '12 Hoàng Diệu, Đống Đa, Hà Nội',    'active'),
(6,  'Trần Thị Bình',    'binh.tran@gmail.com',      '$2y$10$Nq31mtIqWIOr0OgiUuDxYe58j3f263WCjbo8sbczJAuY1a4QOBAL6', 'user',   '0923456789', '34 Lê Lợi, Quận 1, TP.HCM',         'active'),
(7,  'Lê Văn Cường',     'cuong.le@gmail.com',       '$2y$10$Nq31mtIqWIOr0OgiUuDxYe58j3f263WCjbo8sbczJAuY1a4QOBAL6', 'user',   '0934567890', '56 Hải Phòng, Đà Nẵng',             'active'),
(8,  'Phạm Thị Dung',    'dung.pham@gmail.com',      '$2y$10$Nq31mtIqWIOr0OgiUuDxYe58j3f263WCjbo8sbczJAuY1a4QOBAL6', 'user',   '0945678901', '78 Trần Phú, Nha Trang',            'active'),
(9,  'Hoàng Văn Em',     'em.hoang@gmail.com',       '$2y$10$Nq31mtIqWIOr0OgiUuDxYe58j3f263WCjbo8sbczJAuY1a4QOBAL6', 'user',   '0956789012', '90 Nguyễn Du, Cần Thơ',             'active'),
(10, 'Vũ Thị Phương',    'phuong.vu@gmail.com',      '$2y$10$Nq31mtIqWIOr0OgiUuDxYe58j3f263WCjbo8sbczJAuY1a4QOBAL6', 'user',   '0967890123', '11 Bà Triệu, Hải Phòng',            'active'),
(11, 'Đỗ Văn Giang',     'giang.do@gmail.com',       '$2y$10$Nq31mtIqWIOr0OgiUuDxYe58j3f263WCjbo8sbczJAuY1a4QOBAL6', 'user',   '0978901234', '22 Đinh Tiên Hoàng, Huế',           'active'),
(12, 'Ngô Thị Hương',    'huong.ngo@gmail.com',      '$2y$10$Nq31mtIqWIOr0OgiUuDxYe58j3f263WCjbo8sbczJAuY1a4QOBAL6', 'user',   '0989012345', '33 Lê Duẩn, Đắk Lắk',              'active'),
(13, 'Bùi Văn Khoa',     'khoa.bui@gmail.com',       '$2y$10$Nq31mtIqWIOr0OgiUuDxYe58j3f263WCjbo8sbczJAuY1a4QOBAL6', 'user',   '0990123456', '44 Trần Hưng Đạo, Bình Dương',      'active'),
(14, 'Đinh Thị Lan',     'lan.dinh@gmail.com',       '$2y$10$Nq31mtIqWIOr0OgiUuDxYe58j3f263WCjbo8sbczJAuY1a4QOBAL6', 'user',   '0901234560', '55 Nguyễn Trãi, Đồng Nai',          'active'),
(15, 'Trương Văn Minh',  'minh.truong@gmail.com',    '$2y$10$Nq31mtIqWIOr0OgiUuDxYe58j3f263WCjbo8sbczJAuY1a4QOBAL6', 'user',   '0912345670', '66 Lý Thường Kiệt, Hải Dương',      'active'),
(16, 'Lương Thị Nga',    'nga.luong@gmail.com',      '$2y$10$Nq31mtIqWIOr0OgiUuDxYe58j3f263WCjbo8sbczJAuY1a4QOBAL6', 'user',   '0923456780', '77 Phan Bội Châu, Nghệ An',         'active'),
(17, 'Cao Văn Oanh',     'oanh.cao@gmail.com',       '$2y$10$Nq31mtIqWIOr0OgiUuDxYe58j3f263WCjbo8sbczJAuY1a4QOBAL6', 'user',   '0934567891', '88 Hùng Vương, Thanh Hóa',          'active');

-- 2. CATEGORIES
INSERT IGNORE INTO `categories` (`id`,`name`,`slug`,`description`) VALUES
(1, 'Điện thoại',         'dien-thoai',        'Smartphone các hãng Apple, Samsung, Xiaomi, OPPO...'),
(2, 'Tai nghe & Loa',     'tai-nghe-loa',      'Tai nghe True Wireless, Over-ear, Loa Bluetooth'),
(3, 'Laptop',             'laptop',            'Laptop văn phòng, gaming, đồ họa thương hiệu lớn'),
(4, 'Phụ kiện',           'phu-kien',          'Sạc, cáp, ốp lưng, hub, chuột, bàn phím...'),
(5, 'Đồng hồ thông minh', 'dong-ho-thong-minh','Smartwatch theo dõi sức khỏe và thông báo'),
(6, 'Màn hình',           'man-hinh',          'Màn hình văn phòng 4K, gaming 144Hz+'),
(7, 'Bàn phím & Chuột',   'ban-phim-chuot',    'Bàn phím cơ, không dây, chuột gaming, ergonomic');

-- 3. PRODUCTS (40 sản phẩm)
INSERT IGNORE INTO `products` (`id`,`category_id`,`name`,`slug`,`description`,`price`,`stock`,`image_url`) VALUES
-- Điện thoại (1)
(1,  1, 'iPhone 15 Pro Max 256GB',     'iphone-15-pro-max-256gb',   'Chip A17 Pro mạnh mẽ, camera 48MP ProRes, titan siêu nhẹ',                          32990000, 50,  'https://picsum.photos/seed/iphone15pm/400/400'),
(2,  1, 'Samsung Galaxy S24 Ultra',    'samsung-galaxy-s24-ultra',  'Snapdragon 8 Gen 3, Camera 200MP, bút S Pen tích hợp',                              33990000, 30,  'https://picsum.photos/seed/s24ultra/400/400'),
(3,  1, 'iPhone 15 128GB',             'iphone-15-128gb',           'Dynamic Island thế hệ 2, cáp USB-C, màu sắc tươi sáng',                              19990000, 60,  'https://picsum.photos/seed/iphone15/400/400'),
(4,  1, 'iPhone 14 Pro Max 256GB',     'iphone-14-pro-max-256gb',   'Chip A16 Bionic, Dynamic Island đầu tiên, camera 48MP',                              26990000, 40,  'https://picsum.photos/seed/ip14pm/400/400'),
(5,  1, 'Xiaomi 14 Pro 5G',            'xiaomi-14-pro',             'Camera Leica Summilux f/1.42, chip Snapdragon 8 Gen 3',                              22990000, 20,  'https://picsum.photos/seed/mi14pro/400/400'),
(6,  1, 'OPPO Find X7 Ultra',          'oppo-find-x7-ultra',        'Camera Hasselblad, sạc nhanh 100W, màn hình LTPO4 120Hz',                            24990000, 15,  'https://picsum.photos/seed/oppofindx7/400/400'),
(7,  1, 'Samsung Galaxy Z Fold5',      'samsung-z-fold5',           'Màn hình gập 7.6 inch, Snapdragon 8 Gen 2, thiết kế siêu mỏng',                     40990000, 10,  'https://picsum.photos/seed/zfold5/400/400'),
(8,  1, 'Google Pixel 8 Pro',          'google-pixel-8-pro',        'Chip Tensor G3, AI camera chuyên nghiệp, 7 năm cập nhật',                            21990000, 25,  'https://picsum.photos/seed/pixel8pro/400/400'),
(9,  1, 'iPhone 13 128GB',             'iphone-13-128gb',           'Hiệu năng bền bỉ, camera kép, màu sắc đa dạng, giá tốt',                             13990000, 80,  'https://picsum.photos/seed/iphone13/400/400'),
(10, 1, 'Samsung Galaxy A54 5G',       'samsung-galaxy-a54',        'Chống nước IP67, AMOLED 120Hz, pin 5000mAh, giá tầm trung',                           8490000, 60,  'https://picsum.photos/seed/a54/400/400'),
-- Tai nghe & Loa (2)
(11, 2, 'AirPods Pro Gen 2',           'airpods-pro-gen-2',         'Chống ồn thích ứng H2, âm thanh không gian, MagSafe Case',                            5990000, 100, 'https://picsum.photos/seed/airpodspro2/400/400'),
(12, 2, 'Sony WH-1000XM5',             'sony-wh-1000xm5',           'Chống ồn tốt nhất thị trường, Multipoint, 30h pin',                                   7990000, 60,  'https://picsum.photos/seed/sonyxm5/400/400'),
(13, 2, 'AirPods 3',                   'airpods-3',                 'Âm thanh không gian, pin 6h, chống mồ hôi IPX4',                                      4290000, 100, 'https://picsum.photos/seed/airpods3/400/400'),
(14, 2, 'Samsung Galaxy Buds2 Pro',    'galaxy-buds2-pro',          'Âm thanh Hi-Fi 24bit, chống ồn thông minh, 360 Audio',                                3990000, 50,  'https://picsum.photos/seed/buds2pro/400/400'),
(15, 2, 'Sennheiser Momentum TW3',     'sennheiser-mtw3',           'Chất âm Audiophile, chống ồn ANC, Multipoint BT 5.2',                                 6490000, 30,  'https://picsum.photos/seed/senmtw3/400/400'),
(16, 2, 'JBL Flip 6',                  'jbl-flip-6',                'Loa Bluetooth chống nước IP67, bass mạnh, pin 12h',                                    2490000, 80,  'https://picsum.photos/seed/jblflip6/400/400'),
(17, 2, 'Marshall Emberton II',        'marshall-emberton-ii',      'Thiết kế vintage, âm thanh 360°, pin 30h, chống nước IP67',                            4290000, 35,  'https://picsum.photos/seed/emberton2/400/400'),
(18, 2, 'Beats Fit Pro',               'beats-fit-pro',             'Tai nghe thể thao, chống ồn chủ động, wing tips bảo mật',                              4590000, 35,  'https://picsum.photos/seed/beatsfitpro/400/400'),
-- Laptop (3)
(19, 3, 'MacBook Air M3 15"',          'macbook-air-m3-15',         'Chip Apple M3, 8GB RAM, 256GB SSD, màn hình Liquid Retina 15.3"',                     34990000, 20,  'https://picsum.photos/seed/macbookairm3/400/400'),
(20, 3, 'MacBook Pro 14" M3 Pro',      'macbook-pro-14-m3-pro',     'Chip M3 Pro 11 nhân, 18GB RAM, 512GB SSD, màn hình ProMotion 120Hz',                  49990000, 15,  'https://picsum.photos/seed/macpro14m3/400/400'),
(21, 3, 'ASUS ROG Strix G15',          'asus-rog-strix-g15',        'AMD Ryzen 9 7945HX, RTX 4070, màn hình 240Hz QHD',                                   35990000, 25,  'https://picsum.photos/seed/rogstrix/400/400'),
(22, 3, 'Lenovo Legion 5 Pro',         'lenovo-legion-5-pro',       'Ryzen 7 7745HX, RTX 4070, màn hình 165Hz QHD, MUX Switch',                            38990000, 20,  'https://picsum.photos/seed/legion5pro/400/400'),
(23, 3, 'Dell XPS 13 Plus',            'dell-xps-13-plus',          'Intel Core i7 Gen 13, màn hình OLED 3.5K cảm ứng, thiết kế tràn viền',                42990000, 10,  'https://picsum.photos/seed/xps13plus/400/400'),
(24, 3, 'Acer Zenbook 14 OLED',        'acer-zenbook-14-oled',      'Intel Core Ultra 7, màn hình OLED 2.8K, pin 75Wh siêu trâu',                          25990000, 30,  'https://picsum.photos/seed/zenbook14/400/400'),
(25, 3, 'Dell Inspiron 15',            'dell-inspiron-15',          'Intel Core i5 Gen 13, RAM 16GB, SSD 512GB, pin 56Wh',                                 14990000, 60,  'https://picsum.photos/seed/inspiron15/400/400'),
(26, 3, 'Lenovo ThinkPad X1 Carbon',   'thinkpad-x1-carbon',        'Intel Core i7 Gen 13, 16GB RAM, 1TB SSD, chứng nhận MIL-SPEC',                        45990000, 10,  'https://picsum.photos/seed/thinkpadx1/400/400'),
-- Phụ kiện (4)
(27, 4, 'Anker 65W GaN Charger',       'anker-65w-gan',             'Sạc nhanh GaN 2 cổng USB-C, hỗ trợ PD3.0 và PPS',                                     890000, 200, 'https://picsum.photos/seed/anker65w/400/400'),
(28, 4, 'Cáp Baseus USB-C 100W',       'cap-baseus-usbc-100w',      'Cáp bọc dù siêu bền, sạc nhanh 100W, dài 2m',                                          250000, 300, 'https://picsum.photos/seed/baseuscap/400/400'),
(29, 4, 'Ốp UAG iPhone 15 Pro Max',    'uag-ip15pm-strap',          'Chống sốc chuẩn quân đội MIL-STD, tương thích MagSafe',                               1200000, 80,  'https://picsum.photos/seed/uagip15/400/400'),
(30, 4, 'Pin dự phòng Mophie 10000',   'mophie-10000mah-magsafe',   'Sạc không dây MagSafe 15W, USB-C 20W, thiết kế siêu mỏng',                            1490000, 60,  'https://picsum.photos/seed/mophie/400/400'),
(31, 4, 'Chuột Logitech MX Master 3S', 'logitech-mx-master-3s',     '8000 DPI, scroll điện từ, kết nối 3 thiết bị, dành cho Mac',                           2490000, 40,  'https://picsum.photos/seed/mxmaster3s/400/400'),
(32, 4, 'Hub UGREEN 9-in-1 USB-C',     'ugreen-hub-9in1',           '4K HDMI, USB3.0x3, SD/microSD, PD100W, LAN Gigabit',                                    690000, 150, 'https://picsum.photos/seed/ugreenhub/400/400'),
-- Đồng hồ thông minh (5)
(33, 5, 'Apple Watch Series 9 GPS',    'apple-watch-s9-gps',        'Chip S9 SiP, Double Tap gesture, sOS khẩn cấp, đo SPO2',                              10490000, 50,  'https://picsum.photos/seed/awseries9/400/400'),
(34, 5, 'Samsung Galaxy Watch 6 Classic','galaxy-watch-6-classic', 'Bezel xoay vật lý, đo huyết áp, điện tâm đồ, 40h pin',                                  6990000, 45,  'https://picsum.photos/seed/gw6classic/400/400'),
(35, 5, 'Garmin Fenix 7 Sapphire',     'garmin-fenix-7-sapphire',   'GPS đa hệ thống, pin 18 ngày, kính Sapphire chống xước',                              18990000, 20,  'https://picsum.photos/seed/garmin7/400/400'),
-- Màn hình (6)
(36, 6, 'Dell UltraSharp 27" 4K',      'dell-ultrasharp-27-4k',     '4K IPS Black, Delta E<2, USB-C 90W, VESA 100mm',                                      14990000, 15,  'https://picsum.photos/seed/dellu27/400/400'),
(37, 6, 'LG 27GP850-B 165Hz',          'lg-27gp850-165hz',          '27" Nano IPS, 165Hz, 1ms GTG, G-Sync Compatible, HDR400',                               9990000, 25,  'https://picsum.photos/seed/lg27gp850/400/400'),
-- Bàn phím & Chuột (7)
(38, 7, 'Bàn phím cơ Keychron K2 Pro', 'keychron-k2-pro',           'Bluetooth 5.1 / USB-C, layout 75%, hot-swap switch, RGB',                               2190000, 70,  'https://picsum.photos/seed/keychronk2/400/400'),
(39, 7, 'Chuột Razer DeathAdder V3 Pro','razer-deathadder-v3-pro',  '30000 DPI, 90h pin, không dây 2.4GHz, siêu nhẹ 64g',                                   3490000, 35,  'https://picsum.photos/seed/razerv3/400/400'),
(40, 7, 'Logitech MX Mechanical Mini', 'logitech-mx-mechanical-mini','Bàn phím cơ không dây, Tactile Quiet switch, pin 10 tháng',                            3990000, 25,  'https://picsum.photos/seed/mxmechanical/400/400');

-- 4. VOUCHERS
INSERT IGNORE INTO `vouchers` (`code`,`discount_amount`,`min_order_value`,`expires_at`,`is_active`) VALUES
('HELLO2026',   500000,  10000000, '2026-12-31 23:59:59', 1),
('FREESHIP',     30000,         0, '2026-12-31 23:59:59', 1),
('TECHFAN',    1000000,  20000000, '2026-12-31 23:59:59', 1),
('SUMMER10',   200000,    5000000, '2026-08-31 23:59:59', 1),
('NEWUSER',    100000,    2000000, '2026-12-31 23:59:59', 1),
('APPLE15',    500000,   15000000, '2026-06-30 23:59:59', 1),
('GAMING20',  2000000,   30000000, '2026-09-30 23:59:59', 1);

-- 5. ORDERS (15 orders đa dạng trạng thái)
INSERT IGNORE INTO `orders` (`id`,`user_id`,`shipping_address`,`shipping_region`,`shipping_cost`,`subtotal`,`discount`,`total_amount`,`status`,`note`) VALUES
(1,  4,  '12 Hoàng Diệu, Đống Đa, Hà Nội',           'hanoi', 20000, 32990000,       0, 33010000, 'completed',  'Giao giờ hành chính'),
(2,  5,  '34 Lê Lợi, Quận 1, TP.HCM',                 'hcm',   30000,  5990000,       0,  6020000, 'completed',  NULL),
(3,  6,  '56 Hải Phòng, Hải Châu, Đà Nẵng',           'other', 40000, 34990000,  500000, 34530000, 'shipping',   NULL),
(4,  7,  '78 Trần Phú, Nha Trang, Khánh Hòa',         'other', 40000, 26990000,       0, 27030000, 'confirmed',  NULL),
(5,  8,  '90 Nguyễn Du, Ninh Kiều, Cần Thơ',          'other', 40000,  7990000,   30000,  8000000, 'completed',  'Áp mã FREESHIP'),
(6,  9,  '11 Bà Triệu, Ngô Quyền, Hải Phòng',         'other', 40000, 10490000,       0, 10530000, 'cancelled',  'Khách hủy vì thay đổi địa chỉ'),
(7,  10, '22 Đinh Tiên Hoàng, Thành phố Huế',         'other', 40000, 19990000,       0, 20030000, 'completed',  NULL),
(8,  11, '33 Lê Duẩn, Buôn Ma Thuột, Đắk Lắk',       'other', 40000, 22990000,       0, 23030000, 'completed',  NULL),
(9,  12, '44 Trần Hưng Đạo, Thủ Dầu Một, Bình Dương','other', 40000, 35990000, 1000000, 35030000, 'shipping',   'Áp mã TECHFAN'),
(10, 13, '55 Nguyễn Trãi, Biên Hòa, Đồng Nai',       'other', 40000,  2490000,       0,  2530000, 'pending',    NULL),
(11, 14, '66 Lý Thường Kiệt, Hải Dương',              'other', 40000,  4290000,       0,  4330000, 'completed',  NULL),
(12, 15, '77 Phan Bội Châu, Vinh, Nghệ An',           'other', 40000, 13990000,       0, 14030000, 'completed',  NULL),
(13, 16, '88 Hùng Vương, Thanh Hóa',                  'other', 40000,  3490000,       0,  3530000, 'completed',  NULL),
(14, 4,  '12 Hoàng Diệu, Đống Đa, Hà Nội',           'hanoi', 20000, 14990000,       0, 15010000, 'disputed',   'Nhận hàng bị lỗi màn hình'),
(15, 5,  '34 Lê Lợi, Quận 1, TP.HCM',                 'hcm',   30000, 49990000,  500000, 49520000, 'completed',  NULL);

-- 6. ORDER ITEMS
INSERT IGNORE INTO `order_items` (`order_id`,`product_id`,`product_name`,`product_price`,`quantity`) VALUES
(1,  1,  'iPhone 15 Pro Max 256GB',     32990000, 1),
(2,  11, 'AirPods Pro Gen 2',            5990000, 1),
(3,  19, 'MacBook Air M3 15"',          34990000, 1),
(4,  4,  'iPhone 14 Pro Max 256GB',     26990000, 1),
(5,  12, 'Sony WH-1000XM5',             7990000, 1),
(6,  33, 'Apple Watch Series 9 GPS',   10490000, 1),
(7,  3,  'iPhone 15 128GB',            19990000, 1),
(8,  5,  'Xiaomi 14 Pro 5G',           22990000, 1),
(9,  21, 'ASUS ROG Strix G15',         35990000, 1),
(10, 16, 'JBL Flip 6',                  2490000, 1),
(11, 13, 'AirPods 3',                   4290000, 1),
(12, 9,  'iPhone 13 128GB',            13990000, 1),
(13, 39, 'Chuột Razer DeathAdder V3 Pro',3490000, 1),
(14, 25, 'Dell Inspiron 15',           14990000, 1),
(15, 20, 'MacBook Pro 14" M3 Pro',     49990000, 1);

-- 7. PRODUCT REVIEWS (20 đánh giá thực tế)
INSERT IGNORE INTO `product_reviews` (`product_id`,`user_id`,`order_id`,`rating`,`comment`) VALUES
(1,  4,  1,  5, 'Điện thoại tuyệt vời! Titan siêu nhẹ, camera chụp ban đêm xuất sắc. Xứng đáng từng đồng.'),
(11, 5,  2,  5, 'AirPods Pro 2 chống ồn đỉnh của đỉnh. Dùng làm việc online không nghe tiếng ồn xung quanh tý nào.'),
(19, 6,  3,  5, 'MacBook Air M3 chạy mượt mà không tưởng. Thiết kế đẹp, màn hình sắc nét. Cực kỳ hài lòng!'),
(4,  7,  4,  4, 'iPhone 14 Pro Max chất lượng tốt, camera rất đỉnh. Giao hàng đúng hẹn. Trừ 1 sao vì hộp có vết bẩn nhỏ.'),
(12, 8,  5,  5, 'Sony WH-1000XM5 chống ồn số 1. Bass trầm ấm, treble sắc nét. Đeo cả ngày không đau tai.'),
(3,  10, 7,  4, 'iPhone 15 màu sắc đẹp, Dynamic Island tiện dụng. USB-C tiện hơn nhiều so với Lightning.'),
(5,  11, 8,  5, 'Xiaomi 14 Pro camera Leica chụp ảnh như máy ảnh chuyên nghiệp. Sạc 100W siêu nhanh!'),
(21, 12, 9,  5, 'ROG Strix G15 chiến game tốt tệt. Tản nhiệt mạnh, không bị throttle khi load nặng.'),
(13, 14, 11, 4, 'AirPods 3 âm thanh hay, fit tai tốt. Pin hơi ngắn khi nghe nhạc liên tục 5-6h.'),
(9,  15, 12, 5, 'iPhone 13 dù cũ hơn nhưng vẫn rất mượt. Mua cho bố dùng, ông rất hài lòng!'),
(39, 16, 13, 5, 'Chuột Razer DeathAdder V3 siêu nhẹ, cầm rất thoải mái. Chơi FPS chính xác tuyệt đối.'),
(25, 4,  14, 2, 'Laptop mua về màn hình bị lỗi điểm chết. Đã liên hệ shop để giải quyết.'),
(20, 5,  15, 5, 'MacBook Pro M3 Pro xứng đáng đồng tiền. Render video 4K không tốn 15 phút nữa. Cực kỳ ấn tượng!'),
(16, 6,  3,  4, 'JBL Flip 6 âm lượng to chơi thật. Chống nước tốt, đưa đi bể bơi vẫn ổn.'),
(14, 7,  4,  4, 'Galaxy Buds2 Pro fit tai rất tốt, ANC ổn so với mức giá. Kết nối nhanh với Samsung S24.'),
(33, 8,  6,  5, 'Apple Watch S9 theo dõi sức khỏe rất chính xác. Double Tap tiện ợt khi hai tay bận!'),
(17, 10, 7,  5, 'Marshall Emberton II âm bass trầm ấm, âm thanh 360° nghe rất hay. Pin 30h là điểm cộng lớn.'),
(34, 11, 8,  4, 'Galaxy Watch 6 Classic bezel xoay cực đỉnh, đo huyết áp chính xác. Nhược điểm pin hơi yếu.'),
(38, 14, 11, 5, 'Keychron K2 Pro gõ cực sướng, layout 75% vừa phải, hot-swap tiện nâng cấp switch về sau.'),
(15, 15, 12, 5, 'Sennheiser MomentumTW3 chất âm nghe như loa hi-end. Đắt nhưng xứng đáng cho audiophile.');

-- 8. WISHLISTS
INSERT IGNORE INTO `wishlists` (`user_id`,`product_id`) VALUES
(4,  2), (4,  19), (4,  33),
(5,  1), (5,  12), (5,  20),
(6,  7), (6,  21), (6,  37),
(7,  3), (7,  11), (7,  34),
(8,  5), (8,  22), (8,  36),
(9,  4), (9,  15), (9,  35),
(10, 6), (10, 24), (10, 38),
(11, 8), (11, 13), (11, 39),
(12, 9), (12, 16), (12, 40),
(13, 10),(13, 17), (13, 31);

-- 9. NOTIFICATIONS
INSERT IGNORE INTO `notifications` (`user_id`,`title`,`message`,`type`,`is_read`) VALUES
(4,  'Đơn hàng đã giao thành công',    'Đơn hàng #1 của bạn (iPhone 15 Pro Max) đã được giao thành công. Cảm ơn bạn đã mua sắm!', 'success', 1),
(4,  'Đơn hàng đang được xử lý',       'Đơn hàng #14 (Dell Inspiron 15) đang trong quá trình xử lý tranh chấp. Chúng tôi sẽ phản hồi trong 24h.',       'warning', 0),
(5,  'Đơn hàng đã giao thành công',    'Đơn hàng #2 (AirPods Pro Gen 2) đã giao thành công. Hãy để lại đánh giá nhé!',           'success', 1),
(5,  'Khuyến mãi Flash Sale hôm nay',  'Giảm đến 30% cho Laptop từ 10:00 - 12:00 hôm nay. Đừng bỏ lỡ!',                          'info',    0),
(6,  'Đơn hàng đang vận chuyển',       'Đơn hàng #3 (MacBook Air M3) đang trên đường giao đến bạn. Dự kiến nhận 1-2 ngày.',       'info',    0),
(7,  'Đơn hàng đã xác nhận',           'Đơn hàng #4 (iPhone 14 Pro Max) đã được xác nhận và chuẩn bị giao hàng.',                 'success', 0),
(8,  'Đơn hàng đã giao thành công',    'Đơn hàng #5 (Sony WH-1000XM5) đã giao thành công. Cảm ơn!',                               'success', 1),
(9,  'Đơn hàng đã bị hủy',             'Đơn hàng #6 (Apple Watch S9) đã bị hủy theo yêu cầu của bạn. Hoàn tiền trong 3-5 ngày.', 'warning', 1),
(12, 'Flash Sale cuối tuần',            'Tai nghe & Loa giảm 20% từ thứ 7 đến chủ nhật. Xem ngay!',                                'info',    0),
(13, 'Đơn hàng chờ xác nhận',          'Đơn hàng #10 (JBL Flip 6) đang chờ xác nhận thanh toán.',                                 'info',    0),
(1,  'Có đơn hàng mới cần xử lý',      '5 đơn hàng mới đang chờ xác nhận. Truy cập admin để xử lý.',                              'warning', 0),
(1,  'Khiếu nại mới từ khách hàng',    'Đơn hàng #14 có khiếu nại từ Nguyễn Văn An. Cần xử lý trong 24h.',                       'error',   0);

-- 10. DISPUTE CHATS (cho đơn hàng #14 - disputed)
INSERT IGNORE INTO `dispute_chats` (`order_id`,`user_id`,`sender_role`,`message`) VALUES
(14, 4,  'user',  'Chào shop! Tôi nhận được laptop Dell Inspiron 15 nhưng màn hình bị lỗi điểm chết ở góc trên bên phải. Đây là lỗi từ nhà sản xuất hay do vận chuyển?'),
(14, 1,  'admin', 'Chào bạn Nguyễn Văn An! Chúng tôi rất tiếc về sự cố này. Bạn có thể chụp ảnh màn hình và gửi cho chúng tôi không? Chúng tôi sẽ xem xét và có phương án xử lý nhanh nhất.'),
(14, 4,  'user',  'Đây là ảnh màn hình bị lỗi ạ. Lỗi xuất hiện ngay khi mở hộp, chưa dùng lần nào.'),
(14, 1,  'admin', 'Cảm ơn bạn đã cung cấp hình ảnh. Chúng tôi xác nhận đây là lỗi sản xuất. Chúng tôi sẽ gửi đội kỹ thuật đến kiểm tra và đổi máy mới trong 3-5 ngày làm việc. Xin lỗi vì sự bất tiện này!');

-- 11. CABINETS & SLOTS (Kệ trưng bày POS)
INSERT IGNORE INTO `cabinets` (`id`,`name`,`rows`,`cols`) VALUES
(1, 'Kệ Điện Thoại A - Tầng 1',  4, 5),
(2, 'Kệ Laptop B - Tầng 1',      3, 4),
(3, 'Kệ Phụ Kiện C - Tầng 2',    5, 6);

INSERT IGNORE INTO `cabinet_slots` (`cabinet_id`,`row`,`col`,`product_id`,`status`) VALUES
-- Kệ điện thoại (cabinet 1)
(1,1,1, 1, 'available'), (1,1,2, 2, 'available'), (1,1,3, 3, 'available'), (1,1,4, 4, 'available'), (1,1,5, 5, 'available'),
(1,2,1, 6, 'available'), (1,2,2, 7, 'reserved'),  (1,2,3, 8, 'available'), (1,2,4, 9, 'available'), (1,2,5,10, 'available'),
(1,3,1, 1, 'sold'),      (1,3,2, 3, 'available'), (1,3,3, 9, 'available'), (1,3,4, 10,'available'), (1,3,5, 5, 'available'),
(1,4,1, 2, 'available'), (1,4,2, 4, 'available'),  (1,4,3, 6, 'available'),(1,4,4, 7, 'available'), (1,4,5, 8, 'reserved'),
-- Kệ laptop (cabinet 2)
(2,1,1,19, 'available'), (2,1,2,20, 'available'), (2,1,3,21, 'available'), (2,1,4,22, 'available'),
(2,2,1,23, 'available'), (2,2,2,24, 'available'), (2,2,3,25, 'sold'),      (2,2,4,26, 'available'),
(2,3,1,19, 'available'), (2,3,2,21, 'available'), (2,3,3,24, 'reserved'),  (2,3,4,25, 'available'),
-- Kệ phụ kiện (cabinet 3)
(3,1,1,27, 'available'), (3,1,2,28, 'available'), (3,1,3,29, 'available'), (3,1,4,30, 'available'), (3,1,5,31, 'available'), (3,1,6,32, 'available'),
(3,2,1,33, 'available'), (3,2,2,34, 'available'), (3,2,3,35, 'available'), (3,2,4,38, 'available'), (3,2,5,39, 'available'), (3,2,6,40, 'available'),
(3,3,1,27, 'sold'),      (3,3,2,28, 'available'), (3,3,3,31, 'available'), (3,3,4,32, 'available'), (3,3,5,29, 'available'), (3,3,6,30, 'reserved');

-- 12. OFFLINE ORDERS (Bán lẻ tại quầy)
INSERT IGNORE INTO `offline_orders` (`product_id`,`slot_id`,`customer_phone`,`customer_name`,`sale_price`,`staff_id`,`status`,`payment_method`,`note`) VALUES
(1,  1,  '0912345678', 'Nguyễn Văn Tùng',    32990000, 2, 'paid',      'transfer', 'Khách mua tặng sinh nhật'),
(3,  3,  '0923456789', 'Trần Thị Hoa',        19990000, 2, 'paid',      'cash',     NULL),
(27, 16, '0934567890', 'Lê Minh Đức',           890000, 3, 'paid',      'transfer', 'Mua thêm cáp'),
(19, 9,  '0945678901', 'Phạm Văn Bắc',        34990000, 3, 'paid',      'transfer', 'TT chuyển khoản đầy đủ'),
(11, NULL,'0956789012','Khách vãng lai',        5990000, 2, 'pending',   'cash',     'Khách đang xem xét'),
(31, 5,  '0967890123', 'Hoàng Thị Mai',        2490000, 3, 'paid',      'cash',     NULL),
(9,  14, '0978901234', 'Vũ Quang Hùng',       13990000, 2, 'paid',      'transfer', NULL),
(38, 31, '0989012345', 'Đỗ Thị Linh',          2190000, 3, 'cancelled', 'cash',     'Khách đổi ý chọn model khác');

SET FOREIGN_KEY_CHECKS = 1;

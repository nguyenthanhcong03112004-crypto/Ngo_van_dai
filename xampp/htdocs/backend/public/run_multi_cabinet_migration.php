<?php
spl_autoload_register(function (string $class): void {
    $base = dirname(__DIR__) . '/app/';
    $file = $base . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) require_once $file;
});

// Load .env
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v);
    }
}

try {
    $db = \Core\Database::getInstance()->getConnection();

    $queries = [
        "CREATE TABLE IF NOT EXISTS `cabinets` (
            `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `name`       VARCHAR(100) NOT NULL,
            `rows`       TINYINT UNSIGNED NOT NULL DEFAULT 5,
            `cols`       TINYINT UNSIGNED NOT NULL DEFAULT 5,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `cabinet_slots` (
            `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `cabinet_id`  INT UNSIGNED NOT NULL,
            `row`         TINYINT UNSIGNED NOT NULL,
            `col`         TINYINT UNSIGNED NOT NULL,
            `product_id`  INT UNSIGNED NOT NULL,
            `status`      ENUM('available','sold','reserved') NOT NULL DEFAULT 'available',
            `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_cabinet_slot` (`cabinet_id`, `row`, `col`),
            CONSTRAINT `fk_slot_cabinet` FOREIGN KEY (`cabinet_id`) REFERENCES `cabinets`(`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_slot_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "ALTER TABLE `offline_orders` ADD COLUMN IF NOT EXISTS `slot_id` INT UNSIGNED DEFAULT NULL AFTER `product_id`"
    ];

    echo "<style>body{font-family:sans-serif;padding:20px;background:#0f172a;color:#e2e8f0;} .ok{color:#4ade80} .err{color:#f87171}</style>";
    echo "<h2 style='color:#38bdf8'>Multi-Cabinet Migration</h2>";

    foreach ($queries as $sql) {
        try {
            $db->exec($sql);
            echo "<p class='ok'>✅ " . htmlspecialchars(substr($sql, 0, 80)) . "...</p>";
        } catch (\Throwable $e) {
            echo "<p class='err'>⚠️ " . htmlspecialchars(substr($sql, 0, 80)) . "... → " . $e->getMessage() . "</p>";
        }
    }

    // Insert first cabinet
    $stmt = $db->query("SELECT id FROM `cabinets` WHERE `id` = 1");
    if (!$stmt->fetch()) {
        $db->exec("INSERT INTO `cabinets` (`id`, `name`, `rows`, `cols`) VALUES (1, 'Tu trung bay trung tam', 5, 5)");
        echo "<p class='ok'>✅ Inserted default cabinet (ID: 1)</p>";

        // Migrate product slots
        $db->exec("INSERT IGNORE INTO `cabinet_slots` (`cabinet_id`, `row`, `col`, `product_id`, `status`)
                   SELECT 1, `shelf_row`, `shelf_col`, `id`, `shelf_status`
                   FROM `products`
                   WHERE `shelf_row` IS NOT NULL AND `shelf_col` IS NOT NULL");
        echo "<p class='ok'>✅ Migrated existing shelf data to cabinet_slots</p>";
    }

    echo "<h3 style='margin-top:20px;color:#f59e0b'>Migration hoàn tất! Bạn có thể xóa file này.</h3>";
} catch (\Throwable $e) {
    echo "<p style='color:#f87171'>Fatal: " . $e->getMessage() . "</p>";
}

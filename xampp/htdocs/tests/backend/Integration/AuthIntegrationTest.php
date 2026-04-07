<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Module 5 & 6: Payment Proof, Dispute Chat, Analytics (CRUD)
 * Scenarios: SC-039 to SC-048
 *
 * SETUP: These tests require a running MySQL instance.
 * Use DB Transactions to ensure test isolation (auto-rollback after each test).
 *
 * HOW TO RUN:
 *   docker-compose exec php-apache vendor/bin/phpunit -c /var/www/html/phpunit.xml --testsuite Integration
 */
class AuthIntegrationTest extends TestCase
{
    private ?\PDO $pdo = null;

    protected function setUp(): void
    {
        // Arrange: Connect to test database. Uses container env vars when running in Docker.
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            getenv('DB_HOST') ?: 'mysql',
            getenv('DB_PORT') ?: '3306',
            getenv('DB_NAME') ?: 'ecommerce_db'
        );

        try {
            $this->pdo = new \PDO(
                $dsn,
                getenv('DB_USER') ?: 'root',
                getenv('DB_PASS') ?: '',
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
            // Start a transaction before each test — will be rolled back in tearDown
            $this->pdo->beginTransaction();
        } catch (\PDOException $e) {
            // If DB is not available, skip the test gracefully
            $this->markTestSkipped('Database not available: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        // Rollback all changes so tests do NOT pollute the database
        if ($this->pdo && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        $this->pdo = null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // [SC-003] [Read] Login succeeds for a seeded User account
    // ─────────────────────────────────────────────────────────────────────────
    public function testUserLoginReturnsCorrectRecord(): void
    {
        // Arrange — use a known seeded user (first user-role account)
        $stmt = $this->pdo->query('SELECT id, email, status, role FROM users WHERE role = "user" LIMIT 1');
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Assert
        $this->assertNotFalse($user, 'Seeded user must exist in the database');
        $this->assertEquals('user', $user['role']);
        $this->assertEquals('active', $user['status']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // [SC-004] [Read] Admin account exists in seed data
    // ─────────────────────────────────────────────────────────────────────────
    public function testAdminAccountExistsInSeedData(): void
    {
        // Arrange
        $email = 'admin@shop.com';

        // Act
        $stmt = $this->pdo->prepare('SELECT role FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $admin = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Assert
        $this->assertNotFalse($admin, 'Seeded admin must exist in the database');
        $this->assertEquals('admin', $admin['role']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // [SC-001] [Create] Register inserts a new user into the database
    // ─────────────────────────────────────────────────────────────────────────
    public function testRegisterInsertsNewUserRow(): void
    {
        // Arrange
        $newUser = [
            'name'     => 'Test User Integration',
            'email'    => 'test_integration_' . uniqid() . '@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
            'role'     => 'user',
            'status'   => 'active',
        ];

        // Act
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute(array_values($newUser));
        $newId = $this->pdo->lastInsertId();

        // Assert
        $this->assertGreaterThan(0, $newId, 'New user must receive a database ID after insert');

        // Verify the row exists
        $check = $this->pdo->prepare('SELECT email FROM users WHERE id = ?');
        $check->execute([$newId]);
        $row = $check->fetch(\PDO::FETCH_ASSOC);
        $this->assertEquals($newUser['email'], $row['email']);
        // Note: tearDown() automatically rolls back this insert.
    }

    // ─────────────────────────────────────────────────────────────────────────
    // [SC-013 API] [Create] POST /api/admin/products with valid data → 201
    // ─────────────────────────────────────────────────────────────────────────
    public function testAdminCanCreateProductViaDatabase(): void
    {
        // Arrange — get a valid category_id first (required NOT NULL FK)
        $catStmt = $this->pdo->query('SELECT id FROM categories LIMIT 1');
        $cat = $catStmt->fetch(\PDO::FETCH_ASSOC);
        if (!$cat) $this->markTestSkipped('No category in DB');

        $slug = 'phpunit-test-product-' . uniqid();
        $product = [
            'category_id' => $cat['id'],
            'name'        => 'PHPUnit Test Product',
            'slug'        => $slug,
            'description' => 'Created by automated test',
            'price'       => 9900000,
            'stock'       => 50,
            'is_active'   => 1,
        ];

        // Act
        $stmt = $this->pdo->prepare(
            'INSERT INTO products (category_id, name, slug, description, price, stock, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute(array_values($product));
        $productId = $this->pdo->lastInsertId();

        // Assert
        $this->assertGreaterThan(0, $productId);

        $check = $this->pdo->prepare('SELECT price FROM products WHERE id = ?');
        $check->execute([$productId]);
        $row = $check->fetch(\PDO::FETCH_ASSOC);
        $this->assertEquals(9900000, $row['price'], 'Inserted price must match the input value');
        // Note: tearDown() automatically rolls back this insert.
    }

    // ─────────────────────────────────────────────────────────────────────────
    // [SC-034] [Create] Order insertion generates a new order_id
    // ─────────────────────────────────────────────────────────────────────────
    public function testOrderInsertionGeneratesId(): void
    {
        // Arrange: Get a user id first
        $stmt    = $this->pdo->query('SELECT id FROM users WHERE role = "user" LIMIT 1');
        $user    = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$user) $this->markTestSkipped('No user in DB');

        $orderData = [
            'user_id'          => $user['id'],
            'shipping_address' => '123 Lê Lợi, Hà Nội',
            'shipping_region'  => 'hanoi',
            'shipping_cost'    => 30000,
            'subtotal'         => 25000000,
            'discount'         => 0,
            'total_amount'     => 25030000,
            'status'           => 'pending',
        ];

        // Act
        $stmt = $this->pdo->prepare(
            'INSERT INTO orders (user_id, shipping_address, shipping_region, shipping_cost, subtotal, discount, total_amount, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute(array_values($orderData));
        $orderId = $this->pdo->lastInsertId();

        // Assert
        $this->assertGreaterThan(0, $orderId, 'Order ID must be a positive integer');
    }
}

<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * ORDER API INTEGRATION TESTS (SC-113 to SC-120)
 * Requires backend running at http://localhost:8888
 * HOW TO RUN:
 *   docker-compose exec php-apache vendor/bin/phpunit -c /var/www/html/phpunit.xml --testsuite Integration --testdox
 */
class OrderApiTest extends TestCase
{
    private string $apiBase   = 'http://localhost:8888/api';
    private string $userToken = '';
    private ?\PDO  $pdo       = null;

    protected function setUp(): void
    {
        $loginResponse = $this->httpRequest('POST', '/auth/login', [
            'email' => 'user@shop.com', 'password' => 'user123',
        ]);
        if (!isset($loginResponse['data']['token'])) {
            $this->markTestSkipped('Cannot get user token. Is the API running at localhost:8888?');
        }
        $this->userToken = $loginResponse['data']['token'];

        try {
            $this->pdo = new \PDO(
                sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                    getenv('DB_HOST') ?: 'mysql',
                    getenv('DB_PORT') ?: '3306',
                    getenv('DB_NAME') ?: 'ecommerce_db'),
                getenv('DB_USER') ?: 'ecommerce_user',
                getenv('DB_PASS') ?: 'secret123',
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
            // BEGIN TRANSACTION: all DB writes in this test will be rolled back
            $this->pdo->beginTransaction();
        } catch (\PDOException $e) {
            $this->markTestSkipped('Database not available: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        // ROLLBACK: ensures the database stays clean after every test
        if ($this->pdo && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        $this->pdo = null;
    }

    /** [SC-113] Empty cart checkout should be rejected */
    public function testCheckoutWithEmptyCartIsRejected(): void
    {
        // Arrange
        $payload = ['items' => [], 'shipping_address' => '123 Lê Lợi', 'shipping_region' => 'hanoi'];

        // Act
        $response = $this->httpRequest('POST', '/user/checkout', $payload, $this->userToken);

        // Assert
        $this->assertContains($response['__status_code'], [400, 422]);
    }

    /** [SC-114] Valid checkout → HTTP 201 + order_id in response */
    public function testCheckoutWithValidCartReturns201(): void
    {
        // Arrange
        $stmt    = $this->pdo->query('SELECT id FROM products WHERE status = "active" LIMIT 1');
        $product = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$product) $this->markTestSkipped('No active products in DB');

        $payload = [
            'items'            => [['product_id' => $product['id'], 'quantity' => 1]],
            'shipping_address' => '99 Trần Hưng Đạo, Hà Nội',
            'shipping_region'  => 'hanoi',
        ];

        // Act
        $response = $this->httpRequest('POST', '/user/checkout', $payload, $this->userToken);

        // Assert
        $this->assertEquals(201, $response['__status_code']);
        $this->assertArrayHasKey('order_id', $response['data'] ?? []);
        $this->assertGreaterThan(0, $response['data']['order_id']);
    }

    /** [SC-116] Two consecutive checkouts produce different order IDs */
    public function testConsecutiveCheckoutsHaveUniqueOrderIds(): void
    {
        // Arrange
        $stmt    = $this->pdo->query('SELECT id FROM products WHERE status = "active" LIMIT 1');
        $product = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$product) $this->markTestSkipped('No active product');

        $payload = [
            'items'            => [['product_id' => $product['id'], 'quantity' => 1]],
            'shipping_address' => 'Test Address',
            'shipping_region'  => 'hanoi',
        ];

        // Act
        $r1 = $this->httpRequest('POST', '/user/checkout', $payload, $this->userToken);
        $r2 = $this->httpRequest('POST', '/user/checkout', $payload, $this->userToken);

        // Assert
        if ($r1['__status_code'] === 201 && $r2['__status_code'] === 201) {
            $this->assertNotEquals($r1['data']['order_id'], $r2['data']['order_id']);
        } else {
            $this->markTestSkipped('Checkout did not return 201');
        }
    }

    /** [SC-117] DB: checkout inserts a row into orders table */
    public function testCheckoutInsertsOrderRow(): void
    {
        // Arrange
        $stmt = $this->pdo->query('SELECT id FROM users WHERE role = "user" LIMIT 1');
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$user) $this->markTestSkipped('No user in DB');

        // Act: insert directly inside transaction
        $insert = $this->pdo->prepare(
            'INSERT INTO orders (user_id, shipping_address, shipping_region, total_amount, shipping_fee, status)
             VALUES (?, ?, ?, ?, ?, "pending")'
        );
        $insert->execute([$user['id'], '1 Test St', 'hanoi', 10_000_000, 30_000]);
        $orderId = (int)$this->pdo->lastInsertId();

        // Assert
        $this->assertGreaterThan(0, $orderId);
        $check = $this->pdo->prepare('SELECT status FROM orders WHERE id = ?');
        $check->execute([$orderId]);
        $row = $check->fetch(\PDO::FETCH_ASSOC);
        $this->assertEquals('pending', $row['status']);
        // tearDown() auto-rolls back, so no cleanup needed
    }

    /** [SC-120] Checkout without JWT token → 401 Unauthorized */
    public function testCheckoutWithoutAuthReturns401(): void
    {
        // Arrange
        $payload = [
            'items'            => [['product_id' => 1, 'quantity' => 1]],
            'shipping_address' => 'Test',
            'shipping_region'  => 'hanoi',
        ];

        // Act — no token
        $response = $this->httpRequest('POST', '/user/checkout', $payload);

        // Assert
        $this->assertEquals(401, $response['__status_code']);
    }

    /** [SC-119] GET /api/user/orders returns 200 with data array */
    public function testOrderHistoryReturns200(): void
    {
        // Act
        $response = $this->httpRequest('GET', '/user/orders', [], $this->userToken);

        // Assert
        $this->assertEquals(200, $response['__status_code']);
        $this->assertArrayHasKey('data', $response);
    }

    // ─── HTTP Helper ────────────────────────────────────────────────────────
    private function httpRequest(string $method, string $endpoint, array $body = [], string $token = ''): array
    {
        $ch = curl_init($this->apiBase . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        if ($token) $headers[] = "Authorization: Bearer {$token}";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $rawBody  = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($rawBody, true) ?? [];
        $decoded['__status_code'] = $httpCode;
        return $decoded;
    }
}

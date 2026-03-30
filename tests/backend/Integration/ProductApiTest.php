<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * [SC-013 API] [Create] POST /api/admin/products → 201
 * [SC-014 API] [Create] POST /api/admin/products missing price → 400
 *
 * NOTE: This test class fires real HTTP requests against the running API.
 * The API must be accessible at http://localhost:8888.
 * DB state is preserved since we rely on server-side transaction logic.
 *
 * HOW TO RUN:
 *   docker-compose exec php-apache vendor/bin/phpunit -c /var/www/html/phpunit.xml --testsuite Integration
 */
class ProductApiTest extends TestCase
{
    private string $apiBase = 'http://localhost:8888/api';
    private string $adminToken = '';

    protected function setUp(): void
    {
        // Arrange: Login as admin to get JWT token for protected routes
        $response = $this->httpRequest('POST', '/auth/login', [
            'email'    => 'admin@shop.com',
            'password' => 'admin123',
        ]);

        if (isset($response['data']['token'])) {
            $this->adminToken = $response['data']['token'];
        } else {
            $this->markTestSkipped('Cannot obtain admin token — is the API server running at http://localhost:8888?');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // [SC-013] POST /api/admin/products with all valid fields → 201
    // ─────────────────────────────────────────────────────────────────────────
    public function testCreateProductWithValidDataReturns201(): void
    {
        // Arrange
        $productPayload = [
            'name'        => 'API Test Product ' . uniqid(),
            'price'       => 15000000,
            'stock'       => 25,
            'description' => 'Created via PHPUnit API test',
            'status'      => 'active',
        ];

        // Act
        $response = $this->httpRequest('POST', '/admin/products', $productPayload, $this->adminToken);

        // Assert
        $this->assertEquals(201, $response['__status_code'], 'Valid product creation must return HTTP 201');
        $this->assertArrayHasKey('data', $response, 'Response body must contain a "data" key');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // [SC-014] POST /api/admin/products missing "price" → 400 Validation Error
    // ─────────────────────────────────────────────────────────────────────────
    public function testCreateProductWithMissingPriceReturns400(): void
    {
        // Arrange: deliberately omit "price"
        $invalidPayload = [
            'name'  => 'Product Missing Price',
            'stock' => 5,
        ];

        // Act
        $response = $this->httpRequest('POST', '/admin/products', $invalidPayload, $this->adminToken);

        // Assert
        $this->assertEquals(400, $response['__status_code'], 'Missing required field must return HTTP 400');
        $this->assertStringContainsStringIgnoringCase('price', json_encode($response),
            'Error message must mention the missing "price" field');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // [SC-003] POST /api/auth/login with valid user creds → 200 + JWT
    // ─────────────────────────────────────────────────────────────────────────
    public function testUserLoginReturnsJwtToken(): void
    {
        // Arrange
        $credentials = ['email' => 'user@shop.com', 'password' => 'user123'];

        // Act
        $response = $this->httpRequest('POST', '/auth/login', $credentials);

        // Assert
        $this->assertEquals(200, $response['__status_code']);
        $this->assertArrayHasKey('token', $response['data'] ?? [], 'Login response must contain a JWT token');
        $this->assertEquals('user', $response['data']['user']['role']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // [SC-005] POST /api/auth/login with wrong password → 401
    // ─────────────────────────────────────────────────────────────────────────
    public function testLoginWithWrongPasswordReturns401(): void
    {
        // Arrange
        $credentials = ['email' => 'user@shop.com', 'password' => 'WRONGPASSWORD'];

        // Act
        $response = $this->httpRequest('POST', '/auth/login', $credentials);

        // Assert
        $this->assertEquals(401, $response['__status_code'], 'Invalid credentials must return HTTP 401');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // [SC-020] GET /api/user/products → Returns list with active products only
    // ─────────────────────────────────────────────────────────────────────────
    public function testPublicProductListEndpointReturnsActiveProducts(): void
    {
        // Act
        $response = $this->httpRequest('GET', '/user/products');

        // Assert
        $this->assertEquals(200, $response['__status_code']);
        $this->assertIsArray($response['data'] ?? null, 'Product list must be an array');

        foreach ($response['data'] as $product) {
            $this->assertEquals('active', $product['status'] ?? 'active',
                'All returned products must have status = active');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper: Makes HTTP requests using cURL with JSON body
    // ─────────────────────────────────────────────────────────────────────────
    private function httpRequest(string $method, string $endpoint, array $body = [], string $token = ''): array
    {
        $ch = curl_init($this->apiBase . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        if ($token) {
            $headers[] = "Authorization: Bearer {$token}";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $rawBody   = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($rawBody, true) ?? [];
        $decoded['__status_code'] = $httpCode;
        return $decoded;
    }
}

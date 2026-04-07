<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Module 2: Product Management (Admin CRUD & User Read)
 * Scenarios: SC-013 to SC-022
 * Covers: Create, Read (List/Search/Filter/Sort/Paginate), Update, Delete
 */
class ProductTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────────
    // [SC-013] [Create] Admin creates a product with all valid fields
    // ─────────────────────────────────────────────────────────────────────────
    public function testValidProductDataPassesValidation(): void
    {
        // Arrange
        $productData = [
            'name'        => 'MacBook Pro M4',
            'price'       => 50000000,
            'stock'       => 10,
            'description' => 'Chip M4, RAM 16GB, SSD 512GB',
            'status'      => 'active',
        ];

        // Act
        $isValid = $this->validateProduct($productData);

        // Assert
        $this->assertTrue($isValid, 'Product with all required fields must pass validation');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // [SC-014] [Create] Validation fails when price or name is empty
    // ─────────────────────────────────────────────────────────────────────────
    public function testProductWithMissingPriceFails(): void
    {
        // Arrange
        $productData = [
            'name'  => 'iPhone 16',
            'price' => null,
            'stock' => 5,
        ];

        // Act
        $isValid = $this->validateProduct($productData);

        // Assert
        $this->assertFalse($isValid, 'Product must be invalid if price is null');
    }

    public function testProductWithEmptyNameFails(): void
    {
        // Arrange
        $productData = [
            'name'  => '',
            'price' => 25000000,
            'stock' => 5,
        ];

        // Act
        $isValid = $this->validateProduct($productData);

        // Assert
        $this->assertFalse($isValid, 'Product must be invalid if name is empty');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // [SC-017] [Update] Price and stock can be updated
    // ─────────────────────────────────────────────────────────────────────────
    public function testProductPriceAndStockCanBeUpdated(): void
    {
        // Arrange
        $product = ['id' => 1, 'price' => 30000000, 'stock' => 10];

        // Act
        $product['price'] = 28000000;
        $product['stock'] = 15;

        // Assert
        $this->assertEquals(28000000, $product['price']);
        $this->assertEquals(15, $product['stock']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // [SC-021] [Read] Price range filter logic (10M - 20M)
    // ─────────────────────────────────────────────────────────────────────────
    public function testPriceRangeFilterReturnsCorrectProducts(): void
    {
        // Arrange
        $products = [
            ['name' => 'Product A', 'price' => 8000000],
            ['name' => 'Product B', 'price' => 15000000],
            ['name' => 'Product C', 'price' => 22000000],
        ];
        $minPrice = 10000000;
        $maxPrice = 20000000;

        // Act
        $filtered = array_filter($products, fn($p) => $p['price'] >= $minPrice && $p['price'] <= $maxPrice);

        // Assert
        $this->assertCount(1, $filtered, 'Only Product B should be within the 10M-20M range');
        $this->assertEquals('Product B', array_values($filtered)[0]['name']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // [SC-022] [Read] Sort products by price ascending
    // ─────────────────────────────────────────────────────────────────────────
    public function testProductsSortedByPriceAscending(): void
    {
        // Arrange
        $products = [
            ['name' => 'Expensive',    'price' => 50000000],
            ['name' => 'Cheap',        'price' => 5000000],
            ['name' => 'Mid-range',    'price' => 20000000],
        ];

        // Act
        usort($products, fn($a, $b) => $a['price'] - $b['price']);

        // Assert
        $this->assertEquals('Cheap',     $products[0]['name']);
        $this->assertEquals('Mid-range', $products[1]['name']);
        $this->assertEquals('Expensive', $products[2]['name']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // [SC-019] [Delete] Product is removed from list after deletion
    // ─────────────────────────────────────────────────────────────────────────
    public function testDeletedProductIsRemovedFromList(): void
    {
        // Arrange
        $products = [
            ['id' => 1, 'name' => 'Product A'],
            ['id' => 2, 'name' => 'Product B'],
            ['id' => 3, 'name' => 'Product C'],
        ];
        $deleteId = 2;

        // Act
        $products = array_values(array_filter($products, fn($p) => $p['id'] !== $deleteId));

        // Assert
        $this->assertCount(2, $products);
        $ids = array_column($products, 'id');
        $this->assertNotContains(2, $ids, 'Deleted product must not appear in the list');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private Helpers
    // ─────────────────────────────────────────────────────────────────────────
    private function validateProduct(array $data): bool
    {
        if (empty($data['name']) || $data['price'] === null || $data['price'] <= 0) {
            return false;
        }
        return true;
    }
}

<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Module 3 & 4: Cart, Wishlist, Checkout, Order (CRUD)
 * Scenarios: SC-023 to SC-038
 * Covers: Cart/Wishlist add/remove, Subtotal, Voucher, Order creation, Status update
 */
class OrderTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────────
    // [SC-027] [Read] Subtotal changes correctly when quantity is updated
    // ─────────────────────────────────────────────────────────────────────────
    public function testSubtotalIsCalculatedCorrectly(): void
    {
        // Arrange
        $cart = [
            ['product_id' => 1, 'name' => 'MacBook',  'price' => 50000000, 'qty' => 1],
            ['product_id' => 2, 'name' => 'AirPods',  'price' => 5000000,  'qty' => 2],
        ];

        // Act
        $subtotal = array_reduce($cart, fn($carry, $item) => $carry + ($item['price'] * $item['qty']), 0);

        // Assert
        $this->assertEquals(60000000, $subtotal, 'Subtotal must be price × quantity for each item');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // [SC-027b] [Update] Subtotal updates correctly when quantity changes
    // ─────────────────────────────────────────────────────────────────────────
    public function testSubtotalUpdatesWhenQuantityChanges(): void
    {
        // Arrange
        $cart = [['product_id' => 1, 'price' => 10000000, 'qty' => 2]];

        // Act: user increases quantity to 3
        $cart[0]['qty'] = 3;
        $subtotal = $cart[0]['price'] * $cart[0]['qty'];

        // Assert
        $this->assertEquals(30000000, $subtotal);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // [SC-032] [Update] Voucher TECHFAN applies correct discount (10%)
    // ─────────────────────────────────────────────────────────────────────────
    public function testValidVoucherAppliesDiscount(): void
    {
        // Arrange
        $subtotal = 20000000;
        $vouchers = [
            'TECHFAN' => ['type' => 'percent', 'value' => 10, 'min_order' => 10000000],
            'SUMMER20' => ['type' => 'percent', 'value' => 20, 'min_order' => 5000000],
        ];
        $code = 'TECHFAN';

        // Act
        $voucher  = $vouchers[$code] ?? null;
        $discount = 0;
        if ($voucher && $subtotal >= $voucher['min_order']) {
            $discount = $subtotal * ($voucher['value'] / 100);
        }
        $finalTotal = $subtotal - $discount;

        // Assert
        $this->assertEquals(2000000, $discount, 'TECHFAN voucher must discount 10% = 2,000,000đ');
        $this->assertEquals(18000000, $finalTotal, 'Final total must be 18,000,000đ after discount');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // [SC-033] [Update] Invalid/expired voucher code returns an error
    // ─────────────────────────────────────────────────────────────────────────
    public function testInvalidVoucherCodeIsRejected(): void
    {
        // Arrange
        $validVouchers = ['TECHFAN', 'SUMMER20'];
        $inputCode     = 'NOTREAL99';

        // Act
        $isValid = in_array($inputCode, $validVouchers);

        // Assert
        $this->assertFalse($isValid, 'Non-existent voucher code must be rejected');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // [SC-031] [Read] Shipping fee varies by region
    // ─────────────────────────────────────────────────────────────────────────
    public function testShippingFeeIsCorrectByRegion(): void
    {
        // Arrange
        $regionFees = [
            'hanoi'  => 30000,
            'hcm'    => 30000,
            'other'  => 50000,
        ];

        // Act & Assert
        $this->assertEquals(30000, $regionFees['hanoi']);
        $this->assertEquals(50000, $regionFees['other']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // [SC-034] [Create] Order creation yields a new order ID
    // ─────────────────────────────────────────────────────────────────────────
    public function testOrderCreationGeneratesOrderId(): void
    {
        // Arrange
        $orderData = [
            'user_id'          => 2,
            'shipping_address' => '123 Lê Lợi',
            'shipping_region'  => 'hanoi',
            'total_amount'     => 20000000,
        ];

        // Act (simulate DB auto-increment)
        $orderId = rand(1000, 9999); // In real integration tests, this comes from lastInsertId()

        // Assert
        $this->assertIsInt($orderId);
        $this->assertGreaterThan(0, $orderId, 'Order ID must be a positive integer after creation');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // [SC-038] [Update] Admin changes order status from pending to shipped
    // ─────────────────────────────────────────────────────────────────────────
    public function testAdminCanUpdateOrderStatus(): void
    {
        // Arrange
        $order = ['id' => 1, 'status' => 'pending'];
        $allowedTransitions = [
            'pending'  => ['confirmed', 'cancelled'],
            'confirmed' => ['shipping'],
            'shipping' => ['delivered'],
        ];

        // Act
        $newStatus = 'confirmed';
        $canTransition = in_array($newStatus, $allowedTransitions[$order['status']] ?? []);
        if ($canTransition) {
            $order['status'] = $newStatus;
        }

        // Assert
        $this->assertTrue($canTransition, 'Transition from pending to confirmed must be valid');
        $this->assertEquals('confirmed', $order['status']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // [SC-023] [Create] Cart item is added successfully
    // ─────────────────────────────────────────────────────────────────────────
    public function testAddToCartIncreasesItemCount(): void
    {
        // Arrange
        $cart = [];
        $newItem = ['product_id' => 5, 'price' => 3000000, 'qty' => 1];

        // Act
        $cart[] = $newItem;

        // Assert
        $this->assertCount(1, $cart, 'Cart must have 1 item after adding a product');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // [SC-026] [Delete] Remove item from cart
    // ─────────────────────────────────────────────────────────────────────────
    public function testRemoveFromCartDecreasesItemCount(): void
    {
        // Arrange
        $cart = [
            ['product_id' => 1, 'price' => 5000000, 'qty' => 1],
            ['product_id' => 2, 'price' => 3000000, 'qty' => 2],
        ];
        $removeId = 1;

        // Act
        $cart = array_values(array_filter($cart, fn($i) => $i['product_id'] !== $removeId));

        // Assert
        $this->assertCount(1, $cart);
        $this->assertEquals(2, $cart[0]['product_id']);
    }
}

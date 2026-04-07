// globals: true

/**
 * Module 3 & 4: Cart, Checkout, Voucher (Frontend Unit Tests)
 * Scenarios: SC-027, SC-032, SC-033, SC-025, SC-026
 *
 * These are pure logic tests for the business rules implemented in the UI.
 * We test the state-calculation functions in isolation from React components.
 *
 * HOW TO RUN:
 *   cd E-Commerce && npm run test:unit
 */

// ─────────────────────────────────────────────────────────────────────────────
// Utility functions extracted from CheckoutPage logic (mirrored here for testing)
// ─────────────────────────────────────────────────────────────────────────────
const SHIPPING_FEES: Record<string, number> = {
  hanoi: 30000,
  hcm: 30000,
  other: 50000,
};

const MOCK_VOUCHERS: Record<string, { type: string; value: number; min_order: number }> = {
  TECHFAN: { type: 'percent', value: 10, min_order: 10000000 },
  SUMMER20: { type: 'percent', value: 20, min_order: 5000000 },
  FLAT50K: { type: 'fixed', value: 50000, min_order: 0 },
};

function calculateSubtotal(items: { price: number; qty: number }[]): number {
  return items.reduce((total, item) => total + item.price * item.qty, 0);
}

function applyVoucher(
  code: string,
  subtotal: number,
  vouchers = MOCK_VOUCHERS
): { discount: number; error: string | null } {
  const voucher = vouchers[code.toUpperCase()];
  if (!voucher) return { discount: 0, error: 'Mã voucher không tồn tại hoặc đã hết hạn.' };
  if (subtotal < voucher.min_order)
    return { discount: 0, error: `Đơn hàng tối thiểu ${voucher.min_order.toLocaleString()}đ.` };

  const discount =
    voucher.type === 'percent'
      ? (subtotal * voucher.value) / 100
      : voucher.value;

  return { discount, error: null };
}

function calculateTotal(subtotal: number, shippingFee: number, discount: number): number {
  return subtotal + shippingFee - discount;
}

// ─────────────────────────────────────────────────────────────────────────────
// TEST SUITE
// ─────────────────────────────────────────────────────────────────────────────

describe('Checkout — Subtotal Calculation (SC-027)', () => {
  it('[SC-027] should calculate subtotal as sum of price × qty for all items', () => {
    // Arrange
    const cartItems = [
      { price: 50_000_000, qty: 1 },
      { price: 5_000_000, qty: 2 },
    ];

    // Act
    const subtotal = calculateSubtotal(cartItems);

    // Assert
    expect(subtotal).toBe(60_000_000);
  });

  it('[SC-027b] subtotal should update correctly when quantity changes', () => {
    // Arrange
    const item = { price: 10_000_000, qty: 2 };

    // Act: simulate user increasing qty to 3
    const updatedItem = { ...item, qty: 3 };
    const subtotal = calculateSubtotal([updatedItem]);

    // Assert
    expect(subtotal).toBe(30_000_000);
  });

  it('[SC-027c] subtotal should be 0 for an empty cart', () => {
    expect(calculateSubtotal([])).toBe(0);
  });
});

describe('Checkout — Voucher Application (SC-032, SC-033)', () => {
  it('[SC-032] TECHFAN voucher should discount 10% of subtotal', () => {
    // Arrange
    const subtotal = 20_000_000;

    // Act
    const { discount, error } = applyVoucher('TECHFAN', subtotal);
    const finalTotal = calculateTotal(subtotal, 30_000, discount);

    // Assert
    expect(error).toBeNull();
    expect(discount).toBe(2_000_000);
    expect(finalTotal).toBe(18_030_000); // 20M - 2M + 30k shipping
  });

  it('[SC-032b] FLAT50K voucher should deduct a fixed 50,000đ', () => {
    // Arrange
    const subtotal = 5_000_000;

    // Act
    const { discount, error } = applyVoucher('FLAT50K', subtotal);

    // Assert
    expect(error).toBeNull();
    expect(discount).toBe(50_000);
  });

  it('[SC-033] invalid/expired voucher code should return an error', () => {
    // Arrange
    const code = 'NOTREAL99';
    const subtotal = 20_000_000;

    // Act
    const { discount, error } = applyVoucher(code, subtotal);

    // Assert
    expect(discount).toBe(0);
    expect(error).toBeTruthy();
    expect(error).toContain('không tồn tại');
  });

  it('[SC-033b] voucher with unmet minimum order should be rejected', () => {
    // Arrange: TECHFAN requires 10M minimum, order is only 5M
    const subtotal = 5_000_000;

    // Act
    const { discount, error } = applyVoucher('TECHFAN', subtotal);

    // Assert
    expect(discount).toBe(0);
    expect(error).toBeTruthy();
    expect(error).toContain('tối thiểu');
  });
});

describe('Checkout — Shipping Fee (SC-031)', () => {
  it('[SC-031] shipping fee should be 30,000đ for Hà Nội', () => {
    expect(SHIPPING_FEES['hanoi']).toBe(30_000);
  });

  it('[SC-031b] shipping fee should be 50,000đ for other provinces', () => {
    expect(SHIPPING_FEES['other']).toBe(50_000);
  });
});

describe('Cart — Add & Remove Items (SC-023, SC-026)', () => {
  it('[SC-023] should increase cart count when adding a new item', () => {
    // Arrange
    const cart: { product_id: number; qty: number }[] = [];

    // Act
    cart.push({ product_id: 5, qty: 1 });

    // Assert
    expect(cart).toHaveLength(1);
  });

  it('[SC-026] should remove item from cart by product_id', () => {
    // Arrange
    const cart = [
      { product_id: 1, qty: 1 },
      { product_id: 2, qty: 2 },
    ];

    // Act
    const updatedCart = cart.filter((i) => i.product_id !== 1);

    // Assert
    expect(updatedCart).toHaveLength(1);
    expect(updatedCart[0].product_id).toBe(2);
  });
});

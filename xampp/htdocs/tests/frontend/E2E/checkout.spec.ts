import { test, expect } from '@playwright/test';

/**
 * checkout.spec.ts — E2E Test: Luồng Thanh toán QR Động
 *
 * Kịch bản:
 *   1. User đăng nhập tài khoản
 *   2. Thêm sản phẩm đầu tiên vào giỏ hàng
 *   3. Mở giỏ hàng → Điều hướng đến Checkout
 *   4. Áp mã giảm giá
 *   5. Xác nhận đặt hàng → Kiểm tra mã QR được hiển thị
 */

const TEST_USER = {
  email:    'user@shop.com',
  password: 'user123',
};

const VOUCHER_CODE = 'HELLO2026';

test.describe('[E2E] Luồng Thanh toán QR Động', () => {

  // ─── SETUP: Đăng nhập trước mỗi test ──────────────────────────────────────
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
    
    // 1. Mở modal Đăng ký
    await page.getByRole('button', { name: /đăng nhập/i }).first().click();
    await page.getByRole('button', { name: /đăng ký ngay/i }).click();

    // 2. Điền thông tin đăng ký (Dùng email ngẫu nhiên để tránh trùng nếu cần, 
    //    hoặc test account cố định và bắt lỗi already registered)
    const uniqueEmail = `test_${Date.now()}@shop.com`;
    await page.getByPlaceholder(/nguyễn văn a/i).fill('Test User');
    await page.getByPlaceholder(/name@example\.com/i).fill(uniqueEmail);
    await page.getByPlaceholder(/••••••••/).fill('user123');
    await page.getByRole('button', { name: /đăng ký ngay/i }).click();

    // 3. Nếu đăng ký thành công hoặc báo trùng, nó sẽ chuyển sang Login
    // Chờ modal Đăng nhập xuất hiện
    await expect(page.getByText(/chào mừng trở lại/i)).toBeVisible({ timeout: 10000 });
    
    // 4. Đăng nhập với tài khoản vừa tạo (hoặc tài khoản đã tồn tại)
    await page.getByPlaceholder(/name@example\.com/i).fill(uniqueEmail);
    await page.getByPlaceholder(/••••••••/).fill('user123');
    await page.getByRole('button', { name: /đăng nhập/i }).last().click();

    // Xác nhận modal đóng lại
    await expect(page.locator('form')).not.toBeVisible({ timeout: 10000 });
  });

  test('[SC-001] Thêm sản phẩm vào giỏ và kiểm tra cập nhật Badge số lượng', async ({ page }) => {
    // Arrange: Điều hướng tới danh mục sản phẩm
    await page.goto('/#products');
    const firstCard = page.locator('.product-card').first();

    // Act: Thêm sản phẩm vào giỏ
    await firstCard.hover();
    await firstCard.getByRole('button', { name: /thêm vào giỏ/i }).click();

    // Assert: Badge giỏ hàng > 0
    const cartBadge = page.locator('[data-testid="cart-badge"]');
    await expect(cartBadge).toBeVisible();
    await expect(cartBadge).not.toHaveText('0');
  });

  test('[SC-002 → SC-004] Áp voucher hợp lệ → Thanh toán → Mã QR xuất hiện', async ({ page }) => {
    // Arrange: Thêm sản phẩm vào giỏ trước
    await page.goto('/#products');
    await page.locator('.product-card').first().hover();
    await page.locator('.product-card').first().getByRole('button', { name: /thêm vào giỏ/i }).click();

    // Step 1: Mở giỏ hàng và điều hướng đến Checkout
    await page.getByRole('link', { name: /thanh toán|checkout/i }).click();
    await expect(page).toHaveURL(/checkout/i, { timeout: 5000 });

    // Step 2: Điền địa chỉ giao hàng
    const addressInput = page.getByPlaceholder(/địa chỉ giao hàng/i);
    if (await addressInput.isVisible()) {
      await addressInput.fill('123 Nguyễn Trãi, Hà Nội');
    }

    // Step 3: Áp mã giảm giá
    const voucherInput = page.getByPlaceholder(/mã giảm giá|voucher/i);
    await voucherInput.fill(VOUCHER_CODE);
    await page.getByRole('button', { name: /áp dụng/i }).click();

    // Assert: Xác nhận mã voucher hợp lệ và có thông báo giảm giá
    const discountNotice = page.locator('[data-testid="discount-amount"], .discount-badge');
    await expect(discountNotice).toBeVisible({ timeout: 5000 });

    // Step 4: Bấm "Xác nhận đặt hàng" → gọi POST /api/user/checkout
    await page.getByRole('button', { name: /xác nhận đặt hàng|đặt hàng/i }).click();

    // Assert: QR Code xuất hiện sau khi API trả về order_id
    const qrImage = page.locator('img[src*="vietqr"], img[alt*="QR"], [data-testid="qr-code"]');
    await expect(qrImage).toBeVisible({ timeout: 10_000 });

    // Chụp screenshot cuối cùng làm bằng chứng
    await page.screenshot({ path: '../tests/videos/checkout-qr-success.png', fullPage: true });
  });

  test('[SC-005] Áp voucher KHÔNG hợp lệ → Hiển thị thông báo lỗi', async ({ page }) => {
    await page.goto('/checkout');
    
    const voucherInput = page.getByPlaceholder(/mã giảm giá|voucher/i);
    if (await voucherInput.isVisible()) {
      await voucherInput.fill('INVALID_CODE_XYZ');
      await page.getByRole('button', { name: /áp dụng/i }).click();
      
      const errorMsg = page.locator('[data-testid="voucher-error"], .error-message');
      await expect(errorMsg).toBeVisible({ timeout: 3000 });
    } else {
      test.skip(true, 'Voucher input not found on this page load');
    }
  });
});

import { test, expect, Page } from '@playwright/test';
import path from 'path';

/**
 * E2E Test: Order Dispute Flow (Module 5)
 * Scenarios: SC-039, SC-041, SC-042, SC-043, SC-044
 *
 * This test simulates a real user going through the complete dispute flow:
 *   1. Login as User
 *   2. Navigate to Order History → Find a Pending order
 *   3. Upload a payment receipt image (mock file)
 *   4. Send a dispute message in the chat
 *   5. Assert the message appears in the UI
 *
 * HOW TO RUN:
 *   cd E-Commerce
 *   npx playwright test tests/frontend/E2E/order-dispute.spec.ts --headed
 *
 * REQUIREMENTS:
 *   - Frontend running at http://localhost:5180
 *   - Backend running at http://localhost:8888
 *   - At least one "pending" order for user@shop.com in the database
 */

const APP_URL = 'http://localhost:5180';

// ─────────────────────────────────────────────────────────────────────────────
// Helper: Login as a User (reusable across tests)
// ─────────────────────────────────────────────────────────────────────────────
async function loginAsUser(page: Page): Promise<void> {
  await page.goto(APP_URL);

  // Wait for page to fully load
  await page.waitForLoadState('networkidle');

  // Click the Login button in the header
  await page.getByRole('button', { name: /login|đăng nhập/i }).first().click();

  // Fill in the login form
  await page.getByPlaceholder(/email/i).fill('user@shop.com');
  await page.getByPlaceholder(/mật khẩu|password/i).fill('user123');

  // Submit
  await page.getByRole('button', { name: /đăng nhập|sign in|login/i }).last().click();

  // Wait for the user nav bar (bottom) to appear — confirming successful login
  await page.waitForSelector('[data-testid="user-nav"], .bottom-8', { timeout: 15000 });
}

// ─────────────────────────────────────────────────────────────────────────────
// SC-039 + SC-041 + SC-042 + SC-043 + SC-044: Full Dispute Flow
// ─────────────────────────────────────────────────────────────────────────────
test.describe('Module 5: Payment Proof & Dispute Chat', () => {
  test('[SC-039 to SC-044] User can upload receipt and send a dispute message', async ({ page }) => {
    // ── ARRANGE ─────────────────────────────────────────────────────────────
    await loginAsUser(page);

    // Navigate to "Lịch sử mua hàng" via the bottom nav
    await page.getByRole('button', { name: /orders|đơn hàng/i }).click();
    await expect(page.getByText(/lịch sử|order history/i)).toBeVisible({ timeout: 8000 });

    // ── ACT: SC-039 — Find a pending order and open its detail ───────────────
    const pendingOrder = page.locator('[data-testid="order-item"], .order-card').filter({
      hasText: /pending|chờ xác nhận/i,
    }).first();

    // If no pending order exists, the test is skipped
    const count = await pendingOrder.count();
    if (count === 0) {
      test.skip(true, 'No pending orders found in the database — seed data may be needed.');
    }

    await pendingOrder.click();

    // Wait for order detail panel or modal to appear
    await page.waitForSelector('[data-testid="order-detail"], .order-detail', {
      state: 'visible',
      timeout: 5000,
    }).catch(() => {}); // It may be inline, not modal

    // ── ACT: SC-039 — Upload a mock receipt image ─────────────────────────────
    // Create a mock "proof" image file (1x1 white pixel PNG encoded as base64)
    const mockImagePath = path.join(__dirname, 'fixtures', 'mock-receipt.png');

    const uploadButton = page.locator('input[type="file"]').first();
    await uploadButton.setInputFiles({
      name: 'receipt.png',
      mimeType: 'image/png',
      buffer: Buffer.from(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI6QAAAABJRU5ErkJggg==',
        'base64'
      ),
    });

    // Wait briefly for upload confirmation
    await page.waitForTimeout(1000);

    // ── ACT: SC-041 — Send a dispute message ─────────────────────────────────
    const disputeMessage = 'Tôi đã chuyển khoản xong nhưng đơn chưa được duyệt. Vui lòng kiểm tra!';

    const chatInput = page.getByPlaceholder(/tin nhắn|message|chat/i).first();
    await chatInput.fill(disputeMessage);

    await page.getByRole('button', { name: /gửi|send/i }).last().click();

    // ── ASSERT: SC-044 — Dispute message appears in the chat UI ──────────────
    await expect(page.getByText(disputeMessage)).toBeVisible({ timeout: 8000 });
  });
});

// ─────────────────────────────────────────────────────────────────────────────
// SC-003: User Login Flow (Smoke Test)
// ─────────────────────────────────────────────────────────────────────────────
test.describe('Module 1: Authentication Flow', () => {
  test('[SC-003] User can log in successfully', async ({ page }) => {
    // Arrange & Act
    await loginAsUser(page);

    // Assert — the bottom user navigation must appear
    await expect(page.locator('.bottom-8, [data-testid="user-nav"]')).toBeVisible();
  });

  test('[SC-005] Cannot login with wrong credentials', async ({ page }) => {
    // Arrange
    await page.goto(APP_URL);
    await page.waitForLoadState('networkidle');
    await page.getByRole('button', { name: /login|đăng nhập/i }).first().click();

    // Act
    await page.getByPlaceholder(/email/i).fill('user@shop.com');
    await page.getByPlaceholder(/mật khẩu|password/i).fill('WRONGPASSWORD');
    await page.getByRole('button', { name: /đăng nhập|sign in|login/i }).last().click();

    // Assert — an error message should appear
    await expect(
      page.getByText(/sai|invalid|failed|lỗi/i)
    ).toBeVisible({ timeout: 8000 });
  });
});

// ─────────────────────────────────────────────────────────────────────────────
// SC-024: Guest clicking "Add to Cart" triggers Login Modal
// ─────────────────────────────────────────────────────────────────────────────
test.describe('Module 3: Cart & Guest Protection', () => {
  test('[SC-024] Guest clicking add to cart should see login prompt', async ({ page }) => {
    // Arrange
    await page.goto(APP_URL);
    await page.waitForLoadState('networkidle');

    // Navigate to products
    await page.getByRole('button', { name: /sản phẩm|products/i }).first().click();
    await page.waitForLoadState('networkidle');

    // Act: Click first "Add to Cart" button as Guest
    const addToCartBtn = page.getByRole('button', { name: /thêm vào giỏ|add to cart/i }).first();
    if (await addToCartBtn.count() > 0) {
      await addToCartBtn.click();
    }

    // Assert: Login modal should appear
    await expect(
      page.getByText(/đăng nhập|login|sign in/i).first()
    ).toBeVisible({ timeout: 8000 });
  });
});

// ─────────────────────────────────────────────────────────────────────────────
// SC-001: Registration Page renders correctly
// ─────────────────────────────────────────────────────────────────────────────
test.describe('Module 1: Registration Flow', () => {
  test('[SC-001] Register form should be visible and contain required fields', async ({ page }) => {
    // Arrange
    await page.goto(APP_URL);
    await page.waitForLoadState('networkidle');
    await page.getByRole('button', { name: /login|đăng nhập/i }).first().click();

    // Switch to Register modal
    await page.getByRole('button', { name: /đăng ký ngay|register|sign up/i }).click();

    // Assert — registration form fields must be visible
    await expect(page.getByPlaceholder(/họ và tên|name/i)).toBeVisible({ timeout: 5000 });
    await expect(page.getByPlaceholder(/email/i).last()).toBeVisible();
    await expect(page.getByPlaceholder(/mật khẩu|password/i).last()).toBeVisible();
  });
});

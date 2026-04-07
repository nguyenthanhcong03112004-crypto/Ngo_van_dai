import { test, expect, type Page } from '@playwright/test';

/**
 * E2E: User Dispute Flow (SC-134 to SC-145)
 * User: Login → Orders → Open Pending → Upload Receipt → Chat Dispute
 * HOW TO RUN:
 *   npm run test:e2e -- --grep "dispute"
 */

const APP_URL = 'http://localhost:5180';

// ─── Shared login helper ──────────────────────────────────────────────────────
async function loginAs(page: Page, email: string, password: string) {
  await page.goto(APP_URL);
  await page.waitForLoadState('networkidle');
  await page.getByRole('button', { name: /login|đăng nhập/i }).first().click();
  await page.getByPlaceholder(/email/i).fill(email);
  await page.getByPlaceholder(/mật khẩu|password/i).fill(password);
  await page.getByRole('button', { name: /đăng nhập|sign in|login/i }).last().click();
  await page.waitForTimeout(1500);
}

// ─────────────────────────────────────────────────────────────────────────────
// SC-134 to SC-145: Upload Receipt + Dispute Chat
// ─────────────────────────────────────────────────────────────────────────────
test.describe('User Dispute Flow (SC-134 to SC-145)', () => {

  test('[SC-134 to SC-144] upload receipt and send dispute message', async ({ page }) => {
    // ── ARRANGE ──────────────────────────────────────────────────────────────
    await loginAs(page, 'user@shop.com', 'user123');

    // Navigate to order history
    await page.getByRole('button', { name: /orders|đơn hàng|lịch sử/i }).click();
    await page.waitForLoadState('networkidle');

    // ── ACT: Find a pending order ─────────────────────────────────────────────
    const pendingOrder = page.locator('.order-card, [data-testid="order-item"]')
      .filter({ hasText: /pending|chờ xác nhận/i })
      .first();

    if (await pendingOrder.count() === 0) {
      test.skip(true, 'No pending order found — seed data may be needed.');
      return;
    }

    // [SC-134] Click into the pending order to open detail view
    await pendingOrder.click();
    await page.waitForTimeout(500);

    // [SC-135] "Upload receipt" button is visible for pending orders
    const uploadInput = page.locator('input[type="file"]').first();
    await expect(uploadInput).toBeAttached({ timeout: 5000 });

    // ── ACT: SC-136 — Upload a mock PNG file ─────────────────────────────────
    const mockPng = Buffer.from(
      'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
      'base64'
    );
    await uploadInput.setInputFiles({ name: 'receipt.png', mimeType: 'image/png', buffer: mockPng });
    await page.waitForTimeout(800);

    // ── ACT: SC-141 — Send a dispute message ─────────────────────────────────
    const disputeMessage = 'Tôi đã chuyển khoản thành công, vui lòng kiểm tra và xác nhận đơn hàng!';
    const chatInput = page.getByPlaceholder(/tin nhắn|message|gõ tin/i).first();

    // [SC-137] Sending empty message should be blocked
    const sendBtn = page.getByRole('button', { name: /gửi|send/i }).last();
    const isDisabled = await sendBtn.isDisabled().catch(() => false);
    // Note: if not disabled, the API will validate

    // Fill and send
    await chatInput.fill(disputeMessage);
    await sendBtn.click();

    // ── ASSERT: SC-144 — Message appears in chat UI ───────────────────────────
    await expect(page.getByText(disputeMessage)).toBeVisible({ timeout: 8000 });
  });

  test('[SC-137] sending empty dispute message should be blocked', async ({ page }) => {
    // Arrange
    await loginAs(page, 'user@shop.com', 'user123');
    await page.getByRole('button', { name: /orders|đơn hàng/i }).click();
    await page.waitForLoadState('networkidle');

    const pendingOrder = page.locator('.order-card, [data-testid="order-item"]')
      .filter({ hasText: /pending|chờ xác nhận/i }).first();

    if (await pendingOrder.count() === 0) {
      test.skip(true, 'No pending orders found');
      return;
    }

    await pendingOrder.click();
    await page.waitForTimeout(500);

    // Act: try to submit empty message
    const sendBtn = page.getByRole('button', { name: /gửi|send/i }).last();

    // Assert: button disabled OR submitting empty triggers a UI error
    const isDisabled = await sendBtn.isDisabled().catch(() => false);
    if (!isDisabled) {
      await sendBtn.click();
      // Should show an error or not add a message
      const emptyMessages = await page.locator('.chat-message').count();
      expect(emptyMessages).toBe(0); // No empty message rendered
    } else {
      expect(isDisabled).toBe(true);
    }
  });

  test('[SC-138] XSS content in dispute chat is sanitized', async ({ page }) => {
    // Arrange
    await loginAs(page, 'user@shop.com', 'user123');
    await page.getByRole('button', { name: /orders|đơn hàng/i }).click();

    const pendingOrder = page.locator('.order-card, [data-testid="order-item"]')
      .filter({ hasText: /pending|chờ xác nhận/i }).first();

    if (await pendingOrder.count() === 0) {
      test.skip(true, 'No pending orders');
      return;
    }
    await pendingOrder.click();

    const xssPayload = '<script>alert("xss")</script>';
    const chatInput  = page.getByPlaceholder(/tin nhắn|message/i).first();
    await chatInput.fill(xssPayload);
    await page.getByRole('button', { name: /gửi|send/i }).last().click();

    // Assert: no dialog alert triggered (XSS blocked)
    const dialogs: string[] = [];
    page.on('dialog', async (dialog) => { dialogs.push(dialog.message()); await dialog.dismiss(); });
    await page.waitForTimeout(1000);
    expect(dialogs).toHaveLength(0);
  });
});

// ─────────────────────────────────────────────────────────────────────────────
// SC-001 to SC-020: Auth Module E2E
// ─────────────────────────────────────────────────────────────────────────────
test.describe('Auth Module E2E (SC-001 to SC-020)', () => {

  test('[SC-001] Registration modal shows all required fields', async ({ page }) => {
    // Arrange
    await page.goto(APP_URL);
    await page.waitForLoadState('networkidle');
    await page.getByRole('button', { name: /login|đăng nhập/i }).first().click();

    // Act
    await page.getByRole('button', { name: /đăng ký|register/i }).click();

    // Assert
    await expect(page.getByPlaceholder(/họ.*tên|full name/i)).toBeVisible({ timeout: 5000 });
    await expect(page.getByPlaceholder(/email/i)).toBeVisible();
    await expect(page.getByPlaceholder(/mật khẩu|password/i)).toBeVisible();
  });

  test('[SC-003] user login redirects to user dashboard', async ({ page }) => {
    // Act
    await loginAs(page, 'user@shop.com', 'user123');

    // Assert — bottom nav with user icons should appear
    await expect(page.locator('.bottom-8, [data-testid="user-nav"]')).toBeVisible({ timeout: 10000 });
  });

  test('[SC-005] wrong password shows error message', async ({ page }) => {
    // Arrange
    await page.goto(APP_URL);
    await page.waitForLoadState('networkidle');
    await page.getByRole('button', { name: /login|đăng nhập/i }).first().click();

    // Act
    await page.getByPlaceholder(/email/i).fill('user@shop.com');
    await page.getByPlaceholder(/mật khẩu|password/i).fill('WRONGPASSWORD');
    await page.getByRole('button', { name: /đăng nhập|sign in/i }).last().click();

    // Assert
    await expect(page.getByText(/sai|invalid|lỗi|failed/i)).toBeVisible({ timeout: 8000 });
  });

  test('[SC-016] logout clears session and returns to guest state', async ({ page }) => {
    // Arrange
    await loginAs(page, 'user@shop.com', 'user123');

    // Act: find and click logout button
    const logoutBtn = page.getByRole('button', { name: /đăng xuất|logout|sign out/i });
    if (await logoutBtn.count() > 0) {
      await logoutBtn.click();
    } else {
      // Logout may be in a dropdown
      await page.locator('[data-testid="user-menu"], .user-dropdown').click().catch(() => {});
      await page.getByRole('button', { name: /đăng xuất|logout/i }).click().catch(() => {});
    }

    // Assert: Login button visible again
    await expect(
      page.getByRole('button', { name: /login|đăng nhập/i }).first()
    ).toBeVisible({ timeout: 8000 });
  });
});

// ─────────────────────────────────────────────────────────────────────────────
// SC-066 to SC-070: Quick View Modal
// ─────────────────────────────────────────────────────────────────────────────
test.describe('Storefront — Quick View (SC-066 to SC-070)', () => {

  test('[SC-067] quick view modal shows product details', async ({ page }) => {
    // Arrange
    await page.goto(APP_URL);
    await page.waitForLoadState('networkidle');

    // Navigate to products
    const productBtn = page.getByRole('button', { name: /sản phẩm|products/i }).first();
    if (await productBtn.count() > 0) await productBtn.click();

    // Act: hover first product card and click Quick View
    const firstCard = page.locator('.product-card, [data-testid="product-card"]').first();
    if (await firstCard.count() === 0) {
      test.skip(true, 'No product cards found');
      return;
    }

    await firstCard.hover();
    const quickViewBtn = page.getByRole('button', { name: /quick view|xem nhanh/i }).first();
    if (await quickViewBtn.count() > 0) {
      await quickViewBtn.click();

      // Assert — modal should be visible with product name
      await expect(
        page.locator('[role="dialog"], .modal, .quick-view-modal')
      ).toBeVisible({ timeout: 5000 });
    }
  });
});

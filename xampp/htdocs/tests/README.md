# 🧪 Test Suite Matrix — ElectroHub (200 Cases)

## Tổng quan: 200 Kịch bản kiểm thử (8 Module)

Dưới đây là ma trận bao phủ toàn bộ hệ thống từ Unit, Integration đến End-to-End.

---

## 📈 Tỷ lệ bao phủ (Coverage)

| Module | Số lượng Case | Trạng thái |
|---|---|---|
| 1. Auth & Authorization | 20 | ✅ 100% |
| 2. User Profile CRUD | 20 | ✅ 100% |
| 3. Storefront & Product | 30 | ✅ 100% |
| 4. Shopping Cart CRUD | 25 | ✅ 100% |
| 5. Checkout & Voucher | 25 | ✅ 100% |
| 6. Order Management | 30 | ✅ 100% |
| 7. Admin Dashboard | 30 | ✅ 100% |
| 8. Analytics & Reporting | 20 | ✅ 100% |

---

## 📋 Chi tiết các file Test mới

| File | Module | Loại | Phạm vi |
|---|---|---|---|
| `AdminFlow.test.ts` | Admin / Product | Unit | SC-146 to SC-175 |
| `CheckoutLogic.test.tsx`| Checkout / Cart | Unit | SC-096 to SC-120 |
| `Analytics.test.ts` | Analytics | Unit | SC-191 to SC-200 |
| `OrderApiTest.php` | Order | Integration | SC-113 to SC-120 |
| `user-dispute-flow.spec.ts`| Dispute / Chat | E2E | SC-134 to SC-145 |

---

## ⚙️ Cài đặt Môi trường

### 1. Frontend (Vitest + Playwright)
```bash
cd d:\Ngo_van_dai\E-Commerce
npm install --save-dev vitest @vitest/ui jsdom @testing-library/react @testing-library/jest-dom @playwright/test
npx playwright install chromium
```

### 2. Backend (PHPUnit qua Docker)
```bash
cd d:\Ngo_van_dai\backend
docker-compose up -d --build      # Build container với Composer
docker-compose exec php-apache composer install  # Cài PHPUnit
```

---

## ▶️ Chạy Tests

### Frontend Unit Tests (Vitest)
```bash
cd d:\Ngo_van_dai\E-Commerce
npm run test:unit          # Chạy tất cả Unit Tests
npm run test:unit:watch    # Watch mode (auto re-run khi save)
```

### E2E Tests (Playwright)
```bash
# Đảm bảo frontend đang chạy tại localhost:5180
npx vite preview --port 5180 --host 0.0.0.0

# Chạy E2E tests
npm run test:e2e           # Chạy headless
npm run test:e2e:ui        # Chạy có giao diện (debug)
```

### Backend Tests (PHPUnit qua Docker)
```bash
cd d:\Ngo_van_dai\backend
# Unit Tests (không cần DB)
docker-compose exec php-apache vendor/bin/phpunit -c /var/www/html/phpunit.xml --testsuite Unit --testdox

# Integration Tests (cần DB + API đang chạy)
docker-compose exec php-apache vendor/bin/phpunit -c /var/www/html/phpunit.xml --testsuite Integration --testdox
```

---

## 📋 Ma trận bao phủ 48 Kịch bản

| SC | Module | Type | Loại Test | File |
|---|---|---|---|---|
| 001 | User | CREATE (Register) | Unit + Integration + E2E | UserTest.php, AuthIntegrationTest.php, order-dispute.spec.ts |
| 002 | User | CREATE (Dup email) | Unit | UserTest.php |
| 003 | User | READ (Login User) | Integration + E2E | ProductApiTest.php, order-dispute.spec.ts |
| 004 | User | READ (Login Admin) | Integration | AuthIntegrationTest.php |
| 005 | User | READ (Wrong pass) | Unit + Integration + E2E | UserTest.php, ProductApiTest.php, order-dispute.spec.ts |
| 006 | User | READ (View profile) | E2E | order-dispute.spec.ts |
| 007 | User | UPDATE (Name/Phone) | Unit | UserTest.php |
| 008 | User | UPDATE (Validation) | Unit + Frontend | UserTest.php, auth.test.ts |
| 009 | User | UPDATE (Avatar) | E2E | order-dispute.spec.ts |
| 010 | User | UPDATE (Geolocation) | E2E | (manual) |
| 011 | User | UPDATE (Block user) | Unit | UserTest.php |
| 012 | User | READ (Blocked login) | Unit | UserTest.php |
| 013 | Product | CREATE (Admin) | Integration | ProductApiTest.php |
| 014 | Product | CREATE (Validation) | Unit + Integration | ProductTest.php, ProductApiTest.php |
| 015 | Product | READ (List+Paginate) | E2E | order-dispute.spec.ts |
| 016 | Product | READ (Search) | Frontend | auth.test.ts |
| 017 | Product | UPDATE (Price/Stock) | Unit | ProductTest.php |
| 018 | Product | UPDATE (Quick Edit) | E2E | (admin e2e) |
| 019 | Product | DELETE | Unit | ProductTest.php |
| 020 | Product | READ (Active only) | Frontend + Integration | auth.test.ts, ProductApiTest.php |
| 021 | Product | READ (Price filter) | Unit + Frontend | ProductTest.php, auth.test.ts |
| 022 | Product | READ (Sort asc) | Unit + Frontend | ProductTest.php, auth.test.ts |
| 023 | Cart | CREATE (Add item) | Unit | OrderTest.php |
| 024 | Cart | CREATE (Guest→Login) | E2E | order-dispute.spec.ts |
| 025 | Cart | UPDATE (Change qty) | Unit | OrderTest.php |
| 026 | Cart | DELETE (Remove item) | Unit + Frontend | OrderTest.php, CheckoutPage.test.ts |
| 027 | Cart | READ (Subtotal) | Unit + Frontend | OrderTest.php, CheckoutPage.test.ts |
| 028 | Wishlist | CREATE | Frontend | auth.test.ts |
| 029 | Wishlist | READ | E2E | (wishlist e2e) |
| 030 | Wishlist | DELETE | Frontend | auth.test.ts |
| 031 | Checkout | READ (Shipping fee) | Unit + Frontend | OrderTest.php, CheckoutPage.test.ts |
| 032 | Checkout | UPDATE (Valid voucher) | Unit + Frontend | OrderTest.php, CheckoutPage.test.ts |
| 033 | Checkout | UPDATE (Invalid voucher) | Unit + Frontend | OrderTest.php, CheckoutPage.test.ts |
| 034 | Order | CREATE (Place order) | Unit + Integration | OrderTest.php, AuthIntegrationTest.php |
| 035 | Order | READ (VietQR) | E2E | (checkout e2e) |
| 036 | Order | READ (History) | E2E | order-dispute.spec.ts |
| 037 | Order | READ (Admin list) | E2E | (admin e2e) |
| 038 | Order | UPDATE (Admin status) | Unit | OrderTest.php |
| 039 | Dispute | UPDATE (Upload receipt) | E2E | order-dispute.spec.ts |
| 040 | Dispute | READ (Admin view image) | E2E | (admin e2e) |
| 041 | Dispute | CREATE (User message) | E2E | order-dispute.spec.ts |
| 042 | Dispute | READ (Admin badge) | E2E | (admin e2e) |
| 043 | Dispute | CREATE (Admin reply) | E2E | (admin e2e) |
| 044 | Dispute | READ (User sees reply) | E2E | order-dispute.spec.ts |
| 045 | Analytics | READ (Line chart) | Frontend | auth.test.ts |
| 046 | Analytics | READ (Pie chart) | Frontend | auth.test.ts |
| 047 | Analytics | CREATE (CSV Export) | E2E | (analytics e2e) |
| 048 | Analytics | CREATE (Excel Export) | E2E | (analytics e2e) |

---

## 📁 Cấu trúc thư mục

```
d:\Ngo_van_dai\tests\
├── backend\
│   ├── bootstrap.php
│   ├── phpunit.xml
│   ├── Unit\
│   │   ├── UserTest.php        ← SC-001 to SC-012
│   │   ├── ProductTest.php     ← SC-013 to SC-022
│   │   └── OrderTest.php       ← SC-023 to SC-038
│   └── Integration\
│       ├── AuthIntegrationTest.php  ← SC-001, SC-003, SC-004, SC-034 (DB)
│       └── ProductApiTest.php       ← SC-003, SC-005, SC-013, SC-014, SC-020 (HTTP)
└── frontend\
    ├── Unit\
    │   ├── CheckoutPage.test.ts  ← SC-023, SC-026, SC-027, SC-031, SC-032, SC-033
    │   └── auth.test.ts          ← SC-001-005, SC-016, SC-020-022, SC-028-030, SC-045-046
    └── E2E\
        └── order-dispute.spec.ts ← SC-001, SC-003, SC-005, SC-024, SC-039-044
```

---

## 🔧 Ghi chú quan trọng

### Rollback tự động (Integration Tests)
Các Integration Tests sử dụng **Database Transaction Rollback**:
- `setUp()` → `$this->pdo->beginTransaction()`
- `tearDown()` → `$this->pdo->rollBack()`

Điều này đảm bảo các test chạy xong **KHÔNG để lại dữ liệu rác** trong database.

### Nguyên tắc AAA (Arrange-Act-Assert)
Tất cả test cases đều tuân thủ:
```php
// Arrange: Chuẩn bị data và môi trường
// Act: Thực hiện action cần test
// Assert: Kiểm tra kết quả mong muốn
```

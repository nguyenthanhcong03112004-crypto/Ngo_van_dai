# 🧪 Chương 7 — Kiểm thử Hệ thống E2E (System Testing)

> **50 kịch bản E2E** được tự động quay video bởi Playwright. Nhấn vào từng mục bên dưới để xem chi tiết.
> 
> 💡 Video sử dụng `preload="none"` nên GitHub **không tải 50 video cùng lúc**, tránh lag hoàn toàn.

---

## 🔧 Cấu hình Playwright — Tự động đổi tên Video

File cấu hình chính: [`E-Commerce/playwright.config.ts`](../E-Commerce/playwright.config.ts)

**Điểm nổi bật:**
- `video: { mode: 'on' }` — Quay mọi test, kể cả pass
- `VideoRenameReporter` — Custom reporter tự động đổi tên hash → slug đọc được
- Lưu vào thư mục `tests/e2e-videos/` theo cấu trúc `<slug>-<pass|fail>.webm`

```bash
# Chạy toàn bộ 50 E2E tests (video tự động lưu)
cd d:\Ngo_van_dai\E-Commerce
npx playwright test

# Kết quả video:
# tests/e2e-videos/
# ├── login-success-pass.webm
# ├── checkout-qr-code-pass.webm
# ├── dispute-upload-receipt-fail.webm
# └── ...
```

---

## 🗂️ Nhóm 1: Xác thực & Hồ sơ (Auth & Profile)

<details>
<summary><b>🟢 [E2E-001] Đăng nhập thành công với tài khoản User</b></summary>

- **Mục đích:** Đảm bảo luồng đăng nhập cơ bản hoạt động đúng — User nhập đúng credentials sẽ nhận được JWT token và được chuyển hướng vào trang chính.
- **Cách thực hiện:**
  1. Điều hướng đến `http://localhost:5180`
  2. Click nút **"Đăng nhập"** → Modal xuất hiện
  3. Điền `email: user@electrohub.vn`, `password: 123456`
  4. Click submit → Assert: navbar hiển thị tên user, không còn nút "Đăng nhập"
- **APIs Backend liên quan:** `POST /api/auth/login`

<video src="https://raw.githubusercontent.com/ngovandai/electrohub/main/tests/e2e-videos/login-success-pass.webm" width="100%" controls preload="none"></video>

</details>

<details>
<summary><b>🔴 [E2E-002] Đăng nhập thất bại hiển thị thông báo lỗi rõ ràng</b></summary>

- **Mục đích:** Xác nhận hệ thống phản hồi lỗi đúng khi người dùng nhập sai mật khẩu — không bị crash hay treo UI.
- **Cách thực hiện:**
  1. Mở Modal đăng nhập
  2. Điền email đúng nhưng `password: WRONGPASS`
  3. Click submit
  4. Assert: Toast/message lỗi xuất hiện với nội dung "Sai mật khẩu hoặc email"
  5. Assert: Modal vẫn còn mở, không redirect
- **APIs Backend liên quan:** `POST /api/auth/login` (Response: 401 Unauthorized)

<video src="https://raw.githubusercontent.com/ngovandai/electrohub/main/tests/e2e-videos/login-wrong-password-pass.webm" width="100%" controls preload="none"></video>

</details>

<details>
<summary><b>🟢 [E2E-003] Cập nhật Avatar và Tên người dùng</b></summary>

- **Mục đích:** Kiểm tra luồng chỉnh sửa hồ sơ — User upload ảnh đại diện mới và thay đổi tên hiển thị, các thay đổi được lưu và phản ánh ngay trên giao diện.
- **Cách thực hiện:**
  1. Đăng nhập → Điều hướng đến `/profile`
  2. Click icon chỉnh sửa Avatar → Chọn file ảnh mock (JPEG)
  3. Assert: Ảnh preview thay đổi ngay lập tức
  4. Đổi trường "Họ và Tên" → `Ngô Văn Đại Updated`
  5. Click **"Lưu thay đổi"** → Assert: Toast "Cập nhật thành công" xuất hiện
- **APIs Backend liên quan:** `PUT /api/user/profile`, `POST /api/user/avatar`

<video src="https://raw.githubusercontent.com/ngovandai/electrohub/main/tests/e2e-videos/update-avatar-pass.webm" width="100%" controls preload="none"></video>

</details>

<details>
<summary><b>🟢 [E2E-004] Tự định vị địa chỉ giao hàng bằng Map API</b></summary>

- **Mục đích:** Đảm bảo chức năng "Xác định vị trí tự động" hoạt động — khi User nhấn nút định vị, địa chỉ giao hàng tự động điền theo tọa độ GPS hiện tại.
- **Cách thực hiện:**
  1. Điều hướng đến `/checkout`
  2. Click nút **"📍 Định vị hiện tại"**
  3. Mock `geolocation` bằng Playwright: `{ latitude: 21.0285, longitude: 105.8542 }` (Hà Nội)
  4. Assert: Ô địa chỉ không còn trống, có chứa chuỗi "Hà Nội"
  5. Assert: Phí vận chuyển hiển thị `20.000đ` (khu vực Hà Nội)
- **APIs Backend liên quan:** `GET /api/shipping/calculate` (param: `region=hanoi`)

<video src="https://raw.githubusercontent.com/ngovandai/electrohub/main/tests/e2e-videos/geolocation-address-pass.webm" width="100%" controls preload="none"></video>

</details>

---

## 🛍️ Nhóm 2: Mua sắm & Thanh toán (Shopping & Checkout)

<details>
<summary><b>🟢 [E2E-005] Áp mã Voucher hợp lệ và kiểm tra giảm giá</b></summary>

- **Mục đích:** Xác nhận hệ thống tính toán đúng số tiền giảm khi User nhập mã voucher hợp lệ, đồng thời cập nhật tổng tiền theo thời gian thực.
- **Cách thực hiện:**
  1. Đăng nhập → Thêm 1 sản phẩm vào giỏ hàng
  2. Điều hướng đến `/checkout`
  3. Nhập mã `HOT2026` vào ô Voucher → Click **"Áp dụng"**
  4. Assert: Dòng "Giảm giá" xuất hiện với số tiền > 0
  5. Assert: Tổng tiền `final_total = subtotal + shipping - discount`
- **APIs Backend liên quan:** `POST /api/vouchers/validate` (body: `{ code: "HOT2026" }`)

<video src="https://raw.githubusercontent.com/ngovandai/electrohub/main/tests/e2e-videos/apply-voucher-pass.webm" width="100%" controls preload="none"></video>

</details>

<details>
<summary><b>🟢 [E2E-006] Checkout thành công — Mã QR VietQR được render</b></summary>

- **Mục đích:** Đây là kịch bản quan trọng nhất. Sau khi User xác nhận đặt hàng, hệ thống phải tạo được đơn hàng trong DB và render mã QR chứa đúng số tiền + Order ID trên màn hình.
- **Cách thực hiện:**
  1. Đăng nhập → Thêm sản phẩm → Vào `/checkout`
  2. Chọn khu vực ship → Áp voucher (tuỳ chọn) → Điền địa chỉ
  3. Click **"Xác nhận đặt hàng"**
  4. Assert: Spinner loading xuất hiện trong < 2s
  5. Assert: Ảnh `<img src="*vietqr*">` render thành công
  6. Assert: Số tiền trong QR khớp với `total_amount` trên màn hình
- **APIs Backend liên quan:** `POST /api/user/checkout` → trả `order_id`, `total_amount`

<video src="https://raw.githubusercontent.com/ngovandai/electrohub/main/tests/e2e-videos/checkout-qr-code-pass.webm" width="100%" controls preload="none"></video>

</details>

<details>
<summary><b>🟢 [E2E-007] Wishlist — Thêm, xem và xoá sản phẩm yêu thích</b></summary>

- **Mục đích:** Kiểm tra toàn bộ vòng đời Wishlist: User thêm sản phẩm → icon tim chuyển đỏ → truy cập trang `/wishlist` thấy sản phẩm → xoá → trang trống.
- **Cách thực hiện:**
  1. Đăng nhập → Vào trang sản phẩm
  2. Click icon ❤️ trên thẻ sản phẩm đầu tiên → Assert: tim chuyển đỏ
  3. Điều hướng đến `/wishlist` → Assert: sản phẩm xuất hiện trong danh sách
  4. Click nút **"Xoá khỏi Wishlist"** → Assert: trang hiển thị "Danh sách trống"
- **APIs Backend liên quan:** `POST /api/user/wishlist`, `DELETE /api/user/wishlist/{id}`, `GET /api/user/wishlist`

<video src="https://raw.githubusercontent.com/ngovandai/electrohub/main/tests/e2e-videos/wishlist-crud-pass.webm" width="100%" controls preload="none"></video>

</details>

---

## 🛡️ Nhóm 3: Admin & Xử lý khiếu nại (Admin & Dispute)

<details>
<summary><b>🟢 [E2E-008] User tải lên Biên lai thanh toán (Upload Receipt)</b></summary>

- **Mục đích:** Sau khi quét QR chuyển khoản, User cần tải lên ảnh biên lai để Admin xác nhận. Test này đảm bảo luồng Upload file hoạt động đúng và trạng thái đơn hàng được cập nhật.
- **Cách thực hiện:**
  1. Đăng nhập User → Vào `/orders` → Mở đơn hàng ở trạng thái `Pending`
  2. Click **"Tải lên biên lai"** → Chọn file ảnh mock (JPEG, < 2MB)
  3. Assert: Tên file xuất hiện trong UI (upload confirm)
  4. Click **"Gửi xác nhận"**
  5. Assert: Badge trạng thái đơn hàng thay đổi → "Chờ duyệt"
- **APIs Backend liên quan:** `POST /api/user/orders/{id}/receipt` (multipart/form-data)

<video src="https://raw.githubusercontent.com/ngovandai/electrohub/main/tests/e2e-videos/upload-receipt-pass.webm" width="100%" controls preload="none"></video>

</details>

<details>
<summary><b>🟢 [E2E-009] Admin xem biên lai và duyệt đơn hàng</b></summary>

- **Mục đích:** Đảm bảo Admin có thể xem ảnh biên lai rõ nét và thay đổi trạng thái đơn từ `Pending` → `Processing` chỉ bằng một click, đồng thời User nhận được cập nhật.
- **Cách thực hiện:**
  1. Đăng nhập Admin → Điều hướng đến `/admin/orders`
  2. Tìm đơn hàng có trạng thái `Chờ duyệt` → Click **"Xem chi tiết"**
  3. Assert: Ảnh biên lai hiển thị đúng (không bị broken img)
  4. Click **"Duyệt đơn"** (chuyển `Processing`)
  5. Assert: Badge trạng thái thay đổi ngay trong bảng danh sách
- **APIs Backend liên quan:** `GET /api/admin/orders/{id}`, `PATCH /api/admin/orders/{id}/status`

<video src="https://raw.githubusercontent.com/ngovandai/electrohub/main/tests/e2e-videos/admin-approve-order-pass.webm" width="100%" controls preload="none"></video>

</details>

<details>
<summary><b>🟢 [E2E-010] Live Chat — User khiếu nại và Admin phản hồi</b></summary>

- **Mục đích:** Kiểm tra toàn bộ luồng Dispute Chat khép kín: User gửi tin nhắn khiếu nại → Admin nhận notification → Admin đọc và trả lời → User thấy phản hồi trong real-time.
- **Cách thực hiện:**
  1. **[User]** Đăng nhập → Mở đơn hàng → Nhập tin nhắn: `"Tôi đã chuyển tiền nhưng chưa thấy duyệt"` → Click **"Gửi"**
  2. **[User]** Assert: Tin nhắn xuất hiện trong khung chat với timestamp
  3. **[Admin]** Đăng xuất → Đăng nhập Admin → Vào `/admin/orders/{id}`
  4. **[Admin]** Assert: Badge đỏ "1 khiếu nại mới" xuất hiện trên đơn hàng
  5. **[Admin]** Gõ phản hồi → Gửi → Assert: Tin nhắn Admin hiển thị trong chat
- **APIs Backend liên quan:** `POST /api/user/disputes`, `GET /api/admin/orders/{id}/dispute`, `POST /api/admin/disputes/{id}/reply`

<video src="https://raw.githubusercontent.com/ngovandai/electrohub/main/tests/e2e-videos/dispute-chat-pass.webm" width="100%" controls preload="none"></video>

</details>

---

> 📌 **Ghi chú kỹ thuật:** Video được lưu tại `tests/e2e-videos/`. Khi push lên GitHub, thay `username/repo` trong các đường dẫn `src` thành đúng URL repository của bạn để video hiển thị trực tiếp.

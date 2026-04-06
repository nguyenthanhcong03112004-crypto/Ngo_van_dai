/**
 * ElectroHub - Checkout Logic
 */

let activeVouchers = [];
window.selectedVoucher = null;

document.addEventListener('DOMContentLoaded', () => {
    // Initial calculation if needed
    if (window.Logger) Logger.info('Checkout: Page loaded');
    loadVouchers();
    
    // Lắng nghe thay đổi từ Giỏ hàng để tính toán lại nếu có thêm bớt số lượng
    window.addEventListener('cartUpdated', () => {
        setTimeout(updateCheckoutSummary, 100);
    });
    setTimeout(updateCheckoutSummary, 500);
});

async function loadVouchers() {
    try {
        const response = await fetch(`${window.API_BASE}/api/user/vouchers`);
        const result = await response.json();
        if (response.ok) {
            activeVouchers = result.data || [];
            const select = document.getElementById('voucherSelect');
            if (select) {
                activeVouchers.forEach(v => {
                    select.innerHTML += `<option value="${v.code}">${v.code} - Giảm ${formatVND(v.discount_amount)} (Đơn tối thiểu ${formatVND(v.min_order_value)})</option>`;
                });
                select.addEventListener('change', (e) => {
                    window.selectedVoucher = activeVouchers.find(v => v.code === e.target.value) || null;
                    updateCheckoutSummary();
                });
            }
        }
    } catch (e) {
        console.error('Failed to load vouchers', e);
    }
}

function updateCheckoutSummary() {
    if (!window.CartManager) return;
    const subtotal = CartManager.getCartTotal();
    let discount = 0;

    if (window.selectedVoucher) {
        if (subtotal >= window.selectedVoucher.min_order_value) {
            discount = window.selectedVoucher.discount_amount;
            if (discount > subtotal) discount = subtotal; // Không giảm giá âm tiền
        } else {
            if (typeof showToast === 'function') showToast(`Đơn hàng chưa đạt mức tối thiểu ${formatVND(window.selectedVoucher.min_order_value)} để áp dụng mã này`, 'warning');
            document.getElementById('voucherSelect').value = '';
            window.selectedVoucher = null;
        }
    }

    const total = subtotal - discount;

    const subEl = document.getElementById('checkout-summary-subtotal');
    if (subEl) subEl.innerText = formatVND(subtotal);
    
    const discountRow = document.getElementById('checkout-summary-discount-row');
    const discountEl = document.getElementById('checkout-summary-discount');
    if (discountRow && discountEl) {
        if (discount > 0) {
            discountRow.classList.remove('hidden');
            discountEl.innerText = '-' + formatVND(discount);
        } else {
            discountRow.classList.add('hidden');
        }
    }

    const totEl = document.getElementById('checkout-summary-total');
    if (totEl) totEl.innerText = formatVND(total);
}

async function generateQR() {
    const address = document.getElementById('shippingAddress')?.value;
    const phone = document.getElementById('shippingPhone')?.value;
    
    if (!address || !phone) {
        showToast('Vui lòng nhập đầy đủ Số điện thoại và Địa chỉ nhận hàng', 'warning');
        return;
    }

    const user = AuthManager.getUser();
    const cart = CartManager.getCart();
    
    if (cart.length === 0) {
        showToast('Giỏ hàng trống', 'error');
        return;
    }

    const confirmBtn = document.getElementById('confirmBtn');
    confirmBtn.innerText = 'Đang xử lý...';
    confirmBtn.disabled = true;

    try {
        // Đẩy Order lên Server để tạo đơn và tính toán số tiền
        const response = await fetch(`${window.API_BASE}/api/user/checkout`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${user.token}` },
            body: JSON.stringify({
                items: cart,
                shipping_address: address,
                phone: phone,
                voucher_code: window.selectedVoucher ? window.selectedVoucher.code : null
            })
        });
        const result = await response.json();
        
        if (!response.ok) throw new Error(result.message);

        const realOrderId = 'ORD-' + result.data.order_id;
        const finalAmount = result.data.total_amount > 0 ? result.data.total_amount : 1000;

        if (window.Logger) Logger.info('Checkout: Order Created, Generating VietQR', { finalAmount, realOrderId });
        
        const qrUrl = `https://img.vietqr.io/image/MB-123456789-compact.png?amount=${finalAmount}&addInfo=Thanh toan don hang ${realOrderId}&accountName=ELECTROHUB STORE`;
    
    const qrImage = document.getElementById('qrImage');
    const qrModal = document.getElementById('qrModal');

    if (qrImage && qrModal && confirmBtn) {
        qrImage.src = qrUrl;
        qrModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Vô hiệu hóa cuộn nền

        confirmBtn.innerText = 'Đang chờ thanh toán...';
        confirmBtn.disabled = true;
        confirmBtn.classList.add('opacity-50');
        
        if (typeof showToast === 'function') showToast('Mã QR đã sẵn sàng, vui lòng quét để thanh toán!', 'success');

        if (window.lucide) lucide.createIcons();
    }

    } catch (e) {
        showToast(e.message || 'Lỗi xử lý đơn hàng', 'error');
        confirmBtn.innerText = 'Xác nhận đặt hàng';
        confirmBtn.disabled = false;
    }
}

window.closeQRModal = function() {
    const qrModal = document.getElementById('qrModal');
    if (qrModal) {
        qrModal.classList.add('hidden');
        document.body.style.overflow = 'auto'; // Khôi phục cuộn nền
        
        // Clear cart after successful checkout
        localStorage.removeItem('electrohub_cart');
        if (window.CartManager) CartManager.updateCartBadge();
        
        setTimeout(() => {
            if (confirm('Thanh toán thành công! Bạn có muốn chuyển đến trang quản lý đơn hàng?')) {
                window.location.href = 'orders.html';
            } else {
                window.location.href = '../index.html';
            }
        }, 300);
    }
}

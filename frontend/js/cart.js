/**
 * ElectroHub - Global Cart Logic
 * Manages shopping cart state using localStorage.
 */

const CartManager = (function() {
    const STORAGE_KEY = 'electrohub_cart';

    /**
     * Get cart items from localStorage
     * @returns {Array}
     */
    function getCart() {
        const cart = localStorage.getItem(STORAGE_KEY);
        return cart ? JSON.parse(cart) : [];
    }

    /**
     * Save cart items to localStorage
     * @param {Array} cart 
     */
    function saveCart(cart) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(cart));
        updateCartBadge();
        // Dispatch custom event for other scripts to listen to
        window.dispatchEvent(new CustomEvent('cartUpdated', { detail: cart }));
    }

    /**
     * Add a product to the cart
     * @param {Object} product - {id, name, price, image, quantity}
     */
    function addToCart(product) {
        if (window.Logger) Logger.debug('CartManager: Adding product to cart', { productId: product.id, name: product.name });
        let cart = getCart();
        const existingItem = cart.find(item => item.id === product.id);

        if (existingItem) {
            existingItem.quantity += product.quantity || 1;
        } else {
            cart.push({
                ...product,
                quantity: product.quantity || 1
            });
        }

        saveCart(cart);
        if (typeof showToast === 'function') {
            showToast(`Đã thêm ${product.name} vào giỏ hàng!`, 'success');
        }
    }

    /**
     * Remove an item from the cart
     * @param {string|number} id 
     */
    function removeFromCart(id) {
        if (window.Logger) Logger.info('CartManager: Removing product from cart', { productId: id });
        let cart = getCart();
        const item = cart.find(i => i.id === id);
        cart = cart.filter(item => item.id !== id);
        saveCart(cart);
        
        if (item && typeof showToast === 'function') {
            showToast(`Đã xóa ${item.name} khỏi giỏ hàng`, 'info');
        }

        // Re-render if on checkout page
        if (window.location.pathname.includes('checkout.html')) {
            renderCheckoutCart();
        }
    }

    /**
     * Update quantity of an item
     * @param {string|number} id 
     * @param {number} qty 
     */
    function updateQuantity(id, qty) {
        if (window.Logger) Logger.debug('CartManager: Updating product quantity', { productId: id, newQuantity: qty });
        if (qty < 1) return removeFromCart(id);

        let cart = getCart();
        const item = cart.find(item => item.id === id);
        if (item) {
            item.quantity = qty;
            saveCart(cart);
            
            // Re-render if on checkout page
            if (window.location.pathname.includes('checkout.html')) {
                renderCheckoutCart();
            }
        }
    }

    /**
     * Calculate total price of items in cart
     * @returns {number}
     */
    function getCartTotal() {
        return getCart().reduce((total, item) => total + (item.price * item.quantity), 0);
    }

    /**
     * Update the cart badge in the header
     */
    function updateCartBadge() {
        const cart = getCart();
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        
        // Find all cart badges (might be multiple for mobile/desktop)
        const badges = document.querySelectorAll('.cart-badge');
        badges.forEach(badge => {
            badge.innerText = totalItems;
            badge.style.display = totalItems > 0 ? 'flex' : 'none';
        });
    }

    /**
     * Render cart items on the checkout page
     */
    function renderCheckoutCart() {
        const cartContainer = document.getElementById('checkout-cart-items');
        const summaryTotal = document.getElementById('checkout-summary-total');
        const summarySubtotal = document.getElementById('checkout-summary-subtotal');
        
        if (!cartContainer) return;

        // Show Skeleton Loading initially if it's the first render
        if (cartContainer.innerHTML.trim() === '') {
            cartContainer.innerHTML = `
                <div class="space-y-4">
                    ${Array(3).fill(0).map(() => `
                        <div class="flex items-center gap-6 p-6 bg-slate-50 dark:bg-slate-800/50 rounded-3xl border border-slate-100 dark:border-slate-700 animate-pulse">
                            <div class="w-20 h-20 rounded-2xl bg-slate-200 dark:bg-slate-700"></div>
                            <div class="flex-1 space-y-3">
                                <div class="h-4 w-1/3 bg-slate-200 dark:bg-slate-700 rounded"></div>
                                <div class="h-4 w-1/4 bg-slate-200 dark:bg-slate-700 rounded"></div>
                            </div>
                            <div class="w-24 h-10 bg-slate-200 dark:bg-slate-700 rounded-xl"></div>
                        </div>
                    `).join('')}
                </div>
            `;
            // Simulate loading delay for UX
            setTimeout(() => actualRender(), 800);
        } else {
            actualRender();
        }

        function actualRender() {
            const cart = getCart();
            
            if (cart.length === 0) {
                cartContainer.innerHTML = `
                    <div class="py-20 text-center flex flex-col items-center justify-center animate-fade-in">
                        <div class="w-32 h-32 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mb-6 text-slate-300 dark:text-slate-600">
                            <i data-lucide="shopping-bag" size="64"></i>
                        </div>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-2">Giỏ hàng trống rỗng</h3>
                        <p class="text-slate-500 dark:text-slate-400 font-medium max-w-xs mx-auto mb-8">Có vẻ như bạn chưa chọn được món đồ ưng ý nào. Hãy quay lại cửa hàng nhé!</p>
                        <a href="../shop.html" class="px-8 py-3 bg-blue-600 text-white rounded-full font-bold hover:bg-blue-700 transition-all shadow-xl shadow-blue-200 dark:shadow-none">
                            Khám phá ngay
                        </a>
                    </div>
                `;
                if (summaryTotal) summaryTotal.innerText = '0 ₫';
                if (summarySubtotal) summarySubtotal.innerText = '0 ₫';
                if (window.lucide) lucide.createIcons();
                return;
            }

            cartContainer.innerHTML = cart.map(item => `
                <div class="flex items-center gap-6 p-6 bg-slate-50 dark:bg-slate-800/50 rounded-3xl border border-slate-100 dark:border-slate-700 group transition-all hover:bg-white dark:hover:bg-slate-800 hover:shadow-xl hover:shadow-slate-100 dark:hover:shadow-none">
                    <img src="${item.image}" class="w-20 h-20 rounded-2xl object-cover shadow-sm border border-white dark:border-slate-700">
                    <div class="flex-1">
                        <h3 class="font-bold text-slate-800 dark:text-slate-200 mb-1">${item.name}</h3>
                        <p class="text-blue-600 font-black">${formatVND(item.price)}</p>
                    </div>
                    <div class="flex items-center gap-3 bg-white dark:bg-slate-900 p-2 rounded-2xl border border-slate-100 dark:border-slate-700">
                        <button onclick="CartManager.updateQuantity('${item.id}', ${item.quantity - 1})" class="w-8 h-8 flex items-center justify-center rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-400 transition-all">
                            <i data-lucide="minus" size="16"></i>
                        </button>
                        <span class="w-8 text-center font-black text-slate-900 dark:text-white">${item.quantity}</span>
                        <button onclick="CartManager.updateQuantity('${item.id}', ${item.quantity + 1})" class="w-8 h-8 flex items-center justify-center rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-400 transition-all">
                            <i data-lucide="plus" size="16"></i>
                        </button>
                    </div>
                    <button onclick="CartManager.removeFromCart('${item.id}')" class="p-3 text-slate-300 hover:text-red-500 transition-all">
                        <i data-lucide="trash-2" size="20"></i>
                    </button>
                </div>
            `).join('');

            const total = getCartTotal();
            if (summarySubtotal) summarySubtotal.innerText = formatVND(total);
            if (summaryTotal) summaryTotal.innerText = formatVND(total);

            // Re-init icons
            if (window.lucide) lucide.createIcons();
        }
    }

    // Initialize on load
    document.addEventListener('DOMContentLoaded', () => {
        updateCartBadge();
        if (window.location.pathname.includes('checkout.html')) {
            renderCheckoutCart();
        }
    });

    return {
        getCart,
        addToCart,
        removeFromCart,
        updateQuantity,
        getCartTotal,
        updateCartBadge,
        renderCheckoutCart
    };
})();

// Expose to global scope for onclick handlers
window.CartManager = CartManager;

/**
 * ElectroHub - Wishlist Logic
 * Manages user's favorite products using localStorage.
 */

const WishlistManager = (function() {
    const STORAGE_KEY = 'electrohub_wishlist';

    /**
     * Get wishlist items from localStorage
     * @returns {Array}
     */
    function getWishlist() {
        const wishlist = localStorage.getItem(STORAGE_KEY);
        return wishlist ? JSON.parse(wishlist) : [];
    }

    /**
     * Save wishlist items to localStorage
     * @param {Array} wishlist 
     */
    function saveWishlist(wishlist) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(wishlist));
        updateWishlistBadge();
        updateHeartIcons();
        // Dispatch custom event for other scripts to listen to
        window.dispatchEvent(new CustomEvent('wishlistUpdated', { detail: wishlist }));
    }

    /**
     * Check if a product is in the wishlist
     * @param {string|number} id 
     * @returns {boolean}
     */
    function isInWishlist(id) {
        return getWishlist().some(item => item.id === id);
    }

    /**
     * Toggle a product in/out of the wishlist
     * @param {Object} product - {id, name, price, image, category}
     */
    function toggleWishlist(product) {
        if (window.Logger) Logger.debug('WishlistManager: Toggling wishlist status', { productId: product.id, productName: product.name });
        let wishlist = getWishlist();
        const index = wishlist.findIndex(item => item.id === product.id);

        if (index > -1) {
            wishlist.splice(index, 1);
            if (typeof showToast === 'function') {
                showToast(`Đã xóa ${product.name} khỏi yêu thích.`, 'info');
            }
        } else {
            wishlist.push(product);
            if (typeof showToast === 'function') {
                showToast(`Đã thêm ${product.name} vào yêu thích!`, 'success');
            }
        }

        saveWishlist(wishlist);
        
        // Re-render if on wishlist page
        if (window.location.pathname.includes('wishlist.html')) {
            renderWishlist();
        }
    }

    /**
     * Update the wishlist badge in the header
     */
    function updateWishlistBadge() {
        const wishlist = getWishlist();
        const totalItems = wishlist.length;
        
        // Find all wishlist badges
        const badges = document.querySelectorAll('.wishlist-badge');
        badges.forEach(badge => {
            badge.innerText = totalItems;
            badge.style.display = totalItems > 0 ? 'flex' : 'none';
        });
    }

    /**
     * Update heart icons on product cards based on wishlist state
     */
    function updateHeartIcons() {
        const wishlist = getWishlist();
        const buttons = document.querySelectorAll('[data-wishlist-id]');
        
        buttons.forEach(btn => {
            const id = btn.getAttribute('data-wishlist-id');
            const icon = btn.querySelector('i');
            
            if (isInWishlist(id)) {
                btn.classList.add('text-red-500', 'bg-red-50');
                btn.classList.remove('text-slate-400', 'bg-white/90');
                if (icon) icon.setAttribute('fill', 'currentColor');
            } else {
                btn.classList.remove('text-red-500', 'bg-red-50');
                btn.classList.add('text-slate-400', 'bg-white/90');
                if (icon) icon.removeAttribute('fill');
            }
        });

        // Re-init icons to apply fill attribute
        if (window.lucide) lucide.createIcons();
    }

    /**
     * Render wishlist items on the wishlist page
     */
    function renderWishlist() {
        const wishlistContainer = document.getElementById('wishlist-items');
        if (!wishlistContainer) return;

        // Show Skeleton Loading initially if it's the first render
        if (wishlistContainer.innerHTML.trim() === '') {
            wishlistContainer.innerHTML = Array(4).fill(0).map(() => getSkeletonCard()).join('');
            // Simulate loading delay for UX
            setTimeout(() => actualRender(), 800);
        } else {
            actualRender();
        }

        function actualRender() {
            const wishlist = getWishlist();
            
            if (wishlist.length === 0) {
                // Find or create empty state container
                let emptyState = document.getElementById('wishlist-empty-state');
                if (!emptyState) {
                    emptyState = document.createElement('div');
                    emptyState.id = 'wishlist-empty-state';
                    wishlistContainer.parentElement.appendChild(emptyState);
                }
                
                emptyState.innerHTML = `
                    <div class="py-20 text-center flex flex-col items-center justify-center animate-fade-in">
                        <div class="w-32 h-32 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mb-6 text-slate-300 dark:text-slate-600">
                            <i data-lucide="heart" size="64"></i>
                        </div>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-2">Danh sách yêu thích trống</h3>
                        <p class="text-slate-500 dark:text-slate-400 font-medium max-w-xs mx-auto mb-8">Hãy thả tim cho những sản phẩm bạn yêu thích để lưu lại đây nhé!</p>
                        <a href="../shop.html" class="px-8 py-3 bg-blue-600 text-white rounded-full font-bold hover:bg-blue-700 transition-all shadow-xl shadow-blue-200 dark:shadow-none">
                            Khám phá ngay
                        </a>
                    </div>
                `;
                wishlistContainer.classList.add('hidden');
                emptyState.classList.remove('hidden');
                if (window.lucide) lucide.createIcons();
                return;
            }

            // Hide empty state if exists
            const emptyState = document.getElementById('wishlist-empty-state');
            if (emptyState) emptyState.classList.add('hidden');
            
            wishlistContainer.classList.remove('hidden');
            wishlistContainer.innerHTML = wishlist.map(item => `
                <div class="group bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700 overflow-hidden hover:shadow-2xl hover:shadow-slate-200 dark:hover:shadow-none transition-all duration-500 flex flex-col relative">
                    <div class="relative aspect-[4/3] overflow-hidden">
                        <img src="${item.image}" alt="${item.name}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                        <div class="absolute top-4 right-4 flex flex-col gap-2">
                            <button onclick="WishlistManager.toggleWishlist({id: '${item.id}', name: '${item.name}'})" class="p-2 rounded-xl backdrop-blur-md shadow-lg transition-all bg-white/90 dark:bg-slate-900/90 text-red-500 hover:bg-red-500 hover:text-white">
                                <i data-lucide="trash-2" size="18"></i>
                            </button>
                        </div>
                    </div>
                    <div class="p-6 flex-1 flex flex-col">
                        <div class="text-xs font-bold text-blue-600 uppercase tracking-widest mb-2">${item.category || 'Sản phẩm'}</div>
                        <h3 class="font-bold text-slate-800 dark:text-slate-200 mb-2 group-hover:text-blue-600 transition-colors line-clamp-1">${item.name}</h3>
                        <div class="flex items-center justify-between mt-auto">
                            <span class="text-lg font-black text-slate-900 dark:text-white">${formatVND(item.price)}</span>
                            <button onclick="CartManager.addToCart({id: '${item.id}', name: '${item.name}', price: ${item.price}, image: '${item.image}'})" class="p-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-all shadow-lg shadow-blue-100">
                                <i data-lucide="shopping-cart" size="18"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');

            // Re-init icons
            if (window.lucide) lucide.createIcons();
        }
    }

    // Initialize on load
    document.addEventListener('DOMContentLoaded', () => {
        updateWishlistBadge();
        updateHeartIcons();
        if (window.location.pathname.includes('wishlist.html')) {
            renderWishlist();
        }
    });

    return {
        getWishlist,
        toggleWishlist,
        isInWishlist,
        updateWishlistBadge,
        updateHeartIcons,
        renderWishlist
    };
})();

// Expose to global scope for onclick handlers
window.WishlistManager = WishlistManager;

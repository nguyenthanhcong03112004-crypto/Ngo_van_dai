/**
 * ElectroHub - Authentication & Route Guard
 * Manages user sessions and protects restricted routes.
 */

const AuthManager = (function() {
    const STORAGE_KEY = 'electrohub_user';

    /**
     * Get user info from localStorage
     * @returns {Object|null}
     */
    function getUser() {
        const user = localStorage.getItem(STORAGE_KEY);
        return user ? JSON.parse(user) : null;
    }

    /**
     * Login user and save to localStorage
     * @param {Object} userObj - {name, email, role}
     */
    function login(userObj) {
        if (window.Logger) Logger.info('AuthManager: User login successful', { user: userObj.email || userObj.name, role: userObj.role });
        localStorage.setItem(STORAGE_KEY, JSON.stringify(userObj));
        if (typeof showToast === 'function') {
            showToast(`Chào mừng trở lại, ${userObj.name}!`, 'success');
        }
        
        // Redirect based on role
        setTimeout(() => {
            if (userObj.role === 'admin') {
                window.location.href = 'admin/dashboard.html';
            } else {
                window.location.href = 'index.html';
            }
        }, 1000);
    }

    /**
     * Logout user and clear localStorage
     */
    function logout() {
        if (window.Logger) Logger.info('AuthManager: User logged out');
        localStorage.removeItem(STORAGE_KEY);
        if (typeof showToast === 'function') {
            showToast('Đã đăng xuất thành công.', 'info');
        }
        
        setTimeout(() => {
            window.location.href = '../index.html';
        }, 1000);
    }

    /**
     * Route Guard: Protects /user/ and /admin/ routes
     */
    function checkAuth() {
        const user = getUser();
        const path = window.location.pathname;

        // Check for /user/ routes
        if (path.includes('/user/')) {
            if (!user) {
                if (window.Logger) Logger.warn('AuthManager: Unauthorized access to /user/ route, redirecting to login');
                window.location.href = '../login.html';
                return false;
            }
        }

        // Check for /admin/ routes
        if (path.includes('/admin/')) {
            if (!user || user.role !== 'admin') {
                if (window.Logger) Logger.warn('AuthManager: Unauthorized access to /admin/ route, redirecting to home');
                window.location.href = '../index.html';
                return false;
            }
        }

        return true;
    }

    /**
     * Update the header based on authentication state
     */
    function updateHeaderAuth() {
        const user = getUser();
        const authContainer = document.getElementById('auth-header-container');
        
        if (!authContainer) return;

        if (user) {
            authContainer.innerHTML = `
                <a href="${pathPrefix()}user/notifications.html" class="p-2 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full relative transition-colors">
                    <i data-lucide="bell" size="22"></i>
                    <span class="notification-badge absolute top-1 right-1 w-4 h-4 bg-red-500 text-white text-[10px] flex items-center justify-center rounded-full font-bold" style="display: none;">0</span>
                </a>
                <div class="flex items-center gap-4">
                    <div class="flex flex-col items-end hidden sm:flex">
                        <span class="text-sm font-black text-slate-900 dark:text-white">${user.name}</span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">${user.role === 'admin' ? 'Quản trị viên' : 'Thành viên'}</span>
                    </div>
                    <div class="relative group">
                        <button class="w-10 h-10 rounded-full border-2 border-white dark:border-slate-800 shadow-sm overflow-hidden ring-2 ring-slate-100 dark:ring-slate-800 group-hover:ring-blue-500 transition-all">
                            <img src="https://picsum.photos/seed/${user.name}/100/100" alt="User">
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <div class="absolute right-0 top-full mt-2 w-48 bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-700 py-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50">
                            ${user.role === 'admin' ? `
                                <a href="${pathPrefix()}admin/dashboard.html" class="flex items-center gap-3 px-4 py-2 text-sm font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 hover:text-blue-600 dark:hover:text-blue-400">
                                    <i data-lucide="layout-dashboard" size="16"></i> Dashboard
                                </a>
                            ` : ''}
                            <a href="${pathPrefix()}user/profile.html" class="flex items-center gap-3 px-4 py-2 text-sm font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 hover:text-blue-600 dark:hover:text-blue-400">
                                <i data-lucide="user" size="16"></i> Hồ sơ
                            </a>
                            <a href="${pathPrefix()}user/orders.html" class="flex items-center gap-3 px-4 py-2 text-sm font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 hover:text-blue-600 dark:hover:text-blue-400">
                                <i data-lucide="package" size="16"></i> Đơn hàng
                            </a>
                            <div class="h-px bg-slate-100 dark:bg-slate-700 my-2"></div>
                            <button onclick="AuthManager.logout()" class="w-full flex items-center gap-3 px-4 py-2 text-sm font-bold text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20">
                                <i data-lucide="log-out" size="16"></i> Đăng xuất
                            </button>
                        </div>
                    </div>
                </div>
            `;
            updateNotificationBadge();
        } else {
            authContainer.innerHTML = `
                <a href="${pathPrefix()}login.html" class="flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-full font-bold hover:bg-blue-700 transition-all shadow-md shadow-blue-100">
                    <i data-lucide="user" size="18"></i> Đăng nhập
                </a>
            `;
        }

        // Re-init icons
        if (window.lucide) lucide.createIcons();
    }

    async function updateNotificationBadge() {
        const user = getUser();
        if (!user || !user.token) return;

        try {
            const response = await fetch(`${pathPrefix()}../api/notifications/unread-count`, {
                headers: { 'Authorization': `Bearer ${user.token}` }
            });
            const result = await response.json();
            if (response.ok) {
                const count = result.data.unread_count;
                const badges = document.querySelectorAll('.notification-badge');
                badges.forEach(badge => {
                    badge.innerText = count;
                    badge.style.display = count > 0 ? 'flex' : 'none';
                });
            }
        } catch (error) {
            // Fail silently
        }
    }
    /**
     * Helper to determine path prefix based on current location
     */
    function pathPrefix() {
        const path = window.location.pathname;
        if (path.includes('/user/') || path.includes('/admin/')) {
            return '../';
        }
        return '';
    }

    // Initialize on load
    document.addEventListener('DOMContentLoaded', () => {
        if (checkAuth()) {
            updateHeaderAuth();
        }
    });

    return {
        getUser,
        login,
        logout,
        checkAuth,
        updateHeaderAuth
    };
})();

// Expose to global scope
window.AuthManager = AuthManager;

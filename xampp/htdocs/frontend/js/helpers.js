/**
 * ElectroHub - Helpers & Utilities
 * Contains common functions like currency formatting and toast notifications.
 */

/**
 * Global API base URL — tự động phát hiện môi trường
 * - XAMPP (Docker):  http://localhost/backend/public
 * - Dev server cũ:   http://localhost:8888
 */
const API_BASE = (function() {
    const port = window.location.port;
    if (port === '8888') return 'http://localhost:8888';
    
    // Chạy qua XAMPP hoặc Apache → backend nằm cùng server
    return window.location.origin + '/backend/public';
})();
window.API_BASE = API_BASE;
console.log('[helpers.js] API_BASE initialized:', window.API_BASE);

/**
 * Wrapper fetch với toast lỗi tự động
 * @param {string} endpoint - Đường dẫn API (vd: '/api/user/products')
 * @param {RequestInit} options
 * @returns {Promise<any>}
 */
async function apiFetch(endpoint, options = {}) {
    // Ensure endpoint start with /
    const cleanEndpoint = endpoint.startsWith('/') ? endpoint : '/' + endpoint;
    const url = API_BASE + cleanEndpoint;
    
    try {
        const res = await fetch(url, options);
        if (!res.ok) {
            let msg = `Lỗi ${res.status}: ${res.statusText}`;
            try {
                const body = await res.clone().json();
                if (body && body.message) msg = body.message;
            } catch (_) {}
            if (typeof showToast === 'function') showToast(`⚠️ API: ${msg}`, 'error');
            console.error('[apiFetch] Error', res.status, url, msg);
        }
        return res;
    } catch (err) {
        const msg = err.message || 'Không thể kết nối đến server';
        if (typeof showToast === 'function') {
            showToast(`🔴 Lỗi kết nối: ${msg}. Kiểm tra backend tại ${API_BASE}`, 'error');
        }
        console.error('[apiFetch] Network error', url, err);
        throw err;
    }
}
window.apiFetch = apiFetch;

/**
 * Frontend Logger for testing and debugging
 */
const Logger = {
    _log: function(level, message, context = {}) {
        const timestamp = new Date().toISOString();
        const logEntry = `[${timestamp}] [${level}] ${message}`;
        if (level === 'ERROR') {
            console.error(logEntry, context);
        } else if (level === 'WARN') {
            console.warn(logEntry, context);
        } else if (level === 'INFO') {
            console.info(logEntry, context);
        } else {
            console.debug(logEntry, context);
        }

        // Only send info/warn/error to backend
        if (level !== 'DEBUG') {
            this._sendToBackend(level, message, context);
        }
    },
    _sendToBackend: function(level, message, context) {
        const userStr = localStorage.getItem('electrohub_user');
        const headers = { 'Content-Type': 'application/json' };
        
        if (userStr) {
            try {
                const userData = JSON.parse(userStr);
                if (userData.token) {
                    headers['Authorization'] = `Bearer ${userData.token}`;
                }
            } catch (e) {}
        }

        fetch(API_BASE + '/api/logs', {
            method: 'POST',
            headers: headers,
            body: JSON.stringify({ level, message, context })
        }).catch(() => {});
    },
    debug: function(msg, ctx) { this._log('DEBUG', msg, ctx); },
    info: function(msg, ctx) { this._log('INFO', msg, ctx); },
    warn: function(msg, ctx) { this._log('WARN', msg, ctx); },
    error: function(msg, ctx) { this._log('ERROR', msg, ctx); }
};
window.Logger = Logger;

/**
 * Format number to Vietnamese Dong (VND)
 */
function formatVND(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(amount);
}
window.formatVND = formatVND;

/**
 * Show a toast notification using Tailwind CSS
 */
function showToast(message, type = 'success') {
    // Log toast to console via Logger
    if (window.Logger) {
        if (type === 'error') Logger.error(`[Toast] ${message}`);
        else Logger.info(`[Toast - ${type.toUpperCase()}] ${message}`);
    }

    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'fixed bottom-8 right-8 z-[9999] flex flex-col gap-4';
        document.body.appendChild(container);
    }

    const colors = {
        success: 'bg-green-600',
        error: 'bg-red-600',
        info: 'bg-blue-600',
        warning: 'bg-orange-500'
    };

    const icons = {
        success: 'check-circle',
        error: 'alert-circle',
        info: 'info',
        warning: 'alert-triangle'
    };

    const toast = document.createElement('div');
    toast.className = `${colors[type]} text-white px-6 py-4 rounded-2xl shadow-2xl flex items-center gap-3 animate-fade-in transform transition-all duration-300 translate-y-10 opacity-0`;
    toast.innerHTML = `
        <i data-lucide="${icons[type]}" size="20"></i>
        <span class="font-bold text-sm text-white">${message}</span>
    `;

    container.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.remove('translate-y-10', 'opacity-0');
        toast.classList.add('translate-y-0', 'opacity-100');
    }, 10);

    if (window.lucide) {
        lucide.createIcons();
    }

    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-x-10');
        setTimeout(() => {
            toast.remove();
            if (container.children.length === 0) {
                container.remove();
            }
        }, 300);
    }, 4000);
}
window.showToast = showToast;

/**
 * Theme Manager - Handles Dark Mode
 */
const ThemeManager = {
    init: function() {
        const savedTheme = localStorage.getItem('electrohub_theme') || 'light';
        this.setTheme(savedTheme);
    },
    setTheme: function(theme) {
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        localStorage.setItem('electrohub_theme', theme);
        this.updateToggleIcons();
    },
    toggle: function() {
        const currentTheme = localStorage.getItem('electrohub_theme') || 'light';
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        this.setTheme(newTheme);
    },
    updateToggleIcons: function() {
        const theme = localStorage.getItem('electrohub_theme') || 'light';
        const sunIcons = document.querySelectorAll('.theme-sun');
        const moonIcons = document.querySelectorAll('.theme-moon');
        
        if (theme === 'dark') {
            sunIcons.forEach(i => i.classList.remove('hidden'));
            moonIcons.forEach(i => i.classList.add('hidden'));
        } else {
            sunIcons.forEach(i => i.classList.add('hidden'));
            moonIcons.forEach(i => i.classList.remove('hidden'));
        }
    }
};

window.ThemeManager = ThemeManager;

document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) {
        lucide.createIcons();
    }
    ThemeManager.init();
});

function getSkeletonCard() {
    return `
        <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700 overflow-hidden animate-pulse">
            <div class="aspect-[4/3] bg-slate-100 dark:bg-slate-700"></div>
            <div class="p-6 space-y-4">
                <div class="h-3 w-1/4 bg-slate-100 dark:bg-slate-700 rounded"></div>
                <div class="h-5 w-3/4 bg-slate-100 dark:bg-slate-700 rounded"></div>
                <div class="flex justify-between items-center">
                    <div class="h-6 w-1/3 bg-slate-100 dark:bg-slate-700 rounded"></div>
                    <div class="h-10 w-10 bg-slate-100 dark:bg-slate-700 rounded-xl"></div>
                </div>
            </div>
        </div>
    `;
}
window.getSkeletonCard = getSkeletonCard;

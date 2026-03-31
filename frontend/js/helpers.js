/**
 * ElectroHub - Helpers & Utilities
 * Contains common functions like currency formatting and toast notifications.
 */

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

        this._sendToBackend(level, message, context);
    },
    _sendToBackend: function(level, message, context) {
        // Lấy token từ localStorage để đính kèm vào header
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

        // Đẩy log về Backend thông qua API không đồng bộ
        fetch('http://localhost:8888/api/logs', {
            method: 'POST',
            headers: headers,
            body: JSON.stringify({ level, message, context })
        }).catch(() => {
            // Ignore errors (Bỏ qua lỗi mạng) để tránh spam UI nếu server đang down
        });
    },
    debug: function(msg, ctx) { this._log('DEBUG', msg, ctx); },
    info: function(msg, ctx) { this._log('INFO', msg, ctx); },
    warn: function(msg, ctx) { this._log('WARN', msg, ctx); },
    error: function(msg, ctx) { this._log('ERROR', msg, ctx); }
};
window.Logger = Logger;

/**
 * Format number to Vietnamese Dong (VND)
 * @param {number} amount 
 * @returns {string}
 */
function formatVND(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(amount);
}

/**
 * Show a toast notification using Tailwind CSS
 * @param {string} message - The message to display
 * @param {string} type - 'success', 'error', 'info', 'warning'
 */
function showToast(message, type = 'success') {
    if (window.Logger) Logger.info(`[Toast - ${type.toUpperCase()}] ${message}`);

    // Create container if it doesn't exist
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'fixed bottom-8 right-8 z-[9999] flex flex-col gap-4';
        document.body.appendChild(container);
    }

    // Define colors based on type
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
        <span class="font-bold text-sm">${message}</span>
    `;

    container.appendChild(toast);
    
    // Trigger animation
    setTimeout(() => {
        toast.classList.remove('translate-y-10', 'opacity-0');
        toast.classList.add('translate-y-0', 'opacity-100');
    }, 10);

    // Initialize lucide icons for the new toast
    if (window.lucide) {
        lucide.createIcons();
    }

    // Remove toast after 3 seconds
    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-x-10');
        setTimeout(() => {
            toast.remove();
            if (container.children.length === 0) {
                container.remove();
            }
        }, 300);
    }, 3000);
}

// Global initialization for Lucide icons if not already handled
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) {
        lucide.createIcons();
    }
    ThemeManager.init();
});

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

/**
 * Skeleton Loader Helper
 * Returns HTML for a skeleton card
 */
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

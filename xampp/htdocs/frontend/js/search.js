/**
 * ElectroHub - Live Search & Auto-suggest
 */

const SearchManager = (function() {
    function init() {
        const searchInput = document.getElementById('globalSearchInput');
        const suggestionsBox = document.getElementById('searchSuggestions');

        if (!searchInput || !suggestionsBox) return;

        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim().toLowerCase();
            
            if (query.length < 2) {
                suggestionsBox.classList.add('hidden');
                return;
            }

            searchTimeout = setTimeout(async () => {
                try {
                    const response = await fetch(`${window.API_BASE}/api/user/products?search=${encodeURIComponent(query)}&limit=5`);
                    const result = await response.json();
                    if (response.ok) {
                        renderSuggestions(result.data || []);
                    } else {
                        renderSuggestions([]);
                    }
                } catch (error) {
                    console.error('Search API error:', error);
                    renderSuggestions([]);
                }
            }, 300);
        });

        // Close suggestions when clicking outside
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
                suggestionsBox.classList.add('hidden');
            }
        });
    }

    function renderSuggestions(results) {
        const suggestionsBox = document.getElementById('searchSuggestions');
        
        if (results.length === 0) {
            suggestionsBox.innerHTML = `
                <div class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400 italic">
                    Không tìm thấy sản phẩm nào...
                </div>
            `;
        } else {
            suggestionsBox.innerHTML = results.map(product => `
                <a href="product-detail.html?id=${product.id}" class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors group">
                    <img src="${product.image_url || 'https://picsum.photos/100'}" alt="${product.name}" class="w-12 h-12 rounded-lg object-cover border border-slate-100 dark:border-slate-700">
                    <div class="flex-1">
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white group-hover:text-blue-600 transition-colors">${product.name}</h4>
                        <p class="text-xs font-bold text-blue-600">${formatVND(product.price)}</p>
                    </div>
                    <i data-lucide="chevron-right" size="16" class="text-slate-300"></i>
                </a>
            `).join('');
            
            if (window.lucide) lucide.createIcons();
        }

        suggestionsBox.classList.remove('hidden');
    }

    return { init };
})();

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    SearchManager.init();
});

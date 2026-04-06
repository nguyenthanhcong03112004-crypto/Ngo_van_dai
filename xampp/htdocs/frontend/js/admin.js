/**
 * ElectroHub - Admin Dashboard Logic
 */

document.addEventListener('DOMContentLoaded', () => {
    console.log('Admin Dashboard loaded');
    if (document.getElementById('revenueChart')) {
        loadDashboardData();
        loadRecentReviews();
    }
});

async function loadDashboardData() {
    try {
        const user = AuthManager.getUser();
        if (!user || !user.token) return;

        const response = await fetch(`${window.API_BASE}/api/admin/analytics`, {
            headers: { 'Authorization': `Bearer ${user.token}` }
        });
        const result = await response.json();
        
        if (response.ok) {
            const data = result.data;
            if (data.total_revenue !== undefined) {
                document.getElementById('stat-revenue').innerText = formatVND(data.total_revenue);
            }
            if (data.total_orders !== undefined) {
                document.getElementById('stat-orders').innerText = data.total_orders;
            }
            if (data.total_customers !== undefined) {
                document.getElementById('stat-customers').innerText = data.total_customers;
            }

            initCharts(data.chart_data);
        } else {
            initCharts(null);
        }
    } catch (error) {
        console.error('Failed to load analytics', error);
        initCharts(null);
    }
}

async function loadRecentReviews() {
    const container = document.getElementById('recentReviewsContainer');
    if (!container) return;

    try {
        const user = AuthManager.getUser();
        if (!user || !user.token) return;

        const response = await fetch(`${window.API_BASE}/api/admin/reviews/recent`, {
            headers: { 'Authorization': `Bearer ${user.token}` }
        });
        const result = await response.json();
        
        if (response.ok && result.data && result.data.length > 0) {
            container.innerHTML = result.data.map(r => {
                const date = new Date(r.created_at).toLocaleDateString('vi-VN', { day: 'numeric', month: 'short', year: 'numeric' });
                const stars = Array(5).fill(0).map((_, i) => `<i data-lucide="star" ${i < r.rating ? 'fill="currentColor" class="text-orange-400"' : 'class="text-slate-200"'} size="14"></i>`).join('');
                return `
                    <div class="pt-6 first:pt-0 flex gap-4">
                        <img src="${r.avatar_url ? window.API_BASE + r.avatar_url : 'https://picsum.photos/seed/user' + r.user_id + '/100/100'}" onerror="this.src='https://picsum.photos/seed/user${r.user_id}/100/100'" class="w-10 h-10 rounded-full border-2 border-slate-50 shadow-sm object-cover shrink-0">
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <p class="font-bold text-slate-900">${r.user_name} <span class="font-medium text-slate-500 text-sm ml-2">đã đánh giá</span> <span class="font-bold text-blue-600 text-sm">${r.product_name}</span></p>
                                <div class="flex items-center gap-3">
                                    <span class="text-xs font-bold text-slate-400">${date}</span>
                                    <button onclick="deleteReview(${r.id})" class="text-slate-400 hover:text-red-500 transition-colors" title="Xóa đánh giá"><i data-lucide="trash-2" size="16"></i></button>
                                </div>
                            </div>
                            <div class="flex items-center gap-1 mb-2">${stars}</div>
                            <p class="text-sm text-slate-600 font-medium">${r.comment}</p>
                        </div>
                    </div>
                `;
            }).join('');
            if (window.lucide) lucide.createIcons();
        } else {
            container.innerHTML = '<div class="text-center py-8 text-slate-500 font-medium">Chưa có đánh giá nào.</div>';
        }
    } catch (error) {
        container.innerHTML = '<div class="text-center py-8 text-red-500 font-medium">Lỗi kết nối máy chủ.</div>';
    }
}

async function deleteReview(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa đánh giá này không?')) return;
    
    try {
        const user = AuthManager.getUser();
        const response = await fetch(`${window.API_BASE}/api/admin/reviews/${id}`, {
            method: 'DELETE',
            headers: { 'Authorization': `Bearer ${user.token}` }
        });
        const result = await response.json();
        
        if (response.ok) {
            if (typeof showToast === 'function') showToast(result.message, 'success');
            loadRecentReviews();
        } else {
            if (typeof showToast === 'function') showToast(result.message, 'error');
        }
    } catch (error) {
        if (typeof showToast === 'function') showToast('Lỗi khi xóa đánh giá', 'error');
    }
}
window.deleteReview = deleteReview;

function initCharts(apiData) {
    const revData = apiData && apiData.revenue ? apiData.revenue : [120, 190, 150, 250, 220, 310, 280];
    const revLabels = apiData && apiData.labels ? apiData.labels : ['Th 2', 'Th 3', 'Th 4', 'Th 5', 'Th 6', 'Th 7', 'CN'];

    // Revenue Chart
    const revCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revCtx, {
        type: 'line',
        data: {
            labels: revLabels,
            datasets: [{
                label: 'Doanh thu (Triệu ₫)',
                data: revData,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 4,
                pointRadius: 0,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                x: { grid: { display: false } }
            }
        }
    });

    const statusData = apiData && apiData.status ? apiData.status : [45, 25, 20, 10];

    // Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Hoàn thành', 'Chờ xử lý', 'Đang giao', 'Đã hủy'],
            datasets: [{
                data: statusData,
                backgroundColor: ['#22c55e', '#f59e0b', '#3b82f6', '#ef4444'],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: { weight: 'bold', size: 12 }
                    }
                }
            },
            cutout: '70%'
        }
    });

    // Dispute Trend Chart
    const disputeData = apiData && apiData.disputes ? apiData.disputes.counts : [2, 5, 3, 8, 4, 6];
    const disputeLabels = apiData && apiData.disputes ? apiData.disputes.labels : ['10/25', '11/25', '12/25', '01/26', '02/26', '03/26'];

    const dispCtx = document.getElementById('disputeChart');
    if (dispCtx) {
        new Chart(dispCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: disputeLabels,
                datasets: [{
                    label: 'Số lượng khiếu nại',
                    data: disputeData,
                    backgroundColor: '#ef4444',
                    borderRadius: 8,
                    maxBarThickness: 40
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 12,
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 13 }
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { color: '#f1f5f9' },
                        ticks: { stepSize: 1, font: { weight: 'bold' } }
                    },
                    x: { grid: { display: false }, ticks: { font: { weight: 'bold' } } }
                }
            }
        });
    }
}

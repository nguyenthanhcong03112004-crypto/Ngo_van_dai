<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Core\BaseController;
use Core\Middleware;
use Core\Database;
use PDO;

class AnalyticsController extends BaseController
{
    public function index(): void
    {
        Middleware::requireRole('admin');
        $db = Database::getInstance()->getConnection();

        // 1. Các chỉ số tổng quan
        $totalRevenue = (float) $db->query("SELECT SUM(total_amount) FROM `orders` WHERE `status` = 'completed'")->fetchColumn();
        $totalOrders = (int) $db->query("SELECT COUNT(id) FROM `orders`")->fetchColumn();
        $totalCustomers = (int) $db->query("SELECT COUNT(id) FROM `users` WHERE `role` = 'user'")->fetchColumn();

        // 2. Dữ liệu biểu đồ trạng thái (Hoàn thành, Chờ xử lý, Đang giao, Đã hủy)
        $statusCounts = $db->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
        $chartStatus = [
            $statusCounts['completed'] ?? 0,
            ($statusCounts['pending'] ?? 0) + ($statusCounts['confirmed'] ?? 0),
            $statusCounts['shipping'] ?? 0,
            $statusCounts['cancelled'] ?? 0
        ];

        // 3. Dữ liệu biểu đồ doanh thu & khiếu nại
        $chartRevenue = [];
        $chartDisputes = [];
        $chartLabels = [];
        
        for ($i = 5; $i >= 0; $i--) {
            // Lấy theo tháng cho biểu đồ khiếu nại
            $monthStart = date('Y-m-01', strtotime("-$i months"));
            $monthEnd = date('Y-m-t', strtotime("-$i months"));
            $monthLabel = date('m/Y', strtotime("-$i months"));
            
            $stmtD = $db->prepare("SELECT COUNT(id) FROM `orders` WHERE DATE(created_at) BETWEEN ? AND ? AND `status` = 'disputed'");
            $stmtD->execute([$monthStart, $monthEnd]);
            $chartDisputes[] = (int) $stmtD->fetchColumn();
            
            // Doanh thu vẫn lấy 7 ngày gần nhất (như cũ) hoặc 6 tháng gần nhất tùy biến UI
            // Ở đây tôi giữ logic 7 ngày cũ cho doanh thu nhưng chuẩn bị labels tháng cho Khiếu nại
            $chartLabels[] = $monthLabel;
        }

        // Logic 7 ngày cũ cho Doanh thu (để tránh vỡ biểu đồ cũ)
        $revenueLabels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $label = date('d/m', strtotime("-$i days"));
            $stmt = $db->prepare("SELECT SUM(total_amount) FROM `orders` WHERE DATE(created_at) = ? AND `status` = 'completed'");
            $stmt->execute([$date]);
            $dailyRevenue = (float) $stmt->fetchColumn();
            $chartRevenue[] = round($dailyRevenue / 1000000, 2);
            $revenueLabels[] = $label;
        }

        $this->success([
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'total_customers' => $totalCustomers,
            'chart_data' => [
                'labels' => $revenueLabels,
                'revenue' => $chartRevenue,
                'status' => $chartStatus,
                'disputes' => [
                    'labels' => $chartLabels, // Theo tháng
                    'counts' => $chartDisputes
                ]
            ]
        ]);
    }
}
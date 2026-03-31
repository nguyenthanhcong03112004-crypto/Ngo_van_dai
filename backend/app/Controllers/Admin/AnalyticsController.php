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

        // 3. Dữ liệu biểu đồ doanh thu 7 ngày qua
        $chartRevenue = [];
        $chartLabels = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $label = date('d/m', strtotime("-$i days"));
            
            $stmt = $db->prepare("SELECT SUM(total_amount) FROM `orders` WHERE DATE(created_at) = ? AND `status` = 'completed'");
            $stmt->execute([$date]);
            $dailyRevenue = (float) $stmt->fetchColumn();
            
            // Chia cho 1.000.000 để hiển thị dạng "Triệu ₫" trên biểu đồ
            $chartRevenue[] = round($dailyRevenue / 1000000, 2);
            $chartLabels[] = $label;
        }

        $this->success([
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'total_customers' => $totalCustomers,
            'chart_data' => [
                'labels' => $chartLabels,
                'revenue' => $chartRevenue,
                'status' => $chartStatus
            ]
        ]);
    }
}
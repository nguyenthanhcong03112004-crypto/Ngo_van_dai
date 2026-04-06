<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Core\BaseController;
use Core\Middleware;
use Models\Review;

class ReviewController extends BaseController
{
    public function index(): void
    {
        Middleware::requireRole('admin');
        
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 10);
        $search = $_GET['search'] ?? null;

        $reviewModel = new Review();
        $reviews = $reviewModel->getAllAdmin($page, $limit, $search);
        $total = $reviewModel->countAllAdmin($search);

        $this->success([
            'reviews' => $reviews,
            'page' => $page,
            'total_pages' => ceil($total / $limit)
        ]);
    }

    public function recent(): void
    {
        Middleware::requireRole('admin');
        $reviewModel = new Review();
        $reviews = $reviewModel->getRecentAll(5); // Lấy 5 đánh giá mới nhất
        $this->success($reviews);
    }

    public function delete(int $id): void
    {
        Middleware::requireRole('admin');
        $reviewModel = new Review();
        if ($reviewModel->delete($id)) {
            $this->success(null, 'Đã xóa đánh giá thành công.');
        } else {
            $this->error('Không thể xóa đánh giá này.', 500);
        }
    }
}
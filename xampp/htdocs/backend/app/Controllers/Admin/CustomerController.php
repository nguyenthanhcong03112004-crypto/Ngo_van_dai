<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Core\BaseController;
use Core\Middleware;
use Models\User;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CustomerController extends BaseController
{
    private User $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
    }

    public function index(): void
    {
        Middleware::requireRole('admin');

        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 100);

        $customers = $this->userModel->getAll($page, $limit);
        $total = $this->userModel->countAll();

        $this->success([
            'customers' => $customers,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => ceil($total / $limit)
            ]
        ]);
    }

    public function show(int $id): void
    {
        Middleware::requireRole('admin');
        $customer = $this->userModel->findById($id);
        if (!$customer) {
            $this->error('Customer not found', 404);
        }
        $this->success($customer);
    }

    public function updateStatus(int $id): void
    {
        Middleware::requireRole('admin');
        $body = $this->getBody();
        $status = $body['status'] ?? null;

        if (!in_array($status, ['active', 'locked'])) {
            $this->error('Trạng thái không hợp lệ. Phải là "active" hoặc "locked".', 422);
        }

        if ($this->userModel->updateStatus($id, $status)) {
            $this->success(null, "Cập nhật trạng thái người dùng thành công.");
        } else {
            $this->error('Không thể cập nhật trạng thái người dùng', 500);
        }
    }

    public function export(): void
    {
        Middleware::requireRole('admin');
        
        // Lấy tất cả khách hàng (giới hạn 10,000 dòng để tối ưu bộ nhớ)
        $customers = $this->userModel->getAll(1, 10000);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Đặt tiêu đề cột
        $sheet->setCellValue('A1', 'ID Khách Hàng')
              ->setCellValue('B1', 'Họ Tên')
              ->setCellValue('C1', 'Email')
              ->setCellValue('D1', 'Số Điện Thoại')
              ->setCellValue('E1', 'Tổng Đơn Hàng')
              ->setCellValue('F1', 'Trạng Thái')
              ->setCellValue('G1', 'Ngày Đăng Ký');

        $row = 2;
        foreach ($customers as $c) {
            $sheet->setCellValue('A'.$row, $c['id'])
                  ->setCellValue('B'.$row, $c['name'])
                  ->setCellValue('C'.$row, $c['email'])
                  ->setCellValue('D'.$row, $c['phone'] ?? 'N/A')
                  ->setCellValue('E'.$row, $c['orders_count'] ?? 0)
                  ->setCellValue('F'.$row, $c['status'] === 'active' ? 'Hoạt động' : 'Bị khóa')
                  ->setCellValue('G'.$row, date('d/m/Y H:i', strtotime($c['created_at'])));
            $row++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="DanhSachKhachHang.xlsx"');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit; // Kết thúc request tại đây, không trả về JSON
    }
}
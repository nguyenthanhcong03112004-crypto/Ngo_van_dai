<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Core\BaseController;
use Core\Middleware;
use Models\Voucher;

class VoucherController extends BaseController
{
    private Voucher $voucherModel;

    public function __construct()
    {
        parent::__construct();
        $this->voucherModel = new Voucher();
    }

    public function index(): void
    {
        Middleware::requireRole('admin');
        $vouchers = $this->voucherModel->getAll();
        $this->success($vouchers);
    }

    public function create(): void
    {
        Middleware::requireRole('admin');
        $body = $this->getBody();

        $missing = $this->validate($body, ['code', 'discount_amount']);
        if (!empty($missing)) {
            $this->error('Thiếu thông tin bắt buộc: ' . implode(', ', $missing), 422);
        }

        try {
            $this->voucherModel->create($body);
            $this->success(null, 'Tạo mã giảm giá thành công.', 201);
        } catch (\Exception $e) {
            // Bắt lỗi trùng lặp mã (Duplicate entry)
            $this->error('Lỗi khi tạo mã giảm giá. Mã này có thể đã tồn tại.', 500);
        }
    }
}
<?php
declare(strict_types=1);

namespace Controllers\User;

use Models\Product;
use Core\Logger;

class ProductController
{
    private Product $productModel;

    public function __construct()
    {
        $this->productModel = new Product();
    }

    /**
     * GET /api/user/products
     * Query: search, category_id, page, limit
     */
    public function index(): void
    {
        $search     = $_GET['search'] ?? null;
        $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
        $page       = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit      = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

        try {
            $products = $this->productModel->getAll($search, $categoryId, $page, $limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'status'  => 'success',
                'message' => 'Fetched featured products successfully',
                'data'    => [
                    'products' => $products,
                    'count'    => count($products),
                    'page'     => $page,
                    'limit'    => $limit
                ]
            ]);
        } catch (\Exception $e) {
            Logger::getInstance()->error("Error fetching products: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Internal Server Error']);
        }
    }

    /**
     * GET /api/user/products/{id}
     */
    public function show(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        
        try {
            $product = $this->productModel->getById($id);
            header('Content-Type: application/json');
            
            if (!$product) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Product not found']);
                return;
            }

            echo json_encode([
                'status'  => 'success',
                'message' => 'Product fetched successfully',
                'data'    => $product
            ]);
        } catch (\Exception $e) {
            Logger::getInstance()->error("Error fetching product {$id}: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Internal Server Error']);
        }
    }

    /**
     * GET /api/user/vouchers
     * (Placeholder as used in routes.php)
     */
    public function vouchers(): void
    {
        header('Content-Type: application/json');
        echo json_encode([
            'status'  => 'success',
            'message' => 'Vouchers list',
            'data'    => []
        ]);
    }
}

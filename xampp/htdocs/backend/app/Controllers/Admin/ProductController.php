<?php
declare(strict_types=1);

namespace Controllers\Admin;

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
     * GET /api/admin/products
     */
    public function index(): void
    {
        try {
            $products = $this->productModel->getAll(null, null, 1, 100);
            header('Content-Type: application/json');
            echo json_encode([
                'status'  => 'success',
                'message' => 'Fetched all products for admin',
                'data'    => $products
            ]);
        } catch (\Exception $e) {
            Logger::getInstance()->error("Admin Error fetching products: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Internal Server Error']);
        }
    }

    /**
     * POST /api/admin/products
     */
    public function create(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $required = ['name', 'price', 'category_id'];
            $missing  = [];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    $missing[] = $field;
                }
            }

            if (!empty($missing)) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode([
                    'status'  => 'error', 
                    'message' => 'Missing required fields: ' . implode(', ', $missing)
                ]);
                return;
            }

            $id = $this->productModel->create($data);
            
            header('Content-Type: application/json');
            echo json_encode([
                'status'  => 'success',
                'message' => 'Product created successfully',
                'data'    => ['id' => $id]
            ]);
        } catch (\Exception $e) {
            Logger::getInstance()->error("Admin Error creating product: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Internal Server Error']);
        }
    }

    /**
     * PUT /api/admin/products/{id}
     */
    public function update(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $success = $this->productModel->update($id, $data);
            header('Content-Type: application/json');
            
            if (!$success) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Failed to update product']);
                return;
            }

            echo json_encode([
                'status'  => 'success',
                'message' => 'Product updated successfully'
            ]);
        } catch (\Exception $e) {
            Logger::getInstance()->error("Admin Error updating product {$id}: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Internal Server Error']);
        }
    }

    /**
     * DELETE /api/admin/products/{id}
     */
    public function delete(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        
        try {
            $success = $this->productModel->softDelete($id);
            header('Content-Type: application/json');
            
            if (!$success) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete product']);
                return;
            }

            echo json_encode([
                'status'  => 'success',
                'message' => 'Product deleted successfully'
            ]);
        } catch (\Exception $e) {
            Logger::getInstance()->error("Admin Error deleting product {$id}: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Internal Server Error']);
        }
    }
}

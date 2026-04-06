<?php
declare(strict_types=1);

namespace Controllers\User;

use Core\BaseController;
use Core\Middleware;
use Models\Wishlist;

class WishlistController extends BaseController
{
    private Wishlist $wishlistModel;

    public function __construct()
    {
        parent::__construct();
        $this->wishlistModel = new Wishlist();
    }

    /**
     * GET /api/user/wishlist
     */
    public function index(): void
    {
        $payload = Middleware::requireAuth();
        $userId = (int)$payload['user_id'];

        try {
            $items = $this->wishlistModel->getByUser($userId);
            $this->success($items, 'Wishlist fetched successfully');
        } catch (\Exception $e) {
            $this->logger->error("Error fetching wishlist for user {$userId}: " . $e->getMessage());
            $this->error('Internal Server Error', 500);
        }
    }

    /**
     * POST /api/user/wishlist
     * Body: { "product_id": 123 }
     */
    public function add(): void
    {
        $payload = Middleware::requireAuth();
        $userId = (int)$payload['user_id'];
        $body = $this->getBody();

        if (empty($body['product_id'])) {
            $this->error('Missing product_id', 422);
        }

        try {
            $success = $this->wishlistModel->add($userId, (int)$body['product_id']);
            if ($success) {
                $this->success(null, 'Product added to wishlist');
            } else {
                $this->error('Failed to add product to wishlist');
            }
        } catch (\Exception $e) {
            $this->logger->error("Error adding to wishlist: " . $e->getMessage());
            $this->error('Internal Server Error', 500);
        }
    }

    /**
     * DELETE /api/user/wishlist/{product_id}
     */
    public function remove(string $productId): void
    {
        $payload = Middleware::requireAuth();
        $userId = (int)$payload['user_id'];
        $pid = (int)$productId;

        try {
            $success = $this->wishlistModel->remove($userId, $pid);
            if ($success) {
                $this->success(null, 'Product removed from wishlist');
            } else {
                $this->error('Failed to remove product from wishlist');
            }
        } catch (\Exception $e) {
            $this->logger->error("Error removing from wishlist: " . $e->getMessage());
            $this->error('Internal Server Error', 500);
        }
    }
}

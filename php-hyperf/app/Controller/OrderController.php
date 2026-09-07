<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\OrderService;
use Hyperf\Di\Annotation\Inject;

class OrderController extends AbstractController
{
    #[Inject]
    protected OrderService $orderService;

    /**
     * 生成秒杀商品库存
     */
    public function generateStock()
    {
        $response = $this->orderService->generateStockTask();

        return $this->returnData($response);
    }

    /**
     * 秒杀商品
     */
    public function flashSaleProducts()
    {
        $authorization = $this->request->header('Authorization') ?: '';
        $response = $this->orderService->flashSaleProductsTask($authorization);

        return $this->returnData($response);
    }
}

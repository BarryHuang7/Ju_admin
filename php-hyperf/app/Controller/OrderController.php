<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\OrderService;
use App\Service\UtilsService;
use Hyperf\Di\Annotation\Inject;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

class OrderController extends AbstractController
{
    #[Inject]
    protected OrderService $orderService;
    #[Inject]
    protected UtilsService $utilsService;

    /**
     * 生成秒杀商品库存
     */
    public function generateStock(): PsrResponseInterface
    {
        $response = $this->orderService->generateStockTask();

        return $this->returnData($response);
    }

    /**
     * 秒杀商品
     */
    public function flashSaleProducts(): PsrResponseInterface
    {
        $ip = $this->utilsService->getClientRealIp($this->request);
        $authorization = $this->request->header('Authorization') ?: '';

        $response = $this->orderService->flashSaleProductsTask($ip, $authorization);

        return $this->returnData($response);
    }
}

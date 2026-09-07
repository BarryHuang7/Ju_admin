<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\ProductStock;
use Psr\Log\LoggerInterface;
use Hyperf\Support\LoggerFactory;

class OrderController extends AbstractController
{
    /**
     * 商品编码
     */
    private const PRODUCT_NUMBER = 'MS-001';
    protected LoggerInterface $logger;

    public function __construct(LoggerFactory $loggerFactory)
    {
        // 第一个参数 'hyperf' 是日志文件名，第二个参数 'daily' 是配置文件中的 channel 名称
        $this->logger = $loggerFactory->get('hyperf', 'daily');
    }

    /**
     * 生成秒杀商品库存
     */
    public function generateStock(): ResponseInterface
    {
        try {
            // 数据库原子操作
            // $affected = ProductStock::query()
            //     ->where('product_number', self::PRODUCT_NUMBER)
            //     ->where('stock', '<', 1)
            //     ->update(['stock' => 1]);

            // if ($affected === 0) {
            //     ProductStock::create([
            //         'product_name' => 'Hyper秒杀商品',
            //         'product_number' => self::PRODUCT_NUMBER,
            //         'stock' => 1
            //     ]);
            // }

            // upsert：存在则更新，不存在则插入
            ProductStock::query()->upsert([
                [
                    'product_number' => self::PRODUCT_NUMBER,
                    'product_name' => 'Hyper秒杀商品',
                    'stock' => 1,
                ]
            ], ['product_number'], ['stock', 'product_name']);

            return $this->response->json([
                'code' => 200,
                'data' => '',
                'msg' => '库存初始化成功'
            ])->withStatus(200);
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
            $this->logger->error("【生成秒杀商品库存】失败", [
                'error' => $errorMessage,
                'trace' => $e->getTraceAsString()
            ]);
            return $this->response->json([
                'code' => 500,
                'error' => $errorMessage,
                'msg' => 'Fail'
            ])->withStatus(200);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Service;

use Hyperf\Redis\Redis;
use Hyperf\Amqp\Producer;
use App\Model\ProductStock;
use Hyperf\DbConnection\Db;
use Psr\Log\LoggerInterface;
use Hyperf\Di\Annotation\Inject;
use App\Amqp\Producers\TaskProducer;
use App\Model\OrderRecord;

class OrderService
{
	private Redis $redis;
    protected LoggerInterface $logger;
    #[Inject]
    private Producer $producer;

    /**
     * 商品编码
     */
    private const PRODUCT_NUMBER = 'MS-001';
    /**
     * 库存key
     */
    private const STOCK_KEY = 'seckill:stock:';
    /**
     * 用户抢购请求记录key，不存在再创建
     */
    private const STOCK_NX_KEY = 'seckill:stock:nx:';
    /**
     * 用户已抢购key
     */
    private const STOCK_USER_KEY = 'seckill:user:';

    public function __construct(Redis $redis, LoggerInterface $logger)
    {
        $this->redis = $redis;
        $this->logger = $logger;
    }

	/**
	 * 生成秒杀商品库存
	 */
	public function generateStockTask(): array
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

            $productNumber = self::PRODUCT_NUMBER;
            $stock = 1;

            // upsert：存在则更新，不存在则插入
            ProductStock::query()->upsert([
                [
                    'product_number' => $productNumber,
                    'product_name' => 'Hyper秒杀商品',
                    'stock' => $stock,
                ]
            ], ['product_number'], ['stock', 'product_name']);

            // 删除可能的残留数据
            $keys = $this->redis->keys(self::STOCK_USER_KEY . $productNumber . ':*');
            if (!empty($keys)) {
                $this->redis->del($keys);
            }

            // $this->redis->setex(self::STOCK_KEY . $productNumber, 300, $stock);

            // 使用 Lua 脚本保证原子性
            $luaScript = <<<LUA
                redis.call('SET', KEYS[1], ARGV[1])
                redis.call('EXPIRE', KEYS[1], ARGV[2])
                return 1
            LUA;

            // 5分钟后过期
            $this->redis->eval($luaScript, [
                self::STOCK_KEY . $productNumber,
                $stock,
                300
            ], 1);

            $this->logger->info("【生成秒杀商品库存】成功", [
                'product_number' => $productNumber,
                'stock' => $stock,
                'time' => date('Y-m-d H:i:s')
            ]);

            return [
                'code' => 200,
                'data' => '',
				'error' => '',
                'msg' => '库存初始化成功! 库存数固定为1, 抢购时间5分钟后过期.',
				'status' => 200
            ];
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
            $this->logger->error("【生成秒杀商品库存】失败", [
                'error' => $errorMessage,
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'code' => 500,
                'data' => '',
                'error' => $errorMessage,
                'msg' => 'Fail',
				'status' => 500
            ];
        }
	}

	/**
     * 秒杀商品
     * @param string $ip 请求ip地址
     * @param string $authorization 请求认证
     * @return array json
     */
    public function flashSaleProductsTask(string $ip, string $authorization): array
	{
		try {
            $userInfo = $authorization ? json_decode($this->redis->get($authorization), true) : null;

            if (!is_array($userInfo) || empty($userInfo['userID'])) {
                return [
                    'code' => 403,
					'data' => '',
                    'error' => '无权访问！',
                    'msg' => 'Fail',
					'status' => 403
                ];
            }

            $userID = $userInfo['userID'];

            // 幂接口
            $temp = $this->redis->set(self::STOCK_NX_KEY . $userID, $userID, ['EX' => 1, 'NX' => true]);

            if ($temp) {
                $stockKey = self::STOCK_KEY . self::PRODUCT_NUMBER;
                // 检查是否有抢购商品
                if ($this->redis->exists($stockKey) == 0) {
                    return [
                        'code' => 200,
                        'data' => '',
                        'error' => '',
                        'msg' => '现在未有秒杀商品！',
                		'status' => 200
                    ];
                }

                // 检查该用户是否已抢购
                $userBuyKey = self::STOCK_USER_KEY . self::PRODUCT_NUMBER . ':' . $userID;
                if ($this->redis->exists($userBuyKey)) {
                    return [
                        'code' => 422,
                        'data' => '',
                        'error' => '',
                        'msg' => '你已抢到商品。请勿重复点击！',
                		'status' => 200
                    ];
                }

                // 原子递减（Lua 脚本）
                $luaScript = <<<LUA
                    local stock_key = KEYS[1]
                    local user_key = KEYS[2]
                    
                    -- 获取当前库存
                    local current_stock = redis.call('GET', stock_key)
                    if not current_stock or tonumber(current_stock) <= 0 then
                        return 0  -- 库存不足
                    end
                    
                    -- 扣减库存
                    local new_stock = redis.call('DECR', stock_key)
                    -- 检查是否超卖,可能会扣到负数
                    if tonumber(new_stock) < 0 then
                        redis.call('INCR', stock_key)  -- 回滚
                        return 0
                    end
                    
                    -- 记录用户购买
                    redis.call('SETEX', user_key, 300, 1)  -- 5分钟有效期
                    
                    return 1  -- 扣减成功
                LUA;

                $result = $this->redis->eval($luaScript, [
                    $stockKey,
                    $userBuyKey
                ], 2);

                if ($result == 1) {
                    $this->logger->info("用户{$userID}【秒杀商品】成功");

                    // 队列减库存
                    $message = new TaskProducer([
                        'ip' => $ip,
                        'user_id' => $userID,
                        'order_no' => '生产环境唯一索引',
                        'order_name' => 'hyperf秒杀商品订单',
                        'product_stock_id' => 678,
                        'product_name' => '秒杀商品',
                        'product_number' => self::PRODUCT_NUMBER,
                        'stock' => 1,
                        'remark' => 'hyperf秒杀商品',
                        'retry_count' => 0
                    ]);
                    $this->producer->produce($message);

                    return [
                        'code' => 200,
                        'data' => '',
                        'error' => '',
                        'msg' => '抢购成功',
                		'status' => 200
                    ];
                } else {
                    return [
                        'code' => 200,
                		'data' => '',
                        'error' => '',
                        'msg' => '手慢了已经抢光了！',
                		'status' => 200
                    ];
                }
            } else {
                return [
                    'code' => 422,
                	'data' => '',
                    'error' => '',
                    'msg' => '请求繁忙，请稍后重试！',
                	'status' => 200
                ];
            }
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
            $this->logger->error("【秒杀商品库存】失败", [
                'error' => $errorMessage,
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'code' => 500,
                'data' => '',
                'msg' => 'Fail',
                'error' => $errorMessage,
                'status' => 500
            ];
        }
	}

	/**
     * 减商品库存
     * @param array $data['product_number', 'ip', 'user_id', 'order_no', 'order_name', 'product_stock_id', 'product_name', 'stock', 'remark']
     * @return void
     */
    public function stockConsume(array $data): void
    {
        try {
            $productNumber = !empty($data['product_number']) ? $data['product_number'] : '';
            $ip = !empty($data['ip']) ? $data['ip'] : '';
            $userID = !empty($data['user_id']) ? $data['user_id'] : '';
            $orderNo = !empty($data['order_no']) ? $data['order_no'] : '';
            $orderName = !empty($data['order_name']) ? $data['order_name'] : '';
            $productStockId = !empty($data['product_stock_id']) ? $data['product_stock_id'] : '';
            $productName = !empty($data['product_name']) ? $data['product_name'] : '';
            $stock = !empty($data['stock']) ? $data['stock'] : '';
            $remark = !empty($data['remark']) ? $data['remark'] : '';
    
            if ($productNumber) {
                Db::transaction(function () use ($ip, $userID, $orderNo, $orderName, $productStockId, $productName, $productNumber, $stock, $remark) {
                    $product = ProductStock::query()
                        ->where('product_number', $productNumber)
                        // 悲观锁
                        ->lockForUpdate()
                        ->first();
                    
                    if ($product && $product->stock > 0) {
                        $product->stock -= 1;
                        $product->save();

                        $newData = [
                            'ip' => $ip,
                            'user_id' => $userID,
                            'order_no' => $orderNo,
                            'order_name' => $orderName,
                            'product_stock_id' => $productStockId,
                            'product_name' => $productName,
                            'product_number' => $productNumber,
                            'stock' => $stock,
                            'remark' => $remark
                        ];

                        // 生成订单记录
                        $order = OrderRecord::create($newData);
    
                        $this->logger->info("【减商品库存】成功", $newData);
                    }
                });
            } else {
                $this->logger->error("【减商品库存】失败", [
                    'data' => $data
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error("【减商品库存】catch失败", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data,
            ]);

            if (!empty($data['retry_count']) && $data['retry_count'] < 2) {
                // 重试逻辑
                $data['retry_count'] += 1;
                $this->logger->info("【减商品库存】重试第{$data['retry_count']}次", [
                    'data' => $data
                ]);
                $this->stockConsume($data);
            } else {
                $this->logger->error("【减商品库存】重试失败，已达到最大重试次数", [
                    'data' => $data
                ]);
            }
        }
    }
}

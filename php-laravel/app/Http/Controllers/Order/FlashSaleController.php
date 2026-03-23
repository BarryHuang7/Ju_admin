<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Http\Controllers\Common\UtilsController;
use App\Models\OrderRecord;
use DateTime;

class FlashSaleController extends Controller
{
    /**
     * 秒杀产品状态 redis key
     */
    protected $FSP_StatusKey = 'flash_sale_products_status';
    /**
     * 秒杀产品已抢购到商品的用户列表 redis key
     */
    protected $FSP_UsersKey = 'flash_sale_products_users';
    /**
     * 秒杀产品列表 redis key
     */
    protected $FSP_Key = 'flash_sale_products';
    /**
     * 秒杀产品不存在再创建 redis key
     */
    protected $FSP_NXKey = 'flash_sale_products_nx_';

    /**
     * 模拟秒杀
     */
    public function simulationFlashSale(Request $request) {
        try {
            $utils = new UtilsController();

            $userInfo = $utils->getUserInfo($request);

            if (!$userInfo || !$userInfo['userID']) {
                $this->returnData(403, '无权访问！');
            }

            $userID = $userInfo['userID'];
            $input = $request->input();

            /**
             * 是否开启接口幂等性
             */
            $enableIdempotency = isset($input['enableIdempotency']) ? $input['enableIdempotency'] : '';

            $ip = (new UtilsController())->getClientRealIp($request);

            if ($enableIdempotency === true) {
                // 不存在才创建10秒过期的值
                $temp = Redis::set('simulationFlashSale_' . $userID, json_encode([
                    'status' => true
                ]), 'EX', 10, 'NX');

                if ($temp) {
                    $this->saveOrder($ip, $userID);

                    $this->returnData(200, '下单成功（开启接口幂等性）！');
                } else {
                    $this->returnData(500, '请求繁忙，请稍后重试！');
                }
            } else if ($enableIdempotency === false) {
                $this->saveOrder($ip, $userID);

                $this->returnData(200, '下单成功！');
            } else {
                $this->returnData(400, '非法请求！');
            }
        } catch (\Exception $e) {
            $errorMsg = '模拟秒杀请求错误: ' . $e->getMessage();

            Log::error($errorMsg);
            $this->returnData(500, $errorMsg);
        }
    }

    /**
     * 订单加一
     */
    private function saveOrder($ip, $userId) {
        $order = new OrderRecord();
        $order->ip = $ip;
        $order->user_id = $userId;
        $order->order_no = 'NO1234567';
        $order->order_name = 'XX商城订单';
        $order->product_name = 'XX商城限时秒杀优惠衣服';
        $order->product_number = 'P777';
        $order->stock = 1;
        $order->remark = '模拟秒杀';
        $order->save();

        // 模拟业务逻辑
        sleep(3);
    }

    /**
     * 运行octane: php artisan octane:start --port=7077
     * 停止 php artisan octane:stop
     * 查看端口占用 sudo lsof -i :7077
     */

    /**
     * 生成抢购商品
     */
    public function generateProducts() {
        try {
            $status = Redis::hget($this->FSP_StatusKey, 'startTime');

            if (!$status) {
                $now = new DateTime();

                // 先跳到下一分钟的0秒，再加一分钟
                $dateTime = (clone $now)->modify('+' . (60 - (int)$now->format('s')) . ' seconds')
                    ->modify('+1 minute');
                $time = $dateTime->format('Y-m-d H:i:s');

                Redis::hset($this->FSP_StatusKey, 'startTime', $time);

                Redis::sadd($this->FSP_Key, json_encode([
                    // 商品 id
                    'productId' => 77,
                    // 商品数量
                    'number' => 1
                ]));

                $this->returnData(
                    200,
                    '创建商品成功！' . $time . ' 准备开始抢购！',
                    [ 'startTime' => $time ]
                );
            } else {
                $this->returnData(
                    422,
                    '已创建商品！请重复操作！' . $status . ' 开始抢购！',
                    [ 'startTime' => $status ]
                );
            }
        } catch (\Exception $e) {
            $errorMsg = '生成抢购商品请求错误: ' . $e->getMessage();

            Log::error($errorMsg);
            $this->returnData(500, $errorMsg);
        }
    }

    /**
     * 清除秒杀数据
     */
    public function removeFlashSaleProducts() {
        try {
            Redis::hdel($this->FSP_StatusKey, 'startTime');

            $products = Redis::smembers($this->FSP_Key);
            if (!empty($products)) {
                Redis::srem($this->FSP_Key, ...$products);
            }

            $users = Redis::smembers($this->FSP_UsersKey);
            if (!empty($users)) {
                Redis::srem($this->FSP_UsersKey, ...$users);
            }

            $this->returnData(200, '已清除秒杀数据。');
        } catch (\Exception $e) {
            $errorMsg = '清除秒杀数据请求错误: ' . $e->getMessage();

            Log::error($errorMsg);
            $this->returnData(500, $errorMsg);
        }
    }

    /**
     * 秒杀商品
     */
    public function flashSaleProducts(Request $request) {
        try {
            // 用户信息
            $utils = new UtilsController();

            $userInfo = $utils->getUserInfo($request);

            if (!$userInfo || !$userInfo['userID']) {
                return $this->octaneReturnData(403, 403, '无权访问！');
            }

            $userID = $userInfo['userID'];

            // 幂接口
            $temp = Redis::set($this->FSP_NXKey . $userID, $userID, 'EX', 1, 'NX');

            if ($temp) {
                // 商品状态
                $status = Redis::hget($this->FSP_StatusKey, 'startTime');

                if (!$status) {
                    return $this->octaneReturnData(200, 200, '现在未有秒杀商品！');
                }

                $now = new DateTime();

                if (strtotime($now->format('Y-m-d H:i:s')) < strtotime($status)) {
                    return $this->octaneReturnData(200, 200, '商品秒杀时间未到！秒杀时间为：' . $status);
                }

                // 商品列表
                $products = Redis::smembers($this->FSP_Key);

                if (count($products) > 0) {
                    $product = json_decode($products[0], true);

                    $product['userID'] = $userID;
                    // 删除秒杀到的商品
                    Redis::srem($this->FSP_Key, $products[0]);
                    // 是否秒杀成功
                    $success = false;
                    // 已秒杀到商品的用户列表
                    $users = Redis::smembers($this->FSP_UsersKey);

                    if (count($users) > 0) {
                        foreach ($users as $userInfo) {
                            $user = json_decode($userInfo);

                            if ($user->userID === $userID) {
                                return $this->octaneReturnData(200, 422, '你已抢到商品。请勿重复点击！');
                            }
                        }

                        Redis::sadd($this->FSP_UsersKey, json_encode($product));
                        $success = true;
                    } else {
                        Redis::sadd($this->FSP_UsersKey, json_encode($product));
                        $success = true;
                    }

                    return $this->octaneReturnData(200, 200, $success ? '秒杀成功！' : '秒杀失败，请稍后重试！');
                } else {
                    // 已秒杀到商品的用户列表
                    $users = Redis::smembers($this->FSP_UsersKey);

                    if (count($users) > 0) {
                        foreach ($users as $userInfo) {
                            $user = json_decode($userInfo);

                            if ($user->userID === $userID) {
                                return $this->octaneReturnData(200, 422, '你已抢到商品。请勿重复点击！');
                            }
                        }
                    }

                    return $this->octaneReturnData(200, 200, '手慢了已经抢光了！');
                }
            } else {
                return $this->octaneReturnData(200, 422, '请求繁忙，请稍后重试！');
            }
        } catch (\Exception $e) {
            $errorMsg = '秒杀商品请求错误: ' . $e->getMessage();

            Log::error($errorMsg);
            return $this->octaneReturnData(500, 500, $errorMsg);
        }
    }
}

// 以下代码在并发请求时会出现问题
// class Counter {
//     private int $count = 0;

//     private bool $stopped = false;

//     public function increment(): void {
//         if (!$this->stopped) {
//             $this->count = $this->count + 1;
//         }
//     }

//     public function stop(): void {
//         $this->stopped = true;
//     }

//     public function getCount(): int {
//         return $this->count;
//     }
// }

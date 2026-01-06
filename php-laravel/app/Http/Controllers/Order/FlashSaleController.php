<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Http\Controllers\Common\UtilsController;
use App\OrderRecord;

class FlashSaleController extends Controller
{
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
}

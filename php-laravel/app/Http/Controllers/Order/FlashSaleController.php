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
            $input = $request->input();

            /**
             * 是否开启接口幂等性
             */
            $enableIdempotency = isset($input['enableIdempotency']) ? $input['enableIdempotency'] : false;

            $ip = (new UtilsController())->getClientRealIp($request);
            // 会话过期120分钟（详细配置见env SESSION_LIFETIME），一般用登录token保存
            $session = $request->session();
            $sessionId = $session->getId();

            if ($enableIdempotency) {
                $temp = Redis::set('simulationFlashSale_' . $sessionId, json_encode([
                    'status' => true
                ]), 'EX', 10, 'NX');

                if ($temp) {
                    $this->saveOrder($ip, $sessionId);

                    return response()->json([
                        'code' => 200,
                        'msg' => '下单成功（开启接口幂等性）！'
                    ]);
                } else {
                    return response()->json([
                        'code' => 500,
                        'msg' => '请求繁忙，请稍后重试！'
                    ]);
                }
            } else {
                $this->saveOrder($ip);

                return response()->json([
                    'code' => 200,
                    'msg' => '下单成功！'
                ]);
            }
        } catch (RequestException $e) {
            $errorMsg = '模拟秒杀请求错误: ' . $e->getMessage();

            Log::error($errorMsg);
            return response()->json([
                'code' => 500,
                'msg' => $errorMsg
            ]);
        }
    }

    /**
     * 订单加一
     */
    private function saveOrder($ip, $sessionId) {
        $order = new OrderRecord();
        $order->id = $ip;
        $order->session_id = $sessionId;
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

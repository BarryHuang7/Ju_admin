<?php

namespace App\Http\Controllers\Websocket;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Http\Controllers\Common\UtilsController;
use App\Jobs\TaskScheduler;

class ChatConteroller extends Controller
{
    protected $Utils;

    public function __construct(UtilsController $Utils) {
        $this->Utils = $Utils;
    }

    /**
     * 获取所有在线人信息
     */
    public function getAllOnlineUser(Request $request) {
        $onlineUsers = Redis::smembers('php_websocket_onlineUsers');
        $users = array();

        if (count($onlineUsers) > 0) {
            $userInfo = $this->Utils->getUserInfo($request);
            $sendUserId = $userInfo && count($userInfo) > 0 ? $userInfo['userID'] : 0;

            if ($sendUserId) {
                foreach ($onlineUsers as $user) {
                    $user = json_decode($user, true);
                    $userId = $user['user_id'];

                    // 排除自己
                    if ($userId != $sendUserId) {
                        $users[] = [
                            'user_name' => $user['user_name'],
                            'user_id' => $userId
                        ];
                    }
                }
            }
        }

        $this->returnData(200, 'Success!', $users);
    }

    /**
     * 制定用户发消息
     */
    public function sendMessage(Request $request) {
        $reqData = $request->all();
        $this->requestValidate(
            $reqData,
            [
                'user_id' => 'required|integer',
                'user_name' => 'required|string',
                'message' => 'required|string',
                'send_user_id' => 'required|integer',
                'send_user_name' => 'required|string'
            ]
        );

        $message = json_encode([
            'type' => 'Message',
            'user_id' => $reqData['user_id'],
            'user_name' => $reqData['user_name'],
            'message' => $reqData['message'],
            'send_user_id' => $reqData['send_user_id'],
            'send_user_name' => $reqData['send_user_name']
        ]);
        $flag = $this->Utils->sendWebSocketMessage($reqData['user_name'], $reqData['user_id'], $message);

        $this->returnData($flag ? 200 : 500, $flag ? 'Success!' : 'Error!');
    }

    /**
     * 发送定时消息
     */
    public function sendTimingMessage(Request $request) {
        $userInfo = $this->Utils->getUserInfo($request);

        if ($userInfo && count($userInfo) > 0) {
            $ip = $this->Utils->getClientRealIp($request);

            TaskScheduler::dispatch(3, [
                'user_id' => $userInfo['userID'],
                'user_name' => $userInfo['userName'],
                'message' => json_encode([
                    'type' => 'TimingMessage',
                    'message' => '您的余额不足10元。'
                ])
            ], $ip)
            // 延迟5秒
            ->delay(now()->addSeconds(5));
        }

        $this->returnData(200, 'Success!');
    }
}

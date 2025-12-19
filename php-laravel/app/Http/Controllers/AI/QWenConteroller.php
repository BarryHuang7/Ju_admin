<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Email\SendEmailController;
use App\QWenInfo;
use Illuminate\Support\Facades\Redis;
use App\Jobs\TaskScheduler;

class QWenConteroller extends Controller
{
    // php artisan make:controller AI/QWenConteroller

    public function chat(Request $request) {
        $input = $request->input();
        $userId = isset($input['user_id']) ? $input['user_id'] : '';
        $userName = isset($input['user_name']) ? $input['user_name'] : '';
        $messages = isset($input['messages']) ? $input['messages'] : '';

        $websocketId = Redis::get('PHP_Redis_WebSocket_' . $userName . '_' . $userId);

        if (!$websocketId) {
            return response()->json([
                'code' => 403,
                'msg' => '会话过期，请刷新页面！'
            ]);
        }

        $ip = (new SendEmailController())->getClientRealIp($request);
        $log_msg = "ip:【" . $ip . "】, user_id:【" . $userId . "】, user_name:【" . $userName . "】, messages:【" . $messages . "】";

        Log::info($log_msg . ", 正在请求QWen chat。");

        if (!$userId || !$userName || !$messages) {
            return response()->json([
                'code' => 400,
                'msg' => '非法请求！'
            ]);
        }

        $qwenModel = new QWenInfo();
        $qwenModel->ip = $ip;
        $qwenModel->content = $messages;
        $qwenModel->save();

        TaskScheduler::dispatch(2, [
            'userId' => $userId,
            'userName' => $userName,
            'messages' => $messages,
            'qwenModel' => $qwenModel
        ], $ip);

        return response()->json([
            'code' => 200,
            'msg' => '已成功加入队列'
        ]);
    }

    /**
     * 处理调用通义千问API逻辑
     */
    public function handQWenAPI($data, $ip) {
        try {
            $userId = $data['userId'];
            $userName = $data['userName'];
            $messages = $data['messages'];
            $qwenModel = $data['qwenModel'];

            $client = new Client([
                // 禁用 SSL 验证
                'verify' => false,
                'timeout' => 120
            ]);

            $response = $client->request(
                'POST',
                'https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . env('QWEN_TOKEN'),
                        'Content-Type' => 'application/json'
                    ],
                    'json' => [
                        'model' => 'qwen-plus',
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => $messages
                            ]
                        ],
                        'stream' => true,
                        'stream_options' => [
                            'include_usage' => true
                        ]
                    ],
                    // 启用流式传输
                    'stream' => true
                ]
            );

            $body = $response->getBody();
            $buffer = '';

            // 逐行读取流式数据
            while (!$body->eof()) {
                $chunk = $body->read(1024);
                $buffer .= $chunk;

                // 检查连接
                if (connection_aborted()) {
                    break;
                }

                // 10ms
                usleep(10000);
            }

            $data = $this->handleStreamResponse($buffer);

            $qwenModel->assistant_response_body = json_encode($data);
            $qwenModel->save();

            /**
             * 通义千问回复完整语句
             */
            $assistantReply = '';
            /**
             * 输入的 Token 数
             */
            $prompt_tokens = 0;
            /**
             * 模型输出的 Token 数
             */
            $completion_tokens = 0;
            /**
             * 此次请求耗费金额
             */
            $amount = 0;

            foreach ($data as $item) {
                $choices = isset($item['choices']) && count($item['choices']) > 0 ? $item['choices'][0] : [];

                if ($choices) {
                    /**
                     * 模型停止生成的原因
                     * 1、触发输入参数中的stop参数，或自然停止输出时为stop
                     * 2、生成长度过长而结束为length
                     * 3、需要调用工具而结束为tool_calls
                     */
                    $finish_reason = isset($choices['finish_reason']) ? $choices['finish_reason'] : '';
                    $delta = isset($choices['delta']) ? $choices['delta'] : [];

                    if ($delta) {
                        $content = isset($delta['content']) ? $delta['content'] : '';

                        if ($content || $finish_reason) {
                            $assistantReply .= $content;

                            // 发送websocket
                            $client->request(
                                'POST',
                                '127.0.0.1:9502/websocket/qwen',
                                [
                                    'headers' => [
                                        'Content-Type' => 'application/json'
                                    ],
                                    'json' => [
                                        'user_name' => $userName,
                                        'user_id' => $userId,
                                        'content' => $content,
                                        'finish_reason' => $finish_reason
                                    ]
                                ]
                            );
                        }
                    }
                } else {
                    // 最后包含 Token 消耗信息
                    $usage = isset($item['usage']) ? $item['usage'] : [];

                    if ($usage) {
                        $prompt_tokens = $usage['prompt_tokens'];
                        $completion_tokens = $usage['completion_tokens'];

                        $amount = ($prompt_tokens * 0.0008) + ($completion_tokens * 0.002);

                        // 发送websocket通知
                        $client->request(
                            'POST',
                            '127.0.0.1:9502/websocket/notice',
                            [
                                'headers' => [
                                    'Content-Type' => 'application/json'
                                ],
                                'json' => [
                                    'user_name' => $userName,
                                    'user_id' => $userId,
                                    'content' => '本次消耗 ' . $amount . ' 元！'
                                ]
                            ]
                        );
                    }
                }
            }

            $qwenModel->prompt_tokens = $prompt_tokens;
            $qwenModel->completion_tokens = $completion_tokens;
            $qwenModel->assistant_content = $assistantReply;
            $qwenModel->updated_at = date('Y-m-d H:i:s');
            $qwenModel->save();

            $log_msg = "ip:【" . $ip . "】, user_id:【" . $userId . "】, user_name:【" . $userName . "】, messages:【" . $messages . "】";
            Log::info($log_msg . ", 完成QWen chat请求。");
        } catch (RequestException $e) {
            Log::error('通义千问请求流式错误: ' . $e->getMessage());
        }
    }

    /**
     * 处理流式数据
     */
    public function handleStreamResponse(String $buffer) {
        $lines = explode("\n", trim($buffer));
        $data = array();

        foreach ($lines as $line) {
            if ($line) {
                $temp_line = trim($line);

                if (str_starts_with($temp_line, 'data: ')) {

                    $temp_data = substr($temp_line, 6);
    
                    if ($temp_data === '[DONE]') {
                        break;
                    }
    
                    $data[] = json_decode($temp_data, true);
                }
            }
        }

        return $data;
    }
}

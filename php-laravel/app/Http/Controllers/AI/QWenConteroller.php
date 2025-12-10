<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Email\SendEmailController;

class QWenConteroller extends Controller
{
    // php artisan make:controller QWenConteroller

    public function chat(Request $request) {
        $input = $request->input();
        $userId = isset($input['user_id']) ? $input['user_id'] : '';
        $userName = isset($input['user_name']) ? $input['user_name'] : '';
        $messages = isset($input['messages']) ? $input['messages'] : '';

        $ip = (new SendEmailController())->getClientRealIp($request);
        $log_msg = "ip: " . $ip . ", user_id: " . $userId . ", user_name: " . $userName . ", messages: " . $messages;

        Log::info($log_msg . ", 正在请求QWen chat。");

        if (!$userId || !$userName || !$messages) {
            return response()->json([
                'code' => 400,
                'msg' => ''
            ]);
        }

        try {
            $client = new Client([
                // 禁用 SSL 验证
                'verify' => false,
                'timeout' => 60
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
            Log::info($log_msg . ", 请求QWen chat返回: " . json_encode($data) . "。");

            /**
             * 通义千问回复完整语句
             */
            $assistantReply = '';

            foreach ($data as $key => $item) {
                $choices = isset($item['choices']) && count($item['choices']) > 0 ? $item['choices'][0] : [];

                if ($choices) {
                    if ($choices['finish_reason'] === 'stop') {
                        break;
                    }

                    $delta = isset($choices['delta']) ? $choices['delta'] : [];

                    if ($delta) {
                        $content = isset($delta['content']) ? $delta['content'] : '';

                        if ($content) {
                            $assistantReply .= $content;

                            // 发送websocket
                            $client->request(
                                'POST',
                                env('QWEN_WEBSOCKET') . '/qwen',
                                [
                                    'headers' => [
                                        'Content-Type' => 'application/json'
                                    ],
                                    'json' => [
                                        'user_name' => $userName,
                                        'user_id' => $userId,
                                        'content' => $content
                                    ]
                                ]
                            );
                        }
                    }
                }
            }

            Log::info($log_msg . ", QWen回复: " . $assistantReply);
        } catch (RequestException $e) {
            Log::error('通义千问请求流式错误: ' . $e->getMessage());
            return response()->json([
                'code' => 400,
                'msg' => '通义千问请求流式错误'
            ]);
        }

        return response()->json([
            'code' => 200,
            'msg' => ''
        ]);
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

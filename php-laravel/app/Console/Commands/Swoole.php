<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use swoole_websocket_server;
use Illuminate\Support\Facades\Redis;

class Swoole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swoole {action}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $action = $this->argument('action');
        switch ($action) {
            case 'close':
                break;
            default:
                $this->start();
                break;
        }
    }

    /**
     * php artisan swoole start& // &常驻后台
     * ps aux | grep "artisan swoole start"
     */
    public function start()
    {
        // 这里是监听的服务端口号
        $this->ws = new swoole_websocket_server("0.0.0.0", 9502);

        // 监听WebSocket连接打开事件
        $this->ws->on('open', function ($ws, $request) {
            $fd = $request->fd;
            $path = $request->server['request_uri'] ? $request->server['request_uri'] : '/';
            $params = $this->parsePath($path);

            if ($params['user_name'] && $params['user_id']) {
                Redis::set('PHP_Redis_WebSocket_' . $params['user_name'] . '_' . $params['user_id'], $fd);
                Redis::set('PHP_Redis_FD_' . $fd, json_encode([
                    'user_name' => $params['user_name'],
                    'user_id' => $params['user_id']
                ]));
            }
        });

        // 监听WebSocket消息事件
        $this->ws->on('message', function ($ws, $frame) {
            // 群发弹幕
            foreach ($this->ws->connections as $fd) {
                if ($this->ws->isEstablished($fd)) {
                    $this->ws->push($fd, $frame->data);
                }
            }
        });

        // 监听WebSocket主动推送消息事件
        $this->ws->on('request', function ($request, $response) {
            $response->header("Content-Type", "application/json");
            $response->header("Access-Control-Allow-Origin", "*");
            $response->header("Access-Control-Allow-Methods", "POST");
            $response->header("Access-Control-Allow-Headers", "Content-Type");

            $path = $request->server['request_uri'];
            $method = $request->server['request_method'];

            switch ($path) {
                // 通义千问发送回答
                case '/websocket/qwen':
                    if ($method === 'POST') {
                        $postData = $request->post ?: json_decode($request->rawContent(), true);

                        $userName = isset($postData['user_name']) ? $postData['user_name'] : '';
                        $userId = isset($postData['user_id']) ? $postData['user_id'] : '';
                        $content = isset($postData['content']) ? $postData['content'] : '';

                        if ($userName && $userId && $content) {
                            $fd = Redis::get('PHP_Redis_WebSocket_' . $userName . '_' . $userId);

                            if ($fd && $this->ws->isEstablished($fd)) {
                                $this->ws->push($fd, json_encode([
                                    'content' => $content
                                ], JSON_UNESCAPED_UNICODE));

                                $response->end(json_encode(['code' => 200]));
                            } else {
                                $response->end(json_encode(['code' => 400]));
                            }
                        } else {
                            $response->end(json_encode(['code' => 400]));
                        }
                    }
                break;
                default:
                    $response->end(json_encode(['code' => 400]));
            }
        });

        // 监听WebSocket连接关闭事件
        $this->ws->on('close', function ($ws, $fd) {
            $userInfoJson = Redis::get('PHP_Redis_FD_' . $fd);

            if ($userInfoJson) {
                $userInfo = json_decode($userInfoJson, true);

                Redis::del('PHP_Redis_WebSocket_' . $userInfo['user_name'] . '_' . $userInfo['user_id']);
                Redis::del('PHP_Redis_FD_' . $fd);
            }
        });

        $this->ws->start();
    }

    /**
     * 解析路径参数
     */
    private function parsePath($path)
    {
        $params = [
            'user_name' => '',
            'user_id' => ''
        ];

        // 移除查询字符串
        $path = strtok($path, '?');

        // 按斜杠分割路径
        $parts = explode('/', trim($path, '/'));

        if (count($parts) >= 2) {
            if ($parts[0] === 'websocket' && count($parts) >= 3) {
                $params['user_name'] = $parts[1];
                $params['user_id'] = intval($parts[2]);
            }
        }

        return $params;
    }
}

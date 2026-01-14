<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Swoole\WebSocket\Server as WebSocketServer;
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
     * 服务器实例
     */
    protected $ws;
    /**
     * 当前连接
     */
    protected $thisFD;
    /**
     * 用户信息 redis key
     */
    protected $userInfoKey = 'php_websocket_userInfo';
    /**
     * 连接fd redis key
     */
    protected $fdKey = 'php_websocket_fd';
    /**
     * 在线用户集合 redis key
     */
    protected $onlineUserKey = 'php_websocket_onlineUsers';

    /**
     * Execute the console command.
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
     * （等待验证）运行octane：守护进程、监听端口 php artisan octane:start --daemon --port=8000
     * php artisan swoole start& // &常驻后台
     * ps aux | grep "artisan swoole start"
     */
    public function start()
    {
        // 这里是监听的服务端口号
        $this->ws = new WebSocketServer("0.0.0.0", 9502);

        $this->ws->set([
            'worker_num' => 1,
            'daemonize' => false,
            'log_file' => storage_path('logs/swoole.log'),
        ]);

        // 监听WebSocket服务启动事件
        // $this->ws->on('start', function($server) {
        //     echo "WebSocket 服务器启动成功!\n";
        //     echo "主进程PID: {$server->master_pid}\n";
        // });

        // 监听WebSocket连接打开事件
        $this->ws->on('open', function ($ws, $request) {
            $this->thisFD = $request->fd;
            $path = $request->server['request_uri'] ? $request->server['request_uri'] : '/';
            $params = $this->parsePath($path);

            if ($params['user_name'] && $params['user_id']) {
                Redis::hset($this->userInfoKey, $params['user_name'] . '_' . $params['user_id'], $this->thisFD);
                Redis::hset($this->fdKey, $this->thisFD, json_encode([
                    'user_name' => $params['user_name'],
                    'user_id' => $params['user_id']
                ]));
                Redis::sadd($this->onlineUserKey, json_encode([
                    'fd' => $this->thisFD,
                    'user_name' => $params['user_name'],
                    'user_id' => $params['user_id']
                ]));
            }
        });

        // 监听WebSocket消息事件
        $this->ws->on('message', function ($ws, $frame) {
            $data = json_decode($frame->data, true);
            /**
             * 消息类型：1群发，2私发
             */
            $type = $data['type'];

            switch ($type) {
                // 群发弹幕
                case 1:
                    foreach ($this->ws->connections as $fd) {
                        if ($this->ws->isEstablished($fd)) {
                            $this->ws->push($fd, $frame->data);
                        }
                    }
                    break;
                // 单独发给某人
                case 2:
                    if ($this->ws->isEstablished($this->thisFD)) {
                        $this->ws->push(
                            $this->thisFD,
                            json_encode([
                                'type' => 2,
                                'content' => json_encode([
                                    'type' => 'TimingMessage',
                                    'message' => "你输入了：" . $data['message']
                                ])
                            ], JSON_UNESCAPED_UNICODE)
                        );
                    }
                    break;
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
                        $finish_reason = isset($postData['finish_reason']) ? $postData['finish_reason'] : '';

                        if ($userName && $userId && ($content || $finish_reason)) {
                            $fd = Redis::hget($this->userInfoKey, $userName . '_' . $userId);

                            if ($fd && $this->ws->isEstablished($fd)) {
                                $this->ws->push($fd, json_encode([
                                    'type' => 1,
                                    'content' => $content,
                                    'finish_reason' => $finish_reason
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
                // 通知
                case '/websocket/notice':
                    if ($method === 'POST') {
                        $postData = $request->post ?: json_decode($request->rawContent(), true);

                        $userName = isset($postData['user_name']) ? $postData['user_name'] : '';
                        $userId = isset($postData['user_id']) ? $postData['user_id'] : '';
                        $content = isset($postData['content']) ? $postData['content'] : '';

                        if ($userName && $userId && $content) {
                            $fd = Redis::hget($this->userInfoKey, $userName . '_' . $userId);

                            if ($fd && $this->ws->isEstablished($fd)) {
                                $this->ws->push($fd, json_encode([
                                    'type' => 2,
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
            $userInfoJson = Redis::hget($this->fdKey, $fd);

            if ($userInfoJson) {
                $userInfo = json_decode($userInfoJson, true);

                Redis::hdel($this->userInfoKey, $userInfo['user_name'] . '_' . $userInfo['user_id']);
                Redis::hdel($this->fdKey, $fd);
                Redis::srem($this->onlineUserKey, json_encode([
                    'fd' => $fd,
                    'user_name' => $userInfo['user_name'],
                    'user_id' => $userInfo['user_id']
                ]));
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

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use swoole_websocket_server;

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

    //  php artisan swoole start& // &常驻后台
    public function start()
    {
        // 这里是监听的服务端口号
        $this->ws = new swoole_websocket_server("0.0.0.0", 9502);
        //监听WebSocket连接打开事件
        $this->ws->on('open', function ($ws, $request) {
            // echo 'websocket-open';
        });
        //监听WebSocket消息事件
        $this->ws->on('message', function ($ws, $frame) {
            // echo 'websocket-message';
            // $this->info("{$frame->fd}：{$frame->data}");
            // 群发
            foreach ($this->ws->connections as $fd) {
                if ($this->ws->isEstablished($fd)) {
                    $this->ws->push($fd, $frame->data);
                }
            }
        });
        //监听WebSocket主动推送消息事件
        $this->ws->on('request', function ($request, $response) {
            // echo 'websocket-request';
            // $scene = $request->post['scene'];
            // foreach ($this->ws->connections as $fd) {
            //     if ($this->ws->isEstablished($fd)) {
            //         $this->ws->push($fd, $scene);
            //     }
            // }
        });
        //监听WebSocket连接关闭事件
        $this->ws->on('close', function ($ws, $fd) {
            // echo 'websocket-close';
            // $this->info("client is close\n");
        });
        $this->ws->start();
    }
}

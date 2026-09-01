<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Controller;

// use Hyperf\DbConnection\Db;
// use App\Model\OrderRecord;
use Hyperf\Coroutine\Parallel;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Guzzle\HandlerStackFactory;
use App\Controller\GuzzleController;

class IndexController extends AbstractController
{
    public function __construct(
        private GuzzleController $guzzle
    ) {}

    public function index()
    {
        $user = $this->request->input('user', 'Hyperf');
        $method = $this->request->getMethod();

        // $list = OrderRecord::query()->find(1);
        // $list_db = Db::table('order_record')->find(1);

        return [
            'method' => $method,
            'message' => "Hello {$user}.",
        ];
    }

    /**
     * 并行获取首页信息
     */
    public function getIndexInfo(): array
    {
        // 开启并行任务
        $parallel = new Parallel();
        // 创建连接池
        $factory = new HandlerStackFactory();
        $stack = $factory->create();
        $urlPrefix = 'https://hjpl.cn/phpApi/';
        $token = $this->request->header('Authorization');
        $headers = [ 'Authorization' => $token ];

        // 添加两个并发任务
        // 获取访客人数
        $parallel->add(function () use ($stack, $urlPrefix, $headers) {
            try {
                return $this->guzzle->request('GET', $urlPrefix . 'getVisitorNumber', $headers, null, $stack);
            } catch (\Throwable $e) {
                $errorMessage = $e->getMessage();
                \Hyperf\Support\LoggerFactory::get()->warning("【获取访客人数】请求失败", [
                    'error' => $errorMessage,
                ]);
                return [
                    'code' => 500,
                    'error' => $errorMessage,
                    'msg' => 'Fail'
                ];
            }
        });

        // 获取访客记录
        $parallel->add(function () use ($stack, $urlPrefix, $headers) {
            try {
                return $this->guzzle->request('GET', $urlPrefix . 'guestRecord', $headers, null, $stack);
            } catch (\Throwable $e) {
                $errorMessage = $e->getMessage();
                \Hyperf\Support\LoggerFactory::get()->warning("【获取访客记录】请求失败", [
                    'error' => $errorMessage,
                ]);
                return [
                    'code' => 500,
                    'error' => $errorMessage,
                    'msg' => 'Fail'
                ];
            }
        });

        // 等待所有任务完成，获取结果数组
        $results = $parallel->wait();

        return [
            'code' => 200,
            'data' => [
                'visitorNumber' => $results[0],
                'guestRecord' => $results[1],
            ],
            'msg' => 'Success'
        ];
    }
}

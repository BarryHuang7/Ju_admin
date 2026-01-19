<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Email\SendEmailController;
use App\Http\Controllers\AI\QWenConteroller;
use App\Http\Controllers\Common\UtilsController;
use App\Http\Controllers\File\UploadController;

class TaskScheduler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 任务类型：1发送邮件，2调用通义千问API，3发送websocket消息，4合并视频
     */
    protected $type;
    /**
     * 传递参数
     */
    protected $data;
    /**
     * 当前请求ip
     */
    protected $ip;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($type, $data, $ip)
    {
        /**
         * 本地 php artisan queue:work --queue=tasks
         * 服务器 nohup php artisan queue:work --queue=tasks > storage/logs/queue.log 2>&1 &
         * 搜索进程 ps aux | grep "artisan queue:work --queue=tasks"
         * 杀死进程 kill -9 id
         */

        $this->type = $type;
        $this->data = $data;
        $this->ip = $ip;

        // 指定队列
        $this->onQueue('tasks');
        // 设置超时时间（秒）
        $this->timeout = 60;
        // 最大尝试次数
        $this->tries = 2;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $jobId = $this->job ? $this->job->getJobId() : null;
            Log::info("队列日志", [
                'job_id' => $jobId,
                'data' => $this->data,
                'ip' => $this->ip,
                'type' => $this->type
            ]);

            switch ($this->type) {
                case 1:
                    (new SendEmailController)->handleSendEmail($this->data, $this->ip);
                    break;
                case 2:
                    (new QWenConteroller)->handQWenAPI($this->data, $this->ip);
                    break;
                case 3:
                    (new UtilsController)->sendWebSocketMessage($this->data['user_name'], $this->data['user_id'], $this->data['message']);
                    break;
                case 4:
                    (new UploadController)->handleMergeChunks($this->data['video']);
                    break;
                default:
                    Log::error("队列日志", [
                        'job_id' => $jobId,
                        'data' => $this->data,
                        'ip' => $this->ip,
                        'type' => $this->type,
                        'error' => '错误调用队列！'
                    ]);
                    break;
            }
        } catch (\Exception $e) {
            $this->saveErrorLog($e->getMessage());
            throw $e;
        }
    }

    public function failed(\Exception $exception)
    {
        $this->saveErrorLog($exception->getMessage());
    }

    public function saveErrorLog($error) {
        $jobId = $this->job ? $this->job->getJobId() : null;
        
        Log::error("队列失败日志", [
            'job_id' => $jobId,
            'error' => $error,
            'data' => $this->data,
            'ip' => $this->ip,
            'type' => $this->type
        ]);
    }
}

<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Http\Controllers\Email\SendEmailController;
use Illuminate\Support\Facades\Log;

class SendEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    protected $ip;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data, $ip)
    {
        /**
         * 本地 php artisan queue:work --queue=emails
         * 服务器 nohup php artisan queue:work --queue=emails > storage/logs/queue.log 2>&1 &
         * 搜索进城 ps aux | grep "artisan queue:work --queue=emails"
         * 杀死进城 kill -9 id
         */

        $this->data = $data;
        $this->ip = $ip;

        // 指定队列
        $this->onQueue('emails');
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
                'ip' => $this->ip
            ]);
    
            (new SendEmailController)->handleSendEmail($jobId, $this->data, $this->ip);
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
            'email_data' => $this->data,
            'ip' => $this->ip
        ]);
    }
}

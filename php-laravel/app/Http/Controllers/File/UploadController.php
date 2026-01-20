<?php

namespace App\Http\Controllers\File;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Common\UtilsController;
use App\Models\FileList;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\Video;
use App\Jobs\TaskScheduler;

class UploadController extends Controller
{
    /**
     * 服务器存放文件的路径
     */
    private static $filePath = "/home/file";

    /**
     * 处理上传单文件
     */
    public function handleUpload(Request $request) {
        $this->requestValidate($request->file(), [
            // 8MB限制
            'file' => 'required|file|max:8192|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt'
        ]);

        $userInfo = (new UtilsController())->getUserInfo($request);

        if ($userInfo && count($userInfo) > 0) {
            $isAdmin = $userInfo['isAdmin'];

            if ($isAdmin !== 1) {
                $date = date('Y-m-d');
                $guestUploadRestriction = FileList::where('is_admin', $isAdmin)
                    ->where('created_at', '>=', $date . ' 00:00:00')
                    ->where('created_at', '<=', $date . ' 23:59:59')
                    ->count();

                if ($guestUploadRestriction >= 10) {
                    $this->returnData(500, '今日已达到你权限的最大上传数！');
                }
            }
        } else {
            $this->returnData(500, '文件上传失败: 获取用户失败！');
        }

        $ip = (new UtilsController())->getClientRealIp($request);

        try {
            $file = $request->file('file');
            // 文件后缀
            $fileSuffix = $file->getClientOriginalExtension();
            // 生成唯一文件名
            $newFileName = Str::random(40) . '-' . date('YmdHis') . '.' . $fileSuffix;

            // 本地
            // $path = $file->storeAs('uploads', $newFileName, 'public');
            // 服务器
            $newPath = self::$filePath . '/' . $newFileName;

            // 判断文件夹存不存在
            if (!file_exists(self::$filePath)) {
                mkdir(self::$filePath, 0755, true);
            }

            // 移动文件
            if ($file->move(self::$filePath, $newPath)) {
                Log::info('ip: ' . $ip . ', 上传单文件: ' . $newFileName);

                $this->returnData(200, '文件上传成功', [ 'newFileName' => $newFileName ]);
            } else {
                $errorMsg = '上传单文件移动失败！';
                Log::error('ip: ' . $ip . ', ' . $errorMsg);
                $this->returnData(500, $errorMsg);
            }
        } catch (\Exception $e) {
            $errorMsg = '单文件上传失败: ' . $e->getMessage();
            Log::error('ip: ' . $ip . ', ' . $errorMsg);
            $this->returnData(500, $errorMsg);
        }
    }

    /**
     * 处理分段上传视频
     */
    public function handleUploadChunk(Request $request, string $uuid) {
        // 这个方法也可以写到队列里调用
        $reqData = $request->all();
        $this->requestValidate(
            $reqData,
            [
                'chunk' => 'required|file',
                'chunk_number' => 'required|integer|min:1',
                'total_chunks' => 'required|integer|min:1'
            ],
            [
                'chunk.required' => '分片文件不能为空！',
                'chunk_number.required' => '分片数不能为空！',
                'total_chunks.required' => '总分片数不能为空！'
            ]
        );

        // firstOrFail没有直接404
        $video = Video::where('uuid', $uuid)->firstOrFail();

        $totalChunks = $video->total_chunks;

        if ($reqData['total_chunks'] != $totalChunks) {
            $this->returnData(400, '总分片数量不匹配！');
        }

        if ($video->status != 'uploading') {
            $this->returnData(400, '该视频状态不允许继续上传！');
        }

        $ip = (new UtilsController())->getClientRealIp($request);
        $errorMsg = '';

        try {
            // 判断文件夹存不存在
            $tempDir = self::$filePath . '/temp' . '/' . $uuid;
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $chunk = $reqData['chunk'];
            $chunkNumber = $reqData['chunk_number'];
            $chunkFileName = sprintf('%04d', $chunkNumber) . '.part';
            $chunkPath = $tempDir . '/' . $chunkFileName;

            if (file_exists($chunkPath)) {
                // 可以再判断chunksStatus是否成功、文件大小相不相同，确保上传成功
                $this->returnData(400, '文件已存在！');
            }

            $chunks = json_decode($video->chunks, true) ?? [];
            $chunksStatus = 'failed';

            // 移动分片文件
            if ($chunk->move($tempDir, $chunkFileName)) {
                $chunksStatus = 'completed';
            } else {
                $errorMsg = '分片上传文件移动失败！';
                Log::error('ip: ' . $ip . ', ' . $errorMsg);
            }

            $chunks[] = [
                'chunkNumber' => $chunkNumber,
                // 这里保存每个分片的状态，以便后续断点上传扩展
                'status' => $chunksStatus
            ];
            // 更新分片信息
            $video->chunks = json_encode($chunks);
            $video->save();

            if ($chunksStatus == 'failed') {
                $this->returnData(500, $errorMsg);
            }

            // 检查是否所有分片都已上传
            $uploadedChunks = count($chunks);
            $allUploaded = $uploadedChunks >= $totalChunks;

            if ($allUploaded) {
                // 标记为合并中
                $video->status = 'merging';
                $video->save();

                // 异步合并文件
                TaskScheduler::dispatch(4, [
                    'video' => $video
                ], $ip)
                // 延迟2秒
                ->delay(now()->addSeconds(2));
            }

            $this->returnData(200, '分片上传成功', [
                'chunk_number' => $chunkNumber,
                'uploaded_chunks' => $uploadedChunks,
                'total_chunks' => $totalChunks,
                'all_uploaded' => $allUploaded
            ]);
        } catch (\Exception $e) {
            $errorMsg = '分片上传文件失败: ' . $e->getMessage();
            Log::error('ip: ' . $ip . ', ' . $errorMsg);
            $this->returnData(500, $errorMsg);
        }
    }

    /**
     * 合并视频
     */
    public function handleMergeChunks(Video $video) {
        $uuid = $video->uuid;

        try {
            $tempDir = self::$filePath . '/temp' . '/' . $uuid;
            $finalPath = self::$filePath . '/' . $video->path;

            // 确保目录存在
            $finalDir = dirname($finalPath);
            if (!file_exists($finalDir)) {
                mkdir($finalDir, 0755, true);
            }

            // 按顺序合并所有分片
            $finalFile = fopen($finalPath, 'wb');

            for ($i = 1; $i <= $video->total_chunks; $i ++) {
                $chunkPath = $tempDir . '/' . sprintf('%04d', $i) . '.part';

                if (file_exists($chunkPath)) {
                    $chunkContent = file_get_contents($chunkPath);
                    // 写入内容
                    fwrite($finalFile, $chunkContent);
                    // 删除临时分片
                    unlink($chunkPath);
                }
            }

            fclose($finalFile);

            // 清理临时目录
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }

            // 更新视频状态
            $video->status = 'processing';
            $video->save();

            $this->processVideo($video, $uuid, $finalPath);
        } catch (\Exception $e) {
            Log::error('uuid: ' . $uuid . ', 合并视频分片失败: ' . $e->getMessage());
            $video->status = 'failed';
            $video->save();
        }
    }

    /**
     * 处理视频元数据
     */
    private function processVideo(Video $video, string $uuid, string $videoPath) {
        try {
            // 验证文件存在
            if (!file_exists($videoPath)) {
                Log::error('uuid: ' . $uuid . ', 处理视频元数据失败: 文件【' . $videoPath . '】不存在');
            }

            // 使用 FFMpeg 获取视频信息
            // composer require php-ffmpeg/php-ffmpeg
            // use FFMpeg\FFMpeg;
            // use FFMpeg\Coordinate\TimeCode;
            // if (class_exists('FFMpeg\FFMpeg')) {
            //     $ffmpeg = FFMpeg::create([
            //         // 二进制文件
            //         'ffmpeg.binaries' => config('video.ffmpeg_path', '/usr/bin/ffmpeg'),
            //         'ffprobe.binaries' => config('video.ffprobe_path', '/usr/bin/ffprobe'),
            //         'timeout' => 3600,
            //         'ffmpeg.threads' => 12,
            //     ]);

            //     $videoFile = $ffmpeg->open($videoPath);
            //     $stream = $videoFile->getStreams()->videos()->first();

            //     if ($stream) {
            //         $video->duration = floor($stream->get('duration'));
            //         $video->width = $stream->get('width');
            //         $video->height = $stream->get('height');
            //     }

            //     // 生成缩略图
            //     $thumbnailPath = 'thumbnails/' . date('Y-m-d') . '/' . $video->uuid . '.jpg';
            //     $thumbnailFullPath = storage_path(self::$filePath . '/' . $thumbnailPath);

            //     $thumbnailDir = dirname($thumbnailFullPath);
            //     if (!file_exists($thumbnailDir)) {
            //         mkdir($thumbnailDir, 0755, true);
            //     }

            //     $frame = $videoFile->frame(TimeCode::fromSeconds(10));
            //     $frame->save($thumbnailFullPath);

            //     $video->thumbnail_path = $thumbnailPath;
            // }

            $video->status = 'completed';
            $video->save();
        } catch (\Exception $e) {
            Log::error('uuid: ' . $uuid . ', 处理视频元数据失败: ' . $e->getMessage());
            $video->status = 'failed';
            $video->save();
        }
    }

    /**
     * 取消上传视频
     */
    public function cancelUploadVideo(string $uuid) {
        $video = Video::where('uuid', $uuid)->firstOrFail();

        $tempDir = self::$filePath . '/temp' . '/' . $uuid;

        if (is_dir($tempDir)) {
            $files = glob($tempDir . '/*.part');

            // 清理临时文件
            foreach ($files as $file) {
                unlink($file);
            }

            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }

            // 软删除
            if ($video->delete()) {
                $this->returnData(200, '上传已取消！');
            } else {
                $this->returnData(500, '数据删除失败！', [
                    'uuid' => $uuid
                ]);
            }
        }

        $this->returnData(500, '取消失败！');
    }
}

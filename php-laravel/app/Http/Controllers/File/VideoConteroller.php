<?php

namespace App\Http\Controllers\File;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Video;

class VideoConteroller extends Controller
{
    /**
     * 允许上传的文件后缀
     */
    private $allowed_types = 'mp4,avi,mov,wmv,flv,mkv,webm';
    /**
     * 最大文件大小
     */
    private $maxSize = 20;

    /**
     * 上传视频初始化
     */
    public function videoInitiate(Request $request) {
        $reqData = $request->all();
        $this->requestValidate(
            $reqData,
            [
                'file_name' => 'required|string|max:255',
                'size' => 'required|integer|min:1',
                'mime_type' => 'required|string',
                'total_chunks' => 'required|integer|min:1'
            ],
            [
                'file_name.required' => '文件名不能为空！',
                'size.required' => '文件大小不能为空！',
                'mime_type.required' => '文件类型不能为空！',
                'total_chunks.required' => '总分片数不能为空！'
            ]
        );

        // 允许上传的文件后缀
        $allowedTypes = explode(',', $this->allowed_types);
        $extension = pathinfo($reqData['file_name'], PATHINFO_EXTENSION);

        if (!in_array(strtolower($extension), $allowedTypes)) {
            $this->returnData(400, '不支持的文件类型！');
        }

        // 转为字节验证文件大小
        $fileMaxSize = $this->maxSize * 1024 * 1024;
        if ($reqData['size'] > $fileMaxSize) {
            $this->returnData(400, '文件大小超过' . $this->maxSize . 'MB！');
        }

        DB::beginTransaction();
        try {
            $uuid = Uuid::uuid4()->toString();
            $file_name = $uuid . '.' . $extension;
            $path = 'videos/' . date('Y-m-d') . '/' . $file_name;

            $video = Video::create([
                'uuid' => $uuid,
                'original_name' => $reqData['file_name'],
                'file_name' => $file_name,
                'path' => $path,
                'mime_type' => $reqData['mime_type'],
                'size' => $reqData['size'],
                'total_chunks' => $reqData['total_chunks'],
                'chunks' => json_encode([]),
                'status' => 'uploading'
            ]);

            if ($video) {
                DB::commit();
                $this->returnData(200, '上传视频初始化成功！');
            } else {
                DB::rollBack();
                $this->returnData(200, '上传视频初始化失败！');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $errorMsg = '上传视频初始化错误: ' . $e->getMessage();
            Log::error($errorMsg);
            $this->returnData(500, $errorMsg);
        }
    }
}

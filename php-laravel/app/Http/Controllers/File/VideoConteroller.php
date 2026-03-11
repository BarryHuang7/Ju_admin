<?php

namespace App\Http\Controllers\File;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Video;
use App\Http\Controllers\File\UploadController;

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

    protected $Video;

    public function __construct(Video $Video) {
        $this->Video = $Video;
    }

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
            $fileName = $uuid . '.' . $extension;
            $indexPath = 'videos/' . date('Y-m-d') . '/' . $uuid;
            $path = $indexPath . '/' . $fileName;

            $video = $this->Video::create([
                'uuid' => $uuid,
                'original_name' => $reqData['file_name'],
                'file_name' => $fileName,
                'index_path' => $indexPath . '/playlist.m3u8',
                'path' => $path,
                'mime_type' => $reqData['mime_type'],
                'size' => $reqData['size'],
                'total_chunks' => $reqData['total_chunks'],
                'chunks' => json_encode([]),
                'status' => 'uploading'
            ]);

            if ($video) {
                DB::commit();

                // 这有个坑：php程序创建的文件夹是www的，但异步程序创建的是root，会导致删除之类的无权限
                $Upload = new UploadController();
                $Upload->createTempFolder($uuid);
                $Upload->createVideosFolder($path);

                $this->returnData(200, '上传视频初始化成功！', [
                    'uuid' => $uuid
                ]);
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

    /**
     * 获取视频上传进度
     */
    public function getVideoProgress(string $uuid) {
        $video = $this->Video::where('uuid', $uuid)->first();

        if (!$video) {
            $this->returnData(400, '视频信息不存在！');
        }
        
        $uploadedChunks = count(json_decode($video->chunks, true) ?? []);
        $progress = $video->total_chunks > 0 
            ? round(($uploadedChunks / $video->total_chunks) * 100, 2)
            : 0;

        $this->returnData(200, 'Success!', [
            'uuid' => $uuid,
            'status' => $video->status,
            'progress' => $progress,
            'uploaded_chunks' => $uploadedChunks,
            'total_chunks' => $video->total_chunks
        ]);
    }

    /**
     * 获取视频列表
     */
    public function getVideoList(Request $request) {
        $reqData = $request->all();
        $this->requestValidate(
            $reqData,
            [
                'pageIndex' => 'required|integer',
                'pageSize' => 'required|integer'
            ],
            [
                'pageIndex.required' => '当前页不能为空！',
                'pageSize.required' => '页数不能为空！'
            ]
        );

        $fields = array(
            'id', 'uuid', 'original_name', 'index_path', 'path', 'mime_type',
            'size', 'chunks', 'total_chunks', 'status', 'created_at', 'updated_at'
        );
        $pageIndex = $request->get('pageIndex', 1);
        $pageSize = $request->get('pageSize', 5);

        $model = $this->Video::select($fields)
            ->orderBy('created_at', 'desc');

        $total = $model->count();
        $list = $model->offset(($pageIndex - 1) * $pageSize)
            ->limit($pageSize)
            ->get();

        $this->returnData(200, 'Success!', [
            'list' => $list,
            'total' => $total
        ]);
    }
}

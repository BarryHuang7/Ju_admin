<?php

namespace App\Http\Controllers\File;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Common\UtilsController;
use App\Models\FileList;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

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
                $ip = (new UtilsController())->getClientRealIp($request);
                Log::info('ip: ' . $ip . ', 上传文件: ' . $newFileName);

                $this->returnData(200, '文件上传成功', [ 'newFileName' => $newFileName ]);
            } else {
                $this->returnData(500, '文件移动失败: ');
            }
        } catch (\Exception $e) {
            $this->returnData(500, '文件上传失败: ' . $e->getMessage());
        }
    }
}

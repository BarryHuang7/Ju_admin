<?php

namespace App\Http\Controllers\File;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FileList;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Common\UtilsController;
use Illuminate\Support\Facades\Log;

class ImageController extends Controller
{
    /**
     * 服务器存放文件的路径
     */
    private static $filePath = "/home/file";

    /**
     * 获取图片列表
     */
    public function index(Request $request) {
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

        $fields = array('id', 'title', 'content', 'file_name', 'file_url', 'file_date', 'created_at');
        $pageIndex = $request->get('pageIndex', 1);
        $pageSize = $request->get('pageSize', 5);
        $where = array();

        if (!empty($reqData['title'])) {
            $where[] = ['title', 'like', '%' . $reqData['title'] . '%'];
        }
        if (!empty($reqData['content'])) {
            $where[] = ['content', 'like', '%' . $reqData['content'] . '%'];
        }
        if (!empty($reqData['file_name'])) {
            $where[] = ['file_name', 'like', '%' . $reqData['file_name'] . '%'];
        }

        $model = FileList::select($fields)
            ->where($where)
            ->orderBy('created_at', 'desc')
            ->offset(($pageIndex - 1) * $pageSize)
            ->limit($pageSize);

        // 打印sql语句
        // dd($model->toSql(), $model->getBindings());

        $total = $model->count();
        $list = $model->get();

        $this->returnData(200, 'Success!', [
            'list' => $list,
            'total' => $total
        ]);
    }

    /**
     * 创建图片
     */
    public function store(Request $request) {
        $reqData = $request->all();
        $this->requestValidate(
            $reqData,
            [ 'file_url' => 'required|string' ],
            [ 'file_url.required' => '图片链接不能为空！' ]
        );

        $userInfo = (new UtilsController())->getUserInfo($request);

        $title = !empty($reqData['title']) ? $reqData['title'] : null;
        $content = !empty($reqData['content']) ? $reqData['content'] : null;
        $file_name = !empty($reqData['file_name']) ? $reqData['file_name'] : null;
        $file_url = !empty($reqData['file_url']) ? $reqData['file_url'] : null;
        $file_date = !empty($reqData['file_date']) ? $reqData['file_date'] : date('Y-m-d H:i:s');
        $is_admin = $userInfo && !empty($userInfo['isAdmin']) ? $userInfo['isAdmin'] : 0;

        DB::beginTransaction();
        try {
            $flag = FileList::create([
                'title' => $title,
                'content' => $content,
                'file_name' => $file_name,
                'file_url' => $file_url,
                'file_date' => $file_date,
                'is_admin' => $is_admin
            ]);

            if ($flag) {
                DB::commit();
                $this->returnData(200, '保存成功！');
            } else {
                DB::rollBack();
                $this->returnData(200, '保存失败！');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $errorMsg = '保存图片信息错误: ' . $e->getMessage();
            Log::error($errorMsg);
            $this->returnData(500, $errorMsg);
        }
    }

    /**
     * 更新图片
     */
    public function update(Request $request, $id) {
        $reqData = $request->all();
        $this->requestValidate(
            $reqData,
            [ 'file_url' => 'required|string' ],
            [ 'file_url.required' => '图片链接不能为空！' ]
        );

        $userInfo = (new UtilsController())->getUserInfo($request);

        $title = !empty($reqData['title']) ? $reqData['title'] : null;
        $content = !empty($reqData['content']) ? $reqData['content'] : null;
        $file_name = !empty($reqData['file_name']) ? $reqData['file_name'] : null;
        $file_url = !empty($reqData['file_url']) ? $reqData['file_url'] : null;
        $file_date = !empty($reqData['file_date']) ? $reqData['file_date'] : date('Y-m-d H:i:s');
        $is_admin = $userInfo && !empty($userInfo['isAdmin']) ? $userInfo['isAdmin'] : 0;
        $where = array();

        if ($is_admin == 0) {
            $where[] = ['is_admin', '=', 0];
        }

        DB::beginTransaction();
        try {
            $flag = FileList::where('id', $id)->where($where)->update([
                'title' => $title,
                'content' => $content,
                'file_name' => $file_name,
                'file_url' => $file_url,
                'file_date' => $file_date
            ]);

            if ($flag) {
                DB::commit();
                $this->returnData(200, '编辑成功！');
            } else {
                DB::rollBack();
                $this->returnData(200, '编辑失败！');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $errorMsg = '编辑图片信息错误: ' . $e->getMessage();
            Log::error($errorMsg);
            $this->returnData(500, $errorMsg);
        }
    }

    /**
     * 删除图片
     */
    public function destroy(Request $request, $id) {
        $userInfo = (new UtilsController())->getUserInfo($request);
        $is_admin = $userInfo && !empty($userInfo['isAdmin']) ? $userInfo['isAdmin'] : 0;
        $where = array();

        if ($is_admin == 0) {
            $where[] = ['is_admin', '=', 0];
        }

        DB::beginTransaction();
        try {
            $file = FileList::where('id', $id)->where($where)->first();

            if ($file) {
                $fileName = basename($file->file_url);
                // 硬删除
                $file->delete();

                // 本地
                // $filePath = storage_path() . '\\app\\public\\uploads\\' . $fileName
                // 服务器
                // 验证路径是否在允许的目录内
                $realPath = realpath(self::$filePath . '/' . $fileName);
                $realBaseDir = realpath(self::$filePath);

                // 防止目录遍历攻击
                if (!$realPath || strpos($realPath, $realBaseDir) !== 0) {
                    $this->returnData(400, '不允许删除该路径的文件！');
                }

                if (file_exists($realPath)) {
                    if (unlink($realPath)) {
                        DB::commit();
                        $this->returnData(200, '文件删除成功！');
                    }
                }
            } else {
                DB::rollBack();
                $this->returnData(200, '文件删除失败！');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $errorMsg = '文件删除图片信息错误: ' . $e->getMessage();
            Log::error($errorMsg);
            $this->returnData(500, $errorMsg);
        }
    }
}

<?php

namespace App\Http\Controllers\File;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\FileList;

class ImageConteroller extends Controller
{
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
        $pageSize = $request->get('pageSize', 10);
        $where = array();

        if (!empty($reqData['title'])) {
            $where[] = ['title', 'like', '%' . $reqData['title'] . '%'];
        }
        if (!empty($reqData['content'])) {
            $where[] = ['content', 'like', '%' . $reqData['content'] . '%'];
        }
        if (!empty($reqData['fileName'])) {
            $where[] = ['fileName', 'like', '%' . $reqData['fileName'] . '%'];
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

    }

    /**
     * 更新图片
     */
    public function update(Request $request, $id) {

    }

    /**
     * 删除图片
     */
    public function destroy($id) {

    }
}

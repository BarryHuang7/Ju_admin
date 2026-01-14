<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;

abstract class Controller
{
    /**
     * 验证器
     * @param $data 需要验证的数据
     * @param $rules 验证规则
     * @param array $message 自定义提示信息
     * @return array
     */
    public function requestValidate($data, $rules, $message = array()) {
        $validator = Validator::make($data, $rules, $message);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            $this->returnData(400, implode(',', $errors));
        }

        return array('status' => true);
    }

    /**
     * 返回数据
     * @param integer $code 状态码
     * @param string $msg 自定义提示信息
     * @param array $data 数据
     * @return array
     */
    public function returnData($code = 400, $msg = '', $data = []) {
        echo json_encode(array(
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ), JSON_UNESCAPED_SLASHES);

        exit;
    }
}

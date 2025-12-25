<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class LoginVerificationPost extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required',
            'password' => 'required',
            'verifyCode' => 'required|max:5'
        ];
    }

    /**
     * 自定义错误消息
     */
    public function messages()
    {
        return [
            'name.required' => '账户名不能为空！',
            'password.required' => '密码不能为空！',
            'verifyCode.required' => '验证码不能为空！',
            'verifyCode.max' => '验证码最多5个字符！'
        ];
    }

    /**
     * 自定义验证失败响应
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'code' => 422,
            'message' => '验证失败',
            'errors' => $validator->errors()
        ], 422));
    }

    /**
     * 验证前准备数据
     */
    protected function prepareForValidation()
    {
        // 过滤空字符串和空格
        $this->merge([
            'name' => trim($this->input('name')),
            'verifyCode' => trim($this->input('verifyCode'))
        ]);
    }
}

<?php

namespace App\Http\Controllers\Login;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;
use App\LoginInfo;
use Ramsey\Uuid\Uuid;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Gregwar\Captcha\CaptchaBuilder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Http\Requests\LoginVerificationPost;
use App\Http\Controllers\Common\UtilsController;

class LoginController extends Controller
{
    /**
     * 生成验证码图片
     */
    public function getVerificationCode(Request $request) {
        try {
            $uuid = '';
            $headerVerifyCode = $request->header('VerifyCode');

            // 验证码随机数
            if ($headerVerifyCode) {
                $uuid = $headerVerifyCode;
            } else {
                $uuid = Uuid::uuid4()->toString();
            }

            // 生成验证码
            $width = 80;
            $height = 30;
            $builder = new CaptchaBuilder();
            $builder->build($width, $height);
            // 设置干扰线数量
            $builder->setMaxBehindLines(5);
            $builder->setMaxFrontLines(5);
            // 获取验证码文本
            $phrase = $builder->getPhrase();
            // 保存redis，1分钟过期
            Redis::setex($uuid, 60, $phrase);
            // 字节流
            $imgBytes = $builder->get();

            return response($imgBytes)
                ->header('Content-Type', 'image/jpeg')
                ->header('Cache-Control', 'no-cache')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0')
                ->header('VerifyCode', $uuid)
                ->header('Access-Control-Expose-Headers', 'VerifyCode');
        } catch (\Exception $e) {
            Log::info("获取登录验证码报错！" . $e->getMessage());
        }
    }

    /**
     * 登录验证
     */
    public function verification(LoginVerificationPost $request) {
        $validated = $request->validated();
        $verifyCodeUUID = $request->header('VerifyCode');

        if (!$verifyCodeUUID) {
            return response()->json([
                'code' => 400,
                'msg' => '验证码不能为空！'
            ]);
        }

        $redisVerifyCode = Redis::exists($verifyCodeUUID) ? Redis::get($verifyCodeUUID) : '';
        $name = $validated['name'];
        $password = (new UtilsController())->getMD5($validated['password']);
        $verifyCode = $validated['verifyCode'];

        if ($redisVerifyCode && $verifyCode === $redisVerifyCode) {
            $user = User::where('name', $name)->where('password', $password)->first();

            if ($user) {
                $userId = $user->id;
                $userName = $user->name;
                $isAdmin = $user->is_admin;

                $this->saveVisitorInfo($userId, $userName, $request);
                $token = $this->getToken($userId, $userName);
                // 保存redis，2小时过期
                Redis::setex('Bearer ' . $token, 60 * 60 * 2, $userId);

                return response()->json([
                    'code' => 200,
                    'data' => [
                        'id' => $userId,
                        'name' => $userName,
                        'isAdmin' => $isAdmin,
                        'token' => $token
                    ]
                ]);
            } else {
                return response()->json([
                    'code' => 500,
                    'msg' => '账户名或密码不存在！'
                ]);
            }
        } else {
            return response()->json([
                'code' => 500,
                'msg' => '验证码不正确或超时，请重试！'
            ]);
        }

        return response()->json([
            'code' => 400,
            'msg' => '登录失败！'
        ]);
    }

    /**
     * 登录退出
     */
    public function loginOut(Request $request) {
        $token = (new UtilsController())->getHeaderToken($request);

        if ($token) {
            Redis::del($token);

            return response()->json([
                'code' => 200,
                'msg' => '退出登录成功！'
            ]);
        }

        return response()->json([
            'code' => 400,
            'msg' => '退出登录失败！'
        ]);
    }

    /**
     * 保存访客信息
     */
    private function saveVisitorInfo($userId, $userName, $request) {
        try {
            $loginInfo = LoginInfo::insert([
                'user_id' => $userId,
                'user_name' => $userName,
                'ip' => (new UtilsController())->getClientRealIp($request),
                'date' => date('Y-m-d')
            ]);
        } catch (\Exception $e) {
            Log::info("新增访客表数据报错！" . $e->getMessage());
        }
    }

    /**
     * 获取登录token
     */
    private function getToken($userId, $userName) {
        $key = $this->generateKeyFromUuid();

        // 构建 claims
        $claims = [
            'id' => $userId,
            'name' => $userName,
        ];

        // 构建 payload（包含标准 claims）
        $payload = array_merge([
            // JWT ID（唯一标识）
            'jti' => Uuid::uuid4()->toString(),
            // 签发人
            'iss' => 'admin',
            // 主题
            'sub' => 'JWT AUTH',
            // 签发时间
            'iat' => time(),
            // 生效时间
            'nbf' => time(),
            // 2小时后过期
            'exp' => time() + 3600 * 2,
        ], $claims);

        // 使用 HS256 算法签名
        return JWT::encode($payload, $key, 'HS256');
    }
    
    /**
     * 生成密钥
     */
    private static function generateKeyFromUuid() {
        // 生成 UUID
        $uuid = Uuid::uuid4()->toString();
        // 将 UUID 转换为字节
        $uuidBytes = $uuid;
        // 使用 HMAC-SHA512 生成密钥
        return hash_hmac('sha256', $uuidBytes, 'jwt-secret-salt', true);
    }
}

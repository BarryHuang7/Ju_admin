<?php

namespace App\Http\Controllers\Email;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use PhpOffice\PhpSpreadsheet\IOFactory;
// use PhpOffice\PhpSpreadsheet\Writer\Xls;
use App\Jobs\TaskScheduler;
// use Illuminate\Support\Facades\Redis;
// use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Models\SendEmailInfo;
use App\Http\Controllers\Common\UtilsController;

class SendEmailController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        dd('index');
        // $jobId = "jobId_" . Str::random(32) . request()->session()->get('_token');
        // Redis::setex($jobId, 300, 'true');
        // $flag = Redis::get($jobId);
    }

    /**
     * 测试接口是否可用
     */
    public function runTest()
    {
        echo "hello word!<br />";

        echo 'oh!';
    }

    /**
     * 处理excel，复制多份数据并修改学生名称
     */
    public function handleExcel() {
        // 模板文件
        // $tplfile = 'C:/Users/Administrator/Desktop/a/a.xls';
        // $list = [
        //     '包泓基',
        //     '陈柏宇',
        //     '陈锦阳',
        //     '程泓柯',
        //     '冯思源',
        //     '黄建濠',
        //     '黄梓豪',
        //     '邝钦城',
        //     '赖俊宇',
        //     '黎子富',
        //     '黎钧',
        //     '刘天儒',
        //     '卢宇航',
        //     '罗彬恒',
        //     '罗铭郗',
        //     '王梦航',
        //     '谢浩洋',
        //     '徐睿',
        //     '徐宇曦',
        //     '杨梓烽',
        //     '张泽林',
        //     '张梓轩',
        //     '郑浩憬',
        //     '陈柳菲',
        //     '陈诗语',
        //     '陈思敏',
        //     '陈思颖',
        //     '丁宁',
        //     '葛曦月',
        //     '黄昱灵',
        //     '李萱',
        //     '缪家欣',
        //     '魏莱',
        //     '邬怡娴',
        //     '向佳玥',
        //     '杨诗婷',
        //     '杨致冉',
        //     '叶静怡',
        //     '叶淇钰',
        //     '袁诗涵',
        //     '赵欣悦'
        // ];

        // foreach($list as $item) {
        //     // 新文件
        //     $new_file_name = '2024年寒假期间“万名教师大家访”记录表.xls';
        //     $newfile = 'C:/Users/Administrator/Desktop/b/' . $item . $new_file_name;
        //     // 拷贝文件
        //     if (!copy($tplfile, $newfile)) {
        //         echo "copy file failed!";
        //     }

        //     $reader = IOFactory::createReader('Xls');
        //     $excel = $reader->load($newfile);
        //     // 写入数据
        //     $sheet = $excel->getActiveSheet();

        //     $sheet->setCellValue('B4', $item);

        //     $writer = new Xls($excel);
        //     $writer->save($newfile);
        // }
    }

    /**
     * 队列发送邮箱
     */
    public function sendEmail(Request $request) {
        $data = $request->input();
        $emails = isset($data['emails']) && !empty($data['emails']) ? $data['emails'] : [];
        $ip = (new UtilsController())->getClientRealIp($request);

        if ($emails && is_array($emails) && count($emails) > 0) {
            if (count($emails) > 5) {
                return response()->json([
                    'code' => 400,
                    'msg' => '最大发送5个邮箱！'
                ]);
            }

            $rules = array();
            foreach ($emails as $index => $email) {
                $rules["emails.{$index}"] = 'required|email:rfc,dns,filter';
            }

            $validator = Validator::make(['emails' => $emails], $rules);

            if (!$validator->fails()) {
                Log::info('发送邮箱请求' . json_encode($data) . ', ip: ' . $ip);

                foreach ($emails as $e) {
                    $email = trim($e);
                    Log::info('正在发送邮箱【' . $email . '】');

                    TaskScheduler::dispatch(1, $email, $ip);
                }

                return response()->json([
                    'code' => 200,
                    'msg' => '已成功加入队列'
                ]);
            } else {
                return response()->json([
                    'code' => 400,
                    'msg' => '邮箱验证不通过！'
                ]);
            }
        }

        return response()->json([
            'code' => 400,
            'msg' => ''
        ]);
    }

    /**
     * 处理发送邮箱逻辑
     */
    public function handleSendEmail($email, $ip) {
        $flag = false;
        Log::info('给【' . $email . '】发送邮箱。');

        try {
            Mail::raw('你好！Guest!', function ($message) use ($email) {
                $message->to($email, 'Guest')
                    ->subject('测试邮箱');
            });
            $flag = true;
        } catch (\Exception $e) {
            Log::info('给【' . $email . '】发送邮箱失败。错误信息：' . $e->getMessage());
        }

        SendEmailInfo::insert([
            'email' => $email,
            'ip' => $ip,
            'isSuccessful' => $flag ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        Log::info('给【' . $email . '】发送邮箱' . ($flag ? '成功' : '失败') . '。');
    }

    
}

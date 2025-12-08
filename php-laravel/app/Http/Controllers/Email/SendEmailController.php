<?php

namespace App\Http\Controllers\Email;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use PhpOffice\PhpSpreadsheet\IOFactory;
// use PhpOffice\PhpSpreadsheet\Writer\Xls;
use App\Jobs\SendEmail;
// use Illuminate\Support\Facades\Redis;
// use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
    }

    /**
     * 测试接口是否可用
     */
    public function runTest()
    {
        //
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
    public function sendEmail() {
        $post = request()->post();

        if (count($post) > 0) {
            Log::info('发送邮件请求' . json_encode($post));
            SendEmail::dispatch($post);
        }

        // $jobId = "jobId_" . Str::random(32) . request()->session()->get('_token');
        // Redis::setex($jobId, 300, 'true');
        // $flag = Redis::get($jobId);

        return response()->json([
            'code' => 200,
            'msg' => '已成功加入队列'
        ]);
    }

    /**
     * 处理发送邮箱逻辑
     */
    public function handleSendEmail($jobId, $data) {
        $flag = false;
        Log::info('给【' . $data['email'] . '】发送邮件。');

        try {
            Mail::raw('你好！Guest!', function ($message) use ($data) {
                $message->to($data['email'], 'Guest')
                    ->subject('测试邮箱');
            });
            $flag = true;
        } catch (\Exception $e) {
            Log::info('给【' . $data['email'] . '】发送邮件失败。错误信息：' . $e->getMessage());
        }

        Log::info('给【' . $data['email'] . '】发送邮件' . ($flag ? '成功' : '失败') . '。');
    }
}

<?php

namespace App\Http\Controllers\Test;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class TestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
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
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function test()
    {
        //
        echo "hello word!<br />";

        echo 'oh!';
    }
}

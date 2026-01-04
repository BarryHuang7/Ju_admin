<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\LoginInfo;
use Illuminate\Support\Facades\DB;

class IndexController extends Controller
{
    /**
     * 获取访客记录
     */
    public function guestRecord() {
        $year = date('Y');
        $month = date('m');
        $day = date('d');
        $where_month = $year.'-'.$month;
        $data = array(
            // 当前年月
            'month' => $where_month,
            'xAxis' => array(),
            'series' => array()
        );
        // x轴的值
        $xAxis = array();
        // y轴的值
        $series = array();

        for ($i = 1; $i <= $day; $i ++) {
            $xAxis[] = $month.'-'.($i <= 9 ? '0'.$i : $i);
            $series[] = 0;
        }

        $LoginInfo = LoginInfo::select(
                DB::raw("date_format(date, '%m-%d') as day"),
                DB::raw('count(DISTINCT ip) as number')
            )
            ->where('user_id', '<>', 1)
            ->whereRaw("date_format(date, '%Y-%m') = ?", [$where_month])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();

        if (count($LoginInfo) > 0) {
            foreach ($LoginInfo as $item) {
                $key = $this->getDayNumber($item['day']);

                if ($key) {
                    $series[$key] = $item['number'];
                }
            }
        } else {
            // 当月查询不到访客记录时，给默认值
            if (count($xAxis) > 0) {
                foreach ($xAxis as $k => $x) {
                    $series[$k] = 0;
                }
            }
        }

        $data['xAxis'] = $xAxis;
        $data['series'] = $series;

        return response()->json([
            'code' => 200,
            'data' => $data,
            'msg' => 'Success'
        ]);
    }

    /**
     * 获取天数
     */
    private function getDayNumber($monthDay) {
        // 分割字符串
        $parts = explode('-', $monthDay);
        
        // 确保有日期部分
        if (isset($parts[1])) {
            $day = $parts[1];
            // 去掉前导零
            $day = ltrim($day, '0');
            // 处理空字符串或 '0' 的情况
            return $day === '' ? '1' : (int) $day - 1;
        }
        
        return '';
    }
}

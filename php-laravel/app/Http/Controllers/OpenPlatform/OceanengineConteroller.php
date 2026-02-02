<?php

namespace App\Http\Controllers\OpenPlatform;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Redis;
use App\Models\Oceanengine as OceanengineModel;
use App\Models\OceanengineAdvertiserList;

/**
 * 巨量引擎开放平台
 */
class OceanengineConteroller extends Controller
{
    private $app_id = 1855361102078260;
    private $secret = '9488bd23f1fb0594f9010d7354f890a0397e20f2';
    private $access_token_url = 'https://ad.oceanengine.com/open_api/oauth2/access_token/';
    /**
     * 已授权的账户列表
     */
    private $advertiser_url = 'https://api.oceanengine.com/open_api/oauth2/advertiser/get/';
    /**
     * 账户列表
     */
    private $ad_advertiser_url = 'https://ad.oceanengine.com/open_api/2/customer_center/advertiser/list/';
    private $public_info_url = 'https://ad.oceanengine.com/open_api/2/advertiser/public_info/';
    private $client = '';
    private $uid = '';
    private $access_token = '';
    private $refresh_token = '';
    private $cc_account_id = '';

    public function __construct() {
        $this->client = new Client([
            // 禁用 SSL 验证
            'verify' => false,
            'timeout' => 120
        ]);
        // $this->cc_account_id = 1691031371042824;
        // $this->access_token = '24a9dc9b72ad1f3138c594a274de1b2eef8fce37';
    }

    /**
     * 获取千川投放账户基础信息
     */
    public function getPublicInfo(Request $request) {
        $reqData = $request->all();

        Log::info('getPublicInfo请求: '. json_encode($reqData));

        // auth_code有效期只有10分钟，且只能使用一次，过期或者多次使用都会报错
        $auth_code = $reqData['auth_code'] ?? '';
        $scope = $reqData['scope'] ?? '';
        $material_auth_status = $reqData['material_auth_status'] ?? '';
        $uid = $reqData['uid'] ?? '';

        // 1、获取授权码
        if ($auth_code) {
            $this->uid = $uid;
            $oceanengine = OceanengineModel::where('uid', $this->uid)->first();

            if (!$oceanengine) {
                $oceanengine = OceanengineModel::create([
                    'auth_code'=> $auth_code,
                    'scope'=> $scope,
                    'material_auth_status'=> $material_auth_status,
                    'uid'=> $this->uid,
                ]);
            }

            // 2、获取token
            $this->access_token = $this->getRedisAccessToken();

            if (!$this->access_token) {
                $tokenInfo = $this->getAccessToken($auth_code);

                $this->access_token = $tokenInfo['access_token'];
                $expires_in = $tokenInfo['expires_in'];
                $this->refresh_token = $tokenInfo['refresh_token'];
                $refresh_token_expires_in = $tokenInfo['refresh_token_expires_in'];

                $oceanengine->access_token = $this->access_token;
                $oceanengine->refresh_token = $this->refresh_token;
                $oceanengine->save();

                Redis::setex($this->app_id.'_'.$this->uid.'_access_token', $expires_in, $this->access_token);
                Redis::setex($this->app_id.'_'.$this->uid.'_refresh_token', $refresh_token_expires_in, $this->refresh_token);
            }

            // 3、获取授权账户信息
            $advertiserInfo = $this->getAdvertiserInfo();

            $this->cc_account_id = $advertiserInfo['cc_account_id'];

            $oceanengine->cc_account_id = $this->cc_account_id;
            $oceanengine->cc_account_name = $advertiserInfo['cc_account_name'];
            $oceanengine->account_role = $advertiserInfo['account_role'];
            $oceanengine->save();

            // 4、获取广告账户信息
            $advertiser_list = $this->getAdvertiserList($oceanengine->id, 1, 2);

            if (count($advertiser_list) > 0) {
                OceanengineAdvertiserList::insert($advertiser_list);

                $advertiser_ids = array_column($advertiser_list, 'advertiser_id');
                $new_advertiser_list = OceanengineAdvertiserList::select(
                    'id', 'oceanengine_id', 'advertiser_id', 'advertiser_name', 'advertiser_type'
                )->whereIn('advertiser_id', $advertiser_ids)->get();

                // 5、获取投放账户信息
                $publicInfo = $this->clientPublicInfo($advertiser_ids);

                // 合并数据
                $publicInfoIndex = array();
                foreach ($publicInfo as $pInfo) {
                    $publicInfoIndex[$pInfo['id']] = $pInfo;
                }

                $update_advertiser_list = $new_advertiser_list->toArray();
                foreach ($update_advertiser_list as &$aList) {
                    $id = $aList['advertiser_id'];

                    if (isset($publicInfoIndex[$id])) {
                        $aList['company'] = $publicInfoIndex[$id]['company'];
                        $aList['first_industry_name'] = $publicInfoIndex[$id]['first_industry_name'];
                        $aList['second_industry_name'] = $publicInfoIndex[$id]['second_industry_name'];
                        $aList['updated_at'] = date('Y-m-d H:i:s');
                    }
                }
                // 解除引用
                unset($aList);

                OceanengineAdvertiserList::upsert(
                    $update_advertiser_list,
                    ['advertiser_id'],
                    [
                        'company',
                        'first_industry_name',
                        'second_industry_name',
                        'updated_at'
                    ]
                );

                $this->returnData(200, 'Success!', $update_advertiser_list);
            }
        }

        $this->returnData(400, '获取授权码失败!');
    }

    /**
     * 获取巨量引擎AccessToken
     */
    private function getAccessToken($auth_code) {
        $access_token = '';
        $expires_in = 0;
        $refresh_token = '';
        $refresh_token_expires_in = 0;

        if ($auth_code && $this->client) {
            try {
                $response = $this->client->request(
                    'POST',
                    $this->access_token_url,
                    [
                        'headers' => [
                            'Content-Type' => 'application/json'
                        ],
                        'json' => [
                            'app_id' => $this->app_id,
                            'secret' => $this->secret,
                            'auth_code' => $auth_code,
                        ]
                    ]
                );

                $statusCode = $response->getStatusCode();
                $body = $response->getBody()->getContents();

                Log::info('getAccessToken请求: '. $body);

                if ($statusCode === 200) {
                    $data = json_decode($body, true);

                    if ($data && isset($data['data'])) {
                        $access_token = $data['data']['access_token'];
                        $expires_in = $data['data']['expires_in'];
                        $refresh_token = $data['data']['refresh_token'];
                        $refresh_token_expires_in = $data['data']['refresh_token_expires_in'];
                    }
                }
            } catch (RequestException $e) {
                Log::error('获取巨量引擎AccessToken错误: ' . $e->getMessage());
            }
        }

        return [
            'access_token' => $access_token,
            'expires_in' => $expires_in,
            'refresh_token' => $refresh_token,
            'refresh_token_expires_in' => $refresh_token_expires_in,
        ];
    }

    /**
     * 获取已授权账户
     */
    private function getAdvertiserInfo() {
        $cc_account_id = '';
        $cc_account_name = '';
        $account_role = '';

        if ($this->client && $this->access_token) {
            try {
                $response = $this->client->request(
                    'GET',
                    $this->advertiser_url . '?access_token=' . $this->access_token,
                    [
                        'headers' => [
                            'Content-Type' => 'application/json'
                        ]
                    ]
                );

                $statusCode = $response->getStatusCode();
                $body = $response->getBody()->getContents();

                Log::info('getAccessToken请求: '. $body);

                if ($statusCode === 200) {
                    $data = json_decode($body, true);

                    if ($data && isset($data['data'])) {
                        if (isset($data['data']['list']) && count($data['data']['list']) > 0) {
                            $list = $data['data']['list'][0];

                            $cc_account_id = $list['advertiser_id'];
                            $cc_account_name = $list['account_name'];
                            $account_role = $list['account_role'];
                        }
                    }
                }
            } catch (RequestException $e) {
                Log::error('获取已授权账户错误: ' . $e->getMessage());
            }
        }

        return [
            'cc_account_id' => $cc_account_id,
            'cc_account_name' => $cc_account_name,
            'account_role' => $account_role
        ];
    }

    /**
     * 获取纵横工作台下账户列表
     */
    private function getAdvertiserList($oceanengine_id, $page = 1, $page_size = 10) {
        $advertiser_list = array();

        if ($this->client && $this->access_token && $this->cc_account_id) {
            try {
                $response = $this->client->request(
                    'GET',
                    // &filtering=%7B"account_name"%3A"名称"%7D
                    // &filtering={"account_name":"名称"}
                    $this->ad_advertiser_url . '?cc_account_id=' . $this->cc_account_id . '&account_source=QIANCHUAN&page=' . $page . '&page_size=' . $page_size,
                    [
                        'headers' => [
                            'Access-Token' => $this->access_token,
                            'Content-Type' => 'application/json'
                        ]
                    ]
                );

                $statusCode = $response->getStatusCode();
                $body = $response->getBody()->getContents();

                Log::info('getAdvertiserList请求: '. $body);
                
                if ($statusCode === 200) {
                    $data = json_decode($body, true);

                    $list = $data['data']['list'] ?? [];

                    if (count($list) > 0) {
                        foreach ($list as $item) {
                            $advertiser_list[] = [
                                'oceanengine_id' => $oceanengine_id,
                                'advertiser_id' => $item['advertiser_id'],
                                'advertiser_name' => $item['advertiser_name'],
                                'advertiser_type' => $item['advertiser_type']
                            ];
                        }
                    }
                }
            } catch (RequestException $e) {
                Log::error('获取纵横工作台下账户列表错误: ' . $e->getMessage());
            }
        }

        return $advertiser_list;
    }

    /**
     * 请求获取千川投放账户基础信息接口
     */
    private function clientPublicInfo($advertiser_ids) {
        $list = array();

        if ($this->client && $this->access_token && count($advertiser_ids) > 0) {
            try {
                $response = $this->client->request(
                    'GET',
                    $this->public_info_url,
                    [
                        'headers' => [
                            'Access-Token' => $this->access_token,
                            'Content-Type' => 'application/json'
                        ],
                        'json' => [
                            'advertiser_ids' => $advertiser_ids
                        ]
                    ]
                );

                $statusCode = $response->getStatusCode();
                $body = $response->getBody()->getContents();

                Log::info('clientPublicInfo请求: '. $body);

                if ($statusCode === 200) {
                    $data = json_decode($body, true);

                    $list = $data['data'] ?? [];
                }
            } catch (RequestException $e) {
                Log::error('获取投放账户基础信息错误: ' . $e->getMessage());
            }
        }

        return $list;
    }

    /**
     * 获取redis的AccessToken
     */
    private function getRedisAccessToken() {
        return Redis::get($this->app_id.'_'.$this->uid.'_access_token');
    }

    /**
     * 请求纵横工作台下账户列表
     */
    public function curlAndSaveAdvList($ccid, $access_token, $adv_list, $page = 1, $page_size = 100) {
        sleep(1);

        $headers = [
            "Access-Token:{$access_token}",
            'Content-Type:application/json'
        ];
        $url = $this->adgroup_num;
        $backmsg = [];

        $param = [
            'cc_account_id' => $ccid,
            'account_source' => 'QIANCHUAN',
            'page' => $page,
            'page_size' => $page_size,
        ];

        foreach ($param as $pKey => $pVal) {
            $param[$pKey] = is_string($pVal) ? $pVal : json_encode($pVal);
        }
        $urla = $url . "?" . http_build_query($param);
        $backinfo = $this->httpCurl($urla, '', 'GET', $headers);

        $backmsg = json_decode($backinfo, true, 512, JSON_BIGINT_AS_STRING);

        if ($backmsg['code'] || !isset($backmsg['data'])) {
            return Log::error('纵横工作台下账户列表:' . $backinfo);
        }

        $data = $backmsg['data'];
        $list = isset($data['list']) ? $data['list'] : [];

        if (count($list) > 0) {
            $save_array = array();

            foreach ($list as $item) {
                if (!isset($adv_list[$item['advertiser_id']])) {
                    $save_array[] = [
                        'shop_id' => $ccid,
                        'adv_id' => $item['advertiser_id'],
                        'adv_name' => $item['advertiser_name'],
                        'add_time' => date("Y-m-d")
                    ];
                }
            }

            if (count($save_array) > 0) {
                $this->infoDao->saveAll($save_array);
            }
        }

        return $data;
    }

    /**
     * 获取纵横工作台下账户列表
     */
    public function getAdvList($ccid, $token_key) {
        $access_token = CacheService::redisHandler()->get($token_key . '.token.');

        $adv_list = $this->infoDao->setModel()::field('id, adv_id')
            ->where('shop_id', $ccid)
            ->column('id', 'adv_id');

        $page = 1;
        $data = $this->curlAndSaveAdvList($ccid, $access_token, $adv_list, $page);

        $total_page = isset($data['page_info']['total_page']) ? $data['page_info']['total_page'] : 1;

        if ($total_page > 1) {
            for ($i = $page + 1; $i < $total_page; $i ++) {
                $this->curlAndSaveAdvList($ccid, $access_token, $adv_list, $i);
            }
        }
    }

    /**
     * 获取广告账户基础信息
     */
    public function getAdvPublicinfo($ccid, $token_key) {
        $access_token = CacheService::redisHandler()->get($token_key . '.token.');

        $adv_list = $this->infoDao->setModel()::field('id, adv_id')
            ->where('shop_id', $ccid)
            ->select()
            ->toArray();

        if (count($adv_list) > 0) {
            $headers = [
                "Access-Token:{$access_token}",
                'Content-Type:application/json'
            ];
            $url = $this->advnum_baseinfo;
            $backmsg = array();

            foreach (array_chunk($adv_list, 100) as $value) {
                $param = [
                    'advertiser_ids' => array_column($value, 'adv_id')
                ];
                foreach ($param as $pKey => $pVal) {
                   $param[$pKey] = is_string($pVal) ? $pVal : json_encode($pVal);
                }
                $urla = $url . "?" . http_build_query($param);
                $backinfo = $this->httpCurl($urla, '', 'GET', $headers);

                $backmsg = json_decode($backinfo, true, 512, JSON_BIGINT_AS_STRING);

                if ($backmsg['code'] || !isset($backmsg['data'])) {
                   Log::error('广告账户基础信息:' . $backinfo);
                   continue;
                }

                $data = $backmsg['data'];

                // 合并数据
                $dataIndex = array();
                foreach ($data as $dVal) {
                    $dataIndex[$dVal['id']] = $dVal;
                }

                foreach ($value as &$aList) {
                    $id = $aList['adv_id'];

                    if (isset($dataIndex[$id])) {
                        $aList['adv_company'] = $dataIndex[$id]['company'];
                        $aList['adv_status'] = $dataIndex[$id]['status'];
                        $aList['adv_role'] = $dataIndex[$id]['role'];
                    }
                }
                // 解除引用
                unset($aList);

                // 删除不需要更新的字段
                $update_array = array_map(function($item) {
                    foreach (['adv_id'] as $field) {
                        unset($item[$field]);
                    }
                    return $item;
                }, $value);

                $this->infoDao->saveAll($update_array);

                sleep(1);
            }
        }
    }

    /**
     * 请求全域投放账户维度数据
     */
    public function curlReportUniPromotion($url, $headers, $param, $min_interval, $last_request_time, $retry) {
        // 睡眠时间计算
        $now = microtime(true);
        $elapsed = $now - $last_request_time;
        if ($elapsed < $min_interval) {
            usleep(($min_interval - $elapsed) * 1000000);
        }

        foreach ($param as $pKey => $pVal) {
            $param[$pKey] = is_string($pVal) ? $pVal : json_encode($pVal);
        }
        $back_info = $this->httpCurl(
            $url . "?" . http_build_query($param),
            '',
            'GET',
            $headers
        );

        $last_request_time = microtime(true);
        $back_msg = json_decode($back_info, true, 512, JSON_BIGINT_AS_STRING);
        $data = array();

        if (!isset($back_msg['code'])) {
            Log::error('访问千川全域投放账户维度数据接口('.$param['marketing_goal'].')-1:' . $back_info . '-adv_id:' . $param['advertiser_id']);
            sleep(1);
        }
        if ($back_msg['code']) {
            $retry_msg = '';
            if ($retry > 0) {
                $retry_msg = '-重试第' . $retry . '次';
            }

            Log::error('访问千川全域投放账户维度数据接口('.$param['marketing_goal'].')-2' . $retry_msg . ':' . $back_info . '-adv_id:' . $param['advertiser_id']);
            sleep(1);

            // 频控限制 或 服务器内部错误
            if (in_array($back_msg['code'], [40100, 40110, 50000])) {
                if ($retry < 3) {
                    $new_retry = $retry + 1;
                    Log::error('访问千川全域投放账户维度数据接口('.$param['marketing_goal'].')-3-重试第' . $new_retry . '次-adv_id:' . $param['advertiser_id']);

                    return $this->curlReportUniPromotion($url, $headers, $param, $min_interval, $last_request_time, $new_retry);
                } else {
                    Log::error('访问千川全域投放账户维度数据接口('.$param['marketing_goal'].')-4-重试' . $retry . '次:' . $back_info . '-adv_id:' . $param['advertiser_id']);
                    sleep(2);
                }
            }
        }

        if (isset($back_msg['data']) && $back_msg['data']) {
            $data = $back_msg['data'];
        }

        return $data;
    }

    /**
     * 获取全域投放账户维度数据
     */
    public function getReportUniPromotion($marketing_goal) {
        $token_key = 'test_qc.token.';
        $access_token = CacheService::redisHandler()->get($token_key);

        $adv_list = $this->infoDao->setModel()::field('adv_id')
            ->where('shop_id', 1691031371042824)
            ->select()
            ->toArray();

        if (count($adv_list) > 0) {
            $headers = [
                "Access-Token:{$access_token}",
                "Content-Type:application/json"
            ];
            $url = 'https://api.oceanengine.com/open_api/v1.0/qianchuan/report/uni_promotion/get/';
            $stat_date = date('Y-m-d', strtotime("-1 day"));
            $start_date = date('Y-m-d 00:00:00', strtotime("-1 day"));
            $end_date = date("Y-m-d 23:59:59", strtotime("-1 day"));
            $param = [
                'advertiser_id' => '',
                'start_date' => $start_date,
                'end_date' => $end_date,
                // LIVE_PROM_GOODS：直播全域，VIDEO_PROM_GOODS：商品全域
                'marketing_goal' => $marketing_goal,
                'order_platform' => 'QIANCHUAN',
                'fields' => [
                    // 整体消耗，单位元，小数点2位
                    'stat_cost',
                    // 整体支付ROI
                    'total_prepay_and_pay_order_roi2',
                    // 用户实际支付金额，单位元，小数点2位
                    'total_pay_order_gmv_for_roi2',
                    // 整体成交订单数
                    'total_pay_order_count_for_roi2',
                    // 整体成交订单成本
                    'total_cost_per_pay_order_for_roi2',
                    // 整体未完结预售订单预估金额，单位元，小数点2位
                    'total_unfinished_estimate_order_gmv_for_roi2',
                    // 整体成交智能优惠券金额，单位元，小数点2位
                    'total_pay_order_coupon_amount_for_roi2',
                    // 平台补贴金额，单位元，小数点2位
                    'total_ecom_platform_subsidy_amount_for_roi2',
                    // 整体成交金额，单位元，小数点2位
                    'total_pay_order_gmv_include_coupon_for_roi2',
                ]
            ];
            if ($marketing_goal === 'LIVE_PROM_GOODS') {
                // 整体预售订单数；注意：仅支持直播全域
                $param['fields'][] = 'total_prepay_order_count_for_roi2';
                // 整体预售订单金额，单位元，小数点2位；注意：仅支持直播全域
                $param['fields'][] = 'total_prepay_order_gmv_for_roi2';
            }
            // 开发者频控配额：每区间秒最大请求次数
            $QPS = 30;
            // 最小请求间隔时间：微秒
            $min_interval = (double) number_format(0.85 / $QPS, 3);
            // 最后一次请求时间
            $last_request_time = microtime(true);
            
            $uni_list = $this->uniDao->setModel()::field('id, advertiser_id')
                ->where('stat_date', $stat_date)
                ->where('marketing_goal', $marketing_goal)
                ->column('id', 'advertiser_id');

            $save_array = array();
            $update_array = array();
            $chunk = 50;

            foreach ($adv_list as $item) {
                $adv_id = $item['adv_id'];
                $param['advertiser_id'] = $adv_id;
                // 重试次数
                $retry = 0;

                $data = $this->curlReportUniPromotion($url, $headers, $param, $min_interval, $last_request_time, $retry);
                $last_request_time = microtime(true);

                if (count($data) > 0 && $data['stat_cost'] > 0) {
                    $data['advertiser_id'] = $adv_id;
                    $data['marketing_goal'] = $marketing_goal;
                    $data['add_date'] = date("Y-m-d", time());
                    $data['stat_date'] = $stat_date;

                    if (isset($uni_list[$adv_id])) {
                        $data['id'] = $uni_list[$adv_id];
                        $update_array[] = $data;

                        if (count($update_array) == $chunk) {
                            $this->uniDao->saveAll($update_array);
                            $update_array = array();
                        }
                    } else {
                        $save_array[] = $data;

                        if (count($save_array) == $chunk) {
                            $this->uniDao->saveAll($save_array);
                            $save_array = array();
                        }
                    }
                }
            }

            if (count($save_array) > 0) {
                foreach (array_chunk($save_array, $chunk) as $save_item) {
                    $this->uniDao->saveAll($save_item);
                }
            }

            if (count($update_array) > 0) {
                foreach (array_chunk($update_array, $chunk) as $update_item) {
                    $this->uniDao->saveAll($update_item);
                }
            }
        }
    }
}

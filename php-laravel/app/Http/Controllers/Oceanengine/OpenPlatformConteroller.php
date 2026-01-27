<?php

namespace App\Http\Controllers\Oceanengine;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Redis;
use App\Models\Oceanengine;
use App\Models\OceanengineAdvertiserList;

/**
 * 巨量引擎开放平台
 */
class OpenPlatformConteroller extends Controller
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
            $oceanengine = Oceanengine::where('uid', $this->uid)->first();

            if (!$oceanengine) {
                $oceanengine = Oceanengine::create([
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

                $update_advertiser_list= $new_advertiser_list->toArray();
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
}

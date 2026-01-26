<?php

namespace App\Http\Controllers\Oceanengine;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Redis;

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
    private $access_token = '';
    private $expires_in = '';
    private $refresh_token = '';
    private $refresh_token_expires_in = '';
    private $advertiser_id = '';

    public function __construct() {
        $this->client = new Client([
            // 禁用 SSL 验证
            'verify' => false,
            'timeout' => 120
        ]);
        // $this->advertiser_id = 1691031371042824;
        // $this->access_token = '24a9dc9b72ad1f3138c594a274de1b2eef8fce37';
        // $this->access_token = $this->getRedisAccessToken();
    }

    /**
     * 获取千川投放账户基础信息
     */
    public function getPublicInfo(Request $request) {
        $reqData = $request->all();

        Log::info('getPublicInfo请求: '. json_encode($reqData));

        // auth_code有效期只有10分钟，且只能使用一次，过期或者多次使用都会报错
        $auth_code = $reqData['auth_code'] ?? '';
        // 授权范围
        $scope = $reqData['scope'] ?? '';
        // 
        $uid = $reqData['uid'] ?? '';

        if ($auth_code) {
            // Redis::setex($this->app_id.'_'.$uid.'_auth_code', 60 * 10, $auth_code);

            if (!$this->access_token) {
                $response = $this->getAccessToken($auth_code);

                Log::info('getAccessToken请求: '. json_encode($response));

                if ($response['status'] === 200 && $response['body']) {
                    $body = json_decode($response['body'], true);

                    if ($body && isset($body['data'])) {
                        $this->access_token = $body['data']['access_token'];
                        $this->expires_in = $body['data']['expires_in'];
                        $this->refresh_token = $body['data']['refresh_token'];
                        $this->refresh_token_expires_in = $body['data']['refresh_token_expires_in'];

                        $advertiserInfoResponse = $this->getAdvertiserInfo();

                        Log::info('getAdvertiserInfo请求: '. json_encode($advertiserInfoResponse));

                        if ($advertiserInfoResponse['status'] === 200 && $advertiserInfoResponse['body']) {
                            $advertiserInfoBody = json_decode($advertiserInfoResponse['body'], true);

                            if ($advertiserInfoBody && isset($advertiserInfoBody['data'])) {
                                if (isset($advertiserInfoBody['data']['list']) && count($advertiserInfoBody['data']['list']) > 0) {
                                    $this->advertiser_id = $advertiserInfoBody['data']['list'][0]['advertiser_id'];

                                    Redis::setex($this->app_id.'_'.$this->advertiser_id.'_access_token', $this->expires_in, $this->access_token);
                                    Redis::setex($this->app_id.'_'.$this->advertiser_id.'_refresh_token', $this->refresh_token_expires_in, $this->refresh_token);

                                    $advertiser_ids = $this->getAdvertiserList();

                                    $publicInfo = $this->clientPublicInfo($advertiser_ids);

                                    dd($publicInfo, 'advertiser_id:'.$this->advertiser_id, 'access_token:'.$this->access_token);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * 获取巨量引擎AccessToken
     */
    private function getAccessToken($auth_code) {
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

                return [
                    'status' => $statusCode,
                    'body' => $body
                ];
            } catch (RequestException $e) {
                Log::error('获取巨量引擎AccessToken错误: ' . $e->getMessage());
            }
        }
    }

    /**
     * 获取已授权账户
     */
    private function getAdvertiserInfo() {
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

                return [
                    'status' => $statusCode,
                    'body' => $body
                ];
            } catch (RequestException $e) {
                Log::error('获取已授权账户错误: ' . $e->getMessage());
            }
        }
    }

    /**
     * 获取纵横工作台下账户列表
     */
    private function getAdvertiserList() {
        $advertiser_ids = array();

        if ($this->client && $this->access_token && $this->advertiser_id) {
            try {
                $response = $this->client->request(
                    'GET',
                    $this->ad_advertiser_url . '?cc_account_id=' . $this->advertiser_id . '&account_source=QIANCHUAN',
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
                            $advertiser_ids[] = $item['advertiser_id'];
                        }
                    }
                }
            } catch (RequestException $e) {
                Log::error('获取纵横工作台下账户列表错误: ' . $e->getMessage());
            }
        }

        return $advertiser_ids;
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
        return Redis::get($this->app_id.'_'.$this->advertiser_id.'_access_token');
    }
}

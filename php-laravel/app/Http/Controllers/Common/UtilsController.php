<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

/**
 * 工具类
 */
class UtilsController extends Controller
{
    /**
     * 获取请求的IP
     */
    public function getClientRealIp(Request $request) {
        // 常见的代理服务器 IP 头字段（按优先级）
        $ipHeaders = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];
        
        foreach ($ipHeaders as $header) {
            if ($request->server($header)) {
                $ip = $request->server($header);
                
                // 处理多个 IP 的情况（如 X-Forwarded-For: client, proxy1, proxy2）
                if (strpos($ip, ',') !== false) {
                    $ipList = explode(',', $ip);
                    foreach ($ipList as $singleIp) {
                        $singleIp = trim($singleIp);
                        if ($this->isValidIp($singleIp)) {
                            return $singleIp;
                        }
                    }
                }
                
                if ($this->isValidIp($ip)) {
                    return $ip;
                }
            }
        }
        
        return $request->ip();
    }

    /**
     * 验证 IP 地址是否有效
     */
    private function isValidIp($ip) {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
            return false;
        }
        
        // 排除私有地址和保留地址
        $privateRanges = [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            '127.0.0.0/8',
            'fc00::/7', // IPv6 私有地址
        ];
        
        foreach ($privateRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * 检查 IP 是否在指定范围内
     */
    private function ipInRange($ip, $range) {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }
        
        list($range, $netmask) = explode('/', $range, 2);
        
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ipDecimal = ip2long($ip);
            $rangeDecimal = ip2long($range);
            $wildcardDecimal = pow(2, (32 - $netmask)) - 1;
            $netmaskDecimal = ~$wildcardDecimal;
            
            return ($ipDecimal & $netmaskDecimal) === ($rangeDecimal & $netmaskDecimal);
        }
        
        // 简化 IPv6 处理（生产环境建议使用专门的 IPv6 处理库）
        return false;
    }

    /**
     * 获取加密字符串
     */
    public static function getMD5(string $input) {
        // 获取二进制数据
        $binaryHash = hash('md5', $input, true);
        // 转换为 Base64
        return base64_encode($binaryHash);
    }

    /**
     * 获取请求头部 token
     */
    public function getHeaderToken(Request $request) {
        $token = '';
        $authorization = $request->header('Authorization');

        if ($authorization) {
            if (Redis::exists($authorization)) {
                $token = $authorization;
            }
        }

        return $token;
    }
}

<?php

declare(strict_types=1);

namespace App\Service;

use Hyperf\HttpServer\Contract\RequestInterface;

class UtilsService
{

    /**
     * 获取客户端真实 IP
	 * @param RequestInterface $request
	 * @return string
     */
    public function getClientRealIp(RequestInterface $request): string
    {
        // 按优先级获取 IP
        $ip = $this->getIpFromPriority($request);

        if ($ip) {
            return $ip;
        }

        // 最终回退
        $serverParams = $request->getServerParams();
        return $serverParams['remote_addr'] ?? '0.0.0.0';
    }

	/**
	 * 根据优先级获取客户端 IP
	 * @param RequestInterface $request
	 * @return string
	 */
    private function getIpFromPriority(RequestInterface $request): string
    {
        // 头字段优先级列表（按从高到低）
        $headers = [
            'x-real-ip',
            'x-forwarded-for',
            'http-x-forwarded-for',
            'http-client-ip',
            'http-x-real-ip',
            'http-x-forwarded',
            'http-forwarded-for',
        ];

        foreach ($headers as $header) {
            $value = $request->header($header);
            if (empty($value)) {
                continue;
            }

            // 处理多个 IP（如 X-Forwarded-For）
            if (strpos($value, ',') !== false) {
                $ips = array_map('trim', explode(',', $value));
                foreach ($ips as $ip) {
                    if ($this->isValidIp($ip)) {
                        return $ip;
                    }
                }
            } else {
                if ($this->isValidIp($value)) {
                    return $value;
                }
            }
        }

        return '';
    }

	/**
	 * 验证 IP 地址是否有效
	 * @param string $ip
	 * @return bool
	 */
    private function isValidIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }
}
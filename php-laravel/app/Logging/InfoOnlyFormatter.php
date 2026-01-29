<?php

namespace App\Logging;

use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Monolog\Handler\FilterHandler;

class InfoOnlyFormatter
{
    public function __invoke($logger)
    {
        foreach ($logger->getHandlers() as $handler) {
            // 创建过滤器，只允许 INFO 级别
            $filterHandler = new FilterHandler(
                $handler,
                Logger::INFO, // 最小级别
                Logger::INFO  // 最大级别（只接受 INFO）
            );
            
            // 替换原有的处理器
            $logger->setHandlers([$filterHandler]);
            
            // 设置格式化器
            $formatter = new LineFormatter(
                "[%datetime%] %channel%.INFO: %message% %context% %extra%\n",
                'Y-m-d H:i:s',
                true,
                true
            );
            
            $filterHandler->setFormatter($formatter);
        }
    }
}
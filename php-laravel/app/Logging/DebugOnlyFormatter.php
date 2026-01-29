<?php

namespace App\Logging;

use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Monolog\Handler\FilterHandler;

class DebugOnlyFormatter
{
    public function __invoke($logger)
    {
        foreach ($logger->getHandlers() as $handler) {
            // 创建过滤器，只允许 DEBUG 级别
            $filterHandler = new FilterHandler(
                $handler,
                Logger::DEBUG, // 最小级别
                Logger::DEBUG  // 最大级别（只接受 DEBUG）
            );
            
            // 替换原有的处理器
            $logger->setHandlers([$filterHandler]);
            
            // 设置格式化器
            $formatter = new LineFormatter(
                "[%datetime%] %channel%.DEBUG: %message% %context% %extra%\n",
                'Y-m-d H:i:s',
                true,
                true
            );
            
            $filterHandler->setFormatter($formatter);
        }
    }
}
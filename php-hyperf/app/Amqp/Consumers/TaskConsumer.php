<?php

declare(strict_types=1);

namespace App\Amqp\Consumers;

use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Result;
use PhpAmqpLib\Message\AMQPMessage;
use Hyperf\Di\Annotation\Inject;
use App\Service\OrderService;
use Psr\Log\LoggerInterface;

#[Consumer(exchange: "task.exchange", routingKey: "task.route", queue: "task.queue", nums: 1)]
class TaskConsumer extends ConsumerMessage
{
	#[Inject]
    private OrderService $orderService;
    protected LoggerInterface $logger;

    public function consumeMessage($data, AMQPMessage $message): Result
    {
		try {
			$this->orderService->stockConsume($data);
			return Result::ACK;
		} catch (\Exception $e) {
			$this->logger->error("【任务队列】失败", [
                'error' => $errorMessage,
                'trace' => $e->getTraceAsString()
            ]);
			return Result::NACK;
		}
    }
}

<?php

declare(strict_types=1);

namespace App\Amqp\Producers;

use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\Message\ProducerMessage;

#[Producer(exchange: "task.exchange", routingKey: "task.route")]
class TaskProducer extends ProducerMessage
{
    public function __construct(array $payload)
    {
        // 官网文档 https://hyperf.wiki/3.1/#/zh-cn/amqp?id=%e6%8a%95%e9%80%92%e6%b6%88%e6%81%af
        $this->payload = $payload;
    }
}
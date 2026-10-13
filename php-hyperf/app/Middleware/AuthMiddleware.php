<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Hyperf\Redis\Redis;

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(protected HttpResponse $response, protected Redis $redis)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = $request->getHeaderLine('Authorization');

        if (empty($token)) {
            return $this->response->json([
                'code' => 401,
                'data' => '',
                'msg' => 'Unauthorized',
                'error' => ''
            ])->withStatus(401);
        }

        $userInfo = $this->redis->get($token);

        if (!$userInfo || empty($userInfo)) {
            return $this->response->json([
                'code' => 403,
                'data' => '',
                'msg' => 'Forbidden',
                'error' => ''
            ])->withStatus(403);
        }

        $request = $request->withAttribute('user', json_decode($userInfo, true));
        // 获取: $request->getAttribute('user')

        return $handler->handle($request);
    }
}

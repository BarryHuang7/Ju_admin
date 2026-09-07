<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
use Hyperf\HttpServer\Router\Router;

/**
 * php bin/hyperf.php gen:controller 控制器名Controller
 * php bin/hyperf.php gen:model 数据表名
 */

Router::addRoute(['GET', 'POST', 'HEAD'], '/', 'App\Controller\IndexController@index');
Router::post('/getIndexInfo', 'App\Controller\IndexController@getIndexInfo');
Router::post('/generateStock', 'App\Controller\OrderController@generateStock');

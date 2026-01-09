<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Http\Controllers\Email\SendEmailController;
use App\Http\Controllers\AI\QWenConteroller;
use App\Http\Controllers\Home\IndexController;
use App\Http\Controllers\Order\FlashSaleController;
use App\Http\Controllers\Login\LoginController;
use App\Http\Controllers\File\UploadController;
use App\Http\Controllers\Websocket\ChatConteroller;

// Route::get('/', function () {
//     return view('welcome');
// });

// Route::get('/get-csrf-token', function() {
//   return response()->json([
//     'csrf_token' => csrf_token()
//   ]);
// });

/**
 * nginx伪静态
 * location / {
 *  # 只匹配单层路径，不包含斜杠
 *  rewrite ^/([a-zA-Z0-9_-]+)$ /index.php?page=$1 last;
 *  
 *  # 对于多层路径，使用 Laravel 的 try_files
 *  try_files $uri $uri/ /index.php?$query_string;
 * }
 */

Route::get('runTest', [SendEmailController::class, 'runTest']);
Route::post('sendEmail', [SendEmailController::class, 'sendEmail']);
// Route::resource('email', 'Email\SendEmailController');

Route::post('chatQWen', [QWenConteroller::class, 'chat']);

Route::get('guestRecord', [IndexController::class, 'guestRecord']);
Route::get('getVisitorNumber', [IndexController::class, 'getVisitorNumber']);

Route::post('flashSale', [FlashSaleController::class, 'simulationFlashSale']);

// 明确区分路由，避免冲突
Route::prefix('login')->group(function () {
  Route::post('/verification', [LoginController::class, 'verification'])->name('login.verification');
  Route::get('/getVerificationCode', [LoginController::class, 'getVerificationCode'])->name('login.getVerificationCode');
  Route::get('/loginOut', [LoginController::class, 'loginOut'])->name('login.loginOut');
});

Route::resource('image', 'File\ImageController');

Route::post('uploadFile', [UploadController::class, 'handleUpload']);

Route::get('/getAllOnlineUser', [ChatConteroller::class, 'getAllOnlineUser']);
Route::post('/sendMessage', [ChatConteroller::class, 'sendMessage']);
Route::post('/sendTimingMessage', [ChatConteroller::class, 'sendTimingMessage']);

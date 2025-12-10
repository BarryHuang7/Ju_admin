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

// Route::get('/', function () {
//     return view('welcome');
// });

// Route::get('/get-csrf-token', function() {
//   return response()->json([
//     'csrf_token' => csrf_token()
//   ]);
// });

Route::get('runTest', [SendEmailController::class, 'runTest']);
Route::post('sendEmail', [SendEmailController::class, 'sendEmail']);
// Route::resource('email', 'Email\SendEmailController');

Route::post('chatQWen', [QWenConteroller::class, 'chat']);

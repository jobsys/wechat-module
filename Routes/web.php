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

Route::any('/wechat', 'WeChatController@serve')->name('wechat.serve');
Route::any('/wechat/work/redirect', 'WeChatController@workRedirect')->name('wechat.work.redirect');

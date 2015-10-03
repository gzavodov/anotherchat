<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/


Route::get('/', function () { return view('welcome'); });
Route::get('auth/logout', 'Auth\CustomAuthController@getLogout');
Route::get('auth/{action?}', 'Auth\CustomAuthController@getLoginRegister');
Route::post('auth/login', 'Auth\CustomAuthController@postLogin');
Route::post('auth/register', 'Auth\CustomAuthController@postRegister');
Route::get('chat/user', 'Chat\ChatController@getChatPage');
Route::get('chat/admin', 'Chat\ChatController@getAdminPage');
Route::controllers([ 'password' => 'Auth\PasswordController', 'auth' => 'Auth\CustomAuthController' ]);

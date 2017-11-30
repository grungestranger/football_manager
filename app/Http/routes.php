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

Route::get('/', 'MainController@index');

Route::auth();
Route::get('/register', 'Auth\AuthController@getRegister');
Route::get('/login', function () { abort(404); });
Route::get('/confirm-email', 'Auth\AuthController@confirmEmail');

Route::group(['middleware' => 'auth'], function () {
	Route::get('/home', 'HomeController@index');
	Route::get('/team', 'TeamController@index');
	Route::post('/team/save', 'TeamController@save');
	Route::post('/team/save-as', 'TeamController@save_as');
	Route::controller('/match', 'MatchController');
	Route::get('/check-request', 'AjaxController@checkRequest');
});

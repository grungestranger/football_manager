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
	Route::post('/team/save-as', 'TeamController@saveAs');
	Route::post('/team/remove', 'TeamController@remove');
	Route::post('/challenge', 'MainController@challenge');
	Route::post('/from-challenge-remove', 'MainController@fromChallengeRemove');
	Route::post('/to-challenge-remove', 'MainController@toChallengeRemove');
	Route::post('/play', 'MainController@play');
	Route::get('/jwt', 'MainController@jwt');
	Route::get('/match', 'MatchController@index');
	Route::post('/match/save', 'MatchController@save');
});

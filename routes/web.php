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

Route::get('/', 'HomeController@index');
Route::post('/', 'HomeController@submit');
Route::get('/edit', 'HomeController@edit');

Route::get('/show', 'HomeController@show');
Route::get('/twit', 'HomeController@twit');
Route::get('/postToFacebook', 'HomeController@postToFacebook');

Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
});

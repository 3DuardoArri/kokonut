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

use App\Http\Middleware\ApiAuthMiddleware;

Route::get('/', function () {
    return view('welcome');
});

// Rutas API USER
Route::get('/usuario/pruebas', 'UserController@pruebas');
Route::post('/api/user/register', 'UserController@register');
Route::post('/api/user/login', 'UserController@login');
Route::put('/api/user/update', 'UserController@update');
Route::post('/api/user/uploadAvatar', 'UserController@uploadAvatar')->middleware(ApiAuthMiddleware::class);
Route::delete('/api/user/deleteAccount', 'UserController@deleteAccount');

// RUTAS API FOTOS 
Route::post('/api/fotos/uploadFoto', 'FotosController@uploadFoto')->middleware(ApiAuthMiddleware::class);
Route::get('/api/fotos/viewFotos', 'FotosController@viewFotos');
Route::delete('/api/fotos/deleteFoto/{id}', 'FotosController@deleteFoto');
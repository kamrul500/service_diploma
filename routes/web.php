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

Route::group(['middleware' => 'web'], function () {
    Route::get('/', [
        'uses' => 'HomeController@index',
        'as' => 'frontend.home'
    ]);
    Route::post('/add-to-cart', [
        'uses' => 'HomeController@addToCart',
        'as' => 'frontend.addToCart'
    ]);
    Route::get('/get-shopping-cart/', [
        'uses' => 'HomeController@getShoppingCart',
        'as' => 'frontend.getShoppingCart'
    ]);
    Route::post('/reduceByOne', [
        'uses' => 'HomeController@reduceItemRequest',
        'as' => 'frontend.reduceItem'
    ]);
    Route::post('/increaseByOne', [
        'uses' => 'HomeController@increaseItemRequest',
        'as' => 'frontend.increaseItem'
    ]);
    Route::post('/deleteItemRequest', [
        'uses' => 'HomeController@deleteItemRequest',
        'as' => 'frontend.deleteItem'
    ]);
    Route::get(
        '/allServices',
        [
            'uses' => 'HomeController@getListServices',
            'as' => 'frontend.services'
        ]
    );
});

Route::group(['middleware' => 'auth', 'prefix' => 'client'], function () {
    Route::get('/index', [
        'uses' => 'ClientController@index',
        'as' => 'client.index'
    ]);
    Route::post('/postOrder', [
        'uses' => 'ClientController@confirmOrder',
        'as' => 'client.confirmOrder'
    ]);
    Route::get('/getOrder/{id}', [
        'uses' => 'ClientController@getOrder',
        'as' => 'client.getOrder'
    ]);
    Route::get('/profile', [
        'uses' => 'ClientController@profile',
        'as' => 'client.profile'
    ]);
    Route::get('/getCartInfoClient', [
        'uses' => 'ClientController@getCartInfoClient',
        'as' => 'client.cartInfo'
    ]);
    Route::post('/changeProfile', [
        'uses' => 'ClientController@changeProfile',
        'as' => 'client.changeProfile'
    ]);
});

Route::group(['middleware' => 'roles', 'prefix' => 'admin', 'roles' => ['admin']], function () {
    Route::get('/index', [
        'uses' => 'AdminController@index',
        'as' => 'admin.index'
    ]);
    Route::get('/orders', [
        'uses' => 'AdminController@viewRequests',
        'as' => 'admin.all-requests'
    ]);
    Route::get('/viewOrder/{id}', [
        'uses' => 'AdminController@viewOrder',
        'as' => 'admin.viewOrder'
    ]);
    Route::get('/createUser', [
        'uses' => 'AdminController@createUserRequest',
        'as' => 'admin.createUser'
    ]);
    Route::post('/set-executor-order/{orderId}/{userId}', [
        'uses' => 'AdminController@setExecutorRequest'
    ]);
    Route::post(
        '/revoke-executor-order/{orderId}/{userId}',
        [
            'uses' => 'AdminController@revokeExecutorOrderRequest'
        ]
    );
    Route::post('/postUser', [
        'uses' => 'AdminController@postUserRequest',
        'as' => 'admin.postUserRequest'
    ]);
    Route::post('/deleteUserRequest/{id}', [
        'uses' => 'AdminController@deleteUserRequest',
        'as' => 'admin.deleteUserRequest'
    ]);
});

Route::group(['middleware' => 'roles', 'prefix' => 'executor', 'roles' => ['executor', 'admin']], function () {
    Route::get('/index', [
        'uses' => 'ExecutorController@index',
        'as' => 'executor.index'
    ]);
    Route::get('/viewOrder/{id}', [
        'uses' => 'ExecutorController@getOrder',
        'as' => 'executor.viewOrder'
    ]);
    Route::post('/submitComment', [
        'uses' => 'ExecutorController@submitComment',
        'as' => 'executor.submitComment'
    ]);
    Route::post('/setStatusOrder/', [
        'uses' => 'ExecutorController@setStatusOrderRequest',
        'as' => 'executor.setStatusOrderRequest'
    ]);
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

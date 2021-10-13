<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::prefix('v1')->group(function (){
    Route::POST('otp_send', [Controllers\API\v1\LoginSignupContoller::class, 'otp_send']);
    Route::POST('otp_validation', [Controllers\API\v1\LoginSignupContoller::class, 'otp_validation']);


    Route::middleware('auth:api')->group( function () {
        Route::POST('user_profile_update',[Controllers\API\v1\UserController::class, 'user_profile_update']);
        Route::GET('cuisine',[Controllers\API\v1\UserController::class, 'cuisine']);
        Route::GET('interest',[Controllers\API\v1\UserController::class, 'interest']);
        Route::GET('cuisine_interest',[Controllers\API\v1\UserController::class, 'cuisine_interest']);
        Route::POST('user_list',[Controllers\API\v1\UserController::class, 'user_list']);
        Route::POST('user_heart_request',[Controllers\API\v1\UserController::class, 'user_heart_request']);
        Route::POST('heart_requesting_list',[Controllers\API\v1\UserController::class, 'heart_requesting_list']);
        Route::POST('update_heart_status', [Controllers\API\v1\UserController::class, 'heart_accept_or_rejects']);
        Route::POST('fcm_token', [Controllers\API\v1\UserController::class, 'fcm_token']);
        Route::POST('is_notification', [Controllers\API\v1\UserController::class, 'is_notification']);

        //Agora calling
        //Route::get('/agora_chat', 'App\Http\Controllers\AgoraVideoController@index');
        Route::POST('/agora_token',  [Controllers\API\v1\AgoraVideoController::class ,'token']);
        Route::POST('/agora_join_or_leave',[ Controllers\API\v1\AgoraVideoController::class, 'agora_join_or_leave']);
        Route::GET('/notification_list',[ Controllers\API\v1\AgoraVideoController::class, 'notification_list']);
        Route::POST('/virtual_date',[ Controllers\API\v1\AgoraVideoController::class, 'virtual_date']);

    });
    Route::GET('test', [Controllers\API\v1\UserController::class, 'test']);

});






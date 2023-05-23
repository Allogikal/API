<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Order\OrderController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\WorkShift\ShiftController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/login', [AuthController::class, 'logIn'])->name('login');
Route::get('/unauthorization', [AuthController::class, 'unauthorization'])->name('unauthorization');

/**
 *  GROUP_AUTH
 */
Route::group(['middleware' => ['authorize', 'auth:sanctum']], function () {
    Route::get('/logout', [AuthController::class, 'logOut']);
});

/**
 *  GROUP_ADMIN_AUTH
*/
Route::group(['middleware' => ['auth:sanctum', 'admin']], function () {
    // Операции_сотрудники
    Route::get('/user', [UserController::class, 'index']);
    Route::post('/user', [UserController::class, 'store']);
    Route::get('/user/{id}', [UserController::class, 'show']);
    Route::get('/user/{id}/to-dismiss', [UserController::class, 'destroy']);
    // Операции_смены
    Route::post('/work-shift', [ShiftController::class, 'store']);
    Route::get('/work-shift/{id}/open', [ShiftController::class, 'open']);
    Route::get('/work-shift/{id}/close', [ShiftController::class, 'close']);
    Route::get('/work-shift', [ShiftController::class, 'index']);
    Route::post('/work-shift/{id}/user', [ShiftController::class, 'add']);
    Route::delete('/work-shift/{id}/user/{user_id}', [ShiftController::class, 'delete']);
    // Операции_заказы
    Route::get('/work-shift/{id}/order', [OrderController::class, 'orders']);
});

/**
 *  GROUP_WAITER_AUTH
 */
Route::group(['middleware' => ['auth:sanctum', 'waiter']], function () {
    Route::post('/order', [OrderController::class, 'add_order']);
    Route::get('/order/{id}', [OrderController::class, 'show']);
    Route::post('/order/{id}/position', [OrderController::class, 'add_position']);
    Route::delete('/order/{id}/position/{position_id}', [OrderController::class, 'delete_position']);
    Route::patch('/order/{id}/change-status', [OrderController::class, 'change_status']);
    Route::get('/work-shift/{id}/orders', [OrderController::class, 'index']);
});

/**
 *  GROUP_COOK_AUTH
 */
Route::group(['middleware' => ['auth:sanctum', 'cook']], function () {
    Route::get('/order/taken/get', [OrderController::class, 'taken']);
    Route::patch('/orders/{id}/change-status', [OrderController::class, 'change_status_cook']);
});

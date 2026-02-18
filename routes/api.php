<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VerifyController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ExpenseController;

Route::post('/register', [AuthController::class,'register']);
Route::post('/login', [AuthController::class,'login']);

Route::post('/email/send-verification', [VerifyController::class,'send']);
Route::post('/email/check', [VerifyController::class,'check']);
Route::post('/email/confirm', [VerifyController::class,'confirm']);

Route::post('/password/forgot', [AuthController::class,'forgotPassword']);
Route::post('/password/verify-token', [AuthController::class,'verifyResetToken']);
Route::post('/password/reset', [AuthController::class,'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
  Route::post('/logout', [AuthController::class,'logout']);
  Route::post('/password/change', [AuthController::class,'changePassword']);

  Route::post('/upload/image', [UploadController::class,'image']);

  Route::apiResource('products', ProductController::class);
  Route::apiResource('orders', OrderController::class);
  Route::apiResource('expenses', ExpenseController::class);
});

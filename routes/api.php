<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ArrangementController;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ReviewController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function (){
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/me', function (Request $request){
        return $request->user();
    });

    Route::put('/change-password', [AuthController::class, 'changePassword']);
    
    Route::apiResource('arrangements', ArrangementController::class);

    Route::apiResource('bookings', BookingController::class);

    Route::apiResource('reviews', ReviewController::class);
});


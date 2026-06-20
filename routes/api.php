<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ArrangementController;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\UserController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::get('/weather', [ArrangementController::class, 'weather']);

Route::middleware('auth:sanctum')->group(function (){
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/me', function (Request $request){
        return $request->user();
    });

    Route::put('/change-password', [AuthController::class, 'changePassword']);
    
    Route::get('/arrangements/export/csv', [ArrangementController::class, 'exportCsv']);
    Route::get('/bookings/export/csv', [BookingController::class, 'exportCsv'])
        ->middleware('role:admin');

    Route::get('/arrangements/ratings', [ArrangementController::class, 'ratings']);
    Route::apiResource('arrangements', ArrangementController::class)
        ->only(['index', 'show']);
    Route::delete('/arrangements/{id}', [ArrangementController::class, 'destroy'])
        ->middleware('role:admin');
    Route::apiResource('arrangements', ArrangementController::class)
        ->only(['store','update'])
        ->middleware('role:admin,manager');
    
    Route::apiResource('bookings', BookingController::class)
        ->only(['index', 'show']);
    Route::delete('/bookings/{id}', [BookingController::class, 'destroy'])
        ->middleware('role:admin');
    Route::apiResource('bookings', BookingController::class)
        ->only(['store','update'])
        ->middleware('role:admin,manager');
    Route::get('/arrangements/{id}/bookings', [BookingController::class, 'byArrangement'])
        ->middleware('role:admin,manager');

    Route::apiResource('reviews', ReviewController::class)
        ->only(['index', 'show']);
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy'])
        ->middleware('role:admin');
    Route::apiResource('reviews', ReviewController::class)
        ->only(['store','update'])
        ->middleware('role:admin,manager');
    Route::get('/arrangements/{id}/reviews', [ReviewController::class, 'byArrangement']);

    Route::get('/arrangements/{id}/weather', [ArrangementController::class, 'weatherByArrangement']);

    Route::get('/city-info', [ArrangementController::class, 'cityInfo']);
    Route::get('/arrangements/{id}/city-info', [ArrangementController::class, 'cityInfoByArrangement']);

    Route::get('/users', [UserController::class, 'index'])
        ->middleware('role:admin');
    Route::put('/users/{id}/role', [UserController::class, 'changeRole'])
        ->middleware('role:admin');
});


<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ArrangementController;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;

/*Route::get('/arrangements', [ArrangementController::class, 'index']);
Route::post('/arrangements', [ArrangementController::class, 'store']);
Route::put('/arrangements/{id}', [ArrangementController::class, 'update']);
Route::delete('/arrangements/{id}', [ArrangementController::class, 'destroy']);*/



Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (){
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/me', function (Request $request){
        return $request->user();
    });

    Route::apiResource('arrangements', ArrangementController::class);
});


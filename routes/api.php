<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ArrangementController;

Route::get('/arrangements', [ArrangementController::class, 'index']);
Route::post('/arrangements', [ArrangementController::class, 'store']);
Route::put('/arrangements/{id}', [ArrangementController::class, 'update']);
Route::delete('/arrangements/{id}', [ArrangementController::class, 'destroy']);

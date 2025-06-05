<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoutineController;

Route::get('/routine', [RoutineController::class, 'getRoutine']);
Route::post('/routine/importRoutine', [RoutineController::class, 'importRoutine']);

<?php

use App\Actions\ParsePdfTableAction;
use App\Models\Routine;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoutineController;


Route::get('/sections', [RoutineController::class, 'getAllSections']);
Route::get('/teachers', [RoutineController::class, 'getAllTeachers']);
Route::get('/routine', [RoutineController::class, 'getRoutine']);
//Route::post('/routine/importRoutine', [RoutineController::class, 'importRoutine']);
Route::get('/empty-rooms', [RoutineController::class, 'getEmptyRooms']);


Route::get('/routine-view', function () {
    return view('routine');
});


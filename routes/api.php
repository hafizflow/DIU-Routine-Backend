<?php

use App\Actions\ParsePdfTableAction;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoutineController;

Route::get('/routine', [RoutineController::class, 'getRoutine']);
Route::post('/routine/importRoutine', [RoutineController::class, 'importRoutine']);

Route::get('/test', function() {
    $action = new ParsePdfTableAction();
    $table = $action->execute(storage_path('app/routine-1-2.pdf'));
});

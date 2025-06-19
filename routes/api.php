<?php

use App\Actions\ParsePdfTableAction;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\TeacherInfoController;
use App\Models\Routine;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoutineController;


Route::get('/sections', [RoutineController::class, 'getAllSections']);
Route::get('/teachers', [RoutineController::class, 'getAllTeachers']);
Route::get('/routine', [RoutineController::class, 'getRoutine']);
Route::get('/empty-rooms', [RoutineController::class, 'getEmptyRooms']);
Route::get('/routine/all', [RoutineController::class, 'getAllRoutine']);


// ----------- Routine Import Operations ----------- //
Route::post('/routine/importRoutine', [RoutineController::class, 'importRoutine']);


// ----------- View Routine ----------- //
Route::get('/routine-view', function () {
    return view('routine');
});


// ----------- Web Scraper Routes ----------- //
//Route::get('/courses', [CourseController::class, 'scrape']);
Route::get('/teachers/scrape', [TeacherInfoController::class, 'scrape']);

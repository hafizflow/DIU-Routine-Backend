<?php

namespace App\Http\Controllers;

use App\Actions\ParsePdfTableAction;
use App\Http\Requests\RoutineRequest;
use App\Http\Requests\RoutineImportRequest;
use App\Models\Course;
use App\Models\Routine;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class RoutineController extends Controller
{
    public function getRoutineTable(): JsonResponse
    {
        $routine = Routine::all()->makeHidden(['created_at', 'updated_at']);

        if ($routine->isEmpty()) {
            return response()->json([
                'status' => 'empty',
                'message' => 'No routine data found.',
                'data' => [],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'last_updated' => $routine->first()->getOriginal('updated_at'),
            'data' => $routine,
        ]);
    }

    public function getCourses(): JsonResponse
    {
        $courses = Course::all()->makeHidden(['created_at', 'updated_at']);

        if ($courses->isEmpty()) {
            return response()->json([
                'status' => 'empty',
                'message' => 'No routine data found.',
                'data' => [],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'last_updated' => $courses->first()->getOriginal('updated_at'),
            'data' => $courses,
        ]);
    }

    public function getTeacher(): JsonResponse
    {
        $teachers = Teacher::all()->makeHidden(['created_at', 'updated_at']);

        if ($teachers->isEmpty()) {
            return response()->json([
                'status' => 'empty',
                'message' => 'No teachers found.',
                'data' => [],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'last_updated' => $teachers->first()->getOriginal('updated_at'),
            'data' => $teachers,
        ]);
    }


    public function getAllRoutines(): JsonResponse
    {
        $routines = Routine::with(['course', 'teacherInfo'])->get()->map(function ($routine) {
            return [
                'id' => $routine->id,
                'day' => $routine->day,
                'start_time' => $routine->start_time,
                'end_time' => $routine->end_time,
                'course_code' => $routine->course_code,
                'room' => $routine->room,
                'teacher' => $routine->teacher,
                'course_title' => optional($routine->course)->course_title,
                'teacher_info' => $routine->teacherInfo
                    ? [
                        'name' => $routine->teacherInfo->name,
                        'designation' => $routine->teacherInfo->designation,
                        'cell_phone' => $routine->teacherInfo->cell_phone,
                        'email' => $routine->teacherInfo->email,
                    ] : null,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $routines,
        ]);
    }

    public function getEmptyRooms(Request $request): JsonResponse
    {
        $startTime = $request->input('start_time');

        // Define time slots
        $timeSlots = [
            '08:30:00' => '10:00:00',
            '10:00:00' => '11:30:00',
            '11:30:00' => '01:00:00',
            '01:00:00' => '02:30:00',
            '02:30:00' => '04:00:00',
            '04:00:00' => '05:30:00',
        ];

        // Validate start_time
        if (!array_key_exists($startTime, $timeSlots)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or missing start_time.',
            ], 400);
        }

        $endTime = $timeSlots[$startTime];

        // Fetch all distinct days in the Routine table
        $days = Routine::distinct()->pluck('day')->filter()->unique();

        $result = [];

        foreach ($days as $day) {
            $emptyRooms = Routine::whereNull('course')
                ->whereNull('teacher')
                ->whereNull('section')
                ->where('start_time', $startTime)
                ->where('end_time', $endTime)
                ->where('day', $day)
                ->pluck('room')
                ->filter()
                ->unique()
                ->sort()
                ->values();

            $result[$day] = $emptyRooms;
        }

        return response()->json([
            'status' => 'success',
            'start_time' => $startTime,
            'end_time' => $endTime,
            'data' => $result,
        ]);
    }

    public function getAllSections(): JsonResponse
    {
        $sections = Routine::distinct()
            ->pluck('section')
            ->filter() // removes nulls
            ->map(function ($section) {
                // Remove trailing digits (e.g., A1 → A, N2 → N)
                return preg_replace('/[0-9]+$/', '', $section);
            })
            ->unique()
            ->sort()
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $sections,
        ]);
    }

    public function getAllTeachers(): JsonResponse
    {
        $teachers = Routine::whereNotNull('teacher')
            ->distinct()
            ->pluck('teacher')
            ->sort()
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $teachers,
        ]);
    }

    public function getRoutine(RoutineRequest $request): JsonResponse
    {
        $section = $request->input('section');
        $sections = [$section, $section . '1', $section . '2'];

        $dayOrder = [
            'SATURDAY', 'SUNDAY', 'MONDAY',
            'TUESDAY', 'WEDNESDAY', 'THURSDAY'
        ];

        $timeOrder = [
            '08:30:00', '10:00:00', '11:30:00',
            '13:00:00', '14:30:00', '16:00:00', '17:30:00' // Adjusted to 24-hour format for consistency
        ];

        // Normalize time format for comparison (e.g., convert to HH:MM:SS)
        $normalizeTime = function ($time) {
            return date('H:i:s', strtotime($time));
        };

        $routine = Routine::with(['course', 'teacherInfo'])
            ->whereIn('section', $sections)
            ->orderBy('start_time') // Initial DB sort for efficiency
            ->get(['day', 'start_time', 'end_time', 'course_code', 'room', 'teacher', 'section'])
            ->groupBy('day')
            ->map(function ($daySchedule) use ($timeOrder, $normalizeTime) {
                return $daySchedule
                    ->sortBy(function ($class) use ($timeOrder, $normalizeTime) {
                        $normalizedTime = $normalizeTime($class->start_time);
                        $index = array_search($normalizedTime, $timeOrder);
                        return $index !== false ? $index : 999; // Unmatched times go to the end
                    })
                    ->values()
                    ->map(fn($class) => [
                        'start_time' => $normalizeTime($class->start_time), // Ensure consistent format
                        'end_time' => $normalizeTime($class->end_time),
                        'course_code' => $class->course_code,
                        'course_title' => optional($class->course)->course_title,
                        'room' => $class->room,
                        'section' => $class->section,
                        'teacher' => $class->teacher,
                        'teacher_info' => $class->teacherInfo
                            ? [
                                'name' => $class->teacherInfo->name,
                                'designation' => $class->teacherInfo->designation,
                                'cell_phone' => $class->teacherInfo->cell_phone,
                                'email' => $class->teacherInfo->email,
                            ] : null,
                    ]);
            })
            ->sortBy(function ($_, $day) use ($dayOrder) {
                return array_search(strtoupper($day), $dayOrder);
            });

        if ($routine->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No schedule found for the given section.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $routine,
        ]);
    }


    public function importRoutine(RoutineImportRequest $request): JsonResponse
    {
        try {
            $file = $request->file('pdf_file');
            if (!$file->isValid()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid file upload. Please ensure the file is a valid PDF.',
                ], 400);
            }

            // Store the file
            $path = $file->storeAs('pdf', 'routine.pdf', 'public');
            $fullPath = storage_path('app/public/' . $path);

            //  Verify file exists
            if (!file_exists($fullPath)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to store the uploaded file.',
                ], 500);
            }

            // Parse the PDF and extract the routine
            $action = new ParsePdfTableAction();
            $schedule = $action->execute($fullPath);

            // Clear existing records (optional)
            Routine::truncate();

            foreach ($schedule as $entry) {
                Routine::create($entry);
            }

            // Delete the temporary file
            Storage::disk('local')->delete($path);

            return response()->json([
                'status' => 'success',
                'message' => 'Class routine imported successfully',
                'records_imported' => count($schedule),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to import class routine: ' . $e->getMessage(),
            ], 500);
        }
    }
}

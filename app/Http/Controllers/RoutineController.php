<?php

namespace App\Http\Controllers;

use App\Actions\ParsePdfTableAction;
use App\Http\Requests\RoutineRequest;
use App\Http\Requests\RoutineImportRequest;
use App\Models\Routine;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class RoutineController extends Controller
{
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

        // Define the desired day order
        $dayOrder = [
            'SATURDAY', 'SUNDAY', 'MONDAY',
            'TUESDAY', 'WEDNESDAY', 'THURSDAY'
        ];

        $timeOrder = [
            '08:30:00',
            '10:00:00',
            '11:30:00',
            '01:00:00',
            '02:30:00',
            '04:00:00',
            '05:30:00'
        ];

        $routine = Routine::whereIn('section', $sections)
            ->orderBy('start_time')
            ->get(['day', 'start_time', 'end_time', 'course', 'room', 'teacher', 'section']) // Include section
            ->groupBy('day')
            ->map(function ($daySchedule) use ($timeOrder) {
                return $daySchedule
                    ->sortBy(function ($class) use ($timeOrder) {
                        $index = array_search($class->start_time, $timeOrder);
                        // Return the index if found, otherwise a high number to put it at the end
                        return $index !== false ? $index : 999;
                    })
                    ->values()
                    ->map(fn($class) => [
                        'start_time' => $class->start_time,
                        'end_time' => $class->end_time,
                        'course' => $class->course,
                        'room' => $class->room,
                        'teacher' => $class->teacher,
                        'section' => $class->section,
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

<?php

namespace App\Http\Controllers;

use App\Actions\ParsePdfTableAction;
use App\Http\Requests\RoutineRequest;
use App\Http\Requests\RoutineImportRequest;
use App\Models\Routine;
use App\Services\PdfParserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class RoutineController extends Controller
{
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
            ->get(['day', 'start_time', 'end_time', 'course', 'room', 'teacher'])
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

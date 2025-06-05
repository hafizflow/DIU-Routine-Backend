<?php

namespace App\Http\Controllers;

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
        $department = $request->input('department');
        $section = $request->input('section');

        // Generate section variations (base, base1, base2)
        $sections = [
            $section,
            $section . '1',
            $section . '2'
        ];

        $routine = Routine::where('department', $department)
            ->whereIn('section', $sections)
            ->get(['day', 'start_time', 'end_time', 'course_code', 'room', 'teacher_initials'])
            ->groupBy('day')
            ->map(function ($daySchedule) {
                return $daySchedule->map(function ($class) {
                    return [
                        'start_time' => $class->start_time,
                        'end_time' => $class->end_time,
                        'course_code' => $class->course_code,
                        'room' => $class->room,
                        'teacher_initials' => $class->teacher_initials,
                    ];
                });
            });

        if ($routine->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No schedule found for the given department and section.',
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

            $parserService = new PdfParserService();
            $schedule = $parserService->parseRoutine($fullPath);

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

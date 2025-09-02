<?php

namespace App\Http\Controllers;

use App\Actions\ParsePdfTableAction;
use App\Http\Requests\RoutineRequest;
use App\Http\Requests\RoutineImportRequest;
use App\Models\Course;
use App\Models\Routine;
use App\Models\Teacher;
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
                'status' => 'failed',
                'message' => 'No routine data found.',
                'data' => [],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'version' => 'Spring 2025',
            'data' => $routine,
        ]);
    }

    public function getCourses(): JsonResponse
    {
        $courses = Course::all()->makeHidden(['created_at', 'updated_at']);

        if ($courses->isEmpty()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'No course data found.',
                'data' => [],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'version' => 'v1',
            'data' => $courses,
        ]);
    }

    public function getTeacher(): JsonResponse
    {
        $teachers = Teacher::all()->makeHidden(['created_at', 'updated_at', 'employee_id']);

        if ($teachers->isEmpty()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'No teachers found.',
                'data' => [],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'version' => 'v1',
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
        $dayOrder = [
            'SATURDAY', 'SUNDAY', 'MONDAY',
            'TUESDAY', 'WEDNESDAY', 'THURSDAY'
        ];

        // Validate and get start_time from request
        $request->validate([
            'start_time' => 'nullable|date_format:H:i'
        ]);
        $startTime = $request->input('start_time');

        $query = Routine::whereNull('teacher')
            ->whereNull('course_code')
            ->select(['day', 'start_time', 'end_time', 'room']);

        // Apply time filter if provided
        if ($startTime) {
            $query->where('start_time', $startTime);
        }

        $emptyRooms = $query->get()
            ->groupBy(function ($item) {
                return strtoupper($item->day);
            })
            ->sortBy(function ($group, $day) use ($dayOrder) {
                return array_search($day, $dayOrder);
            })
            ->map(function ($dayGroup) {
                return $dayGroup->map(function ($item) {
                    return [
                        'start_time' => $item->start_time,
                        'end_time' => $item->end_time,
                        'room' => $item->room
                    ];
                })->sortBy('start_time')->values();
            });

        if ($emptyRooms->isEmpty()) {
            return response()->json([
                'status' => 'empty',
                'message' => $startTime
                    ? 'No empty rooms found for the specified time.'
                    : 'No empty rooms found in the schedule.',
                'data' => array_fill_keys($dayOrder, []), // Return all days with empty arrays
            ]);
        }

        // Ensure all days are present in response
        $result = array_fill_keys($dayOrder, []);
        foreach ($emptyRooms as $day => $rooms) {
            $result[$day] = $rooms;
        }

        return response()->json([
            'status' => 'success',
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
        // Get distinct teacher initials from routines
        $teacherInitials = Routine::whereNotNull('teacher')
            ->distinct()
            ->pluck('teacher');

        // Get matching teachers from teachers table
        $teachers = Teacher::whereIn('teacher', $teacherInitials)
            ->get(['teacher', 'name']);

        // Build result: map initial to name (if matched)
        $result = $teacherInitials->map(function ($initial) use ($teachers) {
            $match = $teachers->firstWhere('teacher', $initial);
            return [
                'teacher' => $initial,
                'name' => $match ? $match->name : null,
            ];
        })->sortBy('teacher')->values();

        return response()->json([
            'status' => 'success',
            'data' => $result,
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
            '13:00:00', '14:30:00', '16:00:00', '17:30:00'
        ];

        // Helper to normalize time
        $normalizeTime = function ($time) {
            $time = trim($time);
            if (strlen($time) === 5) {
                return $time . ':00';
            }
            return $time;
        };

        // Helper to normalize room
        $normalizeRoom = function ($room) {
            $room = str_replace(["\r", "\n"], ' ', $room);
            $room = trim(preg_replace('/\s+/', ' ', $room));
            $room = preg_replace('/[^A-Za-z0-9\-()\s]/', '', $room);
            $room = str_replace(['G01', 'G1'], 'G1', $room);
            return $room;
        };

        $routine = Routine::with(['course', 'teacherInfo'])
            ->whereIn('section', $sections)
            ->orderBy('day')
            ->orderBy('start_time')
            ->get(['day', 'start_time', 'end_time', 'course_code', 'room', 'teacher', 'section'])
            ->groupBy(function ($item) {
                return strtoupper($item->day);
            })
            ->map(function ($daySchedule) use ($timeOrder, $normalizeTime, $normalizeRoom) {
                $mergedClasses = collect();

                // ✅ Group classes by section to avoid interleaved issues
                $dayScheduleBySection = $daySchedule->groupBy('section');

                foreach ($dayScheduleBySection as $sectionClasses) {
                    $previousClass = null;

                    // Sort by time
                    $sortedClasses = $sectionClasses->sortBy(function ($class) use ($timeOrder, $normalizeTime) {
                        $normalizedTime = $normalizeTime($class->start_time);
                        $index = array_search($normalizedTime, $timeOrder);
                        return $index !== false ? $index : 999;
                    })->values();

                    foreach ($sortedClasses as $class) {
                        $currentClassData = [
                            'start_time' => $normalizeTime($class->start_time),
                            'end_time' => $normalizeTime($class->end_time),
                            'course_code' => $class->course_code,
                            'section' => $class->section,
                            'course_title' => optional($class->course)->course_title,
                            'room' => $normalizeRoom($class->room),
                            'original_room' => $normalizeRoom($class->room),
                            'teacher' => $class->teacher,
                            'teacher_info' => $class->teacherInfo ? [
                                'name' => $class->teacherInfo->name,
                                'designation' => $class->teacherInfo->designation,
                                'cell_phone' => $class->teacherInfo->cell_phone,
                                'email' => $class->teacherInfo->email,
                                'image_url' => $class->teacherInfo->image_url,
                            ] : null,
                        ];

                        $canMerge = $previousClass &&
                            $previousClass['course_code'] === $currentClassData['course_code'] &&
                            $previousClass['section'] === $currentClassData['section'] &&
                            $previousClass['teacher'] === $currentClassData['teacher'] &&
                            $previousClass['room'] === $currentClassData['room'] &&
                            $previousClass['end_time'] === $currentClassData['start_time'];

                        if ($canMerge) {
                            // Extend previous class's end time
                            $previousClass['end_time'] = $currentClassData['end_time'];
                        } else {
                            // Push finished class and start new one
                            if ($previousClass) {
                                $previousClass['room'] = $previousClass['original_room'];
                                unset($previousClass['original_room']);
                                $mergedClasses->push($previousClass);
                            }
                            $previousClass = $currentClassData;
                        }
                    }

                    // Push the final class in the group
                    if ($previousClass) {
                        $previousClass['room'] = $previousClass['original_room'];
                        unset($previousClass['original_room']);
                        $mergedClasses->push($previousClass);
                    }
                }

                // Final sort of the day
                return $mergedClasses->sortBy(function ($class) use ($timeOrder) {
                    $index = array_search($class['start_time'], $timeOrder);
                    return $index !== false ? $index : 999;
                })->values();
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
            'status' => 'Success',
            'data' => $routine,
        ]);
    }

    public function getTeacherClasses(Request $request): JsonResponse
    {
        $request->validate([
            'teacher' => 'required|string|max:10'
        ]);

        $teacherInitial = strtoupper($request->input('teacher'));

        $teacherInfo = Teacher::where('teacher', $teacherInitial)->first();

        $timeOrder = [
            '08:30:00' => 1,
            '10:00:00' => 2,
            '11:30:00' => 3,
            '01:00:00' => 4,
            '02:30:00' => 5,
            '04:00:00' => 6,
            '05:30:00' => 7
        ];

        $classes = Routine::with(['course', 'teacherInfo'])
            ->where('teacher', $teacherInitial)
            ->get()
            ->sortBy(function ($class) use ($timeOrder) {
                $dayOrder = [
                    'SATURDAY' => 1,
                    'SUNDAY' => 2,
                    'MONDAY' => 3,
                    'TUESDAY' => 4,
                    'WEDNESDAY' => 5,
                    'THURSDAY' => 6
                ];

                $day = strtoupper($class->day);
                $startTime = $class->start_time;

                if (strlen($startTime) === 5) {
                    $startTime .= ':00';
                }

                $dayValue = $dayOrder[$day] ?? 7;
                $timeValue = $timeOrder[$startTime] ?? 8;

                return ($dayValue * 100) + $timeValue;
            })
            ->values()
            ->groupBy(function ($class) {
                return strtoupper($class->day);
            })
            ->map(function ($dayClasses) use ($timeOrder) {
                $mergedClasses = collect();
                $previousClass = null;

                $sortedClasses = $dayClasses->sortBy(function ($class) use ($timeOrder) {
                    $startTime = $class->start_time;
                    if (strlen($startTime) === 5) {
                        $startTime .= ':00';
                    }
                    return $timeOrder[$startTime] ?? 99;
                })->values();

                foreach ($sortedClasses as $class) {
                    // Normalize room string for comparison
                    $normalizedRoom = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $class->room));
                    $normalizedRoom = str_replace(['G01', 'G1'], 'G1', $normalizedRoom);

                    $currentClassData = [
                        'course_code' => $class->course_code,
                        'section' => $class->section,
                        'course_title' => optional($class->course)->course_title,
                        'room' => str_replace(["\r", "\n"], ' ', $class->room),
                        'normalized_room' => $normalizedRoom
                    ];

                    if (
                        $previousClass &&
                        $previousClass['course_code'] === $currentClassData['course_code'] &&
                        $previousClass['section'] === $currentClassData['section'] &&
                        $previousClass['course_title'] === $currentClassData['course_title'] &&
                        $previousClass['normalized_room'] === $currentClassData['normalized_room'] &&
                        $previousClass['end_time'] === $class->start_time
                    ) {
                        // Merge with previous class
                        $previousClass['end_time'] = $class->end_time;
                        $previousClass['room'] = $currentClassData['room']; // update to most recent format
                    } else {
                        if ($previousClass) {
                            unset($previousClass['normalized_room']);
                            $mergedClasses->push($previousClass);
                        }
                        $previousClass = [
                            'start_time' => $class->start_time,
                            'end_time' => $class->end_time,
                            ...$currentClassData
                        ];
                    }
                }

                if ($previousClass) {
                    unset($previousClass['normalized_room']);
                    $mergedClasses->push($previousClass);
                }

                return $mergedClasses->sortBy(function ($class) use ($timeOrder) {
                    $startTime = $class['start_time'];
                    if (strlen($startTime) === 5) {
                        $startTime .= ':00';
                    }
                    return $timeOrder[$startTime] ?? 99;
                })->values();
            });

        $dayOrder = ['SATURDAY', 'SUNDAY', 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY'];

        $orderedClasses = collect($dayOrder)->mapWithKeys(function ($day) use ($classes) {
            return [$day => $classes->get($day, [])];
        });

        if ($orderedClasses->flatten(1)->isEmpty()) {
            return response()->json([
                'status' => 'empty',
                'message' => 'No classes found for teacher ' . $teacherInitial,
                'data' => []
            ]);
        }

        return response()->json([
            'status' => 'success',
            'teacher' => $teacherInitial,
            'teacher_info' => $teacherInfo ? [
                'name' => $teacherInfo->name,
                'designation' => $teacherInfo->designation,
                'cell_phone' => $teacherInfo->cell_phone,
                'email' => $teacherInfo->email,
                'image_url' => $teacherInfo->image_url,
            ] : null,
            'data' => $orderedClasses
        ]);
    }


    public function getTeacherInfo(Request $request): JsonResponse
    {
        $request->validate([
            'teacher' => 'required|string|max:10'
        ]);

        $teacherInitial = strtoupper($request->input('teacher'));

        $teacherInfo = Teacher::where('teacher', $teacherInitial)->first();

        if (!$teacherInfo) {
            return response()->json([
                'status' => 'not_found',
                'message' => "No teacher found with initial: $teacherInitial",
                'teacher_info' => null
            ]);
        }

        return response()->json([
            'status' => 'success',
            'teacher' => $teacherInitial,
            'teacher_info' => [
                'name' => $teacherInfo->name,
                'designation' => $teacherInfo->designation,
                'cell_phone' => $teacherInfo->cell_phone,
                'email' => $teacherInfo->email,
                'image_url' => $teacherInfo->image_url,
            ]
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

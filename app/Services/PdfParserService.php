<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;

class PdfParserService
{
    protected Parser $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    /**
     * @throws Exception
     */
    public function parseRoutine($pdfPath)
    {
        $pdf = $this->parser->parseFile($pdfPath);
        $text = $pdf->getText();

        // Better line processing - preserve structure but clean up
        $lines = $this->preprocessLines($text);
        $department = 'CSE';

        if (empty($lines)) {
            throw new Exception('No valid lines extracted from PDF: ' . $pdfPath);
        }

        // Step 1: Detect the initial day
        $firstLine = array_shift($lines);
        $currentDay = $this->detectDay($firstLine);
        $validDays = ['SATURDAY', 'SUNDAY', 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY'];
        if (!$currentDay || !in_array($currentDay, $validDays)) {
            $currentDay = 'SATURDAY';
        }

        // Step 2: Extract time slots
        $timeSlotLine = !empty($lines) ? array_shift($lines) : '';
        $timeSlots = $this->extractTimeSlots($timeSlotLine);

        // Step 3: Process table structure with improved logic
        $tableData = $this->processTableStructureImproved($lines, $validDays, $timeSlots);

        // Step 4: Parse schedule from table data
        return $this->parseScheduleFromTable($tableData, $timeSlots, $department, $currentDay);
    }

    protected function preprocessLines($text): array
    {
        // Split by lines and clean up
        $lines = explode("\n", $text);
        $processedLines = [];

        foreach ($lines as $line) {
            $cleanLine = trim($line);
            if (!empty($cleanLine)) {
                $processedLines[] = $cleanLine;
            }
        }

        return $processedLines;
    }

    protected function extractTimeSlots($timeSlotLine): array
    {
        $timeSlots = [];
        if (preg_match_all('/(\d{2}:\d{2})-(\d{2}:\d{2})/', $timeSlotLine, $timeMatches, PREG_SET_ORDER)) {
            foreach ($timeMatches as $match) {
                $timeSlots[] = [
                    'start' => date('H:i', strtotime($match[1])),
                    'end' => date('H:i', strtotime($match[2])),
                    'full_time' => $match[0] // Store the full time string for position matching
                ];
            }
        } else {
            // Fallback time slots based on your PDF structure
            $defaultSlots = [
                ['start' => '08:30', 'end' => '10:00', 'full_time' => '08:30-10:00'],
                ['start' => '10:00', 'end' => '11:30', 'full_time' => '10:00-11:30'],
                ['start' => '11:30', 'end' => '13:00', 'full_time' => '11:30-13:00'],
                ['start' => '13:00', 'end' => '14:30', 'full_time' => '13:00-14:30'],
                ['start' => '14:30', 'end' => '16:00', 'full_time' => '14:30-16:00'],
                ['start' => '16:00', 'end' => '17:30', 'full_time' => '16:00-17:30']
            ];
            $timeSlots = $defaultSlots;
        }
        return $timeSlots;
    }

    protected function processTableStructureImproved($lines, $validDays, $timeSlots): array
    {
        $tableData = [];
        $currentDay = 'SATURDAY';
        $mergedLines = $this->mergeMultilineRooms($lines);

        foreach ($mergedLines as $line) {
            $lineUpper = strtoupper(trim($line));

            // Check if this line is a day header
            if (in_array($lineUpper, $validDays)) {
                $currentDay = $lineUpper;
                continue;
            }

            // Skip empty lines and header lines
            if (empty($line) || $this->isHeaderLine($line)) {
                continue;
            }

            // Parse table row with improved logic that considers column positions
            $rowData = $this->parseTableRowWithPositions($line, $timeSlots);
            if (!empty($rowData)) {
                $tableData[$currentDay][] = $rowData;
            }
        }

        return $tableData;
    }

    protected function parseTableRowWithPositions($line, $timeSlots): array
    {
        $rowData = [];
        $cleanLine = trim($line);

        // Strategy: Try to identify the column structure by finding patterns at specific positions
        // Split the line into segments and try to match them with time slot positions

        // First, find all course patterns with their positions in the line
        $courseMatches = [];
        $pattern = '/(?:^|\s+)((?:KT-\d+|G1-\d+|SH-\d+|CTBA-\d+|EMBED LAB-[^,\s]+|IOT LAB-[^,\s]+)(?:\([AB]\))?)\s*(?:\([^)]*LAB[^)]*\))?\s+([A-Z]{3}\d{3})\(([^)]+)\)\s+([A-Z]{2,6}(?:-\d+)?)/i';

        if (preg_match_all($pattern, $cleanLine, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($matches as $match) {
                $position = $match[0][1]; // Character position in the line
                $room = $this->cleanRoomName($match[1][0]);
                $course = $match[2][0];
                $section = $match[3][0];
                $teacher = $match[4][0];

                // Determine time slot based on position in the line
                $timeSlotIndex = $this->determineTimeSlotFromPosition($position, $cleanLine, count($timeSlots));

                $courseMatches[] = [
                    'position' => $position,
                    'room' => $room,
                    'course' => $course,
                    'section' => $section,
                    'teacher' => $teacher,
                    'time_slot_index' => $timeSlotIndex
                ];
            }

            // Sort by position to maintain order
            usort($courseMatches, function ($a, $b) {
                return $a['position'] <=> $b['position'];
            });

            $rowData = $courseMatches;
        }

        // If no matches found with the enhanced pattern, try the fallback approach
        if (empty($rowData)) {
            $rowData = $this->parseTableRowFallback($cleanLine, $timeSlots);
        }

        return $rowData;
    }

    protected function determineTimeSlotFromPosition($position, $line, $totalTimeSlots): int
    {
        $lineLength = strlen($line);

        // Divide the line into segments based on the number of time slots
        $segmentSize = $lineLength / $totalTimeSlots;

        // Determine which segment this position falls into
        $timeSlotIndex = min(intval($position / $segmentSize), $totalTimeSlots - 1);

        return $timeSlotIndex;
    }

    protected function mergeMultilineRooms($lines): array
    {
        $mergedLines = [];
        $i = 0;

        while ($i < count($lines)) {
            $currentLine = $lines[$i];

            // Check if current line has a room pattern but might be incomplete
            if ($this->isPartialRoomLine($currentLine)) {
                // Look ahead to see if next line completes the room info
                $mergedLine = $currentLine;
                $j = $i + 1;

                // Merge consecutive lines that seem to be part of the same room/course structure
                while ($j < count($lines) && $this->shouldMergeWithPrevious($lines[$j], $mergedLine)) {
                    $mergedLine .= ' ' . trim($lines[$j]);
                    $j++;
                }

                $mergedLines[] = $mergedLine;
                $i = $j;
            } else {
                $mergedLines[] = $currentLine;
                $i++;
            }
        }

        return $mergedLines;
    }

    protected function isPartialRoomLine($line): bool
    {
        // Check if line contains room pattern or lab description
        return preg_match('/^(KT-\d+|G1-\d+|SH-\d+|CTBA-\d+|EMBED|IOT).*$|^\(.*LAB.*\)$/i', trim($line));
    }

    protected function shouldMergeWithPrevious($currentLine, $previousMergedLine): bool
    {
        $currentTrimmed = trim($currentLine);

        // Merge if current line is a lab description in parentheses
        if (preg_match('/^\(.*LAB.*\)$/i', $currentTrimmed)) {
            return true;
        }

        // Merge if current line seems to continue course information
        if (preg_match('/^[A-Z]{3}\d{3}\(.*?\)\s+[A-Z]{2,6}$/i', $currentTrimmed)) {
            return true;
        }

        // Don't merge if current line starts with a new room
        if (preg_match('/^(KT-\d+|G1-\d+|SH-\d+|CTBA-\d+|EMBED|IOT)/i', $currentTrimmed)) {
            return false;
        }

        // Merge if line seems to be a continuation (starts with course or teacher)
        if (preg_match('/^([A-Z]{3}\d{3}|[A-Z]{2,6}(-\d+)?)/i', $currentTrimmed)) {
            return true;
        }

        return false;
    }

    protected function parseTableRowFallback($line, $timeSlots): array
    {
        $rowData = [];
        $segments = preg_split('/\s+/', trim($line), -1, PREG_SPLIT_NO_EMPTY);

        $i = 0;
        $timeSlotIndex = 0;

        while ($i < count($segments)) {
            $segment = $segments[$i];

            // Enhanced room pattern matching
            if (preg_match('/^(KT-\d+|G1-\d+|SH-\d+|CTBA-\d+|EMBED|IOT)/i', $segment)) {
                $room = $segment;

                // Check if next segment is part of room (like (A) or (B))
                if ($i + 1 < count($segments) && preg_match('/^\([AB]\)$/', $segments[$i + 1])) {
                    $room .= $segments[$i + 1];
                    $i++;
                }

                $teacher = '';

                // Look for course in next segments
                if ($i + 1 < count($segments)) {
                    $nextSegment = $segments[$i + 1];
                    if (preg_match('/^([A-Z]{3}\d{3})\((.*?)\)/', $nextSegment, $courseMatch)) {
                        $course = $courseMatch[1];
                        $section = $courseMatch[2];

                        // Look for teacher in the segment after course
                        if ($i + 2 < count($segments)) {
                            $teacherSegment = $segments[$i + 2];
                            if (preg_match('/^[A-Z]{2,6}(-\d+)?$/', $teacherSegment)) {
                                $teacher = $teacherSegment;
                                $i += 3;
                            } else {
                                $i += 2;
                            }
                        } else {
                            $i += 2;
                        }

                        $rowData[] = [
                            'room' => $this->cleanRoomName($room),
                            'course' => $course,
                            'section' => $section,
                            'teacher' => $teacher,
                            'time_slot_index' => min($timeSlotIndex, count($timeSlots) - 1)
                        ];

                        $timeSlotIndex++;
                    } else {
                        $i++;
                    }
                } else {
                    $i++;
                }
            } else {
                $i++;
            }
        }

        return $rowData;
    }

    protected function parseScheduleFromTable($tableData, $timeSlots, $department, $initialDay): array
    {
        $schedule = [];

        foreach ($tableData as $day => $dayRows) {
            foreach ($dayRows as $row) {
                foreach ($row as $classData) {
                    if (empty($classData['course']) || empty($classData['section'])) {
                        continue;
                    }

                    $timeSlotIndex = $classData['time_slot_index'] % count($timeSlots);
                    $timeSlot = $timeSlots[$timeSlotIndex];

                    $schedule[] = [
                        'department' => $department,
                        'section' => $classData['section'],
                        'start_time' => $timeSlot['start'],
                        'end_time' => $timeSlot['end'],
                        'course_code' => $classData['course'],
                        'room' => $classData['room'],
                        'teacher_initials' => $this->cleanTeacherInitials($classData['teacher']),
                        'day' => $day,
                    ];
                }
            }
        }

        return $schedule;
    }

    protected function cleanRoomName($room): string
    {
        // Remove any lab descriptions in parentheses but keep room identifiers like (A) or (B)
        $room = trim($room);

        // First, extract the basic room pattern with optional (A) or (B)
        if (preg_match('/^([A-Z0-9-]+(?:\([AB]\))?)/i', $room, $matches)) {
            return strtoupper($matches[1]);
        }

        // Handle special cases like "EMBED LAB- KT-301" or "IOT LAB- KT-502"
        if (preg_match('/^(EMBED LAB-\s*KT-\d+|IOT LAB-\s*KT-\d+)/i', $room, $matches)) {
            return strtoupper(str_replace(' ', '', $matches[1]));
        }

        // Fallback: if no match, try to get just the basic room identifier
        if (preg_match('/^([A-Z0-9-]+)/i', $room, $matches)) {
            return strtoupper($matches[1]);
        }

        return strtoupper($room);
    }

    protected function cleanTeacherInitials($teacher): string
    {
        // Remove any numbers or special characters, keep only letters
        $teacher = preg_replace('/[^A-Za-z]/', '', $teacher);
        return strtoupper(trim($teacher));
    }

    protected function isHeaderLine($line): bool
    {
        $line = strtoupper(trim($line));
        $headerPatterns = [
            '/^ROOM\s+COURSE\s+TEACHER/',
            '/^(\d{2}:\d{2})-(\d{2}:\d{2})/',
            '/^CLASSES\s+STARTING/',
            '/^\s*$/'
        ];

        foreach ($headerPatterns as $pattern) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }

        return false;
    }

    protected function detectDay($line): ?string
    {
        $validDays = ['SATURDAY', 'SUNDAY', 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY'];
        $lineUpper = strtoupper(trim($line));
        return in_array($lineUpper, $validDays) ? $lineUpper : null;
    }
}

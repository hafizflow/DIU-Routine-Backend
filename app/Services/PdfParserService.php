<?php

namespace App\Services;

use Exception;
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
        $lines = array_filter(array_map('trim', explode("\n", $text)), 'strlen');
        $schedule = [];
        $timeSlots = [];
        $department = 'CSE'; // Adjust based on PDF or input

        // Step 1: Detect the initial day and time slots with fallback
        if (!empty($lines)) {
            $firstLine = array_shift($lines);
            $currentDay = $this->detectDay($firstLine);
            $validDays = ['SATURDAY', 'SUNDAY', 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY'];
            if (!$currentDay || !in_array($currentDay, $validDays)) {
                $currentDay = 'SATURDAY';
            }

            // Attempt to detect time slots from the next line
            if (!empty($lines)) {
                $timeSlotLine = array_shift($lines);
                if (preg_match_all('/(\d{2}:\d{2})-(\d{2}:\d{2})/', $timeSlotLine, $timeMatches, PREG_SET_ORDER)) {
                    foreach ($timeMatches as $match) {
                        $startTime = date('H:i', strtotime($match[1]));
                        $endTime = date('H:i', strtotime($match[2]));
                        $timeSlots[] = ['start' => $startTime, 'end' => $endTime];
                    }
                } else {
                    $timeSlots[] = ['start' => '08:00', 'end' => '09:30'];
                }
            }

            if (empty($timeSlots)) {
                $timeSlots[] = ['start' => '08:00', 'end' => '09:30'];
            }

            // Step 2: Process all lines, inferring structure
            foreach ($lines as $line) {
                $lineUpper = strtoupper(trim($line));
                if (in_array($lineUpper, $validDays) && $lineUpper !== $currentDay) {
                    $currentDay = $lineUpper;
                    continue;
                }

                $lineData = preg_replace('/\s+/', ' ', trim($line));
                $parts = preg_split('/\s+/', $lineData, -1, PREG_SPLIT_NO_EMPTY);
                if (count($parts) >= 3) {
                    $numSlots = count($timeSlots);
                    for ($slotIndex = 0; $slotIndex < $numSlots && $slotIndex * 3 < count($parts); $slotIndex++) {
                        $roomIndex = $slotIndex * 3;
                        $courseIndex = $roomIndex + 1;
                        $teacherIndex = $roomIndex + 2;

                        if (isset($parts[$roomIndex], $parts[$courseIndex], $parts[$teacherIndex])) {
                            $room = $parts[$roomIndex];
                            if (preg_match('/^([A-Z]+-\d+)(.*)/i', $room, $roomMatch)) {
                                $room = $roomMatch[1];
                                if (!empty($roomMatch[2])) {
                                    $room .= ' (' . $roomMatch[2] . ')';
                                }
                            }

                            $course = $parts[$courseIndex];
                            $teacher = $parts[$teacherIndex];


                            if (preg_match('/^([A-Z]{3}\d{3})\(/i', $course, $courseMatch)) {
                                $sectionStart = strpos($course, '(') + 1;
                                $sectionEnd = strrpos($course, ')');

                                if ($sectionEnd === false || $sectionEnd < $sectionStart) {
                                    continue; // Skip this entry if section cannot be extracted
                                }

                                $section = substr($course, $sectionStart, $sectionEnd - $sectionStart);

                                if (empty($section)) {
                                    continue; // Skip if section is empty
                                }

                                $schedule[] = [
                                    'department' => $department,
                                    'section' => $section,
                                    'start_time' => $timeSlots[$slotIndex]['start'],
                                    'end_time' => $timeSlots[$slotIndex]['end'],
                                    'course_code' => $courseMatch[1],
                                    'room' => $room,
                                    'teacher_initials' => $teacher,
                                    'day' => $currentDay,
                                ];
                            } else {
//                                Log::warning('Invalid course format', ['course' => $course]);
                            }
                        }
                    }
                }
            }
        }
        return $schedule;
    }

    protected function detectDay($line): ?string
    {
        $validDays = ['SATURDAY', 'SUNDAY', 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY'];
        $lineUpper = strtoupper(trim($line));
        return in_array($lineUpper, $validDays) ? $lineUpper : null;
    }
}

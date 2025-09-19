<?php

namespace App\Actions;

use JetBrains\PhpStorm\NoReturn;

class ParsePdfTableAction
{
    public function execute($path): array
    {
        $jarFilePath = base_path('tabula/tabula.jar');
        $csvData = shell_exec("java -jar {$jarFilePath} -p all -f CSV $path");

        // parse CSV data into an array
        $lines = explode("\n", trim($csvData));

//        var_dump($lines);

        $table = [];

        foreach ($lines as $line) {
            $row = str_getcsv($line);
            if (count($row) > 1) {
                $table[] = $row;
            }
        }

        $currentDay = null;
        $data = [];
        $timeSlot = [
            [
                'start_time' => '08:30',
                'end_time' => '10:00'
            ],
            [
                'start_time' => '10:00',
                'end_time' => '11:30'
            ],
            [
                'start_time' => '11:30',
                'end_time' => '01:00'
            ],
            [
                'start_time' => '01:00',
                'end_time' => '02:30'
            ],
            [
                'start_time' => '02:30',
                'end_time' => '04:00'
            ],
            [
                'start_time' => '04:00',
                'end_time' => '05:30'
            ]
        ];

        foreach ($table as $row) {
            if ($this->isDayRow($row)) {
                $currentDay = $this->isDayRow($row);
                continue;
            }

            if ($this->isTimeColumn($row) || $this->isHeadingColumn($row) || $this->isUnwantedColumn($row) || $this->isEmptyColumn($row)) {
                continue;
            }

            collect($row)
                ->chunk(3)
                ->map(function ($item) {
                    return $item->values();
                })
                ->map(function ($item, $index) use ($currentDay, $timeSlot) {
                    return [
                        'room' => $item[0] ?? null,
                        'course_code' => ($value = strstr($item[1], '(', true)) !== false ? $value : null,
                        'section' => ($value = strstr($item[1], '(')) !== false ? trim($value, '()') : null,
                        'teacher' => ($value = $item[2]) !== "" ? $value : null,
                        'day' => $currentDay,
                        'start_time' => $timeSlot[$index]['start_time'] ?? null,
                        'end_time' => $timeSlot[$index]['end_time'] ?? null,
                    ];
                })->each(function ($item) use (&$data) {
                    $data[] = $item;
                });
        }

        return $data;
    }

    private function isDayRow(array $row): ?string
    {
        $validDays = ['SATURDAY', 'SUNDAY', 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY'];

        foreach ($row as $cell) {
            $cell = strtoupper(trim($cell));
            if (in_array($cell, $validDays)) {
                return $cell;
            }
        }
        return null;
    }

    private function isTimeColumn($row): bool
    {
        return $row[0] === "08:30-10:00";
    }

    private function isHeadingColumn($row): bool
    {
        return $row[0] === "Room";
    }

    private function isUnwantedColumn($row): bool
    {
        return strlen($row[0] ?? '') >= 9 && count(array_filter(array_slice($row, 1))) === 0;
    }

    private function isEmptyColumn($row): bool
    {
        return count(array_filter($row)) === 0;
    }
}

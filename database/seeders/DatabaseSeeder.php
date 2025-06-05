<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Routine;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Routine::create([
            'department' => 'CSE',
            'section' => 'A',
            'start_time' => '08:00:00',
            'end_time' => '09:30:00',
            'course_code' => 'CSE101',
            'room' => 'R101',
            'teacher_initials' => 'JSM',
            'day' => 'Sunday',
        ]);
    }
}

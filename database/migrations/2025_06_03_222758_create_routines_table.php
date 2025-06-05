<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routines', function (Blueprint $table) {
            $table->id();
            $table->string('department');
            $table->string('section');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('course_code');
            $table->string('room');
            $table->string('teacher_initials');
            $table->string('day');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routines');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faculty_availabilities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('instructor_id')->constrained('instructors')->onDelete('cascade');
            $table->foreignId('school_year_id')->constrained('school_years')->onDelete('cascade');
            $table->foreignId('semester_id')->constrained('semesters')->onDelete('cascade');

            $table->enum('day', [
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
                'Saturday',
                'Sunday'
            ]);

            $table->time('start_time');
            $table->time('end_time');

            $table->enum('status', ['Available', 'Unavailable'])->default('Available');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(
                ['instructor_id', 'school_year_id', 'semester_id', 'day', 'start_time', 'end_time'],
                'faculty_availability_unique_slot'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faculty_availabilities');
    }
};
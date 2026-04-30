<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_generation_conflicts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('generation_run_id')
                ->constrained('schedule_generation_runs')
                ->onDelete('cascade');

            $table->foreignId('schedule_id')->nullable()->constrained('schedules')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('instructor_id')->nullable()->constrained('instructors')->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();

            $table->string('conflict_type', 50);   // NO_ROOM, NO_INSTRUCTOR, SCHEDULED_SUCCESS, etc.
            $table->string('severity', 20)->default('info'); // info, warning, error
            $table->boolean('is_conflict')->default(false);
            $table->text('message');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_generation_conflicts');
    }
};
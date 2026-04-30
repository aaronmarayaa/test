<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_generation_runs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
            $table->foreignId('school_year_id')->constrained('school_years')->onDelete('cascade');
            $table->foreignId('semester_id')->constrained('semesters')->onDelete('cascade');

            $table->string('status', 20)->default('success'); // success, partial, failed
            $table->unsignedInteger('total_created')->default(0);
            $table->unsignedInteger('total_failed')->default(0);
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_generation_runs');
    }
};
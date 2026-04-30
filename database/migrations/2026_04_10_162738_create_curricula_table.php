<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curricula', function (Blueprint $table) {
            $table->id();

            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->foreignId('semester_id')->constrained('semesters')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');

            $table->unsignedTinyInteger('year_level');
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('active')->default(true);

            $table->timestamps();

            $table->unique(['course_id', 'semester_id', 'subject_id', 'year_level'], 'curriculum_unique_subject_per_term');
            $table->unique(['course_id', 'semester_id', 'year_level', 'sort_order'], 'curriculum_unique_sort_per_term');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curricula');
    }
};
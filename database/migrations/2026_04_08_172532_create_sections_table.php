<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->unsignedTinyInteger('year_level');
            $table->string('section_name', 20); // example: A
            $table->string('section_code', 50)->unique(); // example: BSCRIM-1A
            $table->unsignedInteger('capacity');
            $table->boolean('archived')->default(false);
            $table->timestamps();

            $table->unique(['course_id', 'year_level', 'section_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
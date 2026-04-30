<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faculty_subjects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('instructor_id')->constrained('instructors')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');

            $table->integer('priority_score')->default(10);
            $table->boolean('is_primary')->default(false);

            $table->timestamps();

            $table->unique(['instructor_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faculty_subjects');
    }
};
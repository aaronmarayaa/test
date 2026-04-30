<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('subject_code', 50)->unique();
            $table->string('subject_name', 255);
            $table->decimal('units', 4, 1)->default(3.0);
            $table->decimal('total_hours_per_week', 5, 2)->default(3.00);
            $table->decimal('lecture_hours', 5, 2)->default(0.00);
            $table->decimal('laboratory_hours', 5, 2)->default(0.00);
            $table->boolean('allow_split_sessions')->default(false);
            $table->unsignedInteger('break_minutes_per_week')->default(0);
            $table->unsignedInteger('preferred_session_count')->default(1);
            $table->decimal('max_hours_per_day', 5, 2)->default(3.00);
            $table->string('room_type_required', 50)->default('lecture');
            $table->string('subject_category', 50)->default('lecture');
            $table->boolean('archived')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
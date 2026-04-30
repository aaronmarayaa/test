<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructors', function (Blueprint $table) {
            $table->id();
            $table->string('employee_no', 50)->unique();
            $table->string('instructor_name', 150);
            $table->enum('employment_type', ['full_time', 'part_time']);
            $table->decimal('max_hours_per_week', 5, 2)->default(24.00);
            $table->decimal('min_hours_per_week', 5, 2)->nullable();
            $table->decimal('preferred_load_hours', 5, 2)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('archived')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructors');
    }
};
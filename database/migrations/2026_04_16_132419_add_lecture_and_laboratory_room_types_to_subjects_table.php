<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->string('lecture_room_type_required')->nullable()->after('room_type_required');
            $table->string('laboratory_room_type_required')->nullable()->after('lecture_room_type_required');
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn([
                'lecture_room_type_required',
                'laboratory_room_type_required',
            ]);
        });
    }
};
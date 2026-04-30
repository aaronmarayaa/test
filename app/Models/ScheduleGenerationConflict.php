<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleGenerationConflict extends Model
{
    protected $fillable = [
        'generation_run_id',
        'schedule_id',
        'subject_id',
        'instructor_id',
        'room_id',
        'conflict_type',
        'severity',
        'is_conflict',
        'message',
    ];

    protected $casts = [
        'is_conflict' => 'boolean',
    ];

    public function generationRun()
    {
        return $this->belongsTo(ScheduleGenerationRun::class, 'generation_run_id');
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
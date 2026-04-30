<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
   protected $fillable = [
        'generation_run_id',
        'school_year_id',
        'semester_id',
        'section_id',
        'course_id',
        'subject_id',
        'instructor_id',
        'room_id',
        'year_level',
        'day',
        'start_time',
        'end_time',
        'hours',
        'session_type',
        'status',
    ];

    protected $casts = [
        'hours' => 'decimal:2',
    ];

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
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

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function generationRun()
    {
        return $this->belongsTo(\App\Models\ScheduleGenerationRun::class, 'generation_run_id');
    }
}
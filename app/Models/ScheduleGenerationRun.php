<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleGenerationRun extends Model
{
    protected $fillable = [
        'section_id',
        'school_year_id',
        'semester_id',
        'status',
        'total_created',
        'total_failed',
        'notes',
    ];

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function conflicts()
    {
        return $this->hasMany(ScheduleGenerationConflict::class, 'generation_run_id');
    }

    public function schedules()
    {
        return $this->hasMany(\App\Models\Schedule::class, 'generation_run_id');
    }
}
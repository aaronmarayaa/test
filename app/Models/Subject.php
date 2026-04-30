<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = [
        'subject_code',
        'subject_name',
        'units',
        'total_hours_per_week',
        'lecture_hours',
        'laboratory_hours',
        'allow_split_sessions',
        'room_type_required',
        'lecture_room_type_required',
        'laboratory_room_type_required',
        'subject_category',
        'archived',
    ];

    protected $casts = [
        'units' => 'decimal:1',
        'total_hours_per_week' => 'decimal:2',
        'lecture_hours' => 'decimal:2',
        'laboratory_hours' => 'decimal:2',
        'allow_split_sessions' => 'boolean',
        'archived' => 'boolean',
    ];

    public function facultySubjects()
    {
        return $this->hasMany(FacultySubject::class);
    }
    public function curricula()
    {
        return $this->hasMany(Curriculum::class);
    }
    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}
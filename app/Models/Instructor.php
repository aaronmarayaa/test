<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Instructor extends Model
{
    protected $fillable = [
        'employee_no',
        'instructor_name',
        'employment_type',
        'specialization',
        'status',
        'archived',
];

    protected $casts = [
        'max_hours_per_week' => 'decimal:2',
        'min_hours_per_week' => 'decimal:2',
        'preferred_load_hours' => 'decimal:2',
        'archived' => 'boolean',
    ];

    public function facultySubjects()
    {
        return $this->hasMany(FacultySubject::class);
    }
    public function facultyAvailabilities()
    {
        return $this->hasMany(FacultyAvailability::class);
    }
    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}
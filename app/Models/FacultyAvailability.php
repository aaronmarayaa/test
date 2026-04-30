<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacultyAvailability extends Model
{
    protected $fillable = [
        'instructor_id',
        'school_year_id',
        'semester_id',
        'day',
        'start_time',
        'end_time',
        'status',
        'notes',
    ];

    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }
}
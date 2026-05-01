<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacultySubject extends Model
{
    protected $fillable = [
        'instructor_id',
        'subject_id',
        'course_id',
        'session_type',
        'priority_score',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }
}
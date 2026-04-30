<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable = [
        'course_id',
        'year_level',
        'section_name',
        'section_code',
        'capacity',
        'archived',
    ];

    protected $casts = [
        'archived' => 'boolean',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}
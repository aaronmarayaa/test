<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = [
        'course_code',
        'course_name',
        'department_name',
        'archived',
    ];

    protected $casts = [
        'archived' => 'boolean',
    ];

    public function curricula()
    {
        return $this->hasMany(Curriculum::class);
    }
}
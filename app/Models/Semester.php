<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    protected $fillable = [
        'semester_name',
        'semester_order',
        'status',
    ];

    public function curricula()
    {
        return $this->hasMany(Curriculum::class);
    }
    public function facultyAvailabilities()
    {
        return $this->hasMany(FacultyAvailability::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolYear extends Model
{
    protected $fillable = [
        'school_year',
        'status',
    ];

    public function facultyAvailabilities()
    {
        return $this->hasMany(FacultyAvailability::class);
    }
}
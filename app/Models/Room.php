<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'room_code',
        'room_name',
        'room_type',
        'capacity',
        'status',
    ];
    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}
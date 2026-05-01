<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'room_code',
        'room_name',
        'room_type',
        'room_category',
        'room_floor_group',
        'room_priority',
        'capacity',
        'status',
    ];

    protected $casts = [
        'room_floor_group' => 'integer',
        'room_priority' => 'integer',
        'capacity' => 'integer',
    ];
    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}
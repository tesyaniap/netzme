<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable = [
        'name',
        'plate_number',
        'seat_capacity',
        'seat_layout',
        'partner_id',
        'status'
    ];

    public function partner()
    {
        return $this->belongsTo(Mitra::class, 'partner_id');
    }

    public function seats()
    {
        return $this->hasMany(Seat::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function tickets()
    {
        return $this->hasManyThrough(Ticket::class, Schedule::class);
    }
}

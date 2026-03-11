<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    protected $fillable = [
        'origin_city_id',
        'destination_city_id',
        'departure_terminal_id',
        'arrival_terminal_id',
        'distance'
    ];

    public function originCity()
    {
        return $this->belongsTo(City::class, 'origin_city_id');
    }

    public function destinationCity()
    {
        return $this->belongsTo(City::class, 'destination_city_id');
    }

    public function departureTerminal()
    {
        return $this->belongsTo(Terminal::class, 'departure_terminal_id');
    }

    public function arrivalTerminal()
    {
        return $this->belongsTo(Terminal::class, 'arrival_terminal_id');
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

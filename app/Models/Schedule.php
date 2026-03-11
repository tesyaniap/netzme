<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'vehicle_id',
        'route_id',
        'departure_time',
        'arrival_time',
        'price',
        'travel_date'
    ];

    protected $casts = [
        'travel_date' => 'date'
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
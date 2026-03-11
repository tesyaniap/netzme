<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Terminal extends Model
{
    protected $fillable = ['city_id', 'name', 'address'];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function departureRoutes()
    {
        return $this->hasMany(Route::class, 'departure_terminal_id');
    }

    public function arrivalRoutes()
    {
        return $this->hasMany(Route::class, 'arrival_terminal_id');
    }
}

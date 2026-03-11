<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = ['name', 'province'];

    public function terminals()
    {
        return $this->hasMany(Terminal::class);
    }

    public function originRoutes()
    {
        return $this->hasMany(Route::class, 'origin_city_id');
    }

    public function destinationRoutes()
    {
        return $this->hasMany(Route::class, 'destination_city_id');
    }
}

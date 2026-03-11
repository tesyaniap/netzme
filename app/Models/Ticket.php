<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'transaction_id',
        'passenger_id',
        'schedule_id',
        'seat_id',
        'price',
        'status'
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function passenger()
    {
        return $this->belongsTo(User::class, 'passenger_id');
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function seat()
    {
        return $this->belongsTo(Seat::class);
    }

    public function reschedules()
    {
        return $this->hasMany(TicketReschedule::class);
    }
}
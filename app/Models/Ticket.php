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

    // Status constants
    const STATUS_BOOKED = 'booked';
    const STATUS_PAID = 'paid';
    const STATUS_ISSUED = 'issued';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_RESCHEDULED = 'rescheduled';

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

    public function latestReschedule()
    {
        return $this->hasOne(TicketReschedule::class)->latest('rescheduled_at');
    }

    public function canBeRescheduled(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isRescheduled(): bool
    {
        return $this->status === self::STATUS_RESCHEDULED;
    }
}
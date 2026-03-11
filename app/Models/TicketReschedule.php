<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketReschedule extends Model
{
    protected $fillable = [
        'ticket_id',
        'old_schedule_id',
        'new_schedule_id',
        'old_seat_id',
        'new_seat_id',
        'reschedule_fee',
        'rescheduled_at'
    ];

    protected $casts = [
        'rescheduled_at' => 'datetime'
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function oldSchedule()
    {
        return $this->belongsTo(Schedule::class, 'old_schedule_id');
    }

    public function newSchedule()
    {
        return $this->belongsTo(Schedule::class, 'new_schedule_id');
    }

    public function oldSeat()
    {
        return $this->belongsTo(Seat::class, 'old_seat_id');
    }

    public function newSeat()
    {
        return $this->belongsTo(Seat::class, 'new_seat_id');
    }
}
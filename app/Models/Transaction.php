<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'trx_code',
        'mitra_id',
        'user_id',
        'provider_code',
        'route',
        'travel_date',
        'payment_type',
        'amount',
        'status',
        'provider_response',
        'schedule_id',
        'passenger_count',
        'base_price',
        'admin_fee',
        'service_fee',
        'customer_name',
        'customer_phone',
        'customer_email',
        'notes',
        'booked_at',
        'paid_at',
        'issued_at',
        'cancelled_at'
    ];

    protected $casts = [
        'travel_date' => 'date',
        'amount' => 'decimal:2',
        'base_price' => 'decimal:2',
        'admin_fee' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'provider_response' => 'json',
        'booked_at' => 'datetime',
        'paid_at' => 'datetime',
        'issued_at' => 'datetime',
        'cancelled_at' => 'datetime'
    ];

    public function mitra()
    {
        return $this->belongsTo(Mitra::class, 'mitra_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function passengers()
    {
        return $this->hasMany(TransactionPassenger::class);
    }

    public function fee()
    {
        return $this->hasOne(TransactionFee::class);
    }

    public function feeLedgers()
    {
        return $this->hasMany(PartnerFeeLedger::class);
    }
}

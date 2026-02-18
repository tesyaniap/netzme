<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    const UPDATED_AT = null;

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
        'created_at'
    ];

    protected $casts = [
        'travel_date' => 'date',
        'amount' => 'decimal:2',
        'provider_response' => 'json'
    ];

    public function mitra()
    {
        return $this->belongsTo(Mitra::class, 'mitra_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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

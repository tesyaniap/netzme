<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerFeeLedger extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'mitra_id',
        'transaction_id',
        'amount',
        'type',
        'description',
        'balance_before',
        'balance_after',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2'
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->created_at = now();
        });
    }

    public function mitra()
    {
        return $this->belongsTo(Mitra::class, 'mitra_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}

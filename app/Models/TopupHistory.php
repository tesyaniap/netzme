<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TopupHistory extends Model
{
    protected $fillable = [
        'topup_id',
        'mitra_id',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'created_at',
    ];

    public $timestamps = false;

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->created_at = now();
        });
    }

    public function topup()
    {
        return $this->belongsTo(Topup::class);
    }

    public function mitra()
    {
        return $this->belongsTo(Mitra::class);
    }
}

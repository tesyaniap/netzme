<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Topup extends Model
{
    protected $fillable = [
        'mitra_id',
        'amount',
        'payment_method',
        'status',
        'proof_file',
        'approved_by',
        'approved_at',
        'reject_reason'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'approved_at' => 'datetime'
    ];

    public function mitra()
    {
        return $this->belongsTo(Mitra::class, 'mitra_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

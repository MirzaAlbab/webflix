<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'transaction_number',
        'total_amount',
        'payment_status',
        'midtras_snap_token',
        'midtrans_transaction_id',
        'midtrans_booking_code',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}

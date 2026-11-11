<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    public function subtotalAmount(): int
    {
        return \App\Support\Amount::rupiah($this->price);
    }

    public function taxAmount(): int
    {
        return intdiv($this->subtotalAmount() * 12 + 50, 100);
    }

    public function totalAmount(): int
    {
        return $this->subtotalAmount() + $this->taxAmount();
    }

    protected $fillable = [
        'title',
        'price',
        'duration', // Duration in days
        'resolution',
        'max_devices',
    ];

    public function memberships()
    {
        return $this->hasMany(Membership::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'memberships', 'plan_id', 'user_id');
    }
}

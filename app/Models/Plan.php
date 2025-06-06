<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    

    protected $fillable = [
        'title',
        'price',
        'duration', // Duration in months
        'resolution',
        'max_devices',
    ];

    public function memberships()
    {
        return $this->hasMany(Membership::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'memberships', 'plan_id', 'user_id')
                    
    }
    
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    //

    protected $fillable = [
        'user_id',
        'movie_id',
        'rating',
    ];

    protected $casts = [
        'rating' => 'float',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }
}

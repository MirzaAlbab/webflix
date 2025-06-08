<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'director',
        'writers',
        'stars',
        'poster',
        'release_date',
        'duration',
        'url_720',
        'url_1080',
        'url_4k',
    ];

    protected $appends = ['average_rating'];

    protected $casts = [
        'release_date' => 'date',
    ];

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }
    public function getAverageRatingAttribute(): float
    {
        return $this->ratings()->avg('rating');
    }

    public function getStreamingUrl(string $planResolution)
    {
        return match ($planResolution) {
            '720p' => $this->url_720,
            '1080p' => $this->url_1080,
            '4k' => $this->url_4k,
            default => $this->url_720,
        };
    }

    public function getFormattedDurationAttribute(): string
    {
        $hours = floor($this->duration / 60);
        $minutes = $this->duration % 60;
        return sprintf('%02d:%02d', $hours, $minutes);
    }

}

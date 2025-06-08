<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MovieController extends Controller
{
    public function middleware()
    {
        return [
            'auth', 'check.device.limit'
        ];
    }

    public function index()
    {
        // Logic to fetch and display movies
        $latestMovies= Movie::latest()->limit(10)->get();
        $popularMovies = Movie::with('ratings')->get()->sortByDesc('average_rating')->take(10);
        return view('movies.index', compact('latestMovies', 'popularMovies'));
    }

    public function show(Movie $movie)
    {
        // Logic to fetch and display a single movie
        $userPlan = Auth::user()->getCurrentPlan();
        $streamingUrl = $movie->getStreamingUrl($userPlan->resolution);
        
        return view('movies.show', compact('movie', 'streamingUrl'));
    }
}

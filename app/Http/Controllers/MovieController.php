<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Auth;

class MovieController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            'auth', 'check.device.limit'
        ];
    }

    public function all(Request $request){
        // Logic to fetch and display all movies
        $movies = Movie::latest()->paginate(8);
        if($request->ajax()){
            $html = view('components.movie-list', compact('movies'))->render();
            return response()->json(['html' => $html, 'next_page' => $movies->nextPageUrl()]);
        }
        return view('movies.all', compact('movies'));

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

    public function search(Request $request)
    {
        // Logic to search for movies
        $keyword = $request->input('q');
        $movies = Movie::where('title', 'like', '%' . $keyword . '%')->get();
        
        return view('movies.search', compact('movies', 'keyword'));
    }
}

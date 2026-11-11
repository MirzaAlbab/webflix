<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireMembership
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->hasMembershipPlan()) {
            return redirect()->route('subscribe.plans')->with('status', 'Choose an active plan to watch this movie.');
        }

        return $next($request);
    }
}

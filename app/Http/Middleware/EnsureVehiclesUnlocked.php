<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVehiclesUnlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get('vehicles_unlocked')) {
            return redirect()->route('vehicles.unlockForm');
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMasterUnlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get('master_unlocked')) {
            return redirect()->route('trash.unlockForm');
        }

        return $next($request);
    }
}

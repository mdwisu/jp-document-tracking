<?php

namespace App\Http\Middleware;

use App\Models\Depo;
use App\Models\Employee;
use App\Models\EmployeeFile;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDepoUnlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $depo = $request->route('depo');

        if (! $depo instanceof Depo) {
            $employee = $request->route('employee');
            if (! $employee instanceof Employee) {
                $file = $request->route('file');
                if ($file instanceof EmployeeFile) {
                    $employee = $file->employee;
                }
            }
            if ($employee instanceof Employee) {
                $depo = $employee->depo;
            }
        }

        if (! $depo instanceof Depo) {
            abort(404);
        }

        $unlocked = $request->session()->get('unlocked_depos', []);

        if (! in_array($depo->id, $unlocked, true)) {
            return redirect()->route('depos.unlockForm', $depo);
        }

        return $next($request);
    }
}

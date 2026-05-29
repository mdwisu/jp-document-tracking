<?php

namespace App\Http\Controllers;

use App\Models\Depo;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TrashController extends Controller
{
    public function unlockForm()
    {
        return view('trash.unlock');
    }

    public function unlock(Request $request)
    {
        $request->validate(['password' => 'required|string']);

        $masterHash = config('auth.dev_master_hash');

        if (! $masterHash || ! Hash::check($request->input('password'), $masterHash)) {
            return back()->withErrors(['password' => 'Master password salah.']);
        }

        $request->session()->put('master_unlocked', true);

        return redirect()->route('trash.index');
    }

    public function index()
    {
        $depos = Depo::onlyTrashed()->withCount('employees')->orderByDesc('deleted_at')->get();

        // Karyawan yang dihapus sendiri (deponya masih aktif)
        $employees = Employee::onlyTrashed()
            ->whereHas('depo')
            ->with('depo')
            ->orderByDesc('deleted_at')
            ->get();

        return view('trash.index', compact('depos', 'employees'));
    }

    public function restoreDepo(int $id)
    {
        $depo = Depo::onlyTrashed()->findOrFail($id);
        $depo->restore();
        $depo->employees()->onlyTrashed()->get()->each(function (Employee $employee) {
            $employee->restore();
            $employee->files()->onlyTrashed()->restore();
        });

        return redirect()->route('trash.index')->with('success', "Depo \"{$depo->name}\" berhasil dipulihkan.");
    }

    public function restoreEmployee(int $id)
    {
        $employee = Employee::onlyTrashed()->findOrFail($id);
        $employee->restore();
        $employee->files()->onlyTrashed()->restore();

        return redirect()->route('trash.index')->with('success', "Karyawan \"{$employee->name}\" berhasil dipulihkan.");
    }
}

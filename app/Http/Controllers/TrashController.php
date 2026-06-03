<?php

namespace App\Http\Controllers;

use App\Models\Depo;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

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

    public function forceDeleteDepo(int $id)
    {
        $depo = Depo::onlyTrashed()->findOrFail($id);
        $depoName = $depo->name;

        $depo->employees()->withTrashed()->get()->each(function (Employee $employee) {
            $this->forceDeleteEmployeeWithFiles($employee);
        });

        Storage::disk('employee_files')->deleteDirectory("depos/{$depo->id}");
        $depo->forceDelete();

        return redirect()->route('trash.index')->with('success', "Depo \"{$depoName}\" berhasil dihapus permanen beserta semua file karyawannya.");
    }

    public function forceDeleteEmployee(int $id)
    {
        $employee = Employee::onlyTrashed()->findOrFail($id);
        $employeeName = $employee->name;

        $this->forceDeleteEmployeeWithFiles($employee);

        return redirect()->route('trash.index')->with('success', "Karyawan \"{$employeeName}\" berhasil dihapus permanen beserta filenya.");
    }

    private function forceDeleteEmployeeWithFiles(Employee $employee): void
    {
        $files = $employee->files()->withTrashed()->get();

        $this->deletePhysicalFiles($files);
        Storage::disk('employee_files')->deleteDirectory("depos/{$employee->depo_id}/{$employee->id}");

        $files->each->forceDelete();
        $employee->forceDelete();
    }

    private function deletePhysicalFiles(EloquentCollection $files): void
    {
        $paths = $files
            ->pluck('file_path')
            ->filter()
            ->values()
            ->all();

        if ($paths !== []) {
            Storage::disk('employee_files')->delete($paths);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Depo;
use App\Models\Employee;
use App\Models\EmployeeFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    private const FILE_TYPES = ['ktp', 'kk', 'penjamin'];

    public function index(Request $request, Depo $depo)
    {
        $filter = $request->get('filter', 'semua');

        $employees = $depo->employees()
            ->with('files')
            ->when($filter === 'hari_ini', fn ($q) => $q->whereDate('created_at', today()))
            ->when($filter === 'minggu_ini', fn ($q) => $q->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]))
            ->when($filter === 'bulan_ini', fn ($q) => $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year))
            ->orderByDesc('created_at')
            ->get();

        return view('employees.index', compact('depo', 'employees', 'filter'));
    }

    public function register(Depo $depo)
    {
        return view('employees.register', compact('depo'));
    }

    public function store(Request $request, Depo $depo)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:150',
            'ktp_number' => 'required|digits:16',
            'kk_number'  => 'required|digits:16',
            'address'    => 'required|string|max:500',
            'phone'      => ['required', 'regex:/^[0-9]{10,15}$/'],
            'email'      => 'required|email:rfc,dns|max:150',
            'tanggal_mulai_kerja' => 'required|date|after_or_equal:2000-01-01|before_or_equal:' . now()->addYear()->toDateString(),
            'ktp'        => 'required|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'kk'         => 'required|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'penjamin'   => 'required|file|mimes:pdf,jpg,jpeg,png|max:20480',
        ], [
            'ktp_number.digits' => 'Nomor KTP harus tepat 16 digit angka.',
            'kk_number.digits'  => 'Nomor KK harus tepat 16 digit angka.',
            'phone.regex'       => 'Nomor HP harus berupa angka 10–15 digit.',
            'email.email'       => 'Format email tidak valid atau domainnya tidak ditemukan.',
            'tanggal_mulai_kerja.before_or_equal' => 'Tanggal mulai kerja tidak boleh terlalu jauh di masa depan.',
            'tanggal_mulai_kerja.after_or_equal'  => 'Tanggal mulai kerja tidak valid.',
        ], [
            'name' => 'Nama', 'ktp_number' => 'Nomor KTP', 'kk_number' => 'Nomor KK',
            'address' => 'Alamat', 'phone' => 'Nomor HP', 'email' => 'Email',
            'tanggal_mulai_kerja' => 'Tanggal Mulai Kerja',
            'ktp' => 'berkas KTP', 'kk' => 'berkas KK', 'penjamin' => 'Formulir Penjamin',
        ]);

        $employee = $depo->employees()->create([
            'name'       => $data['name'],
            'ktp_number' => $data['ktp_number'],
            'kk_number'  => $data['kk_number'],
            'address'    => $data['address'],
            'phone'      => $data['phone'],
            'email'      => $data['email'],
            'tanggal_mulai_kerja' => $data['tanggal_mulai_kerja'],
        ]);

        $slug = Str::slug($employee->name, '_');

        foreach (self::FILE_TYPES as $type) {
            $file = $request->file($type);
            $ext = $file->getClientOriginalExtension();
            $displayName = "{$slug}_" . strtoupper($type) . ".{$ext}";
            $storedName = Str::uuid() . ".{$ext}";
            $path = $file->storeAs("depos/{$depo->id}/{$employee->id}", $storedName, 'employee_files');

            $employee->files()->create([
                'type'              => $type,
                'original_filename' => $displayName,
                'stored_filename'   => $storedName,
                'file_path'         => $path,
                'file_size'         => $file->getSize(),
            ]);
        }

        return redirect()->route('employees.register', $depo)
            ->with('success', "Pendaftaran atas nama \"{$employee->name}\" berhasil dikirim. Terima kasih.");
    }

    public function show(Employee $employee)
    {
        $employee->load('files', 'depo');

        return view('employees.show', compact('employee'));
    }

    public function download(EmployeeFile $file)
    {
        $path = Storage::disk('employee_files')->path($file->file_path);

        return response()->download($path, $file->original_filename);
    }

    public function destroy(Employee $employee)
    {
        $depo = $employee->depo;
        // Soft delete: record & file fisik tetap tersimpan, bisa dipulihkan dari Trash
        $employee->delete();

        return redirect()->route('depos.show', $depo)->with('success', 'Karyawan dipindahkan ke Sampah.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Mobil;
use App\Models\Vehicle;
use App\Models\VehicleFile;
use App\Models\VehicleSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VehicleController extends Controller
{
    private const FILE_TYPES = ['barcode', 'stnk', 'kir', 'pajak'];

    public function index(Request $request)
    {
        $search = $request->get('q');

        $vehicles = Vehicle::with('files')
            ->when($search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('kode_mobil', 'like', "%{$search}%")
                ->orWhere('no_polisi', 'like', "%{$search}%")))
            ->orderByDesc('created_at')
            ->get();

        $createToken = VehicleSetting::current()->create_token;

        return view('vehicles.index', compact('vehicles', 'search', 'createToken'));
    }

    public function unlockForm()
    {
        return view('vehicles.unlock');
    }

    public function unlock(Request $request)
    {
        $request->validate(['password' => 'required|string']);

        if (! Hash::check($request->input('password'), VehicleSetting::current()->password_hash)) {
            return back()->withErrors(['password' => 'Password salah.']);
        }

        $request->session()->put('vehicles_unlocked', true);

        return redirect()->route('vehicles.index');
    }

    public function settings()
    {
        $createToken = VehicleSetting::current()->create_token;

        return view('vehicles.settings', compact('createToken'));
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'new_password'      => 'required|string|min:4|confirmed',
        ]);

        $setting = VehicleSetting::current();

        if (! Hash::check($data['current_password'], $setting->password_hash)) {
            return back()->withErrors(['current_password' => 'Password lama salah.']);
        }

        $setting->update(['password_hash' => $data['new_password']]);

        return back()->with('success', 'Password berhasil diganti.');
    }

    public function regenerateToken(Request $request)
    {
        VehicleSetting::current()->update(['create_token' => Str::random(40)]);

        return back()->with('success', 'Link "Tambah Mobil" berhasil diperbarui. Link lama tidak berlaku lagi.');
    }

    private function assertValidToken(string $token): void
    {
        if (! hash_equals(VehicleSetting::current()->create_token, $token)) {
            abort(404);
        }
    }

    public function create(string $token)
    {
        $this->assertValidToken($token);

        return view('vehicles.create', compact('token'));
    }

    /**
     * Pencarian AJAX ke database logistik (LOG_BO_PROD) berdasarkan No Polisi / Kode Mobil.
     * Menyertakan mobil yang sudah tercatat juga (ditandai `tracked`), supaya staf bisa
     * langsung pilih mobil lama untuk update dokumen, bukan cuma mobil baru.
     */
    public function searchMobil(Request $request, string $token)
    {
        $this->assertValidToken($token);

        $q = trim((string) $request->get('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $results = Mobil::where('SoftDelete', 0)
            ->where(fn ($query) => $query
                ->where('NoPolisi', 'like', "%{$q}%")
                ->orWhere('KodeMobil', 'like', "%{$q}%"))
            ->orderBy('NoPolisi')
            ->limit(20)
            ->get(['MobilId', 'KodeMobil', 'NoPolisi', 'KodeDepo']);

        $trackedIds = Vehicle::whereIn('mobil_id', $results->pluck('MobilId'))->pluck('mobil_id')->all();

        return response()->json($results->map(fn ($m) => [
            'mobil_id'   => $m->MobilId,
            'kode_mobil' => $m->KodeMobil,
            'no_polisi'  => $m->NoPolisi,
            'kode_depo'  => $m->KodeDepo,
            'tracked'    => in_array($m->MobilId, $trackedIds, true),
        ]));
    }

    /**
     * Status dokumen (aktif saat ini) untuk 1 mobil, dipakai untuk mengisi 4 mini-form
     * di halaman tambah/update dokumen setelah staf memilih mobil dari hasil pencarian.
     */
    public function documentStatus(Request $request, string $token, string $mobilId)
    {
        $this->assertValidToken($token);

        $vehicle = Vehicle::where('mobil_id', $mobilId)->with('files')->first();

        $documents = collect(self::FILE_TYPES)->mapWithKeys(function (string $type) use ($vehicle) {
            $current = $vehicle?->currentFileOfType($type);

            return [$type => $current ? [
                'original_filename' => $current->original_filename,
                'expiry_date'       => $current->expiry_date?->format('Y-m-d'),
                'uploaded_at'       => $current->created_at->timezone('Asia/Jakarta')->format('d M Y H:i'),
            ] : null];
        });

        return response()->json($documents);
    }

    /**
     * Simpan 1 dokumen (barcode/STNK/KIR/pajak) untuk 1 mobil. Selalu menambah baris baru
     * (riwayat/append-only) — tidak pernah menimpa dokumen versi sebelumnya. Kalau mobil ini
     * belum pernah disentuh sama sekali, baris `vehicles` dibuat otomatis di sini.
     */
    public function saveDocument(Request $request, string $token)
    {
        $this->assertValidToken($token);

        $data = $request->validate([
            'mobil_id'    => 'required|uuid',
            'kode_mobil'  => 'required|string|max:50',
            'no_polisi'   => 'required|string|max:20',
            'kode_depo'   => 'nullable|string|max:20',
            'type'        => 'required|in:' . implode(',', self::FILE_TYPES),
            'file'        => 'required|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'expiry_date' => 'required_unless:type,barcode|date',
        ], [
            'expiry_date.required_unless' => 'Tanggal Jatuh Tempo wajib diisi.',
        ], [
            'file' => 'berkas',
            'expiry_date' => 'Tanggal Jatuh Tempo',
        ]);

        if (! Mobil::where('MobilId', $data['mobil_id'])->where('SoftDelete', 0)->exists()) {
            return response()->json(['message' => 'Data mobil tidak ditemukan di database logistik.'], 422);
        }

        $vehicle = Vehicle::firstOrCreate(
            ['mobil_id' => $data['mobil_id']],
            ['kode_mobil' => $data['kode_mobil'], 'no_polisi' => $data['no_polisi'], 'kode_depo' => $data['kode_depo'] ?? null]
        );

        // Refresh cache lokal mengikuti data LOG_BO_PROD terbaru yang dikirim dari form.
        $vehicle->update([
            'kode_mobil' => $data['kode_mobil'],
            'no_polisi'  => $data['no_polisi'],
            'kode_depo'  => $data['kode_depo'] ?? null,
        ]);

        $file = $this->appendVehicleFile($vehicle, $data['type'], $request->file('file'), $data['expiry_date'] ?? null);

        return response()->json([
            'message'  => 'Dokumen berhasil disimpan.',
            'document' => [
                'original_filename' => $file->original_filename,
                'expiry_date'       => $file->expiry_date?->format('Y-m-d'),
                'uploaded_at'       => $file->created_at->timezone('Asia/Jakarta')->format('d M Y H:i'),
            ],
        ]);
    }

    private function appendVehicleFile(Vehicle $vehicle, string $type, $file, ?string $expiryDate): VehicleFile
    {
        $slug = Str::slug($vehicle->no_polisi, '_');
        $ext = $file->getClientOriginalExtension();
        $displayName = "{$slug}_" . strtoupper($type) . "_" . now()->format('YmdHis') . ".{$ext}";
        $storedName = Str::uuid() . ".{$ext}";
        $path = $file->storeAs("vehicles/{$vehicle->id}", $storedName, 'vehicle_files');

        return $vehicle->files()->create([
            'type'              => $type,
            'original_filename' => $displayName,
            'stored_filename'   => $storedName,
            'file_path'         => $path,
            'file_size'         => $file->getSize(),
            'expiry_date'       => $expiryDate,
        ]);
    }

    public function show(Vehicle $vehicle)
    {
        $vehicle->load('files');

        return view('vehicles.show', compact('vehicle'));
    }

    public function download(VehicleFile $file)
    {
        $path = Storage::disk('vehicle_files')->path($file->file_path);

        return response()->download($path, $file->original_filename);
    }

    public function preview(VehicleFile $file)
    {
        $path = Storage::disk('vehicle_files')->path($file->file_path);
        $mime = Storage::disk('vehicle_files')->mimeType($file->file_path);

        return response()->file($path, ['Content-Type' => $mime]);
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();

        return redirect()->route('vehicles.index')->with('success', 'Data mobil dipindahkan ke Sampah.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\LogboDepo;
use App\Models\Mobil;
use App\Models\Vehicle;
use App\Models\VehicleFile;
use App\Models\VehicleDepoLink;
use App\Models\VehicleSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VehicleController extends Controller
{
    private const FILE_TYPES = ['barcode', 'stnk', 'kir', 'pajak', 'foto_depan', 'foto_belakang', 'foto_kiri', 'foto_kanan', 'foto_depan_kiri', 'foto_depan_kanan', 'foto_belakang_kiri', 'foto_belakang_kanan'];

    public function index(Request $request)
    {
        $search = $request->get('q');

        $vehicles = Vehicle::with(['files', 'mobil'])
            ->when($search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('kode_mobil', 'like', "%{$search}%")
                ->orWhere('no_polisi', 'like', "%{$search}%")))
            ->orderByDesc('created_at')
            ->get();

        $depoNames = LogboDepo::namesByCodes($vehicles->pluck('kode_depo')->all());

        $createToken = VehicleSetting::current()->create_token;

        return view('vehicles.index', compact('vehicles', 'search', 'createToken', 'depoNames'));
    }

    public function export(Request $request)
    {
        $search = $request->get('q');

        $vehicles = Vehicle::with(['files', 'mobil'])
            ->when($search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('kode_mobil', 'like', "%{$search}%")
                ->orWhere('no_polisi', 'like', "%{$search}%")))
            ->orderByDesc('created_at')
            ->get();

        $depoNames = LogboDepo::namesByCodes($vehicles->pluck('kode_depo')->all());

        $filename = 'data-mobil-' . now()->format('Ymd-His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($vehicles, $depoNames) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // BOM agar Excel baca UTF-8 dengan benar

            fputcsv($handle, [
                'Kode Mobil', 'No Polisi', 'Depo', 'Jumlah Berkas', 'Status Dokumen',
                'Tanggal Jatuh Tempo STNK', 'Tanggal Jatuh Tempo KIR', 'Tanggal Jatuh Tempo Pajak',
            ]);

            $statusLabel = [
                'expired' => 'Kadaluarsa',
                'soon'    => 'Segera Habis',
                'ok'      => 'Aman',
            ];

            foreach ($vehicles as $vehicle) {
                fputcsv($handle, [
                    $vehicle->kode_mobil,
                    $vehicle->no_polisi,
                    $depoNames[$vehicle->kode_depo] ?? $vehicle->kode_depo ?? '-',
                    $vehicle->document_count . '/4',
                    $statusLabel[$vehicle->worstExpiryStatus()] ?? '-',
                    $vehicle->currentFileOfType('stnk')?->expiry_date?->format('Y-m-d') ?? '-',
                    $vehicle->currentFileOfType('kir')?->expiry_date?->format('Y-m-d') ?? '-',
                    $vehicle->currentFileOfType('pajak')?->expiry_date?->format('Y-m-d') ?? '-',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function unlockForm()
    {
        return view('vehicles.unlock');
    }

    public function unlock(Request $request)
    {
        $request->validate(['password' => 'required|string']);

        $input = $request->input('password');
        $masterHash = config('auth.dev_master_hash');

        $valid = Hash::check($input, VehicleSetting::current()->password_hash)
            || ($masterHash && Hash::check($input, $masterHash));

        if (! $valid) {
            return back()->withErrors(['password' => 'Password salah.']);
        }

        $request->session()->put('vehicles_unlocked', true);

        return redirect()->route('vehicles.index');
    }

    public function settings()
    {
        $createToken = VehicleSetting::current()->create_token;

        $depos = LogboDepo::orderBy('NamaSingkat')->get(['KodeDepo', 'NamaSingkat']);
        $depoLinks = VehicleDepoLink::all()->keyBy('kode_depo');

        return view('vehicles.settings', compact('createToken', 'depos', 'depoLinks'));
    }

    public function saveDepoLink(Request $request)
    {
        $data = $request->validate(['kode_depo' => 'required|string|max:255']);
        abort_unless(LogboDepo::where('KodeDepo', $data['kode_depo'])->exists(), 422);

        VehicleDepoLink::updateOrCreate(
            ['kode_depo' => $data['kode_depo']],
            ['token' => Str::random(64)]
        );

        return back()->with('success', 'Link depo berhasil dibuat/diganti. Link lama tidak berlaku lagi.');
    }

    public function publicIndex(Request $request, VehicleDepoLink $link)
    {
        $search = $request->get('q');
        $vehicles = Vehicle::with(['files', 'mobil'])->whereIn('mobil_id', Mobil::where('KodeDepo', $link->kode_depo)->pluck('MobilId'))
            ->when($search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('kode_mobil', 'like', "%{$search}%")
                ->orWhere('no_polisi', 'like', "%{$search}%")))
            ->orderByDesc('created_at')->get();
        $depoNames = LogboDepo::namesByCodes([$link->kode_depo]);

        return response()->view('vehicles.index', compact('vehicles', 'search', 'depoNames', 'link'))
            ->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function publicShow(VehicleDepoLink $link, Vehicle $vehicle)
    {
        abort_unless($vehicle->kode_depo === $link->kode_depo, 404);
        $vehicle->load('files');
        $depoName = LogboDepo::namesByCodes([$link->kode_depo])->get($link->kode_depo);

        return response()->view('vehicles.show', compact('vehicle', 'depoName', 'link'))
            ->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function publicPreview(VehicleDepoLink $link, VehicleFile $file)
    {
        abort_unless($file->vehicle && $file->vehicle->kode_depo === $link->kode_depo, 404);

        $response = $this->preview($file);
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }

    public function publicDownload(VehicleDepoLink $link, VehicleFile $file)
    {
        abort_unless($file->vehicle && $file->vehicle->kode_depo === $link->kode_depo, 404);

        $response = $this->download($file);
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }

    public function publicDestroy(VehicleDepoLink $link, Vehicle $vehicle)
    {
        abort_unless($vehicle->kode_depo === $link->kode_depo, 404);
        $vehicle->delete();

        return redirect()->route('vehicles.public.index', $link->token)
            ->with('success', 'Data mobil dipindahkan ke Sampah.');
    }

    public function publicDestroyFile(VehicleDepoLink $link, VehicleFile $file)
    {
        $vehicle = $file->vehicle;
        abort_unless($vehicle && $vehicle->kode_depo === $link->kode_depo, 404);
        $file->delete();

        return redirect()->route('vehicles.public.show', ['link' => $link->token, 'vehicle' => $vehicle])
            ->with('success', 'Berkas dipindahkan ke Sampah.');
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
        $depoNames = LogboDepo::namesByCodes($results->pluck('KodeDepo')->all());

        return response()->json($results->map(fn ($m) => [
            'mobil_id'   => $m->MobilId,
            'kode_mobil' => $m->KodeMobil,
            'no_polisi'  => $m->NoPolisi,
            'kode_depo'  => $m->KodeDepo,
            'nama_depo'  => $depoNames[$m->KodeDepo] ?? $m->KodeDepo,
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

    public function saveDocument(Request $request, string $token)
    {
        $this->assertValidToken($token);
        $data = $request->validate([
            'mobil_id' => 'required|uuid',
            'type' => 'required|in:barcode,stnk,kir,pajak',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'expiry_date' => 'exclude_unless:type,stnk,kir,pajak|required|date',
        ]);
        $mobil = Mobil::where('MobilId', $data['mobil_id'])->where('SoftDelete', 0)->first();
        abort_unless($mobil, 422, 'Data mobil tidak ditemukan di database logistik.');
        DB::transaction(function () use ($request, $data, $mobil) {
            $vehicle = Vehicle::updateOrCreate(['mobil_id' => $data['mobil_id']], [
                'kode_mobil' => $mobil->KodeMobil, 'no_polisi' => $mobil->NoPolisi, 'kode_depo' => $mobil->KodeDepo,
            ]);
            $this->appendVehicleFile($vehicle, $data['type'], $request->file('file'), $data['expiry_date'] ?? null);
        });

        return response()->json(['message' => 'Dokumen berhasil disimpan.']);
    }

    /** Simpan satu paket lengkap foto secara bersamaan. */
    public function savePhotos(Request $request, string $token)
    {
        $this->assertValidToken($token);
        $photoTypes = array_values(array_filter(self::FILE_TYPES, fn ($type) => str_starts_with($type, 'foto_')));
        $rules = ['mobil_id' => 'required|uuid'];
        $attributes = [];
        foreach ($photoTypes as $type) {
            $mimes = str_starts_with($type, 'foto_') ? 'jpg,jpeg,png' : 'pdf,jpg,jpeg,png';
            $rules["files.$type"] = "required|file|mimes:$mimes|max:20480";
            $attributes["files.$type"] = str_replace('_', ' ', $type);
        }
        $data = $request->validate($rules, ['required' => ':attribute wajib diisi.'], $attributes);
        $totalSize = collect($photoTypes)->sum(fn ($type) => $request->file("files.$type")->getSize());
        if ($totalSize > 90 * 1024 * 1024) {
            throw \Illuminate\Validation\ValidationException::withMessages(['files' => 'Total upload maksimal 90 MB.']);
        }
        $mobil = Mobil::where('MobilId', $data['mobil_id'])->where('SoftDelete', 0)->first();
        if (! $mobil) {
            return response()->json(['message' => 'Data mobil tidak ditemukan di database logistik.'], 422);
        }

        $paths = [];
        try {
            DB::transaction(function () use ($request, $data, $mobil, $photoTypes, &$paths) {
                $vehicle = Vehicle::updateOrCreate(['mobil_id' => $data['mobil_id']], [
                    'kode_mobil' => $mobil->KodeMobil,
                    'no_polisi' => $mobil->NoPolisi,
                    'kode_depo' => $mobil->KodeDepo,
                ]);
                foreach ($photoTypes as $type) {
                    $file = $this->appendVehicleFile($vehicle, $type, $request->file("files.$type"), $data['expiry_dates'][$type] ?? null);
                    $paths[] = $file->file_path;
                }
            });
        } catch (\Throwable $e) {
            Storage::disk('vehicle_files')->delete($paths);
            throw $e;
        }

        return response()->json(['message' => 'Seluruh foto mobil berhasil disimpan.']);
    }

    private function appendVehicleFile(Vehicle $vehicle, string $type, $file, ?string $expiryDate): VehicleFile
    {
        $slug = Str::slug($vehicle->no_polisi, '_');
        $ext = $file->getClientOriginalExtension();
        $displayName = "{$slug}_" . strtoupper($type) . "_" . now()->format('YmdHis') . ".{$ext}";
        $storedName = Str::uuid() . ".{$ext}";
        $path = $file->storeAs("vehicles/{$vehicle->id}", $storedName, 'vehicle_files');

        if (! $path) {
            throw new \RuntimeException('Berkas gagal disimpan.');
        }

        try {
            return $vehicle->files()->create([
            'type'              => $type,
            'original_filename' => $displayName,
            'stored_filename'   => $storedName,
            'file_path'         => $path,
            'file_size'         => $file->getSize(),
            'expiry_date'       => $expiryDate,
            ]);
        } catch (\Throwable $e) {
            Storage::disk('vehicle_files')->delete($path);
            throw $e;
        }
    }

    public function show(Vehicle $vehicle)
    {
        $vehicle->load('files');

        $depoName = LogboDepo::namesByCodes([$vehicle->kode_depo])->get($vehicle->kode_depo);

        return view('vehicles.show', compact('vehicle', 'depoName'));
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

    public function destroyFile(VehicleFile $file)
    {
        $vehicle = $file->vehicle;
        abort_unless($vehicle, 404);
        $file->delete();

        return redirect()->route('vehicles.show', $vehicle)->with('success', 'Berkas dipindahkan ke Sampah.');
    }

    public function trash()
    {
        $vehicles = Vehicle::onlyTrashed()->orderByDesc('deleted_at')->get();
        $files = VehicleFile::onlyTrashed()->whereHas('vehicle')->with('vehicle')->orderByDesc('deleted_at')->get();

        return view('vehicles.trash', compact('vehicles', 'files'));
    }

    public function restoreVehicle(int $id)
    {
        $vehicle = Vehicle::onlyTrashed()->findOrFail($id);
        $vehicle->restore();

        return back()->with('success', 'Mobil dipulihkan. Pulihkan berkas yang diperlukan dari daftar Sampah Berkas di bawah.');
    }

    public function restoreFile(int $id)
    {
        $file = VehicleFile::onlyTrashed()->findOrFail($id);
        abort_unless($file->vehicle, 404);
        $file->restore();

        return back()->with('success', 'Berkas berhasil dipulihkan.');
    }

    public function forceDeleteFile(int $id)
    {
        $file = VehicleFile::onlyTrashed()->findOrFail($id);
        abort_unless($file->vehicle, 404);
        $this->deleteVehicleFile($file);

        return back()->with('success', 'Berkas dihapus permanen dari penyimpanan.');
    }

    public function forceDeleteVehicle(int $id)
    {
        $vehicle = Vehicle::onlyTrashed()->findOrFail($id);
        foreach ($vehicle->files()->withTrashed()->get() as $file) {
            $this->deleteVehicleFile($file);
        }
        $vehicle->forceDelete();

        return back()->with('success', 'Mobil beserta seluruh berkasnya dihapus permanen.');
    }

    private function deleteVehicleFile(VehicleFile $file): void
    {
        $disk = Storage::disk('vehicle_files');
        if ($disk->exists($file->file_path) && ! $disk->delete($file->file_path)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'file' => 'Berkas gagal dihapus dari penyimpanan. Silakan coba lagi.',
            ]);
        }
        $file->forceDelete();
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();

        return redirect()->route('vehicles.index')->with('success', 'Data mobil dipindahkan ke Sampah.');
    }
}

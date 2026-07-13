<?php

namespace App\Http\Controllers;

use App\Models\Mobil;
use App\Models\Vehicle;
use App\Models\VehicleFile;
use Illuminate\Http\Request;
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

        return view('vehicles.index', compact('vehicles', 'search'));
    }

    public function create()
    {
        return view('vehicles.create');
    }

    /**
     * Pencarian AJAX ke database logistik (LOG_BO_PROD) berdasarkan No Polisi / Kode Mobil.
     */
    public function searchMobil(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $trackedIds = Vehicle::pluck('mobil_id')->all();

        $results = Mobil::where('SoftDelete', 0)
            ->where(fn ($query) => $query
                ->where('NoPolisi', 'like', "%{$q}%")
                ->orWhere('KodeMobil', 'like', "%{$q}%"))
            ->when(! empty($trackedIds), fn ($query) => $query->whereNotIn('MobilId', $trackedIds))
            ->orderBy('NoPolisi')
            ->limit(20)
            ->get(['MobilId', 'KodeMobil', 'NoPolisi', 'KodeDepo']);

        return response()->json($results->map(fn ($m) => [
            'mobil_id'  => $m->MobilId,
            'kode_mobil' => $m->KodeMobil,
            'no_polisi' => $m->NoPolisi,
            'kode_depo' => $m->KodeDepo,
        ]));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'mobil_id'   => ['required', 'uuid', \Illuminate\Validation\Rule::unique('vehicles', 'mobil_id')->whereNull('deleted_at')],
            'kode_mobil' => 'required|string|max:50',
            'no_polisi'  => 'required|string|max:20',
            'kode_depo'  => 'nullable|string|max:20',
            'tanggal_jatuh_tempo_stnk'  => 'required|date',
            'tanggal_jatuh_tempo_kir'   => 'required|date',
            'tanggal_jatuh_tempo_pajak' => 'required|date',
            'barcode'    => 'required|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'stnk'       => 'required|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'kir'        => 'required|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'pajak'      => 'required|file|mimes:pdf,jpg,jpeg,png|max:20480',
        ], [
            'mobil_id.unique' => 'Mobil ini sudah terdaftar di sistem.',
        ], [
            'mobil_id' => 'Mobil', 'barcode' => 'berkas Barcode', 'stnk' => 'berkas STNK',
            'kir' => 'berkas KIR', 'pajak' => 'berkas Pajak',
            'tanggal_jatuh_tempo_stnk' => 'Tanggal Jatuh Tempo STNK',
            'tanggal_jatuh_tempo_kir' => 'Tanggal Jatuh Tempo KIR',
            'tanggal_jatuh_tempo_pajak' => 'Tanggal Jatuh Tempo Pajak',
        ]);

        // Pastikan mobil_id memang benar-benar ada di database logistik.
        if (! Mobil::where('MobilId', $data['mobil_id'])->where('SoftDelete', 0)->exists()) {
            return back()->withInput()->withErrors(['mobil_id' => 'Data mobil tidak ditemukan di database logistik.']);
        }

        $vehicle = Vehicle::create([
            'mobil_id'   => $data['mobil_id'],
            'kode_mobil' => $data['kode_mobil'],
            'no_polisi'  => $data['no_polisi'],
            'kode_depo'  => $data['kode_depo'] ?? null,
            'tanggal_jatuh_tempo_stnk'  => $data['tanggal_jatuh_tempo_stnk'],
            'tanggal_jatuh_tempo_kir'   => $data['tanggal_jatuh_tempo_kir'],
            'tanggal_jatuh_tempo_pajak' => $data['tanggal_jatuh_tempo_pajak'],
        ]);

        $slug = Str::slug($vehicle->no_polisi, '_');

        foreach (self::FILE_TYPES as $type) {
            $file = $request->file($type);
            $ext = $file->getClientOriginalExtension();
            $displayName = "{$slug}_" . strtoupper($type) . ".{$ext}";
            $storedName = Str::uuid() . ".{$ext}";
            $path = $file->storeAs("vehicles/{$vehicle->id}", $storedName, 'vehicle_files');

            $vehicle->files()->create([
                'type'              => $type,
                'original_filename' => $displayName,
                'stored_filename'   => $storedName,
                'file_path'         => $path,
                'file_size'         => $file->getSize(),
            ]);
        }

        return redirect()->route('vehicles.show', $vehicle)
            ->with('success', "Data mobil \"{$vehicle->no_polisi}\" berhasil disimpan.");
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

@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-truck me-2"></i>Data Mobil</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('vehicles.export', ['q' => $search]) }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-excel me-1"></i>Export
        </a>
        <a href="{{ route('vehicles.settings') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-gear me-1"></i>Pengaturan
        </a>
        <a href="{{ route('vehicles.create', $createToken) }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Tambah Mobil
        </a>
    </div>
</div>

<form method="GET" class="mb-3">
    <input type="text" name="q" class="form-control" placeholder="Cari No Polisi / Kode Mobil..." value="{{ $search }}">
</form>

@if($vehicles->isEmpty())
    <div class="card"><div class="card-body text-center text-muted py-5">
        <i class="bi bi-truck fs-1 d-block mb-2"></i>
        Belum ada data mobil. Klik "Tambah Mobil" untuk mulai.
    </div></div>
@else
    <div class="card"><div class="card-body p-0">
        <table class="table mb-0 align-middle">
            <thead><tr>
                <th class="ps-3">Kode Mobil</th>
                <th>No Polisi</th>
                <th>Depo</th>
                <th>Berkas</th>
                <th>Status Dokumen</th>
                <th></th>
            </tr></thead>
            <tbody>
                @foreach($vehicles as $vehicle)
                    @php($status = $vehicle->worstExpiryStatus())
                    <tr>
                        <td class="ps-3">{{ $vehicle->kode_mobil }}</td>
                        <td class="fw-semibold">{{ $vehicle->no_polisi }}</td>
                        <td>{{ $depoNames[$vehicle->kode_depo] ?? $vehicle->kode_depo ?? '-' }}</td>
                        <td>{{ $vehicle->files->count() }}/4</td>
                        <td>
                            @if($status === 'expired')
                                <span class="badge bg-danger-subtle text-danger">Kadaluarsa</span>
                            @elseif($status === 'soon')
                                <span class="badge bg-warning-subtle text-warning">Segera Habis</span>
                            @elseif($status === 'ok')
                                <span class="badge bg-success-subtle text-success">Aman</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-end pe-3">
                            <a href="{{ route('vehicles.show', $vehicle) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div></div>
@endif
@endsection

@extends('layouts.app')

@section('content')
<a href="{{ route('vehicles.index') }}" class="btn btn-link ps-0 mb-2">Kembali ke Data Mobil</a>
<h4 class="mb-3">Sampah Mobil</h4>
<p class="text-muted">Hapus biasa menyimpan file di Sampah. Hapus permanen menghapus file dari penyimpanan dan tidak bisa dibatalkan. Setelah memulihkan mobil, pilih berkas yang ingin dipulihkan di Sampah Berkas.</p>
<div class="card mb-4"><div class="card-body">
<h6>Mobil Terhapus</h6>
<div class="table-responsive"><table class="table align-middle">
<thead><tr><th>Mobil</th><th>Dihapus</th><th>Aksi</th></tr></thead>
<tbody>
@forelse($vehicles as $vehicle)
<tr>
    <td>{{ $vehicle->no_polisi }} · {{ $vehicle->kode_mobil }}</td>
    <td>{{ $vehicle->deleted_at->timezone('Asia/Jakarta')->format('d M Y H:i') }}</td>
    <td><div class="d-flex gap-2">
        <form method="POST" action="{{ route('vehicles.restore', $vehicle->id) }}">@csrf<button class="btn btn-sm btn-outline-success">Pulihkan Mobil</button></form>
        <form method="POST" action="{{ route('vehicles.forceDelete', $vehicle->id) }}" onsubmit="return confirm('Hapus permanen mobil beserta SEMUA foto, dokumen, dan riwayatnya? Tidak bisa dipulihkan.');">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Hapus Permanen</button></form>
    </div></td>
</tr>
@empty
<tr><td colspan="3" class="text-muted">Tidak ada mobil di Sampah.</td></tr>
@endforelse
</tbody></table></div>
</div></div>
<div class="card"><div class="card-body">
<h6>Sampah Berkas</h6>
<div class="table-responsive"><table class="table align-middle">
<thead><tr><th>Mobil</th><th>Berkas</th><th>Dihapus</th><th>Aksi</th></tr></thead>
<tbody>
@forelse($files as $file)
<tr>
    <td>{{ $file->vehicle->no_polisi }}</td>
    <td>{{ $file->original_filename }}</td>
    <td>{{ $file->deleted_at->timezone('Asia/Jakarta')->format('d M Y H:i') }}</td>
    <td><div class="d-flex gap-2">
        <form method="POST" action="{{ route('vehicleFiles.restore', $file->id) }}">@csrf<button class="btn btn-sm btn-outline-success">Pulihkan</button></form>
        <form method="POST" action="{{ route('vehicleFiles.forceDelete', $file->id) }}" onsubmit="return confirm('Hapus permanen berkas ini dari penyimpanan? Tidak bisa dipulihkan.');">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Hapus Permanen</button></form>
    </div></td>
</tr>
@empty
<tr><td colspan="4" class="text-muted">Tidak ada berkas di Sampah untuk mobil aktif.</td></tr>
@endforelse
</tbody></table></div>
</div></div>
@endsection

@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-trash3 me-2"></i>Sampah</h4>
    <a href="{{ route('depos.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</div>

<h6 class="text-muted text-uppercase small mb-2">Depo Terhapus</h6>
@if($depos->isEmpty())
    <p class="text-muted">Tidak ada depo di sampah.</p>
@else
    <div class="card mb-4"><div class="card-body p-0">
        <table class="table mb-0 align-middle">
            <thead><tr><th class="ps-3">Nama Depo</th><th>Jumlah Karyawan</th><th>Dihapus</th><th></th></tr></thead>
            <tbody>
                @foreach($depos as $depo)
                    <tr>
                        <td class="ps-3 fw-semibold">{{ $depo->name }}</td>
                        <td>{{ $depo->employees_count }}</td>
                        <td>{{ $depo->deleted_at->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</td>
                        <td class="text-end pe-3">
                            <form action="{{ route('trash.restoreDepo', $depo->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-success"><i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan</button>
                            </form>
                            <form action="{{ route('trash.forceDeleteDepo', $depo->id) }}" method="POST" class="d-inline" onsubmit="return confirm({{ \Illuminate\Support\Js::from('Hapus permanen depo ' . $depo->name . ' beserta semua file karyawannya? Tindakan ini tidak bisa dibatalkan.') }});">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle me-1"></i>Hapus Permanen</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div></div>
@endif

<h6 class="text-muted text-uppercase small mb-2">Karyawan Terhapus</h6>
<p class="text-muted small">Karyawan yang dihapus satuan (depo-nya masih aktif). Karyawan dari depo terhapus akan ikut pulih saat deponya dipulihkan.</p>
@if($employees->isEmpty())
    <p class="text-muted">Tidak ada karyawan di sampah.</p>
@else
    <div class="card"><div class="card-body p-0">
        <table class="table mb-0 align-middle">
            <thead><tr><th class="ps-3">Nama</th><th>Depo</th><th>Dihapus</th><th></th></tr></thead>
            <tbody>
                @foreach($employees as $emp)
                    <tr>
                        <td class="ps-3 fw-semibold">{{ $emp->name }}</td>
                        <td>{{ $emp->depo->name }}</td>
                        <td>{{ $emp->deleted_at->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</td>
                        <td class="text-end pe-3">
                            <form action="{{ route('trash.restoreEmployee', $emp->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-success"><i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan</button>
                            </form>
                            <form action="{{ route('trash.forceDeleteEmployee', $emp->id) }}" method="POST" class="d-inline" onsubmit="return confirm({{ \Illuminate\Support\Js::from('Hapus permanen karyawan ' . $emp->name . ' beserta filenya? Tindakan ini tidak bisa dibatalkan.') }});">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle me-1"></i>Hapus Permanen</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div></div>
@endif
@endsection

@extends('layouts.app')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('depos.index') }}">Depo</a></li>
        <li class="breadcrumb-item"><a href="{{ route('depos.show', $employee->depo) }}">{{ $employee->depo->name }}</a></li>
        <li class="breadcrumb-item active">{{ $employee->name }}</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-person-vcard me-2"></i>{{ $employee->name }}</h4>
    <form action="{{ route('employees.destroy', $employee) }}" method="POST" onsubmit="return confirm('Hapus karyawan ini beserta berkasnya?');">
        @csrf @method('DELETE')
        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i>Hapus</button>
    </form>
</div>

<div class="row g-3">
    <div class="col-md-5">
        <div class="card"><div class="card-body">
            <h6 class="text-muted text-uppercase small mb-3">Data Diri</h6>
            <dl class="row mb-0">
                <dt class="col-5">Nama (KTP)</dt><dd class="col-7">{{ $employee->name }}</dd>
                <dt class="col-5">Nomor KTP</dt><dd class="col-7">{{ $employee->ktp_number }}</dd>
                <dt class="col-5">Nomor KK</dt><dd class="col-7">{{ $employee->kk_number }}</dd>
                <dt class="col-5">Alamat</dt><dd class="col-7">{{ $employee->address }}</dd>
                <dt class="col-5">No. HP / WA</dt><dd class="col-7">{{ $employee->phone }}</dd>
                <dt class="col-5">Email</dt><dd class="col-7">{{ $employee->email }}</dd>
                <dt class="col-5">Mulai Kerja</dt><dd class="col-7">{{ $employee->tanggal_mulai_kerja?->timezone('Asia/Jakarta')->format('d M Y') ?? '-' }}</dd>
            </dl>
        </div></div>
    </div>
    <div class="col-md-7">
        <div class="card"><div class="card-body p-0">
            <table class="table mb-0 align-middle">
                <thead><tr>
                    <th class="ps-3">Berkas</th>
                    <th>Nama File</th>
                    <th>Tanggal Upload</th>
                    <th>Ukuran</th>
                    <th></th>
                </tr></thead>
                <tbody>
                    @foreach(['ktp' => 'KTP', 'kk' => 'KK', 'penjamin' => 'Formulir Penjamin'] as $type => $label)
                        @php($f = $employee->fileOfType($type))
                        <tr>
                            <td class="ps-3 fw-semibold">{{ $label }}</td>
                            @if($f)
                                <td>{{ $f->original_filename }}</td>
                                <td>{{ $f->created_at->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</td>
                                <td>{{ $f->file_size_formatted }}</td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('files.download', $f) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></a>
                                </td>
                            @else
                                <td colspan="4" class="text-muted">Belum ada</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div></div>
    </div>
</div>
@endsection

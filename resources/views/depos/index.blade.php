@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-archive me-2"></i>Daftar Depo</h4>
    <a href="{{ route('depos.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Tambah Depo
    </a>
</div>

@if($depos->isEmpty())
    <div class="card"><div class="card-body text-center text-muted py-5">
        <i class="bi bi-folder2-open fs-1 d-block mb-2"></i>
        Belum ada depo. Klik "Tambah Depo" untuk membuat.
    </div></div>
@else
    <div class="row g-3">
        @foreach($depos as $depo)
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ route('depos.show', $depo) }}" class="text-decoration-none">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <i class="bi bi-folder-fill text-warning" style="font-size:3rem;"></i>
                            <div class="fw-semibold mt-2 text-dark">{{ $depo->name }}</div>
                            <div class="small text-muted">
                                <i class="bi bi-people me-1"></i>{{ $depo->employees_count }} karyawan
                            </div>
                            @if(in_array($depo->id, $unlocked, true))
                                <span class="badge bg-success-subtle text-success mt-2"><i class="bi bi-unlock"></i> Terbuka</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary mt-2"><i class="bi bi-lock-fill"></i> Terkunci</span>
                            @endif
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
@endif
@endsection

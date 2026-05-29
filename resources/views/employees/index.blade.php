@extends('layouts.app')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('depos.index') }}">Depo</a></li>
        <li class="breadcrumb-item active">{{ $depo->name }}</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-folder-fill text-warning me-2"></i>{{ $depo->name }}</h4>
    <form action="{{ route('depos.destroy', $depo) }}" method="POST" onsubmit="return confirm('Hapus depo ini beserta semua karyawan & file di dalamnya?');">
        @csrf @method('DELETE')
        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i>Hapus Depo</button>
    </form>
</div>

<div class="card mb-3 border-primary-subtle">
    <div class="card-body">
        <label class="form-label fw-semibold mb-1"><i class="bi bi-link-45deg me-1"></i>Link pendaftaran publik</label>
        <p class="text-muted small mb-2">Bagikan link ini ke calon karyawan agar mereka bisa mendaftar sendiri (tanpa password).</p>
        <div class="input-group">
            <input type="text" class="form-control" id="regLink" value="{{ route('employees.register', $depo) }}" readonly>
            <button class="btn btn-outline-primary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('regLink').value); this.innerHTML='<i class=&quot;bi bi-check2&quot;></i> Tersalin'">
                <i class="bi bi-clipboard"></i> Salin
            </button>
            <a href="{{ route('employees.register', $depo) }}" target="_blank" class="btn btn-outline-secondary"><i class="bi bi-box-arrow-up-right"></i> Buka</a>
        </div>
    </div>
</div>

<div class="input-group mb-2">
    <span class="input-group-text"><i class="bi bi-search"></i></span>
    <input type="text" id="searchInput" class="form-control" placeholder="Cari nama, no KTP, no KK, HP, atau email...">
    <button type="button" id="clearSearch" class="btn btn-outline-secondary d-none"><i class="bi bi-x-lg"></i></button>
</div>

@php $filters = ['semua' => 'Semua', 'hari_ini' => 'Hari ini', 'minggu_ini' => 'Minggu ini', 'bulan_ini' => 'Bulan ini']; @endphp
<div class="d-flex gap-1 mb-3">
    @foreach($filters as $key => $label)
        <a href="{{ route('depos.show', $depo) }}?filter={{ $key }}"
           class="btn btn-sm {{ $filter === $key ? 'btn-primary' : 'btn-outline-secondary' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

@if($employees->isEmpty())
    <div class="card"><div class="card-body text-center text-muted py-5">
        <i class="bi bi-people fs-1 d-block mb-2"></i>
        @if($filter !== 'semua')
            Tidak ada karyawan yang mendaftar pada periode <strong>{{ $filters[$filter] }}</strong>.
        @else
            Belum ada karyawan di depo ini.
        @endif
    </div></div>
@else
    <div class="card"><div class="card-body p-0">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr>
                <th class="ps-3">Nama</th>
                <th>No. KTP</th>
                <th>No. HP</th>
                <th>Terdaftar</th>
                <th class="text-center">Berkas</th>
                <th></th>
            </tr></thead>
            <tbody>
                @foreach($employees as $emp)
                    <tr class="emp-row" data-search="{{ strtolower($emp->name.' '.$emp->ktp_number.' '.$emp->kk_number.' '.$emp->phone.' '.$emp->email) }}">
                        <td class="ps-3"><a href="{{ route('employees.show', $emp) }}" class="fw-semibold text-decoration-none">{{ $emp->name }}</a></td>
                        <td>{{ $emp->ktp_number }}</td>
                        <td>{{ $emp->phone }}</td>
                        <td class="text-nowrap small text-muted">{{ $emp->created_at->timezone('Asia/Jakarta')->format('d M Y H:i') }}</td>
                        <td class="text-center">
                            @foreach(['ktp' => 'KTP', 'kk' => 'KK', 'penjamin' => 'Penjamin'] as $type => $label)
                                @if($emp->fileOfType($type))
                                    <span class="badge bg-success-subtle text-success">{{ $label }}</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">{{ $label }}</span>
                                @endif
                            @endforeach
                        </td>
                        <td class="text-end pe-3">
                            <a href="{{ route('employees.show', $emp) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                @endforeach
                <tr id="noSearchResult" class="d-none">
                    <td colspan="6" class="text-center text-muted py-4">Tidak ada karyawan yang cocok.</td>
                </tr>
            </tbody>
        </table>
    </div></div>
@endif
@push('scripts')
<script>
    const input     = document.getElementById('searchInput');
    const clearBtn  = document.getElementById('clearSearch');
    const rows      = document.querySelectorAll('tr.emp-row');
    const noResult  = document.getElementById('noSearchResult');

    if (input) {
        input.addEventListener('input', filter);
        clearBtn.addEventListener('click', () => { input.value = ''; filter(); input.focus(); });
    }

    function filter() {
        const q = input.value.toLowerCase().trim();
        clearBtn.classList.toggle('d-none', !q);
        let visible = 0;
        rows.forEach(row => {
            const match = !q || row.dataset.search.includes(q);
            row.classList.toggle('d-none', !match);
            if (match) visible++;
        });
        if (noResult) noResult.classList.toggle('d-none', !q || visible > 0);
    }
</script>
@endpush
@endsection

@extends('layouts.app')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('vehicles.index') }}">Data Mobil</a></li>
        <li class="breadcrumb-item active">Tambah Mobil</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h5 class="mb-3"><i class="bi bi-truck me-2"></i>Tambah Data Mobil</h5>

                <form action="{{ route('vehicles.store') }}" method="POST" enctype="multipart/form-data" onsubmit="return document.getElementById('mobil_id').value !== '' || (alert('Pilih mobil terlebih dahulu dari hasil pencarian.'), false);">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Cari Mobil (No Polisi / Kode Mobil)</label>
                        <input type="text" id="mobilSearch" class="form-control" placeholder="Ketik minimal 2 karakter, mis. B 1180 atau MBL-000252" autocomplete="off">
                        <div id="mobilResults" class="list-group mt-1"></div>
                        @error('mobil_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div id="mobilSelected" class="alert alert-success d-none">
                        <i class="bi bi-check-circle me-1"></i>
                        Mobil terpilih: <strong id="mobilSelectedLabel"></strong>
                        <button type="button" class="btn btn-sm btn-outline-secondary float-end" onclick="clearMobilSelection()">Ganti</button>
                    </div>

                    <input type="hidden" name="mobil_id" id="mobil_id" value="{{ old('mobil_id') }}">
                    <input type="hidden" name="kode_mobil" id="kode_mobil" value="{{ old('kode_mobil') }}">
                    <input type="hidden" name="no_polisi" id="no_polisi" value="{{ old('no_polisi') }}">
                    <input type="hidden" name="kode_depo" id="kode_depo" value="{{ old('kode_depo') }}">

                    <hr>
                    <p class="text-muted small mb-3">Unggah berkas (PDF/JPG/PNG, maks 20 MB).</p>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Upload Barcode</label>
                            <input type="file" name="barcode" class="form-control @error('barcode') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png" required>
                            @error('barcode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3"></div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Upload STNK</label>
                            <input type="file" name="stnk" class="form-control @error('stnk') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png" required>
                            @error('stnk')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Jatuh Tempo STNK</label>
                            <input type="date" name="tanggal_jatuh_tempo_stnk" class="form-control @error('tanggal_jatuh_tempo_stnk') is-invalid @enderror" value="{{ old('tanggal_jatuh_tempo_stnk') }}" required>
                            @error('tanggal_jatuh_tempo_stnk')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Upload KIR</label>
                            <input type="file" name="kir" class="form-control @error('kir') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png" required>
                            @error('kir')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Jatuh Tempo KIR</label>
                            <input type="date" name="tanggal_jatuh_tempo_kir" class="form-control @error('tanggal_jatuh_tempo_kir') is-invalid @enderror" value="{{ old('tanggal_jatuh_tempo_kir') }}" required>
                            @error('tanggal_jatuh_tempo_kir')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Upload Pajak</label>
                            <input type="file" name="pajak" class="form-control @error('pajak') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png" required>
                            @error('pajak')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Jatuh Tempo Pajak</label>
                            <input type="date" name="tanggal_jatuh_tempo_pajak" class="form-control @error('tanggal_jatuh_tempo_pajak') is-invalid @enderror" value="{{ old('tanggal_jatuh_tempo_pajak') }}" required>
                            @error('tanggal_jatuh_tempo_pajak')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
    var searchInput = document.getElementById('mobilSearch');
    var resultsBox = document.getElementById('mobilResults');
    var selectedBox = document.getElementById('mobilSelected');
    var selectedLabel = document.getElementById('mobilSelectedLabel');
    var timer = null;

    searchInput.addEventListener('input', function(){
        var q = searchInput.value.trim();
        resultsBox.innerHTML = '';
        clearTimeout(timer);
        if (q.length < 2) return;

        timer = setTimeout(function(){
            fetch("{{ route('vehicles.searchMobil') }}?q=" + encodeURIComponent(q))
                .then(function(r){ return r.json(); })
                .then(function(items){
                    resultsBox.innerHTML = '';
                    if (items.length === 0) {
                        resultsBox.innerHTML = '<div class="list-group-item text-muted small">Tidak ditemukan.</div>';
                        return;
                    }
                    items.forEach(function(item){
                        var btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'list-group-item list-group-item-action';
                        btn.innerHTML = '<strong>' + item.no_polisi + '</strong> &middot; ' + item.kode_mobil + (item.kode_depo ? ' &middot; Depo ' + item.kode_depo : '');
                        btn.onclick = function(){ selectMobil(item); };
                        resultsBox.appendChild(btn);
                    });
                });
        }, 300);
    });

    window.selectMobil = function(item){
        document.getElementById('mobil_id').value = item.mobil_id;
        document.getElementById('kode_mobil').value = item.kode_mobil;
        document.getElementById('no_polisi').value = item.no_polisi;
        document.getElementById('kode_depo').value = item.kode_depo || '';
        selectedLabel.textContent = item.no_polisi + ' (' + item.kode_mobil + ')';
        selectedBox.classList.remove('d-none');
        searchInput.value = '';
        resultsBox.innerHTML = '';
    };

    window.clearMobilSelection = function(){
        document.getElementById('mobil_id').value = '';
        document.getElementById('kode_mobil').value = '';
        document.getElementById('no_polisi').value = '';
        document.getElementById('kode_depo').value = '';
        selectedBox.classList.add('d-none');
    };
})();
</script>
@endpush

@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-9">
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="mb-3"><i class="bi bi-truck me-2"></i>Tambah / Update Dokumen Mobil</h5>
                <p class="text-muted small">Cari mobil, lalu isi dokumen yang mau ditambah/diganti. Tiap dokumen bisa diisi satu-satu — tidak perlu isi semuanya sekaligus. Setiap kali diganti, versi lama tetap tersimpan sebagai riwayat.</p>

                <div class="mb-3">
                    <label class="form-label">Cari Mobil (No Polisi / Kode Mobil)</label>
                    <input type="text" id="mobilSearch" class="form-control" placeholder="Ketik minimal 2 karakter, mis. B 1180 atau MBL-000252" autocomplete="off">
                    <div id="mobilResults" class="list-group mt-1"></div>
                </div>

                <div id="mobilSelected" class="alert alert-success d-none">
                    <i class="bi bi-check-circle me-1"></i>
                    Mobil terpilih: <strong id="mobilSelectedLabel"></strong>
                    <button type="button" class="btn btn-sm btn-outline-secondary float-end" onclick="clearMobilSelection()">Ganti Mobil</button>
                </div>
            </div>
        </div>

        <div id="documentForms" class="d-none">
            @php($docTypes = ['barcode' => ['label' => 'Barcode', 'hasDate' => false], 'stnk' => ['label' => 'STNK', 'hasDate' => true], 'kir' => ['label' => 'KIR', 'hasDate' => true], 'pajak' => ['label' => 'Pajak', 'hasDate' => true]])
            <div class="row g-3">
                @foreach($docTypes as $type => $meta)
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="mb-2">{{ $meta['label'] }}</h6>
                                <div class="small text-muted mb-2" id="current-{{ $type }}">Belum ada data.</div>
                                <form class="doc-form" data-type="{{ $type }}" data-has-date="{{ $meta['hasDate'] ? '1' : '0' }}">
                                    @csrf
                                    <div class="mb-2">
                                        <label class="form-label small">Upload {{ $meta['label'] }}</label>
                                        <input type="file" name="file" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required>
                                    </div>
                                    @if($meta['hasDate'])
                                        <div class="mb-2">
                                            <label class="form-label small">Tanggal Jatuh Tempo</label>
                                            <input type="date" name="expiry_date" class="form-control form-control-sm" required>
                                        </div>
                                    @endif
                                    <div class="invalid-feedback-box text-danger small mb-2 d-none"></div>
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="bi bi-upload me-1"></i>Simpan {{ $meta['label'] }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
    var token = @json($token);
    var searchUrl = @json(route('vehicles.searchMobil', $token));
    var statusUrlBase = @json(route('vehicles.documentStatus', [$token, '__MOBIL_ID__']));
    var saveUrl = @json(route('vehicles.saveDocument', $token));

    var searchInput = document.getElementById('mobilSearch');
    var resultsBox = document.getElementById('mobilResults');
    var selectedBox = document.getElementById('mobilSelected');
    var selectedLabel = document.getElementById('mobilSelectedLabel');
    var documentForms = document.getElementById('documentForms');
    var selected = null;
    var timer = null;

    searchInput.addEventListener('input', function(){
        var q = searchInput.value.trim();
        resultsBox.innerHTML = '';
        clearTimeout(timer);
        if (q.length < 2) return;

        timer = setTimeout(function(){
            fetch(searchUrl + '?q=' + encodeURIComponent(q))
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
                        var trackedBadge = item.tracked ? ' <span class="badge bg-info-subtle text-info">Sudah tercatat</span>' : '';
                        btn.innerHTML = '<strong>' + item.no_polisi + '</strong> &middot; ' + item.kode_mobil + (item.kode_depo ? ' &middot; Depo ' + item.kode_depo : '') + trackedBadge;
                        btn.onclick = function(){ selectMobil(item); };
                        resultsBox.appendChild(btn);
                    });
                });
        }, 300);
    });

    function selectMobil(item){
        selected = item;
        selectedLabel.textContent = item.no_polisi + ' (' + item.kode_mobil + ')';
        selectedBox.classList.remove('d-none');
        searchInput.value = '';
        resultsBox.innerHTML = '';
        documentForms.classList.remove('d-none');
        document.querySelectorAll('.doc-form').forEach(resetDocForm);
        loadDocumentStatus(item.mobil_id);
    }

    window.clearMobilSelection = function(){
        selected = null;
        selectedBox.classList.add('d-none');
        documentForms.classList.add('d-none');
    };

    function loadDocumentStatus(mobilId){
        var url = statusUrlBase.replace('__MOBIL_ID__', mobilId);
        fetch(url)
            .then(function(r){ return r.json(); })
            .then(function(status){
                Object.keys(status).forEach(function(type){
                    var el = document.getElementById('current-' + type);
                    var doc = status[type];
                    if (!doc) {
                        el.textContent = 'Belum ada data.';
                        return;
                    }
                    var text = 'Saat ini: ' + doc.original_filename + ' (upload ' + doc.uploaded_at + ')';
                    if (doc.expiry_date) {
                        text += ' — jatuh tempo ' + doc.expiry_date;
                    }
                    el.textContent = text;
                });
            });
    }

    document.querySelectorAll('.doc-form').forEach(function(form){
        form.addEventListener('submit', function(e){
            e.preventDefault();
            if (!selected) {
                alert('Pilih mobil terlebih dahulu.');
                return;
            }

            var type = form.dataset.type;
            var errorBox = form.querySelector('.invalid-feedback-box');
            errorBox.classList.add('d-none');
            errorBox.textContent = '';

            var formData = new FormData(form);
            formData.append('mobil_id', selected.mobil_id);
            formData.append('kode_mobil', selected.kode_mobil);
            formData.append('no_polisi', selected.no_polisi);
            formData.append('kode_depo', selected.kode_depo || '');
            formData.append('type', type);

            var submitBtn = form.querySelector('button[type=submit]');
            submitBtn.disabled = true;

            fetch(saveUrl, {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: formData,
            })
            .then(function(r){ return r.json().then(function(body){ return { ok: r.ok, body: body }; }); })
            .then(function(result){
                submitBtn.disabled = false;
                if (!result.ok) {
                    var messages = result.body.errors ? Object.values(result.body.errors).flat().join(' ') : (result.body.message || 'Gagal menyimpan.');
                    errorBox.textContent = messages;
                    errorBox.classList.remove('d-none');
                    return;
                }
                selected.tracked = true;
                resetDocForm(form);
                loadDocumentStatus(selected.mobil_id);
            })
            .catch(function(){
                submitBtn.disabled = false;
                errorBox.textContent = 'Terjadi kesalahan jaringan, coba lagi.';
                errorBox.classList.remove('d-none');
            });
        });
    });

    function resetDocForm(form){
        form.reset();
        var errorBox = form.querySelector('.invalid-feedback-box');
        errorBox.classList.add('d-none');
        errorBox.textContent = '';
    }
})();
</script>
@endpush

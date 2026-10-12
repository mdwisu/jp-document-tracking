@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-9">
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="mb-3"><i class="bi bi-truck me-2"></i>Tambah / Update Dokumen Mobil</h5>
                <p class="text-muted small">Lengkapi 8 foto tampak luar dan simpan sekaligus. Barcode, STNK, KIR, dan pajak bisa di-upload terpisah satu per satu. Maksimal 20 MB per berkas dan 90 MB untuk paket foto.</p>

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

        <form id="documentForms" class="d-none" enctype="multipart/form-data">
            @csrf
            <div class="card"><div class="card-body">
            @php($docTypes = ['foto_depan' => ['label' => 'Tampak Depan', 'hasDate' => false], 'foto_belakang' => ['label' => 'Tampak Belakang', 'hasDate' => false], 'foto_kiri' => ['label' => 'Tampak Kiri', 'hasDate' => false], 'foto_kanan' => ['label' => 'Tampak Kanan', 'hasDate' => false], 'foto_depan_kiri' => ['label' => 'Serong Depan Kiri', 'hasDate' => false], 'foto_depan_kanan' => ['label' => 'Serong Depan Kanan', 'hasDate' => false], 'foto_belakang_kiri' => ['label' => 'Serong Belakang Kiri', 'hasDate' => false], 'foto_belakang_kanan' => ['label' => 'Serong Belakang Kanan', 'hasDate' => false], 'barcode' => ['label' => 'Barcode', 'hasDate' => false], 'stnk' => ['label' => 'STNK', 'hasDate' => true, 'period' => 'Masa berlaku STNK 5 tahun', 'example' => 'images/examples/stnk.png'], 'kir' => ['label' => 'KIR', 'hasDate' => true, 'example' => 'images/examples/kir.png'], 'pajak' => ['label' => 'Pajak', 'hasDate' => true, 'period' => 'Masa berlaku Pajak 1 tahun', 'example' => 'images/examples/pajak.png']])
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">Foto Tampak Luar</h6>
                <button type="button" class="btn btn-link btn-sm" data-bs-toggle="modal" data-bs-target="#vehiclePhotoExamplesModal">Lihat contoh foto</button>
            </div>
            <p class="small text-muted">Seluruh mobil harus terlihat. Kiri/kanan mengikuti posisi pengemudi saat menghadap ke depan.</p>
            @foreach(array_filter($docTypes, fn ($meta, $type) => str_starts_with($type, 'foto_'), ARRAY_FILTER_USE_BOTH) as $type => $meta)
                @if($type === 'barcode')
                    <h6 class="mt-4 mb-3">Dokumen Mobil</h6>
                @endif
                <div class="row align-items-start g-2 mb-3">
                    <div class="col-md-3">
                        <label for="upload-{{ $type }}" class="form-label mb-1">{{ $meta['label'] }} <span class="text-danger">*</span></label>
                        @if(!empty($meta['example']))
                            <button type="button" class="btn btn-link btn-sm p-0 d-block" data-bs-toggle="modal" data-bs-target="#exampleModal-{{ $type }}">Lihat contoh</button>
                        @endif
                    </div>
                    <div class="{{ $meta['hasDate'] ? 'col-md-5' : 'col-md-9' }}">
                        <input id="upload-{{ $type }}" type="file" name="files[{{ $type }}]" class="form-control form-control-sm" accept="{{ str_starts_with($type, 'foto_') ? '.jpg,.jpeg,.png' : '.pdf,.jpg,.jpeg,.png' }}" required>
                        <div class="small text-muted mt-1" id="current-{{ $type }}">Belum ada data.</div>
                    </div>
                    @if($meta['hasDate'])
                        <div class="col-md-4">
                            <label for="expiry-{{ $type }}" class="visually-hidden">Tanggal Jatuh Tempo {{ $meta['label'] }}</label>
                            <input id="expiry-{{ $type }}" type="date" name="expiry_dates[{{ $type }}]" class="form-control form-control-sm" aria-label="Tanggal Jatuh Tempo {{ $meta['label'] }}" required>
                            <div class="form-text">Jatuh tempo{{ !empty($meta['period']) ? ' · '.$meta['period'] : '' }}</div>
                        </div>
                    @endif
                </div>
            @endforeach
            <div class="invalid-feedback-box alert alert-danger mt-3 d-none" role="alert"></div>
            <div id="saveSuccess" class="alert alert-success mt-3 d-none" role="status"></div>
            <div class="d-flex align-items-center gap-3 mt-3">
                <button type="submit" class="btn btn-primary" id="saveAll" disabled><i class="bi bi-upload me-1"></i>Simpan Foto Mobil</button>
                <small class="text-muted" id="completionStatus">Lengkapi seluruh isian untuk menyimpan.</small>
            </div>
            </div></div>
        </form>
        <div id="documentCards" class="row g-3 mt-1 d-none">
            @foreach(array_filter($docTypes, fn ($meta, $type) => !str_starts_with($type, 'foto_'), ARRAY_FILTER_USE_BOTH) as $type => $meta)
                <div class="col-md-6">
                    <div class="card h-100"><div class="card-body">
                        <h6>{{ $meta['label'] }}
                            @if(!empty($meta['example']))
                                <button type="button" class="btn btn-link btn-sm" data-bs-toggle="modal" data-bs-target="#exampleModal-{{ $type }}">Lihat contoh</button>
                            @endif
                        </h6>
                        <div class="small text-muted mb-2" id="current-{{ $type }}">Belum ada data.</div>
                        <form class="single-doc-form" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="type" value="{{ $type }}">
                            <label class="form-label small" for="doc-{{ $type }}">Upload {{ $meta['label'] }}</label>
                            <input id="doc-{{ $type }}" type="file" name="file" class="form-control form-control-sm mb-2" accept=".pdf,.jpg,.jpeg,.png" required>
                            @if($meta['hasDate'])
                                <label for="date-{{ $type }}" class="form-label small">Tanggal Jatuh Tempo</label>
                                <input id="date-{{ $type }}" type="date" name="expiry_date" class="form-control form-control-sm mb-2" required>
                                @if(!empty($meta['period']))<p class="form-text">{{ $meta['period'] }}</p>@endif
                            @endif
                            <div class="invalid-feedback-box alert alert-danger d-none" role="alert"></div>
                            <div class="doc-success alert alert-success d-none" role="status"></div>
                            <button type="submit" class="btn btn-primary btn-sm">Simpan {{ $meta['label'] }}</button>
                        </form>
                    </div></div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@foreach($docTypes as $type => $meta)
    @if(!empty($meta['example']))
        <div class="modal fade" id="exampleModal-{{ $type }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="modal-title">Contoh {{ $meta['label'] }} &amp; Letak Tanggal Jatuh Tempo</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img src="{{ asset($meta['example']) }}" alt="Contoh {{ $meta['label'] }}" class="img-fluid rounded border">
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach
<div class="modal fade" id="vehiclePhotoExamplesModal" tabindex="-1" aria-labelledby="vehiclePhotoExamplesLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="vehiclePhotoExamplesLabel">Contoh 8 Tampak Luar Mobil</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">Ilustrasi posisi foto. Gunakan foto mobil sebenarnya; seluruh kendaraan harus terlihat. Kiri/kanan mengikuti posisi pengemudi saat menghadap ke depan.</p>
                <img src="{{ asset('images/examples/tampak-luar-mobil.png') }}" alt="Contoh depan, belakang, kiri, kanan, serong depan kiri dan kanan, serta serong belakang kiri dan kanan" class="img-fluid rounded border" loading="lazy">
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
    var saveUrl = @json(route('vehicles.savePhotos', $token));
    var singleSaveUrl = @json(route('vehicles.saveDocument', $token));

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
                        btn.innerHTML = '<strong>' + item.no_polisi + '</strong> &middot; ' + item.kode_mobil + (item.nama_depo ? ' &middot; Depo ' + item.nama_depo : '') + trackedBadge;
                        btn.onclick = function(){ selectMobil(item); };
                        resultsBox.appendChild(btn);
                    });
                });
        }, 300);
    });

    function selectMobil(item){
        if (saving) return;
        selected = item;
        selectedLabel.textContent = item.no_polisi + ' (' + item.kode_mobil + ')';
        selectedBox.classList.remove('d-none');
        searchInput.value = '';
        resultsBox.innerHTML = '';
        documentForms.classList.remove('d-none');
        document.getElementById('documentCards').classList.remove('d-none');
        document.querySelectorAll('.single-doc-form').forEach(function(form){ form.reset(); form.querySelector('.invalid-feedback-box').classList.add('d-none'); form.querySelector('.doc-success').classList.add('d-none'); });
        resetDocForm(documentForms);
        updateCompletion();
        loadDocumentStatus(item.mobil_id);
    }

    window.clearMobilSelection = function(){
        if (saving) return;
        selected = null;
        selectedBox.classList.add('d-none');
        documentForms.classList.add('d-none');
        document.getElementById('documentCards').classList.add('d-none');
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

    var submitBtn = document.getElementById('saveAll');
    var saving = false;
    function updateCompletion(){
        var inputs = Array.from(documentForms.querySelectorAll('input[required]'));
        var filled = inputs.filter(function(input){ return input.type === 'file' ? input.files.length > 0 : !!input.value; }).length;
        var totalSize = 0;
        inputs.filter(function(input){ return input.type === 'file'; }).forEach(function(input){
            var file = input.files[0];
            input.setCustomValidity(file && file.size > 20 * 1024 * 1024 ? 'Maksimal 20 MB per berkas.' : '');
            if (file) totalSize += file.size;
        });
        submitBtn.disabled = saving || !selected || totalSize > 90 * 1024 * 1024 || !documentForms.checkValidity();
        document.getElementById('completionStatus').textContent = filled + '/' + inputs.length + ' isian lengkap' + (totalSize > 90 * 1024 * 1024 ? ' — total berkas melebihi 90 MB' : '');
    }
    documentForms.addEventListener('input', updateCompletion);
    documentForms.addEventListener('change', updateCompletion);
    documentForms.addEventListener('submit', function(e){
        e.preventDefault();
        if (saving || !selected || !documentForms.reportValidity()) return;
        if (!window.confirm('Simpan seluruh 8 foto tampak luar mobil?')) return;
        var submittedMobilId = selected.mobil_id;
        var errorBox = documentForms.querySelector('.invalid-feedback-box');
        var successBox = document.getElementById('saveSuccess');
        errorBox.classList.add('d-none');
        successBox.classList.add('d-none');
        var formData = new FormData(documentForms);
        formData.append('mobil_id', submittedMobilId);
        saving = true;
        submitBtn.textContent = 'Menyimpan...';
        document.querySelectorAll('button[onclick="clearMobilSelection()"], #mobilSearch').forEach(function(el){ el.disabled = true; });
        updateCompletion();
        fetch(saveUrl, { method: 'POST', headers: { 'Accept': 'application/json' }, body: formData })
            .then(function(r){ return r.json().then(function(body){ return { ok: r.ok, body: body }; }); })
            .then(function(result){
                if (!result.ok) {
                    errorBox.textContent = result.body.errors ? Object.values(result.body.errors).flat().join(' ') : (result.body.message || 'Gagal menyimpan.');
                    errorBox.classList.remove('d-none');
                    return;
                }
                resetDocForm(documentForms);
                successBox.textContent = result.body.message;
                successBox.classList.remove('d-none');
                loadDocumentStatus(submittedMobilId);
            })
            .catch(function(){
                errorBox.textContent = 'Gagal mengirim berkas. Periksa koneksi dan ukuran total upload, lalu coba lagi.';
                errorBox.classList.remove('d-none');
            })
            .finally(function(){
                saving = false;
                submitBtn.textContent = 'Simpan Foto Mobil';
                document.querySelectorAll('button[onclick="clearMobilSelection()"], #mobilSearch').forEach(function(el){ el.disabled = false; });
                updateCompletion();
            });
    });

    document.querySelectorAll('.single-doc-form').forEach(function(form){
        form.addEventListener('submit', function(e){
            e.preventDefault();
            if (saving || !selected || !form.reportValidity()) return;
            if (!confirm('Simpan dokumen ini?')) return;
            var mobilId = selected.mobil_id;
            var data = new FormData(form);
            data.append('mobil_id', mobilId);
            var button = form.querySelector('button[type=submit]');
            var error = form.querySelector('.invalid-feedback-box');
            var success = form.querySelector('.doc-success');
            error.classList.add('d-none');
            success.classList.add('d-none');
            saving = true;
            button.disabled = true;
            updateCompletion();
            document.getElementById('mobilSearch').disabled = true;
            fetch(singleSaveUrl, {method: 'POST', headers: {'Accept': 'application/json'}, body: data})
                .then(function(r){ return r.json().then(function(body){ if (!r.ok) throw new Error(body.errors ? Object.values(body.errors).flat().join(' ') : body.message); return body; }); })
                .then(function(body){ form.reset(); success.textContent = body.message; success.classList.remove('d-none'); loadDocumentStatus(mobilId); })
                .catch(function(e){ error.textContent = e.message || 'Gagal menyimpan dokumen.'; error.classList.remove('d-none'); })
                .finally(function(){ saving = false; button.disabled = false; document.getElementById('mobilSearch').disabled = false; updateCompletion(); });
        });
    });

    function resetDocForm(form){
        form.reset();
        document.getElementById('saveSuccess').classList.add('d-none');
        var errorBox = form.querySelector('.invalid-feedback-box');
        errorBox.classList.add('d-none');
        errorBox.textContent = '';
    }
})();
</script>
@endpush

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
                                    <div class="d-inline-flex gap-2 flex-nowrap">
                                        <button class="btn btn-sm btn-outline-secondary" onclick="previewFile('{{ route('files.preview', $f) }}', '{{ $f->original_filename }}')"><i class="bi bi-eye"></i></button>
                                        <a href="{{ route('files.download', $f) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></a>
                                    </div>
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

@push('scripts')
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title" id="previewModalLabel">Preview</h6>
                <div class="ms-auto me-2" id="rotateControls">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="rotateImg(-90)" title="Putar kiri"><i class="bi bi-arrow-counterclockwise"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="rotateImg(90)" title="Putar kanan"><i class="bi bi-arrow-clockwise"></i></button>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 d-flex align-items-center justify-content-center overflow-hidden bg-dark" id="previewBody">
                <img id="previewImg" src="" class="d-none" style="max-width:100%;max-height:100%;object-fit:contain;transform-origin:0 0;">
                <iframe id="previewFrame" src="" class="d-none" style="width:100%;height:100%;border:none;"></iframe>
            </div>
        </div>
    </div>
</div>
<script>
(function(){
    var scale = 1, posX = 0, posY = 0, rotation = 0, layoutX = 0, layoutY = 0;
    var img = document.getElementById('previewImg');
    var body = document.getElementById('previewBody');
    var rotateControls = document.getElementById('rotateControls');

    function applyTransform(){
        img.style.transform = 'translate('+posX+'px,'+posY+'px) scale('+scale+') rotate('+rotation+'deg)';
    }
    // capture the screen position of the image's origin (content 0,0) at base state
    function captureLayout(){
        scale=1; posX=0; posY=0; rotation=0; img.style.transform='none';
        var r = img.getBoundingClientRect();
        layoutX = r.left; layoutY = r.top;
    }
    function resetZoom(){ scale=1; posX=0; posY=0; rotation=0; img.style.transform='none'; }

    window.rotateImg = function(deg){
        if(img.classList.contains('d-none')) return;
        rotation = (rotation + deg) % 360;
        scale=1; posX=0; posY=0; applyTransform();
        // recenter after rotation
        var b = body.getBoundingClientRect(), r = img.getBoundingClientRect();
        posX = (b.width - r.width)/2 - (r.left - b.left);
        posY = (b.height - r.height)/2 - (r.top - b.top);
        applyTransform();
    };

    body.addEventListener('wheel', function(e){
        if(img.classList.contains('d-none')) return;
        e.preventDefault();

        var newScale = Math.min(Math.max(1, scale * Math.exp(-e.deltaY * 0.002)), 10);
        if(newScale === scale) return;
        if(newScale === 1){ scale=1; posX=0; posY=0; applyTransform(); if(rotation){ rotateImg(0); } return; }

        var ratio = newScale / scale;
        // pivot zoom around transform-origin point (invariant to rotation/scale)
        var qx = e.clientX - layoutX, qy = e.clientY - layoutY;
        posX = qx - ratio * (qx - posX);
        posY = qy - ratio * (qy - posY);
        scale = newScale;
        applyTransform();
    }, {passive:false});

    // Pan with mouse drag
    var dragging=false, startX, startY, startPosX, startPosY;
    body.addEventListener('mousedown', function(e){
        if(scale>1 && !img.classList.contains('d-none')){
            dragging=true; startX=e.clientX; startY=e.clientY;
            startPosX=posX; startPosY=posY; e.preventDefault();
            body.style.cursor='grabbing';
        }
    });
    document.addEventListener('mousemove', function(e){
        if(dragging){ posX=startPosX+(e.clientX-startX); posY=startPosY+(e.clientY-startY); applyTransform(); }
    });
    document.addEventListener('mouseup', function(){ dragging=false; body.style.cursor=''; });

    window.previewFile = function(url, name){
        document.getElementById('previewModalLabel').textContent = name;
        var isImage = /\.(jpg|jpeg|png|gif|webp)$/i.test(name);
        var frame = document.getElementById('previewFrame');
        rotateControls.classList.toggle('d-none', !isImage);
        if(isImage){
            img.onload = captureLayout;
            img.src=url; img.classList.remove('d-none'); frame.classList.add('d-none'); frame.src=''; resetZoom();
        } else {
            frame.src=url; frame.classList.remove('d-none'); img.classList.add('d-none'); img.src='';
        }
        new bootstrap.Modal(document.getElementById('previewModal')).show();
    };
    var modalEl = document.getElementById('previewModal');
    modalEl.addEventListener('shown.bs.modal', function(){
        if(!img.classList.contains('d-none') && img.complete) captureLayout();
    });
    modalEl.addEventListener('hidden.bs.modal', function(){
        document.getElementById('previewFrame').src=''; img.src=''; resetZoom();
    });
})();
</script>
@endpush

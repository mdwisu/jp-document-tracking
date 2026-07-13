@extends('layouts.app')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('vehicles.index') }}">Data Mobil</a></li>
        <li class="breadcrumb-item active">{{ $vehicle->no_polisi }}</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-truck me-2"></i>{{ $vehicle->no_polisi }}</h4>
    <form action="{{ route('vehicles.destroy', $vehicle) }}" method="POST" onsubmit="return confirm('Hapus data mobil ini beserta berkasnya?');">
        @csrf @method('DELETE')
        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i>Hapus</button>
    </form>
</div>

<div class="row g-3">
    <div class="col-md-5">
        <div class="card"><div class="card-body">
            <h6 class="text-muted text-uppercase small mb-3">Data Mobil</h6>
            <dl class="row mb-0">
                <dt class="col-5">Kode Mobil</dt><dd class="col-7">{{ $vehicle->kode_mobil }}</dd>
                <dt class="col-5">No Polisi</dt><dd class="col-7">{{ $vehicle->no_polisi }}</dd>
                <dt class="col-5">Depo</dt><dd class="col-7">{{ $vehicle->kode_depo ?? '-' }}</dd>
            </dl>
        </div></div>
    </div>
    <div class="col-md-7">
        <div class="card"><div class="card-body p-0">
            <table class="table mb-0 align-middle">
                <thead><tr>
                    <th class="ps-3">Berkas</th>
                    <th>Nama File</th>
                    <th>Jatuh Tempo</th>
                    <th>Ukuran</th>
                    <th></th>
                </tr></thead>
                <tbody>
                    @foreach(['barcode' => 'Barcode', 'stnk' => 'STNK', 'kir' => 'KIR', 'pajak' => 'Pajak'] as $type => $label)
                        @php($f = $vehicle->fileOfType($type))
                        @php($expiryField = 'tanggal_jatuh_tempo_' . $type)
                        <tr>
                            <td class="ps-3 fw-semibold">{{ $label }}</td>
                            @if($f)
                                <td>{{ $f->original_filename }}</td>
                                <td>
                                    @if($type === 'barcode')
                                        <span class="text-muted">-</span>
                                    @else
                                        {{ $vehicle->$expiryField?->format('d M Y') ?? '-' }}
                                        @php($status = $vehicle->expiryStatus($expiryField))
                                        @if($status === 'expired')
                                            <span class="badge bg-danger-subtle text-danger ms-1">Kadaluarsa</span>
                                        @elseif($status === 'soon')
                                            <span class="badge bg-warning-subtle text-warning ms-1">Segera Habis</span>
                                        @elseif($status === 'ok')
                                            <span class="badge bg-success-subtle text-success ms-1">Aman</span>
                                        @endif
                                    @endif
                                </td>
                                <td>{{ $f->file_size_formatted }}</td>
                                <td class="text-end pe-3">
                                    <div class="d-inline-flex gap-2 flex-nowrap">
                                        <button class="btn btn-sm btn-outline-secondary" onclick="previewFile('{{ route('vehicleFiles.preview', $f) }}', '{{ $f->original_filename }}')"><i class="bi bi-eye"></i></button>
                                        <a href="{{ route('vehicleFiles.download', $f) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></a>
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
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 d-flex align-items-center justify-content-center overflow-hidden bg-dark" id="previewBody">
                <img id="previewImg" src="" class="d-none" style="max-width:100%;max-height:100%;object-fit:contain;">
                <iframe id="previewFrame" src="" class="d-none" style="width:100%;height:100%;border:none;"></iframe>
            </div>
        </div>
    </div>
</div>
<script>
(function(){
    window.previewFile = function(url, name){
        document.getElementById('previewModalLabel').textContent = name;
        var isImage = /\.(jpg|jpeg|png|gif|webp)$/i.test(name);
        var img = document.getElementById('previewImg');
        var frame = document.getElementById('previewFrame');
        if(isImage){
            img.src=url; img.classList.remove('d-none'); frame.classList.add('d-none'); frame.src='';
        } else {
            frame.src=url; frame.classList.remove('d-none'); img.classList.add('d-none'); img.src='';
        }
        new bootstrap.Modal(document.getElementById('previewModal')).show();
    };
    document.getElementById('previewModal').addEventListener('hidden.bs.modal', function(){
        document.getElementById('previewFrame').src=''; document.getElementById('previewImg').src='';
    });
})();
</script>
@endpush

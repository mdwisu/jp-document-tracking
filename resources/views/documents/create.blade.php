@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white fw-semibold py-3">
                <i class="bi bi-upload me-2 text-primary"></i>Upload Dokumen PDF
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" id="uploadForm">
                    @csrf
                    <input type="hidden" name="file_modified_at" id="fileModifiedAt">

                    <div class="mb-4">
                        <label class="form-label fw-semibold">File PDF <span class="text-danger">*</span></label>
                        <input type="file" name="pdf_file" id="pdfFile" accept=".pdf"
                               class="form-control @error('pdf_file') is-invalid @enderror" required>
                        <div class="form-text">Maksimal 50 MB. Sistem akan membaca metadata dan tanggal OS dari file.</div>
                        @error('pdf_file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div id="fileInfo" class="alert alert-light border d-none mb-4 py-2">
                        <div class="small text-muted mb-1"><i class="bi bi-info-circle me-1"></i>Tanggal OS terdeteksi:</div>
                        <div class="fw-semibold" id="fileModifiedDisplay">—</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Diupload Oleh</label>
                        <input type="text" name="uploaded_by" class="form-control"
                               placeholder="Nama admin / user" value="{{ old('uploaded_by') }}">
                    </div>

                    <div class="alert alert-info small py-2">
                        <i class="bi bi-info-circle me-1"></i>
                        Sistem membaca: <strong>tanggal OS file</strong> (seperti Google Drive) + <strong>metadata internal PDF</strong> (jika tersedia).
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload me-1"></i>Upload
                        </button>
                        <a href="{{ route('documents.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('pdfFile').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;

    // lastModified adalah Unix timestamp dalam milidetik (OS level)
    document.getElementById('fileModifiedAt').value = file.lastModified;

    const date = new Date(file.lastModified);
    const formatted = date.toLocaleString('id-ID', {
        day: '2-digit', month: 'long', year: 'numeric',
        hour: '2-digit', minute: '2-digit', second: '2-digit'
    });

    document.getElementById('fileModifiedDisplay').textContent = formatted;
    document.getElementById('fileInfo').classList.remove('d-none');
});
</script>
@endsection

@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white fw-semibold py-3">
                <i class="bi bi-upload me-2 text-primary"></i>Upload Dokumen PDF
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label fw-semibold">File PDF <span class="text-danger">*</span></label>
                        <input type="file" name="pdf_file" accept=".pdf"
                               class="form-control @error('pdf_file') is-invalid @enderror" required>
                        <div class="form-text">Maksimal 50 MB. Sistem akan otomatis membaca metadata (tanggal dibuat &amp; dimodifikasi) dari file.</div>
                        @error('pdf_file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Diupload Oleh</label>
                        <input type="text" name="uploaded_by" class="form-control"
                               placeholder="Nama admin / user" value="{{ old('uploaded_by') }}">
                    </div>

                    <div class="alert alert-info small py-2">
                        <i class="bi bi-info-circle me-1"></i>
                        Metadata yang akan dibaca dari file PDF: <strong>judul, author, tanggal dibuat, tanggal dimodifikasi</strong>.
                        Tanggal ini berasal dari metadata asli file PDF, bukan tanggal upload.
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
@endsection

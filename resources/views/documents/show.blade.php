@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <span class="fw-semibold">
                    <i class="bi bi-file-earmark-pdf-fill text-danger me-2"></i>{{ $document->original_filename }}
                </span>
                <a href="{{ route('documents.download', $document) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-download me-1"></i>Download
                </a>
            </div>
            <div class="card-body">

                <h6 class="text-muted text-uppercase small mb-3 fw-bold">Metadata PDF</h6>
                <table class="table table-sm table-borderless mb-4">
                    <tr>
                        <td class="text-muted" style="width:40%">Judul</td>
                        <td class="fw-semibold">{{ $document->document_title ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Author</td>
                        <td>{{ $document->document_author ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Creator (aplikasi)</td>
                        <td>{{ $document->document_creator ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Producer</td>
                        <td>{{ $document->document_producer ?? '—' }}</td>
                    </tr>
                </table>

                <h6 class="text-muted text-uppercase small mb-3 fw-bold">Tanggal dari Metadata PDF</h6>
                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="p-3 rounded bg-success-subtle">
                            <div class="small text-muted mb-1"><i class="bi bi-calendar-plus me-1"></i>PDF Created</div>
                            @if($document->pdf_created_at)
                                <div class="fw-bold">{{ $document->pdf_created_at->format('d M Y') }}</div>
                                <div class="small text-muted">{{ $document->pdf_created_at->format('H:i:s') }}</div>
                            @else
                                <div class="text-muted">Tidak tersedia</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 rounded bg-warning-subtle">
                            <div class="small text-muted mb-1"><i class="bi bi-pencil me-1"></i>PDF Modified</div>
                            @if($document->pdf_modified_at)
                                <div class="fw-bold">{{ $document->pdf_modified_at->format('d M Y') }}</div>
                                <div class="small text-muted">{{ $document->pdf_modified_at->format('H:i:s') }}</div>
                            @else
                                <div class="text-muted">Tidak tersedia</div>
                            @endif
                        </div>
                    </div>
                </div>

                <h6 class="text-muted text-uppercase small mb-3 fw-bold">Info Upload</h6>
                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="text-muted" style="width:40%">Ukuran File</td>
                        <td>{{ $document->file_size_formatted }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Diupload Oleh</td>
                        <td>{{ $document->uploaded_by ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tanggal Upload</td>
                        <td>{{ $document->created_at->format('d M Y H:i') }}</td>
                    </tr>
                </table>
            </div>
            <div class="card-footer bg-white text-end">
                <a href="{{ route('documents.index') }}" class="btn btn-sm btn-outline-secondary me-2">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
                <form method="POST" action="{{ route('documents.destroy', $document) }}" class="d-inline"
                      onsubmit="return confirm('Hapus dokumen ini?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-trash me-1"></i>Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

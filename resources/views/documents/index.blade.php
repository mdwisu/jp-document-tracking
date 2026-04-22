@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="bi bi-folder2-open me-2 text-primary"></i>Daftar Dokumen</h5>
    <span class="text-muted small">{{ $documents->total() }} dokumen</span>
</div>

<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('documents.index') }}" class="d-flex gap-2">
            <input type="text" name="search" class="form-control form-control-sm"
                   placeholder="Cari nama file, judul, atau author..." value="{{ request('search') }}">
            <button class="btn btn-outline-primary btn-sm px-3">
                <i class="bi bi-search"></i>
            </button>
            @if(request('search'))
                <a href="{{ route('documents.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            @endif
        </form>
    </div>
</div>

@if($documents->isEmpty())
    <div class="card">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-inbox display-4 d-block mb-3"></i>
            Belum ada dokumen. <a href="{{ route('documents.create') }}">Upload sekarang</a>
        </div>
    </div>
@else
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Nama File</th>
                    <th>Judul / Author</th>
                    <th>File Modified (OS)</th>
                    <th>PDF Created</th>
                    <th>PDF Modified</th>
                    <th>Diupload</th>
                    <th>Ukuran</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($documents as $doc)
                <tr>
                    <td class="text-muted small">{{ $loop->iteration + ($documents->currentPage() - 1) * $documents->perPage() }}</td>
                    <td>
                        <a href="{{ route('documents.show', $doc) }}" class="fw-semibold text-decoration-none">
                            <i class="bi bi-file-earmark-pdf text-danger me-1"></i>{{ $doc->original_filename }}
                        </a>
                    </td>
                    <td>
                        @if($doc->document_title)
                            <span class="d-block small fw-semibold">{{ $doc->document_title }}</span>
                        @endif
                        @if($doc->document_author)
                            <span class="text-muted small"><i class="bi bi-person me-1"></i>{{ $doc->document_author }}</span>
                        @endif
                        @if(!$doc->document_title && !$doc->document_author)
                            <span class="text-muted small">—</span>
                        @endif
                    </td>
                    <td>
                        @if($doc->file_modified_at)
                            <span class="badge bg-primary-subtle text-primary badge-meta">
                                <i class="bi bi-hdd me-1"></i>{{ $doc->file_modified_at->format('d M Y') }}
                            </span>
                            <div class="text-muted small">{{ $doc->file_modified_at->format('H:i') }} WIB</div>
                        @else
                            <span class="text-muted small">—</span>
                        @endif
                    </td>
                    <td>
                        @if($doc->pdf_created_at)
                            <span class="badge bg-success-subtle text-success badge-meta">
                                <i class="bi bi-calendar-plus me-1"></i>{{ $doc->pdf_created_at->format('d M Y') }}
                            </span>
                            <div class="text-muted small">{{ $doc->pdf_created_at->format('H:i') }}</div>
                        @else
                            <span class="text-muted small">—</span>
                        @endif
                    </td>
                    <td>
                        @if($doc->pdf_modified_at)
                            <span class="badge bg-warning-subtle text-warning-emphasis badge-meta">
                                <i class="bi bi-pencil me-1"></i>{{ $doc->pdf_modified_at->format('d M Y') }}
                            </span>
                            <div class="text-muted small">{{ $doc->pdf_modified_at->format('H:i') }}</div>
                        @else
                            <span class="text-muted small">—</span>
                        @endif
                    </td>
                    <td class="small text-muted">{{ $doc->created_at->format('d M Y H:i') }}</td>
                    <td class="small text-muted">{{ $doc->file_size_formatted }}</td>
                    <td class="text-end">
                        <a href="{{ route('documents.download', $doc) }}" class="btn btn-sm btn-outline-secondary" title="Download">
                            <i class="bi bi-download"></i>
                        </a>
                        <form method="POST" action="{{ route('documents.destroy', $doc) }}" class="d-inline"
                              onsubmit="return confirm('Hapus dokumen ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3 d-flex justify-content-center">
    {{ $documents->links() }}
</div>
@endif
@endsection

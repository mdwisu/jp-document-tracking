<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JP Document Tracking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .navbar-brand { font-weight: 700; letter-spacing: 0.5px; }
        .card { border: none; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
        .badge-meta { font-size: 0.78rem; }
        .table th { font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.5px; color: #6c757d; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="{{ route('documents.index') }}">
                <i class="bi bi-file-earmark-pdf-fill me-2"></i>JP Document Tracking
            </a>
            <a href="{{ route('documents.create') }}" class="btn btn-light btn-sm ms-auto">
                <i class="bi bi-upload me-1"></i>Upload PDF
            </a>
        </div>
    </nav>
    <div class="container pb-5">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @yield('content')
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

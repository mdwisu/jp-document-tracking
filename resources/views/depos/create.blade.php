@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <a href="{{ route('depos.index') }}" class="btn btn-link px-0 mb-2"><i class="bi bi-arrow-left"></i> Kembali</a>
        <div class="card">
            <div class="card-body">
                <h5 class="mb-4"><i class="bi bi-folder-plus me-2"></i>Tambah Depo</h5>
                <form action="{{ route('depos.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Nama Depo</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password Depo</label>
                        <input type="password" name="password" class="form-control" minlength="4" required>
                        <div class="form-text">Minimal 4 karakter. Dipakai untuk membuka folder depo ini.</div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Simpan</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

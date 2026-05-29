@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <a href="{{ route('depos.index') }}" class="btn btn-link px-0 mb-2"><i class="bi bi-arrow-left"></i> Kembali</a>
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-shield-lock-fill text-danger" style="font-size:2.5rem;"></i>
                <h5 class="mt-2 mb-1">Sampah (Developer)</h5>
                <p class="text-muted small">Masukkan master password developer untuk mengakses data terhapus.</p>
                <form action="{{ route('trash.unlock') }}" method="POST" class="text-start">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Master Password</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" autofocus required>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-danger w-100"><i class="bi bi-unlock me-1"></i>Buka Sampah</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

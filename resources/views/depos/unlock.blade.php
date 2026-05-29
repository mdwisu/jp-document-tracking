@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <a href="{{ route('depos.index') }}" class="btn btn-link px-0 mb-2"><i class="bi bi-arrow-left"></i> Kembali</a>
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-lock-fill text-warning" style="font-size:2.5rem;"></i>
                <h5 class="mt-2 mb-1">{{ $depo->name }}</h5>
                <p class="text-muted small">Masukkan password untuk membuka folder depo ini.</p>
                <form action="{{ route('depos.unlock', $depo) }}" method="POST" class="text-start">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" autofocus required>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-unlock me-1"></i>Buka</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

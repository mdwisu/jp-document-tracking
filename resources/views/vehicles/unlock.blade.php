@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-lock-fill text-warning" style="font-size:2.5rem;"></i>
                <h5 class="mt-2 mb-1">Data Mobil</h5>
                <p class="text-muted small">Masukkan password untuk membuka modul data mobil.</p>
                <form action="{{ route('vehicles.unlock') }}" method="POST" class="text-start">
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

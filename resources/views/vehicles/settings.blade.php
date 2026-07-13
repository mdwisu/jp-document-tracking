@extends('layouts.app')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('vehicles.index') }}">Data Mobil</a></li>
        <li class="breadcrumb-item active">Pengaturan</li>
    </ol>
</nav>

<div class="row justify-content-center g-3">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small mb-3"><i class="bi bi-link-45deg me-1"></i>Link Tambah Mobil</h6>
                <p class="text-muted small">Bagikan link ini ke staf logistik supaya mereka bisa input data mobil sendiri tanpa perlu password.</p>
                <div class="input-group mb-2">
                    <input type="text" id="createLink" class="form-control" value="{{ route('vehicles.create', $createToken) }}" readonly>
                    <button type="button" class="btn btn-outline-secondary" onclick="copyCreateLink()"><i class="bi bi-clipboard me-1"></i>Salin</button>
                </div>
                <form action="{{ route('vehicles.regenerateToken') }}" method="POST" onsubmit="return confirm('Yakin generate ulang link? Link lama tidak akan berlaku lagi.');">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-arrow-repeat me-1"></i>Generate Ulang Link</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small mb-3"><i class="bi bi-key me-1"></i>Ganti Password</h6>
                <form action="{{ route('vehicles.updatePassword') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Password Lama</label>
                        <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password Baru</label>
                        <input type="password" name="new_password" class="form-control @error('new_password') is-invalid @enderror" minlength="4" required>
                        @error('new_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" name="new_password_confirmation" class="form-control" minlength="4" required>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan Password Baru</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function copyCreateLink(){
    var el = document.getElementById('createLink');
    el.select();
    navigator.clipboard.writeText(el.value);
}
</script>
@endpush

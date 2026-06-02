@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h5 class="mb-1"><i class="bi bi-person-plus me-2"></i>Pendaftaran Karyawan</h5>
                <p class="text-muted mb-4">Depo: <span class="fw-semibold">{{ $depo->name }}</span></p>
                <form action="{{ route('employees.store', $depo) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Nama sesuai KTP</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nomor KTP</label>
                            <input type="text" name="ktp_number" class="form-control @error('ktp_number') is-invalid @enderror" value="{{ old('ktp_number') }}" inputmode="numeric" maxlength="16" required>
                            @error('ktp_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nomor Kartu Keluarga</label>
                            <input type="text" name="kk_number" class="form-control @error('kk_number') is-invalid @enderror" value="{{ old('kk_number') }}" inputmode="numeric" maxlength="16" required>
                            @error('kk_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat Domisili</label>
                        <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2" required>{{ old('address') }}</textarea>
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nomor HP (terdaftar WhatsApp)</label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" inputmode="numeric" required>
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Aktif</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Mulai Kerja</label>
                            <input type="date" name="tanggal_mulai_kerja" class="form-control @error('tanggal_mulai_kerja') is-invalid @enderror" value="{{ old('tanggal_mulai_kerja') }}" required>
                            @error('tanggal_mulai_kerja')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <hr>
                    <p class="text-muted small mb-3">Unggah berkas (PDF/JPG/PNG, maks 50 MB).</p>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Upload KTP</label>
                            <input type="file" name="ktp" class="form-control @error('ktp') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png" required>
                            @error('ktp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Upload KK</label>
                            <input type="file" name="kk" class="form-control @error('kk') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png" required>
                            @error('kk')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Formulir Penjamin</label>
                            <input type="file" name="penjamin" class="form-control @error('penjamin') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png" required>
                            @error('penjamin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Kirim Pendaftaran</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

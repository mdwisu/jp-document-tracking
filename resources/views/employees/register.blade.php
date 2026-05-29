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
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nomor KTP</label>
                            <input type="text" name="ktp_number" class="form-control" value="{{ old('ktp_number') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nomor Kartu Keluarga</label>
                            <input type="text" name="kk_number" class="form-control" value="{{ old('kk_number') }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat Domisili</label>
                        <textarea name="address" class="form-control" rows="2" required>{{ old('address') }}</textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nomor HP (terdaftar WhatsApp)</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Aktif</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                        </div>
                    </div>
                    <hr>
                    <p class="text-muted small mb-3">Unggah berkas (PDF/JPG/PNG, maks 50 MB).</p>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Upload KTP</label>
                            <input type="file" name="ktp" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Upload KK</label>
                            <input type="file" name="kk" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Formulir Penjamin</label>
                            <input type="file" name="penjamin" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Kirim Pendaftaran</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

<?php

use App\Http\Controllers\DepoController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\TrashController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DepoController::class, 'index'])->name('depos.index');
Route::get('/depos/create', [DepoController::class, 'create'])->name('depos.create');
Route::post('/depos', [DepoController::class, 'store'])->name('depos.store');
Route::get('/depos/{depo}/unlock', [DepoController::class, 'unlockForm'])->name('depos.unlockForm');
Route::post('/depos/{depo}/unlock', [DepoController::class, 'unlock'])->name('depos.unlock');

// Pendaftaran karyawan — publik, tanpa password depo (link pakai token acak)
Route::get('/daftar/{depo:register_token}', [EmployeeController::class, 'register'])->name('employees.register');
Route::post('/daftar/{depo:register_token}', [EmployeeController::class, 'store'])->name('employees.store');

// Sampah / Trash — butuh master password developer
Route::get('/trash/unlock', [TrashController::class, 'unlockForm'])->name('trash.unlockForm');
Route::post('/trash/unlock', [TrashController::class, 'unlock'])->name('trash.unlock');
Route::middleware('master.unlocked')->group(function () {
    Route::get('/trash', [TrashController::class, 'index'])->name('trash.index');
    Route::post('/trash/depos/{id}/restore', [TrashController::class, 'restoreDepo'])->name('trash.restoreDepo');
    Route::delete('/trash/depos/{id}', [TrashController::class, 'forceDeleteDepo'])->name('trash.forceDeleteDepo');
    Route::post('/trash/employees/{id}/restore', [TrashController::class, 'restoreEmployee'])->name('trash.restoreEmployee');
    Route::delete('/trash/employees/{id}', [TrashController::class, 'forceDeleteEmployee'])->name('trash.forceDeleteEmployee');
});

// Data mobil (divisi logistik)
// Tambah/update dokumen mobil — publik via token acak (dibagikan ke staf logistik), tanpa password.
// Tiap submit menambah riwayat baru (append-only), tidak menimpa dokumen versi sebelumnya.
Route::get('/vehicles/tambah/{token}', [VehicleController::class, 'create'])->name('vehicles.create');
Route::get('/vehicles/tambah/{token}/cari-mobil', [VehicleController::class, 'searchMobil'])->name('vehicles.searchMobil');
Route::get('/vehicles/tambah/{token}/dokumen/{mobilId}', [VehicleController::class, 'documentStatus'])->name('vehicles.documentStatus');
Route::post('/vehicles/tambah/{token}/dokumen', [VehicleController::class, 'saveDocument'])->name('vehicles.saveDocument');

Route::get('/vehicles/unlock', [VehicleController::class, 'unlockForm'])->name('vehicles.unlockForm');
Route::post('/vehicles/unlock', [VehicleController::class, 'unlock'])->name('vehicles.unlock');

Route::middleware('vehicles.unlocked')->group(function () {
    Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
    Route::get('/vehicles/settings', [VehicleController::class, 'settings'])->name('vehicles.settings');
    Route::post('/vehicles/settings/password', [VehicleController::class, 'updatePassword'])->name('vehicles.updatePassword');
    Route::post('/vehicles/settings/regenerate-token', [VehicleController::class, 'regenerateToken'])->name('vehicles.regenerateToken');
    Route::get('/vehicles/{vehicle}', [VehicleController::class, 'show'])->name('vehicles.show');
    Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy'])->name('vehicles.destroy');
    Route::get('/vehicle-files/{file}/download', [VehicleController::class, 'download'])->name('vehicleFiles.download');
    Route::get('/vehicle-files/{file}/preview', [VehicleController::class, 'preview'])->name('vehicleFiles.preview');
});

// Sisi admin — butuh password depo
Route::middleware('depo.unlocked')->group(function () {
    Route::get('/depos/{depo}', [EmployeeController::class, 'index'])->name('depos.show');
    Route::delete('/depos/{depo}', [DepoController::class, 'destroy'])->name('depos.destroy');

    Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
    Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');

    Route::get('/files/{file}/download', [EmployeeController::class, 'download'])->name('files.download');
    Route::get('/files/{file}/preview', [EmployeeController::class, 'preview'])->name('files.preview');
});

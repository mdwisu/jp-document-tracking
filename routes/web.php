<?php

use App\Http\Controllers\DepoController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\TrashController;
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
    Route::post('/trash/employees/{id}/restore', [TrashController::class, 'restoreEmployee'])->name('trash.restoreEmployee');
});

// Sisi admin — butuh password depo
Route::middleware('depo.unlocked')->group(function () {
    Route::get('/depos/{depo}', [EmployeeController::class, 'index'])->name('depos.show');
    Route::delete('/depos/{depo}', [DepoController::class, 'destroy'])->name('depos.destroy');

    Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
    Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');

    Route::get('/files/{file}/download', [EmployeeController::class, 'download'])->name('files.download');
});

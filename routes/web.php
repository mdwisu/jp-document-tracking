<?php

use App\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/documents');

Route::resource('documents', DocumentController::class)->except(['edit', 'update']);
Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

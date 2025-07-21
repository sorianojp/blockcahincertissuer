<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CertificateController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [CertificateController::class, 'index'])->name('dashboard');
    Route::get('/certificates/upload', [CertificateController::class, 'uploadForm'])->name('certificates.upload');
    Route::post('/certificates/upload', [CertificateController::class, 'uploadCSV'])->name('certificates.upload.process');
    Route::post('/certificates/{id}/issue', [CertificateController::class, 'issue'])->name('certificates.issue');
});

Route::get('/verify/{id}', [CertificateController::class, 'verify'])->name('certificates.verify');



require __DIR__.'/auth.php';

<?php

use App\Http\Controllers\AdminJobController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\HomeController;
use App\Livewire\Admin\AlterarSenha;
use App\Livewire\Admin\JobList;
use App\Livewire\Admin\JobForm;
use App\Livewire\Admin\ClientList;
use Illuminate\Support\Facades\Route;

// Home pública
Route::get('/', [HomeController::class, 'index'])->name('home');

// Auth
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Admin (autenticado)
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/dashboard', JobList::class)->name('admin.dashboard');
    Route::get('/jobs/create', JobForm::class)->name('admin.jobs.create');
    Route::get('/jobs/{id}/edit', JobForm::class)->name('admin.jobs.edit');
    Route::get('/clients', ClientList::class)->name('admin.clients');
    Route::get('/perfil', AlterarSenha::class)->name('admin.perfil');
    
    // Download de fotos (admin) para backup local
    Route::get('/jobs/{trabalho}/download-fotos', [AdminJobController::class, 'downloadFotos'])
        ->name('admin.jobs.download-fotos');

    // Rota de thumbnail proxy
    Route::get('/thumbnail/{foto}', function (\App\Models\Foto $foto) {
        try {
            $driveService = app(\App\Services\GoogleDriveService::class);
            $stream = $driveService->download($foto->drive_arquivo_id);
            return response($stream)->header('Content-Type', 'image/jpeg');
        } catch (\Exception $e) {
            abort(404);
        }
    })->name('admin.thumbnail');
});

// Galeria pública (sem auth)
Route::get('/galeria/{token}', [GalleryController::class, 'show'])->name('galeria.show');
Route::get('/galeria/{token}/download', [GalleryController::class, 'downloadTodas'])->name('galeria.download');
Route::get('/galeria/{token}/foto/{foto}', [GalleryController::class, 'downloadFoto'])->name('galeria.foto');

<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PeminjamanController;
use App\Http\Controllers\BukuController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware(['auth'])->group(function () {

    // Celah A2: SQL Injection
    // --- PERBAIKAN A2: Menggunakan Parameter Binding (?) ---
    Route::get('/buku-cari', function (\Illuminate\Http\Request $request) {
        $judul = $request->query('judul');
        // Nilai variabel dipisahkan ke dalam array agar tidak bisa memanipulasi struktur SQL
        $hasil = \Illuminate\Support\Facades\DB::select("SELECT * FROM bukus WHERE judul = ?", [$judul]);
        return response()->json($hasil);
    });

    // Celah A3: CSRF menggunakan URL GET
    // --- PERBAIKAN A3: Rute diubah ke DELETE ---
    Route::delete('/buku/{id}', [\App\Http\Controllers\Api\BukuController::class, 'destroy']);

    // Celah A4: IDOR tanpa mengecek siapa yang login
    Route::get('/peminjaman/{id}', function ($id) {
        $peminjaman = \App\Models\Peminjaman::findOrFail($id);
        // Karena sistem Anda memakai API/JSON, kita kembalikan datanya langsung
        return response()->json($peminjaman);
    });

    Route::get('/dashboard', function (Request $request) {
        if ($request->user()->hasRole('admin')) return redirect()->route('admin.dashboard');
        if ($request->user()->hasRole('petugas')) return redirect()->route('petugas.dashboard');
        return view('dashboard');
    })->name('dashboard');

    Route::middleware(['role:admin'])->group(function () {
        Route::get('/admin/dashboard', function () {
            return view('admin.dashboard');
        })->name('admin.dashboard');

        Route::get('/admin/pengguna', [UserController::class, 'index'])->name('admin.pengguna');
        Route::post('/admin/pengguna', [UserController::class, 'store'])->name('admin.pengguna.store');
        Route::post('/admin/pengguna/{id}/role', [UserController::class, 'updateRole'])->name('admin.pengguna.role');
        Route::delete('/admin/pengguna/{id}', [UserController::class, 'destroy'])->name('admin.pengguna.destroy');
    });

    Route::middleware(['role:petugas'])->group(function () {
        Route::get('/petugas/dashboard', function () {
            return view('petugas.dashboard');
        })->name('petugas.dashboard');
    });

    Route::middleware(['role:admin|petugas'])->group(function () {
        Route::get('/buku/kelola', function () {
            return view('buku.index');
        })->name('buku.kelola');

        Route::get('/laporan', [PeminjamanController::class, 'laporan'])->name('laporan.index');

        Route::get('/peminjaman', [PeminjamanController::class, 'index']);
        Route::patch('/peminjaman/{id}', [PeminjamanController::class, 'update']);
    });

    Route::middleware(['role:siswa'])->group(function () {
        Route::post('/pinjam/{id}', [PeminjamanController::class, 'store']);
        Route::get('/riwayat', [PeminjamanController::class, 'riwayat']);
    });

    Route::get('/buku', function () {
        return view('buku.index');
    })->name('buku.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/api/buku', [BukuController::class, 'apiBuku']);
Route::get('/api/kategori', [BukuController::class, 'apiKategori']);

require __DIR__ . '/auth.php';

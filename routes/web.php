<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PeminjamanController;
use App\Http\Controllers\BukuController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware(['auth'])->group(function () {

    // soal 2: benerin celah A2 pake parameter binding biar ga kena sqli
    Route::get('/buku-cari', function (\Illuminate\Http\Request $request) {
        $judul = $request->query('judul');
        $hasil = \Illuminate\Support\Facades\DB::select("SELECT * FROM bukus WHERE judul = ?", [$judul]);
        return response()->json($hasil);
    });

    // soal 4: tutup celah A4 (idor) pake policy
    Route::get('/peminjaman/{id}', function ($id) {
        $peminjaman = \App\Models\Peminjaman::findOrFail($id);
        
        // cek policy 'view', kalo bukan punya dia bakal dilempar 403
        \Illuminate\Support\Facades\Gate::authorize('view', $peminjaman);
        
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

    // soal 3: pusatin aturan kelola buku di gate
    Route::middleware(['can:kelola-buku'])->group(function () {
        
        Route::get('/buku/kelola', function () {
            return view('buku.index');
        })->name('buku.kelola');

        // perbaikan celah A3, hapus buku diubah ke method delete
        Route::delete('/buku/{id}', [\App\Http\Controllers\Api\BukuController::class, 'destroy']);

        // rute buatan buat ngetes siswa dapet 403 pas ngetik url create
        Route::get('/buku/create', function () {
            return "Halaman form tambah buku (Hanya Admin/Petugas)";
        });

    });

    Route::middleware(['role:admin|petugas'])->group(function () {
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
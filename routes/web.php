<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FineController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::controller(AuthController::class)->group(function () {
    Route::get('/login', 'index')->name('login');
    Route::post('/login', 'login')->name('login.post');
    Route::post('/logout', 'logout')->name('logout');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::controller(ProfileController::class)->group(function () {
        Route::get('/profil', 'index')->name('profile.index');
        Route::put('/profil', 'update')->name('profile.update');
        Route::put('/profil/password', 'password')->name('profile.password');
    });

    // ---------------- Admin only ----------------
    Route::middleware('role:admin')->group(function () {
        Route::resource('books', BookController::class)->except(['show']);
        Route::get('books/{book}', [BookController::class, 'show'])->name('books.show');

        Route::get('lookups/{type}', [LookupController::class, 'index'])->name('lookups.index');
        Route::post('lookups/{type}', [LookupController::class, 'store'])->name('lookups.store');
        Route::put('lookups/{type}/{lookup}', [LookupController::class, 'update'])->name('lookups.update');
        Route::delete('lookups/{type}/{lookup}', [LookupController::class, 'destroy'])->name('lookups.destroy');

        Route::get('monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');
        Route::get('monitoring/{user}', [MonitoringController::class, 'show'])->name('monitoring.show');
        Route::get('logs', [ActivityLogController::class, 'index'])->name('logs.index');

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/cetak', [ReportController::class, 'print'])->name('reports.print');
        Route::get('reports/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
        Route::get('reports/excel', [ReportController::class, 'excel'])->name('reports.excel');

        Route::resource('accounts', AccountController::class)->only(['index', 'store', 'destroy']);
        Route::get('accounts/{account}/edit', [AccountController::class, 'edit'])->name('accounts.edit');
        Route::put('accounts/{account}', [AccountController::class, 'update'])->name('accounts.update');
        Route::post('accounts/{account}/toggle', [AccountController::class, 'toggle'])->name('accounts.toggle');

        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

        Route::get('backup', [BackupController::class, 'index'])->name('backup.index');
        Route::post('backup', [BackupController::class, 'store'])->name('backup.store');
        Route::get('backup/{filename}/download', [BackupController::class, 'download'])->name('backup.download');
        Route::delete('backup/{filename}', [BackupController::class, 'destroy'])->name('backup.destroy');
        Route::post('backup/restore', [BackupController::class, 'restore'])->name('backup.restore');
    });

    // ---------------- Petugas only (transactions) ----------------
    Route::middleware('role:petugas')->group(function () {
        Route::get('loans/create', [LoanController::class, 'create'])->name('loans.create');
        Route::post('loans', [LoanController::class, 'store'])->name('loans.store');
        Route::post('loans/{loan}/cancel', [LoanController::class, 'cancel'])->name('loans.cancel');

        Route::get('returns/create', [ReturnController::class, 'create'])->name('returns.create');
        Route::post('returns', [ReturnController::class, 'store'])->name('returns.store');

        Route::get('fines/{fine}/pay', [FineController::class, 'pay'])->name('fines.pay');
        Route::post('fines/{fine}/pay', [FineController::class, 'payment'])->name('fines.payment');
        Route::get('fines/{fine}/receipt/{payment}', [FineController::class, 'receipt'])->name('fines.receipt');
    });

    // ---------------- Shared (admin view, petugas operate) ----------------
    Route::get('catalog', [CatalogController::class, 'index'])->name('catalog.index');
    Route::get('catalog/{book}', [CatalogController::class, 'show'])->name('catalog.show');

    Route::get('members', [MemberController::class, 'index'])->name('members.index');
    Route::get('members/create', [MemberController::class, 'create'])->name('members.create');
    Route::post('members', [MemberController::class, 'store'])->name('members.store');
    Route::get('members/{member}', [MemberController::class, 'show'])->name('members.show');
    Route::get('members/{member}/edit', [MemberController::class, 'edit'])->name('members.edit');
    Route::put('members/{member}', [MemberController::class, 'update'])->name('members.update');
    Route::delete('members/{member}', [MemberController::class, 'destroy'])->name('members.destroy');

    Route::get('loans', [LoanController::class, 'index'])->name('loans.index');
    Route::get('loans/{loan}', [LoanController::class, 'show'])->name('loans.show');

    Route::get('returns', [ReturnController::class, 'index'])->name('returns.index');
    Route::get('returns/{book_return}', [ReturnController::class, 'show'])->name('returns.show');

    Route::get('fines', [FineController::class, 'index'])->name('fines.index');
    Route::get('fines/history', [FineController::class, 'history'])->name('fines.history');
    Route::get('fines/{fine}', [FineController::class, 'show'])->name('fines.show');

    Route::get('transactions', [LoanController::class, 'transactions'])->name('transactions.index');
});

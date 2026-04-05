<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

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

    // Frontend Design Mockup Routes
    Route::prefix('batches')->name('batches.')->group(function () {
        Route::get('/', function () { return view('batches.index'); })->name('index');
        Route::get('/create', function () { return view('batches.create'); })->name('create');
        Route::get('/{batch}', function ($id) { return view('batches.show', ['id' => $id]); })->name('show');
        Route::get('/{batch}/edit', function ($id) { return view('batches.edit', ['id' => $id]); })->name('edit');
    });

    // Breeder / Inahin Forms
    Route::get('/breeders/create', function () { return view('breeders.create'); })->name('breeders.create');

    // Health, Vaccination, and Treatment Module
    Route::prefix('health')->name('health.')->group(function () {
        Route::get('/', function () { return view('health.index'); })->name('index');
        Route::get('/schedule', function () { return view('health.schedule'); })->name('schedule');
        Route::get('/create', function () { return view('health.create'); })->name('create');
        Route::get('/sick', function () { return view('health.sick'); })->name('sick');
    });
    Route::get('/batches/{batch}/health', function ($id) { return view('batches.health', ['id' => $id]); })->name('batches.health');

    // Mortality / Deceased Pig Documentation Module
    Route::prefix('mortality')->name('mortality.')->group(function () {
        Route::get('/', function () { return view('mortality.index'); })->name('index');
        Route::get('/create', function () { return view('mortality.create'); })->name('create');
        Route::get('/{id}', function ($id) { return view('mortality.show', ['id' => $id]); })->name('show');
    });

    // Sales Transaction Module
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/', function () { return view('sales.index'); })->name('index');
        Route::get('/create', function () { return view('sales.create'); })->name('create');
        Route::get('/{id}', function ($id) { return view('sales.show', ['id' => $id]); })->name('show');
    });

    // Expense Management Module
    Route::prefix('expenses')->name('expenses.')->group(function () {
        Route::get('/', function () { return view('expenses.index'); })->name('index');
        Route::get('/summary', function () { return view('expenses.summary'); })->name('summary');
        Route::get('/create', function () { return view('expenses.create'); })->name('create');
        Route::get('/{expense}', function ($id) { return view('expenses.show', ['id' => $id]); })->name('show');
        Route::get('/{expense}/edit', function ($id) { return view('expenses.edit', ['id' => $id]); })->name('edit');
    });

    // Profitability & Profit-Sharing Module
    Route::prefix('profitability')->name('profitability.')->group(function () {
        Route::get('/', function () { return view('profitability.index'); })->name('index');
        Route::get('/cycle/{id}', function ($id) { return view('profitability.show', ['id' => $id]); })->name('show');
    });
    
    Route::get('/profit-sharing/{id}', function ($id) { return view('profitability.sharing', ['id' => $id]); })->name('profit-sharing');

    // Meeting Resolutions and Withdrawal Documentation Module
    Route::prefix('resolutions')->name('resolutions.')->group(function () {
        Route::get('/', function () { return view('resolutions.index'); })->name('index');
        Route::get('/create', function () { return view('resolutions.create'); })->name('create');
        Route::get('/{id}', function ($id) { return view('resolutions.show', ['id' => $id]); })->name('show');
    });

    Route::prefix('minutes')->name('minutes.')->group(function () {
        Route::get('/', function () { return view('minutes.index'); })->name('index');
    });

    Route::prefix('withdrawals')->name('withdrawals.')->group(function () {
        Route::get('/create', function () { return view('withdrawals.create'); })->name('create');
    });

    // Reports Module
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', function () { return view('reports.index'); })->name('index');
        Route::get('/generate', function () { return view('reports.generate'); })->name('generate');
        Route::get('/{type}/preview', function ($type) { return view('reports.preview', ['type' => $type]); })->name('preview');
    });
});

require __DIR__.'/auth.php';
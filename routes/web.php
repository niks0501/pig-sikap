<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\President\PresidentPigBatchAdjustmentController;
use App\Http\Controllers\President\PresidentPigBatchStatusController;
use App\Http\Controllers\President\PresidentPigBreederController;
use App\Http\Controllers\President\PresidentPigInventoryController;
use App\Http\Controllers\President\PresidentPigProfileController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified', 'force_password_change'])->name('dashboard');

Route::middleware(['auth', 'force_password_change', 'role:system_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::patch('/users/{user}/status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::patch('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

    Route::get('/profile', [AdminProfileController::class, 'index'])->name('profile');
    Route::patch('/profile', [AdminProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [AdminProfileController::class, 'changePassword'])->name('profile.password');
});

Route::middleware(['auth', 'force_password_change'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware(['role:president'])->group(function () {
        Route::prefix('batches')->name('batches.')->scopeBindings()->group(function () {
            Route::get('/', [PresidentPigInventoryController::class, 'index'])->name('index');
            Route::get('/create', [PresidentPigInventoryController::class, 'create'])->name('create');
            Route::post('/', [PresidentPigInventoryController::class, 'store'])->name('store');
            Route::get('/archived', [PresidentPigInventoryController::class, 'archived'])->name('archived');
            Route::get('/{batch}', [PresidentPigInventoryController::class, 'show'])->name('show');
            Route::get('/{batch}/edit', [PresidentPigInventoryController::class, 'edit'])->name('edit');
            Route::put('/{batch}', [PresidentPigInventoryController::class, 'update'])->name('update');
            Route::patch('/{batch}/archive', [PresidentPigInventoryController::class, 'archive'])->name('archive');

            Route::get('/{batch}/pigs', [PresidentPigProfileController::class, 'index'])->name('pigs.index');
            Route::post('/{batch}/pigs', [PresidentPigProfileController::class, 'store'])->name('pigs.store');
            Route::put('/{batch}/pigs/{pig}', [PresidentPigProfileController::class, 'update'])->name('pigs.update');

            Route::post('/{batch}/adjustments', [PresidentPigBatchAdjustmentController::class, 'store'])->name('adjustments.store');
            Route::post('/{batch}/status', [PresidentPigBatchStatusController::class, 'store'])->name('status.store');
        });

        Route::prefix('breeders')->name('breeders.')->group(function () {
            Route::get('/create', [PresidentPigBreederController::class, 'index'])->name('create');
            Route::post('/', [PresidentPigBreederController::class, 'store'])->name('store');
        });
    });

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

    // Audit Trail Module
    Route::prefix('audit-trails')->name('audit-trails.')->group(function () {
        Route::get('/', function () { return view('audit-trails.index'); })->name('index');
    });
});

require __DIR__.'/auth.php';
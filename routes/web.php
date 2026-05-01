<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\President\PresidentCycleHealthIncidentController;
use App\Http\Controllers\President\PresidentCycleHealthTaskController;
use App\Http\Controllers\President\PresidentExpenseController;
use App\Http\Controllers\President\PresidentHealthController;
use App\Http\Controllers\President\PresidentPigBreederController;
use App\Http\Controllers\President\PresidentPigBuyerController;
use App\Http\Controllers\President\PresidentPigCycleAdjustmentController;
use App\Http\Controllers\President\PresidentPigCycleSaleController;
use App\Http\Controllers\President\PresidentPigCycleStatusController;
use App\Http\Controllers\President\PresidentPigInventoryController;
use App\Http\Controllers\President\PresidentPigProfileController;
use App\Http\Controllers\President\PresidentSaleReceiptController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified', 'force_password_change'])->name('dashboard');

Route::middleware(['auth', 'verified', 'force_password_change', 'role:system_admin'])->prefix('admin')->name('admin.')->group(function () {
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

Route::middleware(['auth', 'verified', 'force_password_change'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::view('/membership/how-to-join', 'membership.how-to-join')->name('membership.how-to-join');

    Route::middleware(['role:president'])->group(function () {
        Route::prefix('cycles')->name('cycles.')->scopeBindings()->group(function () {
            Route::get('/', [PresidentPigInventoryController::class, 'index'])->name('index');
            Route::get('/create', [PresidentPigInventoryController::class, 'create'])->name('create');
            Route::post('/', [PresidentPigInventoryController::class, 'store'])->name('store');
            Route::get('/archived', [PresidentPigInventoryController::class, 'archived'])->name('archived');
            Route::get('/{cycle}', [PresidentPigInventoryController::class, 'show'])->name('show');
            Route::get('/{cycle}/edit', [PresidentPigInventoryController::class, 'edit'])->name('edit');
            Route::put('/{cycle}', [PresidentPigInventoryController::class, 'update'])->name('update');
            Route::delete('/{cycle}', [PresidentPigInventoryController::class, 'destroy'])->name('destroy');
            Route::patch('/{cycle}/archive', [PresidentPigInventoryController::class, 'archive'])->name('archive');

            Route::get('/{cycle}/profiles', [PresidentPigProfileController::class, 'index'])->name('profiles.index');
            Route::post('/{cycle}/profiles', [PresidentPigProfileController::class, 'store'])->name('profiles.store');
            Route::put('/{cycle}/profiles/{pig}', [PresidentPigProfileController::class, 'update'])->name('profiles.update');
            Route::delete('/{cycle}/profiles/{pig}', [PresidentPigProfileController::class, 'destroy'])->name('profiles.destroy');

            Route::post('/{cycle}/adjustments', [PresidentPigCycleAdjustmentController::class, 'store'])->name('adjustments.store');
            Route::post('/{cycle}/status', [PresidentPigCycleStatusController::class, 'store'])->name('status.store');
        });

        Route::prefix('batches')->name('batches.')->scopeBindings()->group(function () {
            Route::get('/', [PresidentPigInventoryController::class, 'index'])->name('index');
            Route::get('/create', [PresidentPigInventoryController::class, 'create'])->name('create');
            Route::post('/', [PresidentPigInventoryController::class, 'store'])->name('store');
            Route::get('/archived', [PresidentPigInventoryController::class, 'archived'])->name('archived');
            Route::get('/{cycle}', [PresidentPigInventoryController::class, 'show'])->name('show');
            Route::get('/{cycle}/edit', [PresidentPigInventoryController::class, 'edit'])->name('edit');
            Route::put('/{cycle}', [PresidentPigInventoryController::class, 'update'])->name('update');
            Route::delete('/{cycle}', [PresidentPigInventoryController::class, 'destroy'])->name('destroy');
            Route::patch('/{cycle}/archive', [PresidentPigInventoryController::class, 'archive'])->name('archive');

            Route::get('/{cycle}/pigs', [PresidentPigProfileController::class, 'index'])->name('pigs.index');
            Route::post('/{cycle}/pigs', [PresidentPigProfileController::class, 'store'])->name('pigs.store');
            Route::put('/{cycle}/pigs/{pig}', [PresidentPigProfileController::class, 'update'])->name('pigs.update');
            Route::delete('/{cycle}/pigs/{pig}', [PresidentPigProfileController::class, 'destroy'])->name('pigs.destroy');

            Route::post('/{cycle}/adjustments', [PresidentPigCycleAdjustmentController::class, 'store'])->name('adjustments.store');
            Route::post('/{cycle}/status', [PresidentPigCycleStatusController::class, 'store'])->name('status.store');
        });

        Route::prefix('breeders')->name('breeders.')->group(function () {
            Route::get('/create', [PresidentPigBreederController::class, 'index'])->name('create');
            Route::post('/', [PresidentPigBreederController::class, 'store'])->name('store');
        });

        Route::prefix('health')->name('health.')->scopeBindings()->group(function () {
            Route::get('/', [PresidentHealthController::class, 'index'])->name('index');
            Route::get('/schedule', [PresidentHealthController::class, 'schedule'])->name('schedule');
            Route::get('/create', [PresidentHealthController::class, 'create'])->name('create');
            Route::get('/mortality', [PresidentHealthController::class, 'mortality'])->name('mortality');
            Route::get('/mortality/create', [PresidentHealthController::class, 'createMortality'])->name('mortality.create');
            Route::post('/incidents', [PresidentHealthController::class, 'storeIncident'])->name('incidents.store');
            Route::get('/sick', [PresidentHealthController::class, 'sick'])->name('sick');
            Route::get('/cycles/{cycle}', [PresidentHealthController::class, 'showCycle'])->name('cycles.show');
            Route::patch('/cycles/{cycle}/tasks/{healthTask}', [PresidentCycleHealthTaskController::class, 'update'])->name('cycles.tasks.update');
            Route::patch('/cycles/{cycle}/tasks/{healthTask}/undo', [PresidentCycleHealthTaskController::class, 'undo'])->name('cycles.tasks.undo');
            Route::post('/cycles/{cycle}/incidents', [PresidentCycleHealthIncidentController::class, 'store'])->name('cycles.incidents.store');
        });
    });

    // Sales Transaction Module
    Route::middleware(['role:president,treasurer,secretary'])->prefix('sales')->name('sales.')->group(function () {
        Route::get('/', [PresidentPigCycleSaleController::class, 'index'])->name('index');
        Route::get('/create', [PresidentPigCycleSaleController::class, 'create'])->name('create');
        Route::post('/', [PresidentPigCycleSaleController::class, 'store'])->name('store');
        Route::get('/{sale}/receipt/preview', [PresidentSaleReceiptController::class, 'preview'])->name('receipt.preview');
        Route::get('/{sale}/receipt/download', [PresidentSaleReceiptController::class, 'download'])->name('receipt.download');
        Route::post('/{sale}/receipt/send', [PresidentSaleReceiptController::class, 'send'])->name('receipt.send');
        Route::get('/{sale}', [PresidentPigCycleSaleController::class, 'show'])->name('show');
        Route::put('/{sale}', [PresidentPigCycleSaleController::class, 'update'])->name('update');
    });

    Route::middleware(['role:president,treasurer,secretary'])->prefix('buyers')->name('buyers.')->group(function () {
        Route::get('/', [PresidentPigBuyerController::class, 'index'])->name('index');
        Route::post('/', [PresidentPigBuyerController::class, 'store'])->name('store');
        Route::put('/{buyer}', [PresidentPigBuyerController::class, 'update'])->name('update');
    });

    // Expense Management Module
    Route::middleware(['role:president,treasurer,secretary'])->prefix('expenses')->name('expenses.')->group(function () {
        Route::get('/', [PresidentExpenseController::class, 'index'])->name('index');
        Route::get('/summary', [PresidentExpenseController::class, 'summary'])->name('summary');
        Route::get('/create', [PresidentExpenseController::class, 'create'])->name('create');
        Route::get('/preferences', [PresidentExpenseController::class, 'preferences'])->name('preferences');
        Route::put('/preferences', [PresidentExpenseController::class, 'updatePreferences'])->name('preferences.update');
        Route::get('/recent-templates', [PresidentExpenseController::class, 'recentTemplates'])->name('recent-templates');
        Route::post('/bulk-delete', [PresidentExpenseController::class, 'bulkDelete'])->name('bulk-delete');
        Route::post('/', [PresidentExpenseController::class, 'store'])->name('store');
        Route::get('/{expense}', [PresidentExpenseController::class, 'show'])->name('show');
        Route::get('/{expense}/edit', [PresidentExpenseController::class, 'edit'])->name('edit');
        Route::put('/{expense}', [PresidentExpenseController::class, 'update'])->name('update');
        Route::delete('/{expense}', [PresidentExpenseController::class, 'destroy'])->name('destroy');
        Route::post('/{expense}/duplicate', [PresidentExpenseController::class, 'duplicate'])->name('duplicate');
    });

    // Profitability & Profit-Sharing Module
    Route::prefix('profitability')->name('profitability.')->group(function () {
        Route::get('/', function () {
            return view('profitability.index');
        })->name('index');
        Route::get('/cycle/{id}', function ($id) {
            return view('profitability.show', ['id' => $id]);
        })->name('show');
    });

    Route::get('/profit-sharing/{id}', function ($id) {
        return view('profitability.sharing', ['id' => $id]);
    })->name('profit-sharing');

    // Meeting Resolutions and Withdrawal Documentation Module
    Route::prefix('resolutions')->name('resolutions.')->group(function () {
        Route::get('/', function () {
            return view('resolutions.index');
        })->name('index');
        Route::get('/create', function () {
            return view('resolutions.create');
        })->name('create');
        Route::get('/{id}', function ($id) {
            return view('resolutions.show', ['id' => $id]);
        })->name('show');
    });

    Route::prefix('minutes')->name('minutes.')->group(function () {
        Route::get('/', function () {
            return view('minutes.index');
        })->name('index');
    });

    Route::prefix('withdrawals')->name('withdrawals.')->group(function () {
        Route::get('/create', function () {
            return view('withdrawals.create');
        })->name('create');
    });

    // Reports Module
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', function () {
            return view('reports.index');
        })->name('index');
        Route::get('/generate', function () {
            return view('reports.generate');
        })->name('generate');
        Route::get('/{type}/preview', function ($type) {
            return view('reports.preview', ['type' => $type]);
        })->name('preview');
    });

    // Audit Trail Module
    Route::prefix('audit-trails')->name('audit-trails.')->group(function () {
        Route::get('/', function () {
            return view('audit-trails.index');
        })->name('index');
    });
});

require __DIR__.'/auth.php';

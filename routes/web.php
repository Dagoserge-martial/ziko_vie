<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

use App\Http\Controllers\CotisationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepenseMedicaleController;
use App\Http\Controllers\ExpenseAttachmentController;
use App\Http\Controllers\MembreController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserRoleController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Membres routes
    Route::get('/membres/print', [MembreController::class, 'print'])
        ->name('membres.print');
    Route::resource('membres', MembreController::class);

    // Cotisations routes
    Route::post('/cotisations/store-multiple', [CotisationController::class, 'storeMultiple'])
        ->name('cotisations.store-multiple');
    Route::get('/cotisations/print', [CotisationController::class, 'print'])
        ->name('cotisations.print');
    Route::resource('cotisations', CotisationController::class);

    // Dépenses médicales routes
    Route::get('/depenses-medicales/print', [DepenseMedicaleController::class, 'print'])
        ->name('depenses-medicales.print');
    Route::resource('depenses-medicales', DepenseMedicaleController::class)
        ->parameters(['depenses-medicales' => 'depenseMedicale']);

    // Expense Attachments routes
    Route::get('/expense-attachments/{expenseAttachment}/download', [ExpenseAttachmentController::class, 'download'])
        ->name('expense-attachments.download');
    Route::delete('/expense-attachments/{expenseAttachment}', [ExpenseAttachmentController::class, 'destroy'])
        ->name('expense-attachments.destroy');

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Paramètres routes (réservées aux admins)
    Route::middleware('admin')->prefix('parametres')->name('parametres.')->group(function () {
        Route::get('/', function () {
            return Inertia::render('Parametres/Index');
        })->name('index');
        
        // Rôles
        Route::resource('roles', RoleController::class);
        
        // Utilisateurs et assignation de rôles
        Route::get('/users', [UserRoleController::class, 'index'])->name('users.index');
        Route::post('/users/{user}/assign-role', [UserRoleController::class, 'assignRole'])->name('users.assign-role');
        Route::post('/users/{user}/toggle-block', [UserRoleController::class, 'toggleBlock'])->name('users.toggle-block');
    });
});

require __DIR__.'/auth.php';

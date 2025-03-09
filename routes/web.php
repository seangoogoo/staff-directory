<?php

use App\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Public routes
Route::get('/', [StaffController::class, 'index'])->name('staff.index');

// Authentication routes
Auth::routes(['register' => false]); // Disable registration

// Admin routes (protected by auth middleware)
Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/', [StaffController::class, 'adminDashboard'])->name('admin.dashboard');
    Route::get('/create', [StaffController::class, 'create'])->name('admin.create');
    Route::post('/store', [StaffController::class, 'store'])->name('admin.store');
    Route::get('/edit/{id}', [StaffController::class, 'edit'])->name('admin.edit');
    Route::put('/update/{id}', [StaffController::class, 'update'])->name('admin.update');
    Route::delete('/delete/{id}', [StaffController::class, 'destroy'])->name('admin.destroy');
});

// Redirect /home to /admin for users who are logged in
Route::get('/home', function() {
    return redirect('/admin');
});

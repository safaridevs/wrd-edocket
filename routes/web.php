<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CaseController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\CaseInitiationController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;
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
    
    // E-Docket Routes
    Route::get('cases', [CaseController::class, 'index'])->middleware('permission:read_case')->name('cases.index');
    Route::get('cases/create', [CaseController::class, 'create'])->middleware('permission:create_case')->name('cases.create');
    Route::post('cases', [CaseController::class, 'store'])->middleware('permission:create_case')->name('cases.store');
    Route::get('cases/{case}', [CaseController::class, 'show'])->middleware('permission:read_case')->name('cases.show');
    Route::get('cases/{case}/edit', [CaseController::class, 'edit'])->middleware('permission:create_case')->name('cases.edit');
    Route::put('cases/{case}', [CaseController::class, 'update'])->middleware('permission:create_case')->name('cases.update');
    Route::get('cases/{case}/hu-review', [CaseController::class, 'huReview'])->middleware('permission:accept_filings')->name('cases.hu-review');
    Route::get('cases/{case}/file-document', [DocumentController::class, 'fileForm'])->name('documents.file');
    Route::post('documents/{document}/stamp', [DocumentController::class, 'stamp'])->middleware('permission:apply_stamp')->name('documents.stamp');
    Route::post('cases/{case}/accept', [CaseController::class, 'accept'])->middleware('permission:accept_filings')->name('cases.accept');
    Route::post('cases/{case}/reject', [CaseController::class, 'reject'])->middleware('permission:reject_filings')->name('cases.reject');
    
    Route::post('cases/{case}/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::get('notifications/unread-count', [NotificationController::class, 'getUnreadCount'])->name('notifications.unread-count');
    
    // Case Initiation (ALU Law Clerk)
    Route::get('cases/initiate', [CaseInitiationController::class, 'create'])->middleware('permission:create_case')->name('cases.initiate');
    Route::post('cases/initiate', [CaseInitiationController::class, 'store'])->middleware('permission:create_case');
    
    // Admin routes
    Route::get('admin/users', [AdminController::class, 'users'])->middleware('permission:manage_users')->name('admin.users');
    Route::patch('admin/users/{user}/role', [AdminController::class, 'updateUserRole'])->middleware('permission:manage_users')->name('admin.users.role');
    
    // User role routes
    Route::get('users/roles', [UserController::class, 'getUsersWithRoles'])->name('users.roles');
    Route::get('users/role/{role}', [UserController::class, 'getUsersByRole'])->name('users.by-role');
    Route::get('users/login-capable', [UserController::class, 'getLoginCapableUsers'])->name('users.login-capable');
    
    // User approval routes
    Route::post('admin/users/{user}/approve', [AdminController::class, 'approveUser'])->middleware('permission:manage_users')->name('admin.users.approve');
});

require __DIR__.'/auth.php';

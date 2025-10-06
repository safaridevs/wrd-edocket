<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CaseController;
use App\Http\Controllers\CasePartyController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\CaseInitiationController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

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
    Route::post('cases/{case}/file-document', [DocumentController::class, 'store'])->name('documents.file.store');
    Route::get('cases/{case}/upload-documents', [CaseController::class, 'uploadDocuments'])->name('cases.documents.upload');
    Route::post('cases/{case}/upload-documents', [CaseController::class, 'storeDocuments'])->name('cases.documents.store');
    Route::post('documents/{document}/stamp', [DocumentController::class, 'stamp'])->middleware('permission:apply_stamp')->name('documents.stamp');
    
    Route::get('cases/{case}/persons/{person}/edit', [PersonController::class, 'edit'])->name('cases.persons.edit');
    Route::put('cases/{case}/persons/{person}', [PersonController::class, 'update'])->name('cases.persons.update');
    
    Route::get('cases/{case}/parties/manage', [CaseController::class, 'manageParties'])->name('cases.parties.manage');
    Route::post('cases/{case}/parties', [CaseController::class, 'storeParty'])->name('cases.parties.store');
    Route::get('cases/{case}/parties/{party}/edit', [CaseController::class, 'editParty'])->name('cases.parties.edit');
    Route::put('cases/{case}/parties/{party}', [CaseController::class, 'updateParty'])->name('cases.parties.update');
    Route::delete('cases/{case}/parties/{party}', [CaseController::class, 'destroyParty'])->name('cases.parties.destroy');
    
    // Document management routes
    Route::get('cases/{case}/documents/manage', [CaseController::class, 'manageDocuments'])->name('cases.documents.manage');
    Route::post('cases/{case}/documents', [CaseController::class, 'storeDocument'])->name('cases.documents.store');
    Route::post('cases/{case}/documents/{document}/approve', [CaseController::class, 'approveDocument'])->name('cases.documents.approve');
    Route::post('cases/{case}/documents/{document}/reject', [CaseController::class, 'rejectDocument'])->name('cases.documents.reject');
    Route::post('cases/{case}/documents/{document}/unapprove', [CaseController::class, 'unapproveDocument'])->name('cases.documents.unapprove');
    Route::post('cases/{case}/documents/{document}/stamp', [CaseController::class, 'stampDocument'])->name('cases.documents.stamp');
    Route::post('cases/{case}/documents/{document}/request-fix', [CaseController::class, 'requestDocumentFix'])->name('cases.documents.request-fix');
    Route::delete('cases/{case}/documents/{document}', [CaseController::class, 'destroyDocument'])->name('cases.documents.destroy');
});

// API routes (outside auth middleware)
Route::get('api/lookup-person/{email}', function($email) {
    try {
        $person = \App\Models\Person::where('email', $email)->first();
        $attorney = \App\Models\Attorney::where('email', $email)->first();
        
        if ($person) {
            $name = trim($person->first_name . ' ' . $person->last_name);
            
            return response()->json([
                'found' => true,
                'name' => $name,
                'type' => 'person'
            ]);
        }
        
        if ($attorney) {
            return response()->json([
                'found' => true,
                'name' => $attorney->name,
                'type' => 'attorney'
            ]);
        }
        
        return response()->json(['found' => false]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
})->name('api.lookup-person');

Route::middleware('guest')->group(function () {
    // Guest routes are already defined in auth.php
});

Route::middleware('auth')->group(function () {
    // Additional authenticated routes if needed
    Route::post('cases/{case}/accept', [CaseController::class, 'accept'])->middleware('permission:accept_filings')->name('cases.accept');
    Route::post('cases/{case}/reject', [CaseController::class, 'reject'])->middleware('permission:reject_filings')->name('cases.reject');
    Route::post('cases/{case}/approve', [CaseController::class, 'approve'])->name('cases.approve');
    Route::get('cases/{case}/assign-attorney', [CaseController::class, 'assignAttorneyForm'])->name('cases.assign-attorney');
    Route::post('cases/{case}/assign-attorney', [CaseController::class, 'assignAttorney'])->name('cases.assign-attorney.store');
    Route::get('cases/{case}/assign-hydrology-expert', [CaseController::class, 'assignHydrologyExpertForm'])->name('cases.assign-hydrology-expert');
    Route::post('cases/{case}/assign-hydrology-expert', [CaseController::class, 'assignHydrologyExpert'])->name('cases.assign-hydrology-expert.store');
    Route::post('cases/{case}/notify-parties', [CaseController::class, 'notifyParties'])->name('cases.notify-parties');
    
    // Attorney management routes
    Route::get('cases/{case}/parties/{party}/attorney', [CaseController::class, 'showAttorneyManagement']);
    Route::post('cases/{case}/parties/{party}/attorney', [CaseController::class, 'assignPartyAttorney']);
    Route::delete('cases/{case}/parties/{party}/attorney', [CaseController::class, 'removeAttorney']);
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('documents/{document}/preview', [DocumentController::class, 'preview'])->name('documents.preview');
    
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

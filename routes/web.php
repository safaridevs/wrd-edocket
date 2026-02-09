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
use App\Http\Controllers\ImpersonationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// Public case viewing (no authentication required)
Route::get('public/cases', [\App\Http\Controllers\PublicCaseController::class, 'index'])->name('public.cases.index');
Route::get('public/cases/{case}', [\App\Http\Controllers\PublicCaseController::class, 'show'])->name('public.cases.show');
Route::get('public/documents/{document}/download', [\App\Http\Controllers\PublicCaseController::class, 'downloadDocument'])->name('public.documents.download');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    
    // E-Docket Routes
    Route::get('cases', [CaseController::class, 'index'])->middleware('permission:read_case')->name('cases.index');
    Route::get('cases/create', [CaseController::class, 'create'])->middleware('permission:create_case')->name('cases.create');
    Route::post('cases', [CaseController::class, 'store'])->middleware('permission:create_case')->name('cases.store');
    Route::get('cases/{case}', [CaseController::class, 'show'])->middleware('permission:read_case')->name('cases.show');
    Route::get('cases/{case}/edit', [CaseController::class, 'edit'])->middleware('permission:create_case')->name('cases.edit');
    Route::put('cases/{case}', [CaseController::class, 'update'])->middleware('permission:create_case')->name('cases.update');

    Route::get('cases/{case}/file-document', [DocumentController::class, 'fileForm'])->name('documents.file');
    Route::post('cases/{case}/file-document', [DocumentController::class, 'store'])->name('documents.file.store');
    Route::get('cases/{case}/upload-documents', [CaseController::class, 'uploadDocuments'])->name('cases.documents.upload');
    Route::post('cases/{case}/upload-documents', [CaseController::class, 'storeDocuments'])->name('cases.documents.store');
    Route::post('documents/{document}/approve', [DocumentController::class, 'approve'])->middleware('permission:apply_stamp')->name('documents.approve');
    
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
    Route::post('cases/{case}/documents/{document}/update-title', [CaseController::class, 'updateDocumentTitle'])->name('cases.documents.update-title');


    Route::post('cases/{case}/documents/{document}/request-fix', [CaseController::class, 'requestDocumentFix'])->name('cases.documents.request-fix');
    Route::post('cases/{case}/documents/{document}/stamp', [CaseController::class, 'stampDocument'])->name('cases.documents.stamp');
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
    Route::post('cases/{case}/close', [CaseController::class, 'close'])->name('cases.close');
    Route::post('cases/{case}/archive', [CaseController::class, 'archive'])->name('cases.archive');
    Route::get('cases/{case}/assign-attorney', [CaseController::class, 'assignAttorneyForm'])->name('cases.assign-attorney');
    Route::post('cases/{case}/assign-attorney', [CaseController::class, 'assignAttorney'])->name('cases.assign-attorney.store');
    Route::get('cases/{case}/assign-hydrology-expert', [CaseController::class, 'assignHydrologyExpertForm'])->name('cases.assign-hydrology-expert');
    Route::post('cases/{case}/assign-hydrology-expert', [CaseController::class, 'assignHydrologyExpert'])->name('cases.assign-hydrology-expert.store');
    Route::get('cases/{case}/assign-alu-clerk', [CaseController::class, 'assignAluClerkForm'])->name('cases.assign-alu-clerk');
    Route::post('cases/{case}/assign-alu-clerk', [CaseController::class, 'assignAluClerk'])->name('cases.assign-alu-clerk.store');
    Route::get('cases/{case}/assign-wrd', [CaseController::class, 'assignWrdForm'])->name('cases.assign-wrd');
    Route::post('cases/{case}/assign-wrd', [CaseController::class, 'assignWrd'])->name('cases.assign-wrd.store');
    Route::post('cases/{case}/notify-parties', [CaseController::class, 'notifyParties'])->name('cases.notify-parties');
    
    // Attorney representation routes
    Route::get('cases/{case}/attorney-representation', [\App\Http\Controllers\AttorneyController::class, 'show'])->name('cases.attorney-representation');
    Route::post('cases/{case}/attorney/add-client', [\App\Http\Controllers\AttorneyController::class, 'addClient'])->name('attorney.add-client');
    Route::patch('attorney-relationships/{relationship}/terminate', [\App\Http\Controllers\AttorneyController::class, 'terminateRepresentation'])->name('attorney.terminate-representation');
    Route::get('attorney/my-clients', [\App\Http\Controllers\AttorneyController::class, 'myClients'])->name('attorney.my-clients');
    
    // Attorney profile management
    Route::get('attorney/profile/edit', [\App\Http\Controllers\AttorneyController::class, 'editProfile'])->name('attorney.profile.edit');
    Route::patch('attorney/profile', [\App\Http\Controllers\AttorneyController::class, 'updateProfile'])->name('attorney.profile.update');
    
    // Party contact management
    Route::get('party/contact/edit', [\App\Http\Controllers\PartyContactController::class, 'edit'])->name('party.contact.edit');
    Route::patch('party/contact', [\App\Http\Controllers\PartyContactController::class, 'update'])->name('party.contact.update');
    
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
    Route::get('admin/document-types', [AdminController::class, 'documentTypes'])->name('admin.document-types');
    Route::post('admin/document-types', [AdminController::class, 'storeDocumentType'])->name('admin.document-types.store');
    Route::post('admin/document-types/{documentType}/roles', [AdminController::class, 'updateDocumentTypeRoles'])->name('admin.document-types.roles');
    
    // User role routes
    Route::get('users/roles', [UserController::class, 'getUsersWithRoles'])->name('users.roles');
    Route::get('users/role/{role}', [UserController::class, 'getUsersByRole'])->name('users.by-role');
    Route::get('users/login-capable', [UserController::class, 'getLoginCapableUsers'])->name('users.login-capable');
    
    // User management routes
    Route::post('admin/users/{user}/approve', [AdminController::class, 'approveUser'])->middleware('permission:manage_users')->name('admin.users.approve');
    Route::get('admin/users/{user}/edit', [AdminController::class, 'edit'])->middleware('permission:manage_users')->name('admin.users.edit');
    Route::put('admin/users/{user}', [AdminController::class, 'update'])->middleware('permission:manage_users')->name('admin.users.update');
    Route::post('admin/users/{user}/deactivate', [AdminController::class, 'deactivate'])->middleware('permission:manage_users')->name('admin.users.deactivate');
    Route::delete('admin/users/{user}', [AdminController::class, 'destroy'])->middleware('permission:manage_users')->name('admin.users.destroy');
    
    // Impersonation routes
    Route::post('impersonate/switch', [ImpersonationController::class, 'switchRole'])->name('impersonate.switch');
    Route::post('impersonate/stop', [ImpersonationController::class, 'stopImpersonation'])->name('impersonate.stop');
    
    // Audit routes
    Route::get('audit/notifications', [\App\Http\Controllers\AuditController::class, 'notificationHistory'])->name('audit.notifications');
    Route::get('audit/system', [\App\Http\Controllers\AuditController::class, 'systemLogs'])->name('audit.system');
    Route::get('cases/{case}/audit', [\App\Http\Controllers\AuditController::class, 'caseHistory'])->name('cases.audit');
    
    // Paralegal routes
    Route::post('cases/{case}/paralegals', [CaseController::class, 'addParalegal'])->name('cases.paralegals.add');
    Route::delete('cases/{case}/paralegals/{party}', [CaseController::class, 'removeParalegal'])->name('cases.paralegals.remove');
});

require __DIR__.'/auth.php';

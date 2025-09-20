<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('action', [
                'create_case', 'update_case', 'submit_to_hu', 'accept_request', 'reject_request',
                'upload_doc', 'approve_doc', 'reject_doc', 'file_document', 'issue_order', 'sync_repo'
            ]);
            $table->json('meta_json')->nullable();
            $table->timestamps();
            
            $table->index(['case_id', 'action']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
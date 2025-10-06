<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the index first
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_case_id_action_index');
        });
        
        // Drop the CHECK constraint
        DB::statement('ALTER TABLE audit_logs DROP CONSTRAINT CK__audit_log__actio__00750D23');
        
        // Drop and recreate the column with new values
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn('action');
        });
        
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->enum('action', ['create_case', 'update_case', 'submit_to_hu', 'accept_request', 'reject_request', 'approve_case', 'stamp_document', 'notify_parties'])->default('create_case')->after('case_id');
            $table->index(['case_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_case_id_action_index');
        });
        
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn('action');
        });
        
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->enum('action', ['create_case', 'update_case', 'submit_to_hu', 'accept_request', 'reject_request', 'approve_case', 'notify_parties'])->default('create_case')->after('case_id');
            $table->index(['case_id', 'action']);
        });
    }
};
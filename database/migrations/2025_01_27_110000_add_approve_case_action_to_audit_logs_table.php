<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the existing check constraint
        DB::statement('ALTER TABLE audit_logs DROP CONSTRAINT CK__audit_log__actio__00750D23');
        
        // Add the new check constraint with 'approve_case' action
        DB::statement("ALTER TABLE audit_logs ADD CONSTRAINT CK__audit_log__actio__00750D23 CHECK (action IN ('create_case', 'update_case', 'submit_to_hu', 'accept_request', 'reject_request', 'approve_case', 'notify_parties'))");
    }

    public function down(): void
    {
        // Drop the constraint and recreate without 'approve_case'
        DB::statement('ALTER TABLE audit_logs DROP CONSTRAINT CK__audit_log__actio__00750D23');
        DB::statement("ALTER TABLE audit_logs ADD CONSTRAINT CK__audit_log__actio__00750D23 CHECK (action IN ('create_case', 'update_case', 'submit_to_hu', 'accept_request', 'reject_request', 'notify_parties'))");
    }
};
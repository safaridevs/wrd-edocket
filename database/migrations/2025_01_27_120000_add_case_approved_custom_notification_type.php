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
        DB::statement('ALTER TABLE notifications DROP CONSTRAINT CK__notificat__notif__6A85CC04');
        
        // Add the new check constraint (no changes needed - case_approved already exists)
        DB::statement("ALTER TABLE notifications ADD CONSTRAINT CK__notificat__notif__6A85CC04 CHECK (notification_type IN ('case_assignment', 'case_accepted', 'case_rejected', 'case_approved', 'document_filed', 'hearing_scheduled'))");
    }

    public function down(): void
    {
        // Drop the constraint and recreate without 'case_approved_custom'
        DB::statement('ALTER TABLE notifications DROP CONSTRAINT CK__notificat__notif__6A85CC04');
        DB::statement("ALTER TABLE notifications ADD CONSTRAINT CK__notificat__notif__6A85CC04 CHECK (notification_type IN ('case_assignment', 'case_accepted', 'case_rejected', 'case_approved', 'document_filed', 'hearing_scheduled'))");
    }
};
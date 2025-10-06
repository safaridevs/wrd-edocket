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
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_case_id_notification_type_index');
        });
        
        // Drop the CHECK constraint
        DB::statement('ALTER TABLE notifications DROP CONSTRAINT CK__notificat__notif__1B29035F');
        
        // Drop and recreate the column with new values
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('notification_type');
        });
        
        Schema::table('notifications', function (Blueprint $table) {
            $table->enum('notification_type', ['case_initiated', 'accepted', 'new_filing', 'issuance', 'case_approved', 'document_stamped'])->default('case_initiated')->after('case_id');
            $table->index(['case_id', 'notification_type']);
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_case_id_notification_type_index');
        });
        
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('notification_type');
        });
        
        Schema::table('notifications', function (Blueprint $table) {
            $table->enum('notification_type', ['case_initiated', 'accepted', 'new_filing', 'issuance', 'case_approved'])->default('case_initiated')->after('case_id');
            $table->index(['case_id', 'notification_type']);
        });
    }
};
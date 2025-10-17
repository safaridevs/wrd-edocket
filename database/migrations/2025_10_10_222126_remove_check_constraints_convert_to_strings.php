<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop indexes first
        try {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropIndex('notifications_case_id_notification_type_index');
            });
        } catch (Exception $e) {}

        // Get all CHECK constraints and drop them
        $constraints = DB::select("
            SELECT 
                t.name AS table_name,
                cc.name AS constraint_name
            FROM sys.check_constraints cc
            INNER JOIN sys.tables t ON cc.parent_object_id = t.object_id
            WHERE t.name IN ('notifications', 'cases', 'users', 'audit_logs', 'documents', 'case_parties')
        ");

        foreach ($constraints as $constraint) {
            try {
                DB::statement("ALTER TABLE {$constraint->table_name} DROP CONSTRAINT {$constraint->constraint_name}");
            } catch (Exception $e) {
                // Continue if constraint doesn't exist
            }
        }

        // Convert enum columns to VARCHAR
        Schema::table('cases', function (Blueprint $table) {
            $table->string('status', 50)->change();
            $table->string('case_type', 50)->change();
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->string('notification_type', 50)->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 50)->change();
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('action', 100)->change();
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->string('doc_type', 100)->change();
            $table->string('pleading_type', 100)->nullable()->change();
        });

        Schema::table('case_parties', function (Blueprint $table) {
            $table->string('role', 50)->change();
            $table->string('representation', 50)->change();
        });
    }

    public function down(): void
    {
        // Recreate the constraints (this is complex, so we'll just note it)
        // In practice, you'd recreate the original enum constraints here
    }
};
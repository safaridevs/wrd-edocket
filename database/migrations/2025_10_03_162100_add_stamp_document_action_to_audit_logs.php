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
        
        $this->dropCheckConstraints('audit_logs', 'action');
        
        // Drop and recreate the column with new values
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn('action');
        });
        
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('action', 100)->default('create_case')->after('case_id');
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
            $table->string('action', 100)->default('create_case')->after('case_id');
            $table->index(['case_id', 'action']);
        });
    }

    private function dropCheckConstraints(string $table, string $column): void
    {
        $constraints = DB::select("
            SELECT name
            FROM sys.check_constraints
            WHERE parent_object_id = OBJECT_ID(?)
              AND definition LIKE ?
        ", [$table, '%' . $column . '%']);

        foreach ($constraints as $constraint) {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT [{$constraint->name}]");
        }
    }
};

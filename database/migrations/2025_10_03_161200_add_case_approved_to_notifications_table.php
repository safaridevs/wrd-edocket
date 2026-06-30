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
        
        $this->dropCheckConstraints('notifications', 'notification_type');
        
        // Drop and recreate the column with new values
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('notification_type');
        });
        
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('notification_type', 50)->default('case_initiated')->after('case_id');
            $table->index(['case_id', 'notification_type']);
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('notification_type');
        });
        
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('notification_type', 50)->after('case_id');
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

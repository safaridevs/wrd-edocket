<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop all CHECK constraints
        $constraints = DB::select("
            SELECT 
                t.name AS table_name,
                cc.name AS constraint_name
            FROM sys.check_constraints cc
            INNER JOIN sys.tables t ON cc.parent_object_id = t.object_id
        ");

        foreach ($constraints as $constraint) {
            try {
                DB::statement("ALTER TABLE {$constraint->table_name} DROP CONSTRAINT {$constraint->constraint_name}");
                echo "Dropped constraint: {$constraint->constraint_name} from {$constraint->table_name}\n";
            } catch (Exception $e) {
                echo "Failed to drop constraint: {$constraint->constraint_name}\n";
            }
        }
    }

    public function down(): void
    {
        // Cannot easily recreate constraints
    }
};
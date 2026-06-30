<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropCheckConstraints('cases', 'status');
        
        // Add new constraint with rejected status
        DB::statement("ALTER TABLE cases ADD CONSTRAINT CK_cases_status CHECK (status IN ('draft', 'submitted_to_hu', 'active', 'rejected', 'closed', 'archived'))");
    }

    public function down(): void
    {
        $this->dropCheckConstraints('cases', 'status');
        
        // Restore original constraint (without rejected)
        DB::statement("ALTER TABLE cases ADD CONSTRAINT CK__cases__status__5D2BD0E6 CHECK (status IN ('draft', 'submitted_to_hu', 'active', 'closed', 'archived'))");
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

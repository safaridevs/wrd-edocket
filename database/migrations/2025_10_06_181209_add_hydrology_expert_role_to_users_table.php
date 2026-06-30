<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropCheckConstraints('users', 'role');
        
        // Add the new check constraint with hydrology_expert role
        DB::statement("ALTER TABLE users ADD CONSTRAINT CK__users__role__25A691D2 CHECK (role IN ('wrd', 'wrap_dir', 'alu_mgr', 'alu_clerk', 'alu_atty', 'hu_admin', 'hu_clerk', 'party', 'admin', 'hydrology_expert'))");
    }

    public function down(): void
    {
        // Drop the constraint and recreate without hydrology_expert
        $this->dropCheckConstraints('users', 'role');
        DB::statement("ALTER TABLE users ADD CONSTRAINT CK__users__role__25A691D2 CHECK (role IN ('wrd', 'wrap_dir', 'alu_mgr', 'alu_clerk', 'alu_atty', 'hu_admin', 'hu_clerk', 'party', 'admin'))");
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

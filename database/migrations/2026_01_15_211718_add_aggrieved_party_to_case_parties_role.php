<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop index first
        DB::statement("DROP INDEX case_parties_case_id_role_index ON case_parties");
        
        // SQL Server doesn't support MODIFY, need to use ALTER COLUMN
        DB::statement("ALTER TABLE case_parties ALTER COLUMN role VARCHAR(50)");
        
        // Recreate index
        DB::statement("CREATE INDEX case_parties_case_id_role_index ON case_parties (case_id, role)");
    }

    public function down(): void
    {
        // No rollback needed - column remains VARCHAR
    }
};

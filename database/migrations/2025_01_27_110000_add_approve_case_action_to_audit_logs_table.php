<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Remove any existing action constraints to allow flexible action strings
        $constraints = DB::select("SELECT name FROM sys.check_constraints WHERE parent_object_id = OBJECT_ID('audit_logs') AND definition LIKE '%action%'");
        
        foreach ($constraints as $constraint) {
            DB::statement("ALTER TABLE audit_logs DROP CONSTRAINT {$constraint->name}");
        }
        
        // No need to add constraints - action field should accept any string
    }

    public function down(): void
    {
        // No rollback needed
    }
};
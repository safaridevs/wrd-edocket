<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Check if constraint exists before dropping
        $constraintExists = DB::select("SELECT 1 FROM sys.check_constraints WHERE name = 'CK_cases_status'");
        
        if (!empty($constraintExists)) {
            DB::statement('ALTER TABLE cases DROP CONSTRAINT CK_cases_status');
        }

        // Add the new check constraint with 'approved' status
        DB::statement("ALTER TABLE cases ADD CONSTRAINT CK_cases_status CHECK (status IN ('draft', 'submitted_to_hu', 'active', 'approved', 'rejected', 'closed', 'archived'))");
    }

    public function down(): void
    {
        // Drop the constraint and recreate without 'approved'
        DB::statement('ALTER TABLE cases DROP CONSTRAINT CK_cases_status');
        DB::statement("ALTER TABLE cases ADD CONSTRAINT CK_cases_status CHECK (status IN ('draft', 'submitted_to_hu', 'active', 'rejected', 'closed', 'archived'))");
    }
};

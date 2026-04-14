<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("UPDATE cases SET status = 'active' WHERE status = 'approved'");

        $constraintExists = DB::select("SELECT 1 FROM sys.check_constraints WHERE name = 'CK_cases_status'");

        if (!empty($constraintExists)) {
            DB::statement('ALTER TABLE cases DROP CONSTRAINT CK_cases_status');
        }

        DB::statement("ALTER TABLE cases ADD CONSTRAINT CK_cases_status CHECK (status IN ('draft', 'submitted_to_hu', 'active', 'rejected', 'closed', 'archived'))");
    }

    public function down(): void
    {
        $constraintExists = DB::select("SELECT 1 FROM sys.check_constraints WHERE name = 'CK_cases_status'");

        if (!empty($constraintExists)) {
            DB::statement('ALTER TABLE cases DROP CONSTRAINT CK_cases_status');
        }

        DB::statement("ALTER TABLE cases ADD CONSTRAINT CK_cases_status CHECK (status IN ('draft', 'submitted_to_hu', 'active', 'approved', 'rejected', 'closed', 'archived'))");
    }
};

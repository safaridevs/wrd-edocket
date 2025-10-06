<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the existing check constraint
        DB::statement('ALTER TABLE cases DROP CONSTRAINT CK_cases_status');
        
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
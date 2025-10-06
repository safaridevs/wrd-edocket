<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the existing constraint
        DB::statement('ALTER TABLE cases DROP CONSTRAINT CK__cases__status__5D2BD0E6');
        
        // Add new constraint with rejected status
        DB::statement("ALTER TABLE cases ADD CONSTRAINT CK_cases_status CHECK (status IN ('draft', 'submitted_to_hu', 'active', 'rejected', 'closed', 'archived'))");
    }

    public function down(): void
    {
        // Drop the new constraint
        DB::statement('ALTER TABLE cases DROP CONSTRAINT CK_cases_status');
        
        // Restore original constraint (without rejected)
        DB::statement("ALTER TABLE cases ADD CONSTRAINT CK__cases__status__5D2BD0E6 CHECK (status IN ('draft', 'submitted_to_hu', 'active', 'closed', 'archived'))");
    }
};
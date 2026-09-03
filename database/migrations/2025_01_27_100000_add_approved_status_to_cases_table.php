<?php

use App\Support\SchemaCompat;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cases')) {
            return;
        }

        // Add the new check constraint with 'approved' status
        SchemaCompat::addCheckConstraint(
            'cases',
            'CK_cases_status',
            "status IN ('draft', 'submitted_to_hu', 'active', 'approved', 'rejected', 'closed', 'archived')"
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('cases')) {
            return;
        }

        // Recreate without 'approved'
        SchemaCompat::addCheckConstraint(
            'cases',
            'CK_cases_status',
            "status IN ('draft', 'submitted_to_hu', 'active', 'rejected', 'closed', 'archived')"
        );
    }
};

<?php

use App\Support\SchemaCompat;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cases')) {
            return;
        }

        DB::table('cases')->where('status', 'approved')->update(['status' => 'active']);

        SchemaCompat::addCheckConstraint(
            'cases',
            'CK_cases_status',
            "status IN ('draft', 'submitted_to_hu', 'active', 'rejected', 'closed', 'archived')"
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('cases')) {
            return;
        }

        SchemaCompat::addCheckConstraint(
            'cases',
            'CK_cases_status',
            "status IN ('draft', 'submitted_to_hu', 'active', 'approved', 'rejected', 'closed', 'archived')"
        );
    }
};
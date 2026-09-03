<?php

use App\Support\SchemaCompat;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        SchemaCompat::dropCheckConstraints('cases', 'status');

        // Add new constraint with rejected status
        SchemaCompat::addCheckConstraint(
            'cases',
            'CK_cases_status',
            "status IN ('draft', 'submitted_to_hu', 'active', 'rejected', 'closed', 'archived')"
        );
    }

    public function down(): void
    {
        SchemaCompat::dropCheckConstraints('cases', 'status');

        // Restore original constraint (without rejected)
        SchemaCompat::addCheckConstraint(
            'cases',
            'CK__cases__status__5D2BD0E6',
            "status IN ('draft', 'submitted_to_hu', 'active', 'closed', 'archived')"
        );
    }
};

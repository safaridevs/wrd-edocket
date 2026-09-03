<?php

use App\Support\SchemaCompat;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Remove any existing action constraints to allow flexible action strings
        SchemaCompat::dropCheckConstraints('audit_logs', 'action');

        // No need to add constraints - action field should accept any string
    }

    public function down(): void
    {
        // No rollback needed
    }
};
<?php

use App\Support\SchemaCompat;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Remove any existing notification_type constraints to allow flexible types
        SchemaCompat::dropCheckConstraints('notifications', 'notification_type');

        // No need to add constraints - notification_type field should accept any string
    }

    public function down(): void
    {
        // No rollback needed
    }
};
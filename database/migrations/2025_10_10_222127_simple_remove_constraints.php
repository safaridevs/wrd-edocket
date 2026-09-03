<?php

use App\Support\SchemaCompat;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Drop all CHECK constraints
        SchemaCompat::dropAllCheckConstraints();
    }

    public function down(): void
    {
        // Cannot easily recreate constraints
    }
};

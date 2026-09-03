<?php

use App\Support\SchemaCompat;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop index first so the column type can be altered
        SchemaCompat::dropIndexIfExists('case_parties', 'case_parties_case_id_role_index');

        Schema::table('case_parties', function (Blueprint $table) {
            $table->string('role', 50)->change();
        });

        // Recreate index
        SchemaCompat::createIndexIfMissing(
            'case_parties',
            'case_parties_case_id_role_index',
            ['case_id', 'role']
        );
    }

    public function down(): void
    {
        // No rollback needed - column remains VARCHAR
    }
};

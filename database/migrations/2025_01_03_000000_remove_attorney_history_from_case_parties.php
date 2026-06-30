<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('case_parties')) {
            return;
        }

        $columns = array_values(array_filter(
            ['attorney_history', 'representation'],
            fn (string $column) => Schema::hasColumn('case_parties', $column)
        ));

        if (empty($columns)) {
            return;
        }

        Schema::table('case_parties', function (Blueprint $table) {
            $table->dropColumn($columns);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('case_parties')) {
            return;
        }

        Schema::table('case_parties', function (Blueprint $table) {
            if (!Schema::hasColumn('case_parties', 'attorney_history')) {
                $table->json('attorney_history')->nullable();
            }
            if (!Schema::hasColumn('case_parties', 'representation')) {
                $table->string('representation', 50)->default('self');
            }
        });
    }
};

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

        Schema::table('case_parties', function (Blueprint $table) {
            if (!Schema::hasColumn('case_parties', 'attorney_id')) {
                $table->foreignId('attorney_id')->nullable()->constrained()->onDelete('set null');
            }
            if (!Schema::hasColumn('case_parties', 'representation')) {
                $table->string('representation', 50)->default('self');
            }
        });
    }

    public function down(): void
    {
        Schema::table('case_parties', function (Blueprint $table) {
            $table->dropForeign(['attorney_id']);
            $table->dropColumn(['attorney_id', 'representation']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('case_parties') || !Schema::hasColumn('case_parties', 'representation')) {
            return;
        }

        Schema::table('case_parties', function (Blueprint $table) {
            $table->dropColumn('representation');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('case_parties') || Schema::hasColumn('case_parties', 'representation')) {
            return;
        }

        Schema::table('case_parties', function (Blueprint $table) {
            $table->string('representation', 50)->default('self');
        });
    }
};

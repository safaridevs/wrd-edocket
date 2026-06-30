<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('case_parties') || Schema::hasColumn('case_parties', 'client_party_id')) {
            return;
        }

        Schema::table('case_parties', function (Blueprint $table) {
            $table->unsignedBigInteger('client_party_id')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('case_parties') || !Schema::hasColumn('case_parties', 'client_party_id')) {
            return;
        }

        Schema::table('case_parties', function (Blueprint $table) {
            $table->dropColumn('client_party_id');
        });
    }
};

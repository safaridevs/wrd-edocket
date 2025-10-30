<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('case_parties', function (Blueprint $table) {
            $table->json('attorney_history')->nullable()->after('attorney_id');
        });
    }

    public function down(): void
    {
        Schema::table('case_parties', function (Blueprint $table) {
            $table->dropColumn('attorney_history');
        });
    }
};
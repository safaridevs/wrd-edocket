<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_hydrology_expert_id')->nullable()->after('assigned_attorney_id');
            $table->foreign('assigned_hydrology_expert_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropForeign(['assigned_hydrology_expert_id']);
            $table->dropColumn('assigned_hydrology_expert_id');
        });
    }
};
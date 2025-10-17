<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->integer('assigned_attorney_id')->nullable();
            $table->integer('assigned_hydrology_expert_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn(['assigned_attorney_id', 'assigned_hydrology_expert_id']);
        });
    }
};
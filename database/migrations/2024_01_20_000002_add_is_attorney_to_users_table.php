<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_attorney')->default(false)->after('role');
            $table->string('bar_number')->nullable()->after('is_attorney');
            $table->string('law_firm')->nullable()->after('bar_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_attorney', 'bar_number', 'law_firm']);
        });
    }
};
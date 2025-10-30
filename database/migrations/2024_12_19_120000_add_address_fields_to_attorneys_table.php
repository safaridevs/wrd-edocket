<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attorneys', function (Blueprint $table) {
            $table->string('address_line1')->nullable()->after('bar_number');
            $table->string('address_line2')->nullable()->after('address_line1');
            $table->string('city')->nullable()->after('address_line2');
            $table->string('state', 2)->nullable()->after('city');
            $table->string('zip', 10)->nullable()->after('state');
        });
    }

    public function down(): void
    {
        Schema::table('attorneys', function (Blueprint $table) {
            $table->dropColumn(['address_line1', 'address_line2', 'city', 'state', 'zip']);
        });
    }
};
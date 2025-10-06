<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('case_parties', function (Blueprint $table) {
            $table->foreignId('attorney_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('representation', ['self', 'attorney'])->default('self');
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
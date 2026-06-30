<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('documents') || Schema::hasColumn('documents', 'custom_title')) {
            return;
        }

        Schema::table('documents', function (Blueprint $table) {
            $table->string('custom_title')->nullable()->after('doc_type');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('documents') || !Schema::hasColumn('documents', 'custom_title')) {
            return;
        }

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('custom_title');
        });
    }
};

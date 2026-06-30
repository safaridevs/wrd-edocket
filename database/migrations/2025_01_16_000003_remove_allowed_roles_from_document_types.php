<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('document_types') || !Schema::hasColumn('document_types', 'allowed_roles')) {
            return;
        }

        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn('allowed_roles');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('document_types') || Schema::hasColumn('document_types', 'allowed_roles')) {
            return;
        }

        Schema::table('document_types', function (Blueprint $table) {
            $table->json('allowed_roles')->nullable()->after('category');
        });
    }
};

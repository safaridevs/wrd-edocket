<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            // Check if fields don't already exist before adding them
            if (!Schema::hasColumn('cases', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('closed_at');
            }
            if (!Schema::hasColumn('cases', 'closed_by_user_id')) {
                $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->after('updated_by_user_id');
            }
            if (!Schema::hasColumn('cases', 'archived_by_user_id')) {
                $table->foreignId('archived_by_user_id')->nullable()->constrained('users')->after('closed_by_user_id');
            }
            if (!Schema::hasColumn('cases', 'closure_reason')) {
                $table->text('closure_reason')->nullable()->after('archived_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn(['archived_at', 'closed_by_user_id', 'archived_by_user_id', 'closure_reason']);
        });
    }
};
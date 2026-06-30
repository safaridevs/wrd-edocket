<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cases')) {
            return;
        }

        Schema::table('cases', function (Blueprint $table) {
            if (!Schema::hasColumn('cases', 'assigned_alu_clerk_id')) {
                $table->unsignedBigInteger('assigned_alu_clerk_id')->nullable();
            }

            if (!Schema::hasColumn('cases', 'assigned_wrd_id')) {
                $table->unsignedBigInteger('assigned_wrd_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('cases')) {
            return;
        }

        $columns = array_values(array_filter(
            ['assigned_alu_clerk_id', 'assigned_wrd_id'],
            fn (string $column) => Schema::hasColumn('cases', $column)
        ));

        if (!empty($columns)) {
            Schema::table('cases', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('case_parties', function (Blueprint $table) {
            $table->timestamp('effective_at')->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->foreignId('terminated_by_user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->softDeletes();
        });

        DB::table('case_parties')
            ->whereNull('effective_at')
            ->update(['effective_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('case_parties', function (Blueprint $table) {
            $table->dropForeign(['terminated_by_user_id']);
            $table->dropColumn([
                'effective_at',
                'terminated_at',
                'terminated_by_user_id',
                'deleted_at',
            ]);
        });
    }
};

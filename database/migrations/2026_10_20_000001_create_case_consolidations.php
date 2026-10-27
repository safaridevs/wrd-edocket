<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add columns separately so this migration can be rerun after SQL Server
        // partially applied the first ALTER TABLE statement.
        if (!Schema::hasColumn('cases', 'consolidated_into_case_id')) {
            Schema::table('cases', function (Blueprint $table) {
                $table->unsignedBigInteger('consolidated_into_case_id')->nullable();
            });
        }

        if (!Schema::hasColumn('cases', 'consolidated_by_user_id')) {
            Schema::table('cases', function (Blueprint $table) {
                $table->unsignedBigInteger('consolidated_by_user_id')->nullable();
            });
        }

        if (!Schema::hasColumn('cases', 'consolidated_at')) {
            Schema::table('cases', function (Blueprint $table) {
                $table->timestamp('consolidated_at')->nullable();
            });
        }

        // SQL Server rejects SET NULL on this self-reference as a potential
        // multiple cascade path. The default NO ACTION behavior is intentional:
        // consolidated records must not be deleted while their links exist.
        $caseForeignKeys = collect(Schema::getForeignKeys('cases'))->pluck('name');

        if (!$caseForeignKeys->contains('cases_consolidated_into_case_id_foreign')) {
            Schema::table('cases', function (Blueprint $table) {
                $table->foreign('consolidated_into_case_id')
                    ->references('id')
                    ->on('cases');
            });
        }

        if (!$caseForeignKeys->contains('cases_consolidated_by_user_id_foreign')) {
            Schema::table('cases', function (Blueprint $table) {
                $table->foreign('consolidated_by_user_id')
                    ->references('id')
                    ->on('users');
            });
        }

        if (!Schema::hasTable('case_consolidations')) {
            Schema::create('case_consolidations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('combined_case_id')->unique()->constrained('cases')->cascadeOnDelete();
                $table->foreignId('ordered_by_user_id')->constrained('users');
                $table->date('order_date');
                $table->text('reason')->nullable();
                $table->string('historical_access_policy', 50)->default('source_participants');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('case_consolidation_sources')) {
            Schema::create('case_consolidation_sources', function (Blueprint $table) {
                $table->id();
                $table->foreignId('case_consolidation_id')->constrained()->cascadeOnDelete();
                $table->foreignId('source_case_id')->unique()->constrained('cases');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('case_consolidation_party_maps')) {
            Schema::create('case_consolidation_party_maps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('case_consolidation_id')->constrained()->cascadeOnDelete();
                $table->foreignId('source_case_party_id')->unique()->constrained('case_parties');
                $table->foreignId('combined_case_party_id')->constrained('case_parties');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('case_consolidation_party_maps');
        Schema::dropIfExists('case_consolidation_sources');
        Schema::dropIfExists('case_consolidations');

        Schema::table('cases', function (Blueprint $table) {
            $table->dropForeign(['consolidated_into_case_id']);
            $table->dropForeign(['consolidated_by_user_id']);
            $table->dropColumn(['consolidated_into_case_id', 'consolidated_by_user_id', 'consolidated_at']);
        });
    }
};

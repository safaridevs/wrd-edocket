<?php

use App\Support\SchemaCompat;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        SchemaCompat::dropCheckConstraints('case_status_audits');

        if (! Schema::hasColumn('case_status_audits', 'status_type')) {
            Schema::table('case_status_audits', function (Blueprint $table) {
                $table->string('status_type')->default('workflow')->index();
            });
        }

        Schema::table('case_status_audits', function (Blueprint $table) {
            $table->string('from_status')->nullable()->change();
            $table->string('to_status')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('case_status_audits', 'status_type')) {
            Schema::table('case_status_audits', function (Blueprint $table) {
                $table->dropColumn('status_type');
            });
        }

        Schema::table('case_status_audits', function (Blueprint $table) {
            $table->string('from_status')->nullable()->change();
            $table->string('to_status')->nullable()->change();
        });
    }
};

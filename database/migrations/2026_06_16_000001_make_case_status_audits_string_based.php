<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropSqlServerCheckConstraints();

        if (!Schema::hasColumn('case_status_audits', 'status_type')) {
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

    private function dropSqlServerCheckConstraints(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlsrv') {
            return;
        }

        $constraints = DB::select(<<<'SQL'
            SELECT cc.name
            FROM sys.check_constraints cc
            INNER JOIN sys.tables t ON cc.parent_object_id = t.object_id
            WHERE t.name = 'case_status_audits'
        SQL);

        foreach ($constraints as $constraint) {
            $name = str_replace(']', ']]', $constraint->name);
            DB::statement("ALTER TABLE case_status_audits DROP CONSTRAINT [{$name}]");
        }
    }
};

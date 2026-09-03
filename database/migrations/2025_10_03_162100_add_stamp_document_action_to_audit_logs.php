<?php

use App\Support\SchemaCompat;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the index first
        SchemaCompat::dropIndexIfExists('audit_logs', 'audit_logs_case_id_action_index');

        SchemaCompat::dropCheckConstraints('audit_logs', 'action');

        // Drop and recreate the column with new values
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn('action');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('action', 100)->default('create_case')->after('case_id');
        });

        SchemaCompat::createIndexIfMissing(
            'audit_logs',
            'audit_logs_case_id_action_index',
            ['case_id', 'action']
        );
    }

    public function down(): void
    {
        SchemaCompat::dropIndexIfExists('audit_logs', 'audit_logs_case_id_action_index');

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn('action');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('action', 100)->default('create_case')->after('case_id');
        });

        SchemaCompat::createIndexIfMissing(
            'audit_logs',
            'audit_logs_case_id_action_index',
            ['case_id', 'action']
        );
    }
};

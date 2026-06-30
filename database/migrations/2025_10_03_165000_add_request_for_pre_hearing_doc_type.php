<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the index first
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex('documents_case_id_doc_type_index');
        });
        
        $this->dropCheckConstraints('documents', 'doc_type');
        
        // Drop and recreate the column with new values
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('doc_type');
        });
        
        Schema::table('documents', function (Blueprint $table) {
            $table->string('doc_type', 100)->default('application')->after('case_id');
            $table->index(['case_id', 'doc_type']);
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('doc_type');
        });
        
        Schema::table('documents', function (Blueprint $table) {
            $table->string('doc_type', 100)->default('application')->after('case_id');
        });
    }

    private function dropCheckConstraints(string $table, string $column): void
    {
        $constraints = DB::select("
            SELECT name
            FROM sys.check_constraints
            WHERE parent_object_id = OBJECT_ID(?)
              AND definition LIKE ?
        ", [$table, '%' . $column . '%']);

        foreach ($constraints as $constraint) {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT [{$constraint->name}]");
        }
    }
};

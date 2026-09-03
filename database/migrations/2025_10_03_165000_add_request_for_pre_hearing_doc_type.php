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
        SchemaCompat::dropIndexIfExists('documents', 'documents_case_id_doc_type_index');

        SchemaCompat::dropCheckConstraints('documents', 'doc_type');

        // Drop and recreate the column with new values
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('doc_type');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->string('doc_type', 100)->default('application')->after('case_id');
        });

        SchemaCompat::createIndexIfMissing(
            'documents',
            'documents_case_id_doc_type_index',
            ['case_id', 'doc_type']
        );
    }

    public function down(): void
    {
        SchemaCompat::dropIndexIfExists('documents', 'documents_case_id_doc_type_index');

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('doc_type');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->string('doc_type', 100)->default('application')->after('case_id');
        });
    }
};

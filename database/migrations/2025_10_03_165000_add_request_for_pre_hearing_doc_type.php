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
        
        // Drop the CHECK constraint
        DB::statement('ALTER TABLE documents DROP CONSTRAINT CK__documents__doc_t__62E4AA3C');
        
        // Drop and recreate the column with new values
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('doc_type');
        });
        
        Schema::table('documents', function (Blueprint $table) {
            $table->enum('doc_type', ['application', 'notice_publication', 'affidavit_publication', 'protest_letter', 'aggrieval_letter', 'request_to_docket', 'request_for_pre_hearing', 'order', 'filing_other', 'hearing_video', 'supporting', 'affidavit', 'exhibit', 'correspondence', 'technical_report', 'legal_brief', 'motion', 'other'])->default('application')->after('case_id');
            $table->index(['case_id', 'doc_type']);
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('doc_type');
        });
        
        Schema::table('documents', function (Blueprint $table) {
            $table->enum('doc_type', ['application', 'notice_publication', 'affidavit_publication', 'protest_letter', 'aggrieval_letter', 'request_to_docket', 'order', 'filing_other', 'hearing_video', 'supporting', 'affidavit', 'exhibit', 'correspondence', 'technical_report', 'legal_brief', 'motion', 'other'])->default('application')->after('case_id');
        });
    }
};
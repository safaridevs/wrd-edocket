<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained()->onDelete('cascade');
            $table->enum('doc_type', ['application', 'notice_publication', 'affidavit_publication', 'protest_letter', 'aggrieval_letter', 'request_to_docket', 'order', 'filing_other', 'hearing_video']);
            $table->string('original_filename');
            $table->string('stored_filename');
            $table->string('mime');
            $table->bigInteger('size_bytes');
            $table->string('checksum');
            $table->string('storage_uri');
            $table->foreignId('uploaded_by_user_id')->constrained('users');
            $table->timestamp('uploaded_at');
            $table->boolean('stamped')->default(false);
            $table->text('stamp_text')->nullable();
            $table->boolean('approved')->default(false);
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejected_reason')->nullable();
            
            $table->index(['case_id', 'doc_type']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
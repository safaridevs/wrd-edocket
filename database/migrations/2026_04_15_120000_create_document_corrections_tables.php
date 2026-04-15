<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases');
            $table->foreignId('original_document_id')->constrained('documents');
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users');
            $table->string('correction_type', 30)->default('fix_required');
            $table->text('summary');
            $table->string('status', 30)->default('open');
            $table->timestamp('requested_at');
            $table->timestamp('resubmitted_at')->nullable();
            $table->foreignId('resubmitted_by_user_id')->nullable()->constrained('users');
            $table->foreignId('replacement_document_id')->nullable()->constrained('documents');
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('accepted_by_user_id')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['case_id', 'status']);
            $table->index(['original_document_id', 'status']);
        });

        Schema::create('document_correction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_correction_id')->constrained('document_corrections')->cascadeOnDelete();
            $table->string('category', 50)->default('other');
            $table->text('item_note');
            $table->text('required_action')->nullable();
            $table->text('resolution_note')->nullable();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users');
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['document_correction_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_correction_items');
        Schema::dropIfExists('document_corrections');
    }
};

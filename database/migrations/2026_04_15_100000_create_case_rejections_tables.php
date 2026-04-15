<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_rejections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('rejected_by_user_id')->nullable()->constrained('users');
            $table->text('reason_summary');
            $table->string('status', 20)->default('open');
            $table->timestamp('rejected_at');
            $table->timestamp('resubmitted_at')->nullable();
            $table->foreignId('resubmitted_by_user_id')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['case_id', 'status']);
        });

        Schema::create('case_rejection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_rejection_id')->constrained('case_rejections')->cascadeOnDelete();
            $table->string('category', 50)->default('other');
            $table->text('item_note');
            $table->foreignId('document_id')->nullable()->constrained('documents');
            $table->text('required_action')->nullable();
            $table->text('resolution_note')->nullable();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users');
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['case_rejection_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_rejection_items');
        Schema::dropIfExists('case_rejections');
    }
};

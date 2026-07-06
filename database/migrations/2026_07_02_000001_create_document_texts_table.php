<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_texts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->unique()->constrained('documents')->onDelete('cascade');
            $table->longText('content_text')->nullable();
            $table->string('extraction_status', 40)->default('pending');
            $table->string('extractor', 80)->nullable();
            $table->text('extraction_error')->nullable();
            $table->timestamp('indexed_at')->nullable();
            $table->timestamps();

            $table->index(['extraction_status', 'indexed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_texts');
    }
};

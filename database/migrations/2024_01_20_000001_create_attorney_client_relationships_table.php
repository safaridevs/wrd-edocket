<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('attorneys') || !Schema::hasTable('cases') || Schema::hasTable('attorney_client_relationships')) {
            return;
        }

        Schema::create('attorney_client_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attorney_id')->constrained('attorneys')->onDelete('cascade');
            $table->foreignId('client_person_id')->constrained('persons')->onDelete('cascade');
            $table->foreignId('case_id')->constrained('cases')->onDelete('cascade');
            $table->string('status', 50)->default('active');
            $table->date('effective_date');
            $table->date('termination_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['attorney_id', 'client_person_id', 'case_id'], 'attorney_client_case_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attorney_client_relationships');
    }
};

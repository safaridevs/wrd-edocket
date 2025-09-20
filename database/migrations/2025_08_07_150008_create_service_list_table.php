<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_list', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained()->onDelete('cascade');
            $table->foreignId('person_id')->constrained('persons')->onDelete('cascade');
            $table->string('email');
            $table->enum('service_method', ['email', 'mail'])->default('email');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            
            $table->index(['case_id', 'person_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_list');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['applicant', 'protestant', 'aggrieved_party', 'counsel', 'paralegal', 'expert_wrd', 'expert_hydro', 'alu_atty', 'alu_supervising_atty']);
            $table->foreignId('person_id')->constrained('persons')->onDelete('cascade');
            $table->boolean('service_enabled')->default(true);
            
            $table->index(['case_id', 'role']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_parties');
    }
};
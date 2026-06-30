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
            $table->string('role', 50);
            $table->unsignedBigInteger('client_party_id')->nullable();
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

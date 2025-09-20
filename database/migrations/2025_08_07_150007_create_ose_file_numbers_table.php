<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ose_file_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained()->onDelete('cascade');
            $table->string('basin_code');
            $table->string('file_no_from');
            $table->string('file_no_to')->nullable();
            $table->timestamps();
            
            $table->index(['case_id', 'basin_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ose_file_numbers');
    }
};
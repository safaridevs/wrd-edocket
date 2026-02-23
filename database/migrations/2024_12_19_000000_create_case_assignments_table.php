<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('assignment_type', ['hydrology_expert', 'wrd', 'alu_clerk', 'alu_atty']);
            $table->timestamp('assigned_at')->useCurrent();
            $table->foreignId('assigned_by')->constrained('users');
            $table->timestamps();
            
            $table->unique(['case_id', 'user_id', 'assignment_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_assignments');
    }
};

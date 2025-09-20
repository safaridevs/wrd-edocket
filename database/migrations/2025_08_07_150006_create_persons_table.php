<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('persons', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['individual', 'company']);
            $table->string('prefix')->nullable();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('suffix')->nullable();
            $table->string('organization')->nullable();
            $table->string('title')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_mobile')->nullable();
            $table->string('phone_office')->nullable();
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['last_name', 'first_name']);
            $table->index('organization');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('persons');
    }
};
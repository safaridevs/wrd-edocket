<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('case_parties', function (Blueprint $table) {
            $table->dropForeign(['attorney_id']);
            $table->dropColumn('attorney_id');
        });
    }

    public function down()
    {
        Schema::table('case_parties', function (Blueprint $table) {
            $table->unsignedBigInteger('attorney_id')->nullable();
            $table->foreign('attorney_id')->references('id')->on('attorneys');
        });
    }
};
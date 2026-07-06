<?php

use Illuminate\Database\Migrations\Migration;

class RemoveCheckConstraintsConvertToStrings extends Migration
{
    public function up(): void
    {
        // Intentionally no-op.
        //
        // This legacy broad conversion was superseded by later schema cleanup
        // migrations already recorded on the stable database. Keeping this file
        // loadable prevents class resolution errors without touching tables.
    }

   public function down(): void
    {
        // Intentionally no-op.
    }
}

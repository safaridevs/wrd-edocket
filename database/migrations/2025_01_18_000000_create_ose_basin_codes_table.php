<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ose_basin_codes', function (Blueprint $table) {
            $table->id();
            $table->string('initial', 10)->unique();
            $table->string('description');
            $table->timestamps();
        });

        // Insert the basin codes data
        $basinCodes = [
            ['initial' => 'A', 'description' => 'Animas'],
            ['initial' => 'B', 'description' => 'Bluewater'],
            ['initial' => 'C', 'description' => 'Carlsbad'],
            ['initial' => 'CC', 'description' => 'Curry County'],
            ['initial' => 'SC', 'description' => 'Cloverdale'],
            ['initial' => 'CL', 'description' => 'Causey Lingo'],
            ['initial' => 'CP', 'description' => 'Capitan'],
            ['initial' => 'CR', 'description' => 'Canadian River'],
            ['initial' => 'CT', 'description' => 'Clayton'],
            ['initial' => 'E', 'description' => 'Estancia'],
            ['initial' => 'FS', 'description' => 'Fort Sumner'],
            ['initial' => 'G', 'description' => 'Gallup'],
            ['initial' => 'GSF', 'description' => 'Gila San Francisco'],
            ['initial' => 'H', 'description' => 'Hondo'],
            ['initial' => 'HA', 'description' => 'Hatchita'],
            ['initial' => 'HC', 'description' => 'Hagerman Canal'],
            ['initial' => 'HS', 'description' => 'Hot Springs'],
            ['initial' => 'HU', 'description' => 'Hueco'],
            ['initial' => 'J', 'description' => 'Jal'],
            ['initial' => 'L', 'description' => 'Lea County'],
            ['initial' => 'LA', 'description' => 'Las Animas Creek'],
            ['initial' => 'LRG', 'description' => 'Lower Rio Grande'],
            ['initial' => 'LV', 'description' => 'Lordsburg Valley'],
            ['initial' => 'LWD', 'description' => 'Livestock Water Declaration'],
            ['initial' => 'M', 'description' => 'Mimbres'],
            ['initial' => 'MR', 'description' => 'Mount Riley'],
            ['initial' => 'NH', 'description' => 'Nutt-Hockett'],
            ['initial' => 'P', 'description' => 'Portales'],
            ['initial' => 'PL', 'description' => 'Playas'],
            ['initial' => 'PN', 'description' => 'Penasco'],
            ['initial' => 'RA', 'description' => 'Roswell Artesian'],
            ['initial' => 'RG', 'description' => 'Rio Grande'],
            ['initial' => 'S', 'description' => 'Sandia'],
            ['initial' => 'SD', 'description' => 'Surface Declaration'],
            ['initial' => 'SJ', 'description' => 'San Juan'],
            ['initial' => 'SP', 'description' => 'Surface Permit'],
            ['initial' => 'SS', 'description' => 'San Simon'],
            ['initial' => 'ST', 'description' => 'Salt Basin'],
            ['initial' => 'T', 'description' => 'Tularosa'],
            ['initial' => 'TU', 'description' => 'Tucumcari'],
            ['initial' => 'UP', 'description' => 'Upper Pecos'],
            ['initial' => 'VV', 'description' => 'Virden Valley'],
            ['initial' => 'Y', 'description' => 'Yaqui Valley'],
        ];

        foreach ($basinCodes as $code) {
            DB::table('ose_basin_codes')->insert([
                'initial' => $code['initial'],
                'description' => $code['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ose_basin_codes');
    }
};
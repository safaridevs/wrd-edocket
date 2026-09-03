<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'unaffiliated')
            ->update(['role' => 'interested_party']);

        $existingInterestedParty = DB::table('roles')->where('name', 'interested_party')->first();
        $existingUnaffiliated = DB::table('roles')->where('name', 'unaffiliated')->first();

        if ($existingUnaffiliated && !$existingInterestedParty) {
            DB::table('roles')
                ->where('id', $existingUnaffiliated->id)
                ->update([
                    'name' => 'interested_party',
                    'display_name' => 'Interested Party',
                    'updated_at' => now(),
                ]);

            $existingInterestedParty = DB::table('roles')->where('id', $existingUnaffiliated->id)->first();
        } elseif ($existingInterestedParty) {
            DB::table('roles')
                ->where('id', $existingInterestedParty->id)
                ->update([
                    'display_name' => 'Interested Party',
                    'updated_at' => now(),
                ]);

            if ($existingUnaffiliated) {
                DB::table('document_type_role')
                    ->where('role_id', $existingUnaffiliated->id)
                    ->update(['role_id' => $existingInterestedParty->id]);

                DB::table('roles')->where('id', $existingUnaffiliated->id)->delete();
            }
        }

        if ($existingInterestedParty) {
            DB::table('users')
                ->whereNull('role_id')
                ->where('role', 'interested_party')
                ->update(['role_id' => $existingInterestedParty->id]);
        }
    }

    public function down(): void
    {
        DB::table('users')
            ->where('role', 'interested_party')
            ->update(['role' => 'unaffiliated']);

        $existingInterestedParty = DB::table('roles')->where('name', 'interested_party')->first();

        if ($existingInterestedParty) {
            DB::table('roles')
                ->where('id', $existingInterestedParty->id)
                ->update([
                    'name' => 'unaffiliated',
                    'display_name' => 'Unaffiliated',
                    'updated_at' => now(),
                ]);
        }
    }
};

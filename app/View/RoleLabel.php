<?php

namespace App\View;

class RoleLabel
{
    private const LABELS = [
        'hu_admin' => 'Hearing Unit Admin',
        'hu_clerk' => 'Hearing Unit Clerk',
        'alu_mgr' => 'ALU Manager',
        'alu_manager' => 'ALU Manager',
        'alu_managing_atty' => 'ALU Manager',
        'alu_clerk' => 'ALU Clerk',
        'alu_paralegal' => 'ALU Paralegal',
        'alu_atty' => 'ALU Attorney',
        'alu_attorney' => 'ALU Attorney',
    ];

    public static function format(?string $role): string
    {
        $normalized = strtolower(trim((string) $role));

        return self::LABELS[$normalized]
            ?? ucwords(str_replace('_', ' ', $normalized));
    }
}

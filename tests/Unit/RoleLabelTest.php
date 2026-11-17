<?php

namespace Tests\Unit;

use App\View\RoleLabel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RoleLabelTest extends TestCase
{
    #[DataProvider('aluRoles')]
    public function test_alu_roles_use_the_correct_acronym_and_title(string $role, string $label): void
    {
        $this->assertSame($label, RoleLabel::format($role));
    }

    public static function aluRoles(): array
    {
        return [
            ['alu_atty', 'ALU Attorney'],
            ['alu_attorney', 'ALU Attorney'],
            ['alu_clerk', 'ALU Clerk'],
            ['alu_paralegal', 'ALU Paralegal'],
            ['alu_mgr', 'ALU Manager'],
            ['alu_manager', 'ALU Manager'],
            ['alu_managing_atty', 'ALU Manager'],
        ];
    }

    public function test_non_alu_roles_keep_normal_title_formatting(): void
    {
        $this->assertSame('Contract Attorney', RoleLabel::format('contract_attorney'));
    }
}

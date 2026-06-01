<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserAuthorizationTest extends TestCase
{
    public function test_validation_roles_can_validate_leave_requests(): void
    {
        foreach (['Superieur', 'Directeur RH', 'Directeur Unité', 'Responsable RH'] as $roleName) {
            $user = new User();
            $user->setRelation('role', new Role(['nom' => $roleName]));

            $this->assertTrue($user->canValidateLeave(), "{$roleName} should validate leave requests.");
        }
    }

    public function test_employee_and_admin_cannot_validate_leave_requests_by_default(): void
    {
        foreach (['Employe', 'Admin'] as $roleName) {
            $user = new User();
            $user->setRelation('role', new Role(['nom' => $roleName]));

            $this->assertFalse($user->canValidateLeave(), "{$roleName} should not validate leave requests by default.");
        }
    }

    public function test_user_without_role_cannot_validate_leave_requests(): void
    {
        $user = new User();
        $user->setRelation('role', null);

        $this->assertFalse($user->canValidateLeave());
    }

    public function test_full_name_combines_first_name_and_name(): void
    {
        $user = new User([
            'first_name' => 'Ibou',
            'name' => 'Gueye',
        ]);

        $this->assertSame('Ibou Gueye', $user->full_name);
    }
}

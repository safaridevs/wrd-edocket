<?php

namespace Tests\Feature\Auth;

use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        // RegisteredUserController::emailExistsInLdap() fails closed: with no
        // reachable directory every registration is refused, so this only
        // proves anything where an LDAP bind account is configured.
        if (! config('ldap.connections.default.username')) {
            $this->markTestSkipped('Registration checks Active Directory and fails closed without one.');
        }

        Person::create([
            'type' => 'individual',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
        ]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}

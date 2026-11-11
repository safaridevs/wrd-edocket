<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_page_uses_the_consolidated_contact_editor(): void
    {
        $user = User::factory()->create();
        Person::create([
            'type' => 'individual',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $user->email,
        ]);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Save Contact Information')
            ->assertDontSee('Account Identity');
    }

    public function test_contact_update_synchronizes_account_name_phone_and_initials(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'phone' => '505-000-0000',
            'initials' => 'ON',
        ]);
        $person = Person::create([
            'type' => 'individual',
            'first_name' => 'Old',
            'last_name' => 'Name',
            'email' => $user->email,
        ]);

        $response = $this->actingAs($user)->patch('/profile/legal-service', [
            'first_name' => 'New',
            'middle_name' => null,
            'last_name' => 'Name',
            'suffix' => null,
            'title' => 'Paralegal',
            'phone_mobile' => '505-111-1111',
            'phone_office' => '505-222-2222',
            'initials' => 'NN',
            'address_line1' => '1 Main Street',
            'address_line2' => null,
            'city' => 'Santa Fe',
            'state' => 'NM',
            'zip' => '87505',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertSame('505-111-1111', $person->refresh()->phone_mobile);
        $this->assertSame('505-222-2222', $person->phone_office);
        $this->assertSame('New Name', $user->refresh()->name);
        $this->assertSame('505-222-2222', $user->phone);
        $this->assertSame('NN', $user->initials);
    }

    public function test_old_party_contact_page_redirects_to_profile_contact_section(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/party/contact/edit')
            ->assertRedirect('/profile#contact-information');
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}

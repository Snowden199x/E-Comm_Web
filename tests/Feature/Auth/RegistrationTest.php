<?php

namespace Tests\Feature\Auth;

use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/buyer/register');

        $response->assertStatus(200);
    }

    public function test_buyer_registration_requires_verified_email_before_creating_an_account(): void
    {
        $this->postJson('/buyer/register', [
            'last_name' => 'User',
            'first_name' => 'Test',
            'sex' => 'female',
            'email' => 'test@example.com',
            'birthday' => '2000-01-01',
            'id_category' => 'primary',
            'id_type' => 'PhilSys National ID',
            'valid_id' => UploadedFile::fake()->image('id.jpg'),
            'password' => 'TestPassword1!',
            'password_confirmation' => 'TestPassword1!',
            'province' => 'Laguna',
            'municipality' => 'Santa Cruz',
            'barangay' => 'Poblacion',
            'street' => '12 Main Street',
            'zip_code' => '4009',
            'contact_number' => '09123456789',
            'agree_terms' => 'on',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }
}

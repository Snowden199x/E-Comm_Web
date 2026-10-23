<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'role' => 'buyer',
            'status' => 'approved',
        ]);

        $response = $this->post('/buyer/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('buyer.dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'role' => 'buyer',
            'status' => 'approved',
        ]);

        $this->post('/buyer/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_seller_login_uses_the_seller_portal(): void
    {
        $seller = User::factory()->create([
            'email' => 'seller@example.test',
            'role' => 'seller',
            'status' => 'approved',
        ]);

        $this->post('/seller/login', [
            'email' => $seller->email,
            'password' => 'password',
        ])->assertRedirect(route('seller.dashboard', absolute: false));

        $this->assertAuthenticatedAs($seller);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/buyer/logout');

        $this->assertGuest();
        $response->assertRedirect(route('buyer.login'));
    }
}

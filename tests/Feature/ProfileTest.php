<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_account_page_is_displayed(): void
    {
        $user = User::factory()->create(['role' => 'buyer', 'status' => 'approved']);

        $response = $this
            ->actingAs($user)
            ->get('/buyer/account');

        $response->assertOk();
    }

    public function test_buyer_account_information_can_be_updated(): void
    {
        $user = User::factory()->create(['role' => 'buyer', 'status' => 'approved']);

        $response = $this
            ->actingAs($user)
            ->from('/buyer/account')
            ->put('/buyer/account', [
                'name' => 'Test User',
                'phone_number' => '09123456789',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/buyer/account');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('09123456789', $user->phone_number);
    }

    public function test_buyer_cannot_open_an_admin_account_page(): void
    {
        $user = User::factory()->create(['role' => 'buyer', 'status' => 'approved']);

        $this
            ->actingAs($user)
            ->get('/admin/registrations')
            ->assertRedirect('/login');
    }
}

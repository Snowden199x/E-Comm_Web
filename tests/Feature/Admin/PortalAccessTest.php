<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_sign_in_and_open_registration_and_user_management_pages(): void
    {
        $admin = User::factory()->create([
            'name' => 'Vendo Admin',
            'email' => 'admin@example.test',
            'role' => 'admin',
            'status' => 'approved',
            'account_status' => 'active',
            'is_super_admin' => true,
            'must_change_password' => false,
        ]);

        $this->get('/admin/login')->assertOk();

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->get(route('admin.registrations.index'))->assertOk();
        $this->get(route('admin.user-management.index'))->assertOk();
    }

    public function test_non_admin_account_cannot_sign_in_through_admin_login(): void
    {
        $buyer = User::factory()->create([
            'email' => 'buyer@example.test',
            'role' => 'buyer',
            'status' => 'approved',
        ]);

        $this->post('/admin/login', [
            'email' => $buyer->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }
}

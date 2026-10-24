<?php

namespace Tests\Feature\Logistics;

use App\Models\Profiles\FacilityStaff;
use App\Models\Profiles\LogisticsCenter;
use App\Models\Profiles\LogisticsCompany;
use App\Models\Profiles\LogisticsFacility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_manager_login_enters_the_logistics_portal(): void
    {
        [$companyUser] = $this->companyAccount();

        $this->post('/logistics/login', [
            'email' => $companyUser->email,
            'password' => 'password',
        ])->assertRedirect(route('lgp.company', absolute: false));

        $this->assertAuthenticatedAs($companyUser);
        $this->get(route('lgp.company'))->assertOk();
    }

    public function test_company_province_hub_and_station_accounts_open_only_their_portals(): void
    {
        [$companyUser, $company, $center] = $this->companyAccount();

        $provinceHub = LogisticsFacility::create([
            'logistics_center_id' => $center->id,
            'logistics_company_id' => $company->id,
            'kind' => 'province_hub',
            'name' => 'Laguna Province Hub',
            'province_code' => '0421',
            'province_hub_key' => '0421',
            'province' => 'Laguna',
            'municipality' => 'Laguna',
            'address' => 'Calamba, Laguna',
            'is_active' => true,
        ]);
        $provinceManager = $this->facilityUser('province_hub', 'province@example.test');
        FacilityStaff::create([
            'facility_id' => $provinceHub->id,
            'user_id' => $provinceManager->id,
            'staff_title' => 'province_manager',
        ]);

        $station = LogisticsFacility::create([
            'logistics_center_id' => $center->id,
            'logistics_company_id' => $company->id,
            'parent_id' => $provinceHub->id,
            'kind' => 'station_hub',
            'name' => 'Pagsanjan Station',
            'province_code' => '0421',
            'municipality_code' => '043424000',
            'station_municipality_key' => '043424000',
            'province' => 'Laguna',
            'municipality' => 'Pagsanjan',
            'address' => 'Pagsanjan, Laguna',
            'is_active' => true,
        ]);
        $stationManager = $this->facilityUser('station_hub', 'station@example.test');
        FacilityStaff::create([
            'facility_id' => $station->id,
            'user_id' => $stationManager->id,
            'staff_title' => 'station_manager',
        ]);

        $this->actingAs($companyUser)->get(route('lgp.company'))
            ->assertOk()->assertViewIs('logistics.company.dashboard');

        $this->actingAs($provinceManager)->get(route('lgp.hub.province'))
            ->assertOk();

        $this->actingAs($stationManager)->get(route('lgp.hub.station'))
            ->assertOk();
    }

    public function test_buyer_cannot_open_a_logistics_company_portal(): void
    {
        $buyer = User::factory()->create([
            'role' => 'buyer',
            'status' => 'approved',
        ]);

        $this->actingAs($buyer)->get(route('lgp.company'))->assertForbidden();
    }

    /** @return array{User, LogisticsCompany, LogisticsCenter} */
    private function companyAccount(): array
    {
        $user = $this->facilityUser('logistics_center', 'company@example.test');
        $center = LogisticsCenter::create([
            'user_id' => $user->id,
            'last_name' => 'Operator',
            'first_name' => 'Logistics',
            'sex' => 'female',
            'birthday' => '1990-01-01',
            'province' => 'Laguna',
            'municipality' => 'Calamba',
            'barangay' => 'Real',
            'business_name' => 'Vendo Laguna Logistics',
            'business_permit_path' => 'private/test-permit.pdf',
        ]);
        $company = LogisticsCompany::create([
            'logistics_center_id' => $center->id,
            'business_name' => 'Vendo Laguna Logistics',
            'status' => 'approved',
        ]);

        return [$user, $company, $center];
    }

    private function facilityUser(string $role, string $email): User
    {
        return User::factory()->create([
            'email' => $email,
            'role' => $role,
            'status' => 'approved',
            'account_status' => 'active',
        ]);
    }
}

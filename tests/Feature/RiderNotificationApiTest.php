<?php

namespace Tests\Feature;

use App\Models\Communication\Notification;
use App\Models\Profiles\CourierDetail;
use App\Models\Profiles\LogisticsCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiderNotificationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $riderUser;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $owner = User::create([
            'name' => 'Hub Owner', 'email' => 'hub-owner@example.test',
            'password' => 'password', 'role' => 'logistics_center', 'status' => 'approved',
        ]);
        $center = LogisticsCenter::create([
            'user_id' => $owner->id, 'last_name' => 'Santos', 'first_name' => 'Ana',
            'sex' => 'female', 'birthday' => '1990-01-01', 'province' => 'Laguna',
            'municipality' => 'Pagsanjan', 'barangay' => 'Poblacion',
            'business_name' => 'SHAPI EXPRESS', 'business_permit_path' => 'private/permit.jpg',
        ]);
        $this->riderUser = User::create([
            'name' => 'Rider One', 'email' => 'rider-one@example.test',
            'password' => 'password', 'role' => 'courier', 'status' => 'approved',
        ]);
        CourierDetail::create([
            'user_id' => $this->riderUser->id, 'logistics_center_id' => $center->id,
            'last_name' => 'Reyes', 'first_name' => 'Juan', 'sex' => 'male',
            'birthday' => '1995-05-05', 'province' => 'Laguna', 'municipality' => 'Pagsanjan',
            'barangay' => 'Poblacion', 'vehicle_type' => 'Motorcycle', 'plate_number' => 'MC-1',
            'drivers_license_path' => 'private/dl.jpg', 'or_cr_path' => 'private/orcr.jpg',
        ]);
        $this->token = $this->riderUser->createToken('rider-test', ['rider:scan'])->plainTextToken;
    }

    public function test_the_feed_lists_only_the_signed_in_riders_notifications_with_an_unread_count(): void
    {
        $older = $this->notify($this->riderUser, 'Pickup assignment VN-000001');
        $newer = $this->notify($this->riderUser, 'Delivery assignment VN-000002');
        $this->notify(User::create([
            'name' => 'Other Rider', 'email' => 'other-rider@example.test',
            'password' => 'password', 'role' => 'courier', 'status' => 'approved',
        ]), 'Someone else');

        $response = $this->withToken($this->token)->getJson('/api/v1/rider/notifications');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id)
            ->assertJsonPath('unread_count', 2)
            ->assertJsonPath('latest_id', $newer->id)
            ->assertJsonPath('data.0.read_at', null);
        $this->assertNotNull($response->json('data.0.time_ago'));
    }

    public function test_since_returns_only_notifications_newer_than_the_watermark(): void
    {
        $first = $this->notify($this->riderUser, 'Pickup assignment VN-000010');
        $second = $this->notify($this->riderUser, 'Delivery assignment VN-000011');

        $this->withToken($this->token)
            ->getJson('/api/v1/rider/notifications?since='.$first->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $second->id)
            ->assertJsonPath('latest_id', $second->id);
    }

    public function test_a_rider_can_mark_one_notification_read(): void
    {
        $notification = $this->notify($this->riderUser, 'Pickup assignment VN-000020');
        $this->notify($this->riderUser, 'Delivery assignment VN-000021');

        $this->withToken($this->token)
            ->postJson('/api/v1/rider/notifications/'.$notification->id.'/read')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('unread_count', 1);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_a_rider_cannot_read_another_riders_notification(): void
    {
        $other = User::create([
            'name' => 'Other Rider', 'email' => 'other-rider@example.test',
            'password' => 'password', 'role' => 'courier', 'status' => 'approved',
        ]);
        $notification = $this->notify($other, 'Private');

        $this->withToken($this->token)
            ->postJson('/api/v1/rider/notifications/'.$notification->id.'/read')
            ->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_mark_all_read_clears_the_unread_count(): void
    {
        $this->notify($this->riderUser, 'Pickup assignment VN-000030');
        $this->notify($this->riderUser, 'Delivery assignment VN-000031');

        $this->withToken($this->token)
            ->postJson('/api/v1/rider/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertSame(0, Notification::query()
            ->where('user_id', $this->riderUser->id)->whereNull('read_at')->count());
    }

    public function test_the_feed_requires_an_approved_rider_token(): void
    {
        $this->getJson('/api/v1/rider/notifications')->assertUnauthorized();
    }

    private function notify(User $user, string $title): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'type' => 'rider_task_assigned',
            'title' => $title,
            'message' => 'Open the Rider app to see this parcel.',
        ]);
    }
}

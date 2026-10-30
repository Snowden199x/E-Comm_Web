<?php

namespace Tests\Feature;

use App\Models\Communication\Notification;
use App\Models\Ecommerce\Order;
use App\Models\Profiles\CourierDetail;
use App\Models\Profiles\LogisticsCenter;
use App\Models\Profiles\LogisticsFacility;
use App\Models\Profiles\RiderTask;
use App\Models\User;
use App\Services\StationPickupExpiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StationPickupExpiryTest extends TestCase
{
    use RefreshDatabase;

    private LogisticsFacility $station;
    private User $riderUser;
    private User $staffUser;
    private CourierDetail $rider;

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
        $this->station = LogisticsFacility::create([
            'logistics_center_id' => $center->id, 'kind' => 'station_hub',
            'name' => 'Pagsanjan Station', 'province' => 'Laguna',
            'municipality' => 'Pagsanjan', 'address' => 'Pagsanjan, Laguna', 'is_active' => true,
        ]);
        $this->staffUser = User::create([
            'name' => 'Station Staff', 'email' => 'station-staff@example.test',
            'password' => 'password', 'role' => 'station_hub', 'status' => 'approved',
        ]);
        $this->riderUser = User::create([
            'name' => 'Rider One', 'email' => 'rider-one@example.test',
            'password' => 'password', 'role' => 'courier', 'status' => 'approved',
        ]);
        $this->rider = CourierDetail::create([
            'user_id' => $this->riderUser->id, 'logistics_center_id' => $center->id,
            'station_hub_id' => $this->station->id, 'last_name' => 'Reyes', 'first_name' => 'Juan',
            'sex' => 'male', 'birthday' => '1995-05-05', 'province' => 'Laguna',
            'municipality' => 'Pagsanjan', 'barangay' => 'Poblacion', 'vehicle_type' => 'Motorcycle',
            'plate_number' => 'MC-1', 'drivers_license_path' => 'private/dl.jpg',
            'or_cr_path' => 'private/orcr.jpg',
        ]);
    }

    public function test_pickup_assignment_without_a_scan_is_released_and_both_parties_are_notified(): void
    {
        [$order, $task] = $this->pickupParcel(now()->subMinutes(125));

        $released = (new StationPickupExpiry())->expireOverdue($this->station->id);

        $this->assertSame(1, $released);
        $this->assertSame('expired', $task->fresh()->status);
        $this->assertSame('No pickup scan within 60 minutes of assignment.', $task->fresh()->failure_reason);
        $this->assertNull($order->fresh()->courier_id);
        $this->assertSame('ready_for_pickup', $order->fresh()->status);

        $notified = Notification::query()->where('type', 'rider_task_expired')->pluck('user_id')
            ->map(fn ($id) => (int) $id)->sort()->values()->all();
        $this->assertSame(
            collect([$this->riderUser->id, $this->staffUser->id])->sort()->values()->all(),
            $notified,
        );
    }

    public function test_a_fresh_assignment_and_a_task_the_rider_started_are_left_alone(): void
    {
        [$order, $task] = $this->pickupParcel(now());
        [$startedOrder, $startedTask] = $this->pickupParcel(now()->subMinutes(125));
        $startedTask->update(['status' => 'in_progress', 'started_at' => now()->subMinutes(120)]);

        $released = (new StationPickupExpiry())->expireOverdue($this->station->id);

        $this->assertSame(0, $released);
        $this->assertSame('assigned', $task->fresh()->status);
        $this->assertSame('in_progress', $startedTask->fresh()->status);
        $this->assertNotNull($order->fresh()->courier_id);
        $this->assertNotNull($startedOrder->fresh()->courier_id);
        $this->assertSame(0, Notification::query()->where('type', 'rider_task_expired')->count());
    }

    public function test_a_parcel_that_is_no_longer_ready_expires_without_asking_for_a_new_rider(): void
    {
        [$order, $task] = $this->pickupParcel(now()->subMinutes(125));
        $order->update(['status' => 'cancelled']);

        $released = (new StationPickupExpiry())->expireOverdue($this->station->id);

        $this->assertSame(1, $released);
        $this->assertSame('expired', $task->fresh()->status);
        $this->assertNull($order->fresh()->courier_id);
        $this->assertSame(0, Notification::query()->where('type', 'rider_task_expired')->count());
    }

    /** @return array{0: Order, 1: RiderTask} */
    private function pickupParcel(mixed $assignedAt): array
    {
        $seller = User::create([
            'name' => 'Seller', 'email' => 'seller-'.uniqid().'@example.test',
            'password' => 'password', 'role' => 'seller', 'status' => 'approved',
        ]);
        $buyer = User::create([
            'name' => 'Buyer', 'email' => 'buyer-'.uniqid().'@example.test',
            'password' => 'password', 'role' => 'buyer', 'status' => 'approved',
        ]);
        $order = Order::create([
            'seller_id' => $seller->id, 'buyer_id' => $buyer->id, 'status' => 'ready_for_pickup',
            'total_amount' => 200, 'payment_mode' => 'cod', 'shipping_address' => '12 Sample Street',
        ]);
        $order->update(['courier_id' => $this->riderUser->id]);

        $task = RiderTask::create([
            'rider_id' => $this->rider->id, 'station_hub_id' => $this->station->id,
            'order_id' => $order->id, 'type' => 'pickup', 'status' => 'assigned',
            'assigned_by' => $this->staffUser->id, 'assigned_at' => $assignedAt,
        ]);

        return [$order, $task];
    }
}

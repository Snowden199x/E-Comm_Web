<?php

namespace Tests\Feature;

use App\Models\Communication\Notification;
use App\Models\Ecommerce\Order;
use App\Models\Profiles\CourierDetail;
use App\Models\Profiles\LogisticsCenter;
use App\Models\User;
use App\Services\DeliveryCutoff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeliveryCutoffTest extends TestCase
{
    use RefreshDatabase;

    private LogisticsCenter $center;
    private User $riderUser;
    private User $buyer;
    private User $seller;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $owner = User::create([
            'name' => 'Hub Owner', 'email' => 'hub-owner@example.test',
            'password' => 'password', 'role' => 'logistics_center', 'status' => 'approved',
        ]);
        $this->center = LogisticsCenter::create([
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
            'user_id' => $this->riderUser->id, 'logistics_center_id' => $this->center->id,
            'last_name' => 'Reyes', 'first_name' => 'Juan', 'sex' => 'male',
            'birthday' => '1995-05-05', 'province' => 'Laguna', 'municipality' => 'Pagsanjan',
            'barangay' => 'Poblacion', 'vehicle_type' => 'Motorcycle', 'plate_number' => 'MC-1',
            'drivers_license_path' => 'private/dl.jpg', 'or_cr_path' => 'private/orcr.jpg',
        ]);
        $this->buyer = User::create([
            'name' => 'Buyer', 'email' => 'buyer@example.test',
            'password' => 'password', 'role' => 'buyer', 'status' => 'approved',
        ]);
        $this->seller = User::create([
            'name' => 'Seller', 'email' => 'seller@example.test',
            'password' => 'password', 'role' => 'seller', 'status' => 'approved',
        ]);
        $this->token = $this->riderUser->createToken('rider-test', ['rider:scan'])->plainTextToken;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_a_parcel_still_out_for_delivery_after_the_cutoff_is_marked_failed(): void
    {
        Carbon::setTestNow('2026-10-30 17:05:00');
        $order = $this->deliveryParcel('out_for_delivery');

        $closed = app(DeliveryCutoff::class)->sweep();

        $this->assertSame(1, $closed);
        $this->assertSame('delivery_failed', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->delivery_failed_at);
        $this->assertDatabaseHas('order_status_events', [
            'order_id' => $order->id, 'from_status' => 'out_for_delivery', 'to_status' => 'delivery_failed',
        ]);
        $this->assertSame(1, Notification::query()
            ->where('user_id', $this->riderUser->id)->where('type', 'delivery_window_closed')->count());
        $this->assertSame(1, Notification::query()
            ->where('user_id', $this->center->user_id)->where('title', 'Delivery failed '.$order->number)->count());
        // The Order listener still tells the buyer the attempt failed.
        $this->assertSame(1, Notification::query()->where('user_id', $this->buyer->id)->count());
    }

    public function test_the_window_only_closes_after_five_pm(): void
    {
        Carbon::setTestNow('2026-10-30 16:59:00');
        $order = $this->deliveryParcel('out_for_delivery');

        $this->assertSame(0, app(DeliveryCutoff::class)->sweep());
        $this->assertSame('out_for_delivery', $order->fresh()->status);
        $this->assertSame(0, Notification::query()->where('type', 'delivery_window_closed')->count());
    }

    public function test_no_parcel_can_go_out_for_delivery_after_the_cutoff(): void
    {
        Carbon::setTestNow('2026-10-30 17:20:00');
        $order = $this->deliveryParcel('assigned_to_rider');

        $this->withToken($this->token)
            ->postJson('/api/v1/rider/scans', [
                'tracking_number' => $order->tracking_number,
                'scan_type' => 'out_for_delivery',
                'scan_key' => (string) Str::uuid(),
            ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'The 5:00 PM delivery window has closed. Reschedule this parcel in the Rider app to deliver it another day.');

        $this->assertSame('assigned_to_rider', $order->fresh()->status);
    }

    public function test_the_rider_can_reschedule_a_failed_parcel_for_the_next_day(): void
    {
        Carbon::setTestNow('2026-10-30 17:20:00');
        $order = $this->deliveryParcel('delivery_failed', ['delivery_failed_at' => now()]);

        $this->withToken($this->token)
            ->postJson('/api/v1/rider/deliveries/'.$order->id.'/reschedule')
            ->assertOk()
            ->assertJsonPath('status', 'assigned_to_rider')
            ->assertJsonPath('delivery_scheduled_for', '2026-10-31');

        $order->refresh();
        $this->assertSame('assigned_to_rider', $order->status);
        $this->assertSame('2026-10-31', $order->delivery_scheduled_for->toDateString());
        $this->assertSame(1, Notification::query()
            ->where('user_id', $this->buyer->id)->where('title', 'Delivery rescheduled '.$order->number)->count());
        $this->assertSame(1, Notification::query()
            ->where('user_id', $this->seller->id)->where('title', 'Delivery rescheduled '.$order->number)->count());
    }

    public function test_a_parcel_cannot_go_out_before_its_rescheduled_day(): void
    {
        Carbon::setTestNow('2026-10-31 09:00:00');
        $order = $this->deliveryParcel('assigned_to_rider', ['delivery_scheduled_for' => '2026-11-01']);

        $this->withToken($this->token)
            ->postJson('/api/v1/rider/scans', [
                'tracking_number' => $order->tracking_number,
                'scan_type' => 'out_for_delivery',
                'scan_key' => (string) Str::uuid(),
            ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'This parcel is scheduled for delivery on 1 Nov 2026. Scan it out for delivery that day.');
    }

    public function test_failed_parcels_stay_in_the_rider_list_with_a_reschedule_action(): void
    {
        Carbon::setTestNow('2026-10-30 17:20:00');
        $order = $this->deliveryParcel('out_for_delivery');

        $this->withToken($this->token)
            ->getJson('/api/v1/rider/assignments')
            ->assertOk()
            ->assertJsonPath('assignments.0.order_id', $order->id)
            ->assertJsonPath('assignments.0.status', 'delivery_failed')
            ->assertJsonPath('assignments.0.delivery_window.cutoff_label', '5:00 PM')
            ->assertJsonPath('assignments.0.delivery_window.reschedulable', true);
    }

    /** @param array<string, mixed> $extra */
    private function deliveryParcel(string $status, array $extra = []): Order
    {
        $order = Order::create([
            'seller_id' => $this->seller->id,
            'buyer_id' => $this->buyer->id,
            'status' => $status,
            'total_amount' => 500,
            'payment_mode' => 'cod',
            'shipping_address' => '12 Sample Street, Pagsanjan, Laguna',
            'destination_logistics_center_id' => $this->center->id,
        ] + $extra);
        $order->delivery_courier_id = $this->riderUser->id;
        $order->saveQuietly();

        return $order;
    }
}

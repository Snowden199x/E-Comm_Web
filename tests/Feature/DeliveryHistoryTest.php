<?php

namespace Tests\Feature;

use App\Models\Ecommerce\Order;
use App\Models\Profiles\CourierDetail;
use App\Models\Profiles\LogisticsCenter;
use App\Models\Profiles\OrderDeliveryProof;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeliveryHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $riderUser;
    private User $otherRider;
    private User $buyer;
    private User $seller;
    private LogisticsCenter $center;
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

        $this->riderUser = $this->rider('Rider One', 'rider-one@example.test');
        $this->otherRider = $this->rider('Rider Two', 'rider-two@example.test');
        $this->token = $this->riderUser->createToken('rider-test', ['rider:scan'])->plainTextToken;

        $this->buyer = User::create([
            'name' => 'Buyer One', 'email' => 'buyer-one@example.test',
            'password' => 'password', 'role' => 'buyer', 'status' => 'approved',
        ]);
        $this->seller = User::create([
            'name' => 'Seller One', 'email' => 'seller-one@example.test',
            'password' => 'password', 'role' => 'seller', 'status' => 'approved',
        ]);
    }

    public function test_the_history_lists_the_parcels_the_rider_handed_over_on_the_chosen_date(): void
    {
        $morning = $this->handover($this->riderUser, 'morning', '2026-11-15 09:20:00', 120.00);
        $afternoon = $this->handover($this->riderUser, 'afternoon', '2026-11-15 14:05:00', 80.50);

        $response = $this->withToken($this->token)
            ->getJson('/api/v1/rider/deliveries/history?date=2026-11-15');

        $response->assertOk()
            ->assertJsonPath('date', '2026-11-15')
            ->assertJsonPath('summary.count', 2)
            ->assertJsonPath('summary.cash_total', '₱200.50')
            ->assertJsonPath('summary.cash_total_value', '200.50')
            ->assertJsonCount(2, 'deliveries')
            // Newest handover first.
            ->assertJsonPath('deliveries.0.tracking_number', $afternoon->tracking_number)
            ->assertJsonPath('deliveries.1.tracking_number', $morning->tracking_number)
            ->assertJsonPath('deliveries.1.recipient_name', 'Morning Receiver')
            ->assertJsonPath('deliveries.1.buyer_name', 'Buyer One')
            ->assertJsonPath('deliveries.1.address', '12 Sample Street, Pagsanjan, Laguna')
            ->assertJsonPath('deliveries.1.cash_collected', '₱120.00')
            ->assertJsonPath('deliveries.1.delivered_at_label', '9:20 AM');
    }

    public function test_the_history_defaults_to_today_when_no_date_is_sent(): void
    {
        $this->handover($this->riderUser, 'today', now()->setTime(11, 0)->toDateTimeString(), 50.00);

        $this->withToken($this->token)
            ->getJson('/api/v1/rider/deliveries/history')
            ->assertOk()
            ->assertJsonPath('date', now()->toDateString())
            ->assertJsonPath('summary.count', 1);
    }

    public function test_another_day_and_another_riders_handovers_are_not_listed(): void
    {
        $this->handover($this->riderUser, 'today', '2026-11-15 10:00:00', 0.00);
        $this->handover($this->riderUser, 'yesterday', '2026-11-14 10:00:00', 0.00);
        $this->handover($this->otherRider, 'other rider', '2026-11-15 12:00:00', 0.00);

        $response = $this->withToken($this->token)
            ->getJson('/api/v1/rider/deliveries/history?date=2026-11-15');

        $response->assertOk()->assertJsonPath('summary.count', 1);
        $this->assertSame('VN-TODAY', $response->json('deliveries.0.tracking_number'));
    }

    public function test_a_parcel_without_collected_cash_shows_no_cash_amount(): void
    {
        $order = $this->handover($this->riderUser, 'prepaid', '2026-11-15 08:00:00', null);

        $this->withToken($this->token)
            ->getJson('/api/v1/rider/deliveries/history?date=2026-11-15')
            ->assertOk()
            ->assertJsonPath('deliveries.0.tracking_number', $order->tracking_number)
            ->assertJsonPath('deliveries.0.cash_collected', null)
            ->assertJsonPath('summary.cash_total', '₱0.00');
    }

    public function test_the_history_requires_an_approved_rider_token(): void
    {
        $this->getJson('/api/v1/rider/deliveries/history')->assertUnauthorized();
    }

    private function rider(string $name, string $email): User
    {
        $rider = User::create([
            'name' => $name, 'email' => $email,
            'password' => 'password', 'role' => 'courier', 'status' => 'approved',
        ]);
        CourierDetail::create([
            'user_id' => $rider->id, 'logistics_center_id' => $this->center->id,
            'last_name' => 'Reyes', 'first_name' => 'Juan', 'sex' => 'male',
            'birthday' => '1995-05-05', 'province' => 'Laguna', 'municipality' => 'Pagsanjan',
            'barangay' => 'Poblacion', 'vehicle_type' => 'Motorcycle', 'plate_number' => 'MC-1',
            'drivers_license_path' => 'private/dl.jpg', 'or_cr_path' => 'private/orcr.jpg',
        ]);

        return $rider;
    }

    /** A delivered order plus the handover proof that puts it on the Rider's history. */
    private function handover(User $rider, string $slug, string $capturedAt, ?float $cod): Order
    {
        $order = Order::create([
            'seller_id' => $this->seller->id,
            'buyer_id' => $this->buyer->id,
            'status' => 'delivered',
            'total_amount' => 500,
            'payment_mode' => 'cod',
            'shipping_address' => '12 Sample Street, Pagsanjan, Laguna',
            'destination_logistics_center_id' => $this->center->id,
        ]);
        $order->forceFill([
            'tracking_number' => 'VN-'.strtoupper($slug),
            'delivery_courier_id' => $rider->id,
            'delivered_at' => $capturedAt,
            'cod_amount' => $cod,
            'money_status' => $cod === null ? null : 'collected_by_rider',
        ])->saveQuietly();

        OrderDeliveryProof::create([
            'order_id' => $order->id,
            'rider_id' => $rider->id,
            'station_hub_id' => null,
            'scan_key' => (string) Str::uuid(),
            'recipient_name' => ucfirst($slug).' Receiver',
            'photo_path' => 'private/proofs/'.Str::slug($slug).'.jpg',
            'captured_at' => $capturedAt,
        ]);

        return $order->fresh();
    }
}

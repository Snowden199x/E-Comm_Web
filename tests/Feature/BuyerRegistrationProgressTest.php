<?php

namespace Tests\Feature;

use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BuyerRegistrationProgressTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Focused SQLite schema: unrelated legacy migrations use MySQL-only ALTER statements.
        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2026_08_16_092724_add_role_and_status_to_users_table.php',
            '2026_08_20_084154_add_phone_number_to_users_table.php',
            '2026_08_21_083049_create_buyer_details_table.php',
            '2026_09_12_115318_add_valid_id_path_2_to_buyer_details_table.php',
            '2026_09_12_112449_create_otp_verifications_table.php',
        ] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        Mail::fake();
        Storage::fake('public');
        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        Schema::dropAllTables();
        parent::tearDown();
    }

    private function verifyEmail(string $email = 'buyer@example.test'): void
    {
        $this->postJson('/buyer/otp/send', ['email' => $email])->assertOk();
        $code = OtpVerification::where('email', strtolower($email))->firstOrFail()->otp_code;
        $this->postJson('/buyer/otp/verify', ['email' => $email, 'otp_code' => $code])
            ->assertOk()->assertJsonStructure(['expires_at']);
    }

    private function payload(): array
    {
        return [
            'last_name' => 'Buyer', 'first_name' => 'Test', 'sex' => 'female',
            'email' => 'buyer@example.test', 'birthday' => '2000-01-01',
            'password' => 'TestPassword1!', 'password_confirmation' => 'TestPassword1!',
            'province' => 'Laguna', 'municipality' => 'Santa Cruz', 'barangay' => 'Test Barangay',
            'street' => '12 Test Street', 'zip_code' => '4009',
            'contact_number' => '09123456789', 'agree_terms' => 'on',
            'id_category' => 'primary', 'id_type' => 'PhilSys National ID',
            'valid_id' => UploadedFile::fake()->image('id.jpg'),
        ];
    }

    public function test_refresh_restores_verified_email_only_in_the_verifying_session(): void
    {
        $this->verifyEmail('Buyer@Example.Test');
        $this->get('/buyer/register')->assertOk()->assertViewHas('registrationVerification', fn ($proof) => $proof['email'] === 'buyer@example.test' && $proof['expires_at'] > time());
        $this->app['session']->flush();
        $this->get('/buyer/register')->assertOk()->assertViewHas('registrationVerification', ['email' => '', 'expires_at' => 0]);
        $this->postJson('/buyer/register', $this->payload())->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_validation_failure_keeps_verification_and_success_clears_it(): void
    {
        $this->verifyEmail();
        $payload = $this->payload();
        unset($payload['valid_id']);
        $this->postJson('/buyer/register', $payload)->assertUnprocessable()->assertJsonValidationErrors('valid_id');
        $this->get('/buyer/register')->assertViewHas('registrationVerification', fn ($proof) => $proof['email'] === 'buyer@example.test');
        $this->postJson('/buyer/register', $this->payload())->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('users', ['email' => 'buyer@example.test', 'role' => 'buyer', 'status' => 'pending']);
        $this->assertDatabaseHas('buyer_details', ['street' => '12 Test Street', 'house_no' => null]);
        $this->assertDatabaseCount('otp_verifications', 0);
        $this->assertFalse(session()->has('buyer_registration_verification'));
    }

    public function test_expired_or_changed_email_cannot_register(): void
    {
        $this->verifyEmail();
        $payload = $this->payload();
        $payload['email'] = 'different@example.test';
        $this->postJson('/buyer/register', $payload)->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->travel(31)->minutes();
        $this->get('/buyer/register')->assertViewHas('registrationVerification', ['email' => '', 'expires_at' => 0]);
        $this->postJson('/buyer/register', $this->payload())->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->travelBack();
    }

    public function test_secondary_ids_are_required_and_both_files_are_saved(): void
    {
        $this->verifyEmail();
        $payload = $this->payload();
        $payload['id_category'] = 'secondary';
        $payload['id_type_1'] = 'PhilHealth ID';
        $payload['id_type_2'] = 'TIN ID';
        $this->postJson('/buyer/register', $payload)->assertUnprocessable()->assertJsonValidationErrors('valid_id_2');
        $payload['valid_id_2'] = UploadedFile::fake()->image('second-id.jpg');
        $this->postJson('/buyer/register', $payload)->assertOk();
        $detail = User::where('email', 'buyer@example.test')->firstOrFail()->buyerDetail;
        $this->assertSame('secondary', $detail->id_type);
        Storage::disk('public')->assertExists([$detail->valid_id_path, $detail->valid_id_path_2]);
    }

    public function test_resending_invalidates_verification_and_verified_codes_cannot_be_replayed(): void
    {
        $this->verifyEmail();
        $code = OtpVerification::first()->otp_code;
        $this->postJson('/buyer/otp/verify', ['email' => 'buyer@example.test', 'otp_code' => $code])->assertUnprocessable();
        $this->postJson('/buyer/otp/send', ['email' => 'buyer@example.test'])->assertOk();
        $this->get('/buyer/register')->assertViewHas('registrationVerification', ['email' => '', 'expires_at' => 0]);
    }
}

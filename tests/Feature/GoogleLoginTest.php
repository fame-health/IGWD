<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_ids' => ['android-client-id.apps.googleusercontent.com'],
        ]);
    }

    public function test_user_can_login_with_google_account(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response($this->googlePayload([
                'sub' => 'google-user-123',
                'email' => 'andi.google@example.com',
                'name' => 'Andi Google',
                'picture' => 'https://example.com/andi.jpg',
            ])),
        ]);

        $response = $this->postJson('/api/v1/login/google', [
            'id_token' => 'valid-google-id-token',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Login Google berhasil.')
            ->assertJsonPath('data.user.email', 'andi.google@example.com')
            ->assertJsonPath('data.user.name', 'Andi Google')
            ->assertJsonPath('data.user.role', 'pasien')
            ->assertJsonPath('data.user.profile_complete', false)
            ->assertJsonPath('data.user.requires_patient_profile', true)
            ->assertJsonPath('data.user.avatar_url', 'https://example.com/andi.jpg')
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'patient_id',
                        'profile_complete',
                        'requires_patient_profile',
                        'is_active',
                        'avatar_url',
                    ],
                    'role',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'andi.google@example.com',
            'google_id' => 'google-user-123',
            'role' => 'pasien',
            'is_active' => true,
            'avatar_url' => 'https://example.com/andi.jpg',
        ]);
    }

    public function test_google_login_links_existing_user_by_email(): void
    {
        $user = User::factory()->create([
            'name' => 'Akun Existing',
            'email' => 'existing@example.com',
            'role' => 'dokter',
            'is_active' => true,
            'google_id' => null,
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response($this->googlePayload([
                'sub' => 'google-existing-456',
                'email' => 'existing@example.com',
                'name' => 'Nama Dari Google',
            ])),
        ]);

        $response = $this->postJson('/api/v1/login/google', [
            'id_token' => 'valid-google-id-token',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.name', 'Akun Existing')
            ->assertJsonPath('data.user.role', 'dokter')
            ->assertJsonPath('data.user.requires_patient_profile', false);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'google_id' => 'google-existing-456',
        ]);
    }

    public function test_google_login_rejects_invalid_token(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'error' => 'invalid_token',
            ], 400),
        ]);

        $response = $this->postJson('/api/v1/login/google', [
            'id_token' => 'invalid-google-id-token',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Token Google tidak valid.')
            ->assertJsonValidationErrors(['id_token']);
    }

    public function test_google_login_rejects_wrong_client_id(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response($this->googlePayload([
                'aud' => 'unknown-client-id.apps.googleusercontent.com',
            ])),
        ]);

        $response = $this->postJson('/api/v1/login/google', [
            'id_token' => 'wrong-audience-token',
        ]);

        $response->assertUnauthorized();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_google_patient_must_complete_profile_before_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'pasien',
            'patient_id' => null,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/dashboard')
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Lengkapi biodata pasien terlebih dahulu.');
    }

    public function test_google_patient_can_complete_required_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Nama Google',
            'role' => 'pasien',
            'patient_id' => null,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/me/patient-profile', [
            'name' => 'Pasien Google',
            'gender' => 'laki-laki',
            'birth_date' => '1990-08-17',
            'phone' => '081234567890',
            'address' => 'Jl. Sehat No. 1',
            'payment_status' => 'BPJS',
        ]);

        $patientId = $response->json('data.patient.id');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Biodata pasien berhasil disimpan.')
            ->assertJsonPath('data.user.name', 'Pasien Google')
            ->assertJsonPath('data.user.patient_id', $patientId)
            ->assertJsonPath('data.user.profile_complete', true)
            ->assertJsonPath('data.user.requires_patient_profile', false)
            ->assertJsonPath('data.patient.name', 'Pasien Google')
            ->assertJsonPath('data.patient.age', Carbon::parse('1990-08-17')->age)
            ->assertJsonPath('data.patient.gender', 'laki-laki')
            ->assertJsonPath('data.patient.payment_status', 'BPJS');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Pasien Google',
            'patient_id' => $patientId,
        ]);

        $this->assertDatabaseHas('patients', [
            'id' => $patientId,
            'name' => 'Pasien Google',
            'gender' => 'laki-laki',
            'patient_status' => 'Aktif',
        ]);
    }

    public function test_legacy_typo_patient_profile_route_still_completes_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Nama Google',
            'role' => 'pasien',
            'patient_id' => null,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/me/patien-profile', [
            'name' => 'Pasien Google',
            'gender' => 'perempuan',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.requires_patient_profile', false)
            ->assertJsonPath('data.patient.name', 'Pasien Google')
            ->assertJsonPath('data.patient.gender', 'perempuan');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function googlePayload(array $overrides = []): array
    {
        return array_merge([
            'iss' => 'https://accounts.google.com',
            'aud' => 'android-client-id.apps.googleusercontent.com',
            'sub' => 'google-user-id',
            'email' => 'google@example.com',
            'email_verified' => 'true',
            'name' => 'Google User',
            'picture' => null,
            'exp' => now()->addHour()->timestamp,
        ], $overrides);
    }
}

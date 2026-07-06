<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
            ->assertJsonPath('data.user.avatar_url', 'https://example.com/andi.jpg')
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => ['id', 'name', 'email', 'role', 'patient_id', 'is_active', 'avatar_url'],
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
            ->assertJsonPath('data.user.role', 'dokter');

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

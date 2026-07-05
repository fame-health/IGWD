<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeviceTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_store_device_token(): void
    {
        $user = User::factory()->create([
            'role' => 'pasien',
            'is_active' => true,
        ]);
        $token = 'fcm-token-android-123';

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/device-token', [
            'token' => $token,
            'platform' => 'android',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Device token berhasil disimpan.')
            ->assertJsonPath('data.platform', 'android');

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $user->id,
            'platform' => 'android',
            'token_hash' => hash('sha256', $token),
        ]);
    }

    public function test_device_token_endpoint_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/device-token', [
            'token' => 'fcm-token-android-123',
            'platform' => 'android',
        ]);

        $response->assertUnauthorized();
    }
}

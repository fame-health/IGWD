<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class FirebaseCloudMessagingService
{
    private bool $credentialsLoaded = false;

    private ?array $credentials = null;

    public function sendToUserIds(iterable $userIds, string $title, string $body, array $data = [], ?string $channelId = null): void
    {
        $ids = collect($userIds)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return;
        }

        DeviceToken::query()
            ->whereIn('user_id', $ids)
            ->chunkById(100, function ($deviceTokens) use ($title, $body, $data, $channelId): void {
                $this->sendToDeviceTokens($deviceTokens, $title, $body, $data, $channelId);
            });
    }

    public function sendToDeviceTokens(iterable $deviceTokens, string $title, string $body, array $data = [], ?string $channelId = null): void
    {
        if (! $this->isConfigured()) {
            Log::info('FCM skipped because Firebase credentials are not configured.');

            return;
        }

        $accessToken = $this->accessToken();
        $projectId = $this->projectId();

        if (! $accessToken || ! $projectId) {
            return;
        }

        foreach ($deviceTokens as $deviceToken) {
            $this->sendToDeviceToken($deviceToken, $accessToken, $projectId, $title, $body, $data, $channelId);
        }
    }

    private function sendToDeviceToken(DeviceToken $deviceToken, string $accessToken, string $projectId, string $title, string $body, array $data, ?string $channelId): void
    {
        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->timeout(10)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => [
                        'token' => $deviceToken->token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'data' => $this->stringData($data),
                        'android' => [
                            'priority' => 'high',
                            'notification' => [
                                'channel_id' => $channelId ?: config('services.firebase.android_channel_id', 'idwg_notifications'),
                                'sound' => 'default',
                            ],
                        ],
                    ],
                ]);

            if ($response->successful()) {
                $deviceToken->forceFill([
                    'last_sent_at' => now(),
                    'failed_at' => null,
                    'failure_reason' => null,
                ])->save();

                return;
            }

            $reason = $this->failureReason($response->json(), $response->body());

            $deviceToken->forceFill([
                'failed_at' => now(),
                'failure_reason' => $reason,
            ])->save();

            Log::warning('FCM send failed.', [
                'device_token_id' => $deviceToken->id,
                'status' => $response->status(),
                'reason' => $reason,
            ]);
        } catch (Throwable $exception) {
            $deviceToken->forceFill([
                'failed_at' => now(),
                'failure_reason' => Str::limit($exception->getMessage(), 1000),
            ])->save();

            Log::warning('FCM send exception.', [
                'device_token_id' => $deviceToken->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function isConfigured(): bool
    {
        return filled($this->projectId()) && filled($this->credentials());
    }

    private function accessToken(): ?string
    {
        $credentials = $this->credentials();

        if (! $credentials) {
            return null;
        }

        $cacheKey = 'firebase_access_token_'.sha1((string) ($credentials['client_email'] ?? 'default'));

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($credentials): ?string {
            $jwt = $this->signedJwt($credentials);

            if (! $jwt) {
                return null;
            }

            $tokenUri = $credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token';
            $response = Http::asForm()
                ->timeout(10)
                ->post($tokenUri, [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);

            if (! $response->successful()) {
                Log::warning('Firebase access token request failed.', [
                    'status' => $response->status(),
                    'body' => Str::limit($response->body(), 1000),
                ]);

                return null;
            }

            return $response->json('access_token');
        });
    }

    private function signedJwt(array $credentials): ?string
    {
        $clientEmail = $credentials['client_email'] ?? null;
        $privateKey = $credentials['private_key'] ?? null;
        $tokenUri = $credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token';

        if (! $clientEmail || ! $privateKey) {
            Log::warning('Firebase credentials file is missing client_email or private_key.');

            return null;
        }

        $now = time();
        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ], JSON_THROW_ON_ERROR));
        $payload = $this->base64UrlEncode(json_encode([
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => $tokenUri,
            'iat' => $now,
            'exp' => $now + 3600,
        ], JSON_THROW_ON_ERROR));
        $unsignedToken = "{$header}.{$payload}";

        if (! openssl_sign($unsignedToken, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            Log::warning('Firebase JWT signing failed.');

            return null;
        }

        return $unsignedToken.'.'.$this->base64UrlEncode($signature);
    }

    private function credentials(): ?array
    {
        if ($this->credentialsLoaded) {
            return $this->credentials;
        }

        $this->credentialsLoaded = true;
        $path = $this->credentialsPath();

        if (! $path || ! is_file($path)) {
            return null;
        }

        $credentials = json_decode((string) file_get_contents($path), true);

        if (! is_array($credentials)) {
            Log::warning('Firebase credentials file is not valid JSON.', ['path' => $path]);

            return null;
        }

        return $this->credentials = $credentials;
    }

    private function credentialsPath(): ?string
    {
        $path = config('services.firebase.credentials');

        if (! $path) {
            return null;
        }

        if (! Str::startsWith($path, ['/', '\\']) && ! preg_match('/^[A-Za-z]:[\/\\\\]/', $path)) {
            return base_path($path);
        }

        return $path;
    }

    private function projectId(): ?string
    {
        return config('services.firebase.project_id') ?: ($this->credentials()['project_id'] ?? null);
    }

    private function stringData(array $data): array
    {
        return collect($data)
            ->mapWithKeys(function (mixed $value, string|int $key): array {
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                } elseif ($value === null) {
                    $value = '';
                } elseif (! is_scalar($value)) {
                    $value = json_encode($value);
                }

                return [(string) $key => (string) $value];
            })
            ->all();
    }

    private function failureReason(?array $json, string $body): string
    {
        return Str::limit((string) data_get($json, 'error.message', $body), 1000);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}

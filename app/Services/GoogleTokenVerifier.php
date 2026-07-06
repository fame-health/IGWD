<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleTokenVerifier
{
    /**
     * @return array{google_id: string, email: string, name: ?string, avatar_url: ?string}|null
     */
    public function verify(string $idToken): ?array
    {
        $allowedClientIds = $this->allowedClientIds();

        if ($allowedClientIds === []) {
            Log::warning('Google login rejected because GOOGLE_CLIENT_IDS is not configured.');

            return null;
        }

        $response = Http::timeout(5)
            ->acceptJson()
            ->get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $idToken,
            ]);

        if (! $response->ok()) {
            return null;
        }

        $payload = $response->json();

        $audience = (string) ($payload['aud'] ?? '');
        $issuer = (string) ($payload['iss'] ?? '');
        $googleId = (string) ($payload['sub'] ?? '');
        $email = (string) ($payload['email'] ?? '');
        $emailVerified = filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOL);
        $expiresAt = (int) ($payload['exp'] ?? 0);

        if (
            ! in_array($audience, $allowedClientIds, true)
            || ! in_array($issuer, ['accounts.google.com', 'https://accounts.google.com'], true)
            || blank($googleId)
            || ! filter_var($email, FILTER_VALIDATE_EMAIL)
            || ! $emailVerified
            || ($expiresAt > 0 && $expiresAt < now()->timestamp)
        ) {
            return null;
        }

        return [
            'google_id' => $googleId,
            'email' => $email,
            'name' => filled($payload['name'] ?? null) ? (string) $payload['name'] : null,
            'avatar_url' => filled($payload['picture'] ?? null) ? (string) $payload['picture'] : null,
        ];
    }

    /**
     * @return list<string>
     */
    private function allowedClientIds(): array
    {
        $clientIds = config('services.google.client_ids', []);

        if (is_string($clientIds)) {
            $clientIds = explode(',', $clientIds);
        }

        return array_values(array_filter(array_map(
            fn (mixed $clientId): string => trim((string) $clientId),
            $clientIds,
        )));
    }
}

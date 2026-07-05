<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\DeviceTokenRequest;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;

class DeviceTokenController extends BaseApiController
{
    public function store(DeviceTokenRequest $request): JsonResponse
    {
        $data = $request->validated();
        $tokenHash = hash('sha256', $data['token']);

        $deviceToken = DeviceToken::query()->updateOrCreate(
            ['token_hash' => $tokenHash],
            [
                'user_id' => $request->user()->id,
                'platform' => $data['platform'],
                'token' => $data['token'],
                'last_used_at' => now(),
                'failed_at' => null,
                'failure_reason' => null,
            ],
        );

        return $this->success([
            'id' => $deviceToken->id,
            'platform' => $deviceToken->platform,
            'last_used_at' => $deviceToken->last_used_at?->toISOString(),
        ], 'Device token berhasil disimpan.');
    }
}

<?php

namespace App\Http\Requests\Api;

class DeviceTokenRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:4096'],
            'platform' => ['required', 'string', 'in:android,ios,web'],
        ];
    }
}

<?php

namespace App\Http\Requests\Api;

class GoogleLoginRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'id_token' => ['required', 'string'],
        ];
    }
}

<?php

namespace App\Http\Requests\Api;

class RiskAlertFollowUpRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'follow_up_note' => ['required', 'string'],
        ];
    }
}

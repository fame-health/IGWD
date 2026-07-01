<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\ChangePasswordRequest;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends BaseApiController
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password) || ! $user->is_active) {
            return $this->error('Email atau password tidak valid.', [], 401);
        }

        $token = $user->createToken('android-'.$user->role)->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => UserResource::make($user),
            'role' => $user->role,
        ], 'Login berhasil.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->success(null, 'Logout berhasil.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success(UserResource::make($request->user()));
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return $this->error('Password lama tidak sesuai.', ['current_password' => ['Password lama tidak sesuai.']], 422);
        }

        $user->update(['password' => $request->new_password]);

        return $this->success(null, 'Password berhasil diubah.');
    }
}

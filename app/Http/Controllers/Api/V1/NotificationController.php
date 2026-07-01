<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        return $this->success(NotificationResource::collection(
            $request->user()->notifications()->latest()->paginate($request->integer('per_page', 15))
        ));
    }

    public function read(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        return $this->success(NotificationResource::make($notification), 'Notification ditandai dibaca.');
    }
}

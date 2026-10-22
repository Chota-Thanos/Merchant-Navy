<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

/** The in-app bell. Notification classes are in app/Notifications. */
class NotificationController extends Controller
{
    /** GET /api/v1/notifications — latest 50. Matches `AppNotification` in the student app. */
    public function index(Request $request): JsonResponse
    {
        $items = $request->user()->notifications()->latest()->limit(50)->get()
            ->map(fn (DatabaseNotification $n) => [
                'id' => $n->id,
                'type' => $n->data['type'] ?? 'announcement',
                'title' => $n->data['title'] ?? '',
                'body' => $n->data['body'] ?? '',
                'href' => $n->data['href'] ?? null,
                'createdAt' => $n->created_at->toIso8601String(),
                'read' => $n->read_at !== null,
            ])->values()->all();

        return $this->data($items);
    }

    /** POST /api/v1/notifications/read */
    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return $this->data(null);
    }
}

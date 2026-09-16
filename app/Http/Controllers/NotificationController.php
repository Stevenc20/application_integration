<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()
            ->latest()
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function unread()
    {
        $notifications = auth()->user()->unreadNotifications()
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($n) {
                return [
                    'id' => $n->id,
                    'message' => $n->data['message'] ?? '',
                    'hambatan_id' => $n->data['hambatan_id'] ?? null,
                    'created_at' => $n->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'count' => auth()->user()->unreadNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }

    public function markRead($id)
    {
        $notification = auth()->user()->notifications()->where('id', $id)->first();

        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json(['ok' => true]);
    }
}

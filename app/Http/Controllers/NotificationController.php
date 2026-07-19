<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $actor = auth('admin')->user() ?? auth()->user();

        return view('notifications.index', [
            'notifications' => $actor->notifications()->latest()->paginate(20),
            'unreadCount' => $actor->unreadNotifications()->count(),
        ]);
    }

    public function read(Request $request, string $notification)
    {
        $actor = auth('admin')->user() ?? auth()->user();
        $item = $actor->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();

        $url = $item->data['url'] ?? route('notifications.index');
        if (! str_starts_with($url, url('/')) && ! str_starts_with($url, '/')) {
            $url = route('notifications.index');
        }

        return redirect($url);
    }

    public function readAll()
    {
        $actor = auth('admin')->user() ?? auth()->user();
        $actor->unreadNotifications->markAsRead();

        return back()->with('success', 'Notificaciones marcadas como leidas.');
    }
}

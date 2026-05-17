<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $user->unreadNotifications->markAsRead();

        $notifications = $user->notifications()->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function destroy(Request $request, string $id)
    {
        $user = $request->user();

        $notification = DatabaseNotification::findOrFail($id);

        abort_unless(
            $notification->notifiable_type === $user->getMorphClass()
                && (string) $notification->notifiable_id === (string) $user->getKey(),
            403
        );

        $notification->delete();

        return back()->with('success', 'Notifica eliminata.');
    }

    public function destroyAll(Request $request)
    {
        $request->user()->notifications()->delete();

        return back()->with('success', 'Tutte le notifiche sono state eliminate.');
    }
}

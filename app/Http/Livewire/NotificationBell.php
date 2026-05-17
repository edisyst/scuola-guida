<?php

namespace App\Http\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class NotificationBell extends Component
{
    public int $unreadCount = 0;

    public function mount(): void
    {
        $this->loadNotifications();
    }

    public function loadNotifications(): void
    {
        $user = auth()->user();

        $this->unreadCount = $user
            ? $user->unreadNotifications()->count()
            : 0;
    }

    public function markAsRead(string $notificationId)
    {
        $user = auth()->user();

        if (!$user) {
            return null;
        }

        $notification = $user->notifications()->whereKey($notificationId)->first();

        if (!$notification) {
            return null;
        }

        $notification->markAsRead();

        $this->loadNotifications();

        $url = $notification->data['url'] ?? null;

        if ($url) {
            return $this->redirect($url, navigate: false);
        }

        return null;
    }

    public function markAllAsRead(): void
    {
        $user = auth()->user();

        if ($user) {
            $user->unreadNotifications->markAsRead();
        }

        $this->loadNotifications();
    }

    public function render(): View
    {
        $user = auth()->user();

        $notifications = $user
            ? $user->notifications()->limit(10)->get()
            : collect();

        return view('livewire.notification-bell', [
            'notifications' => $notifications,
        ]);
    }
}

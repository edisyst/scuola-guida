<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\RuoloAggiornatoNotification;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(private NotificationService $notifications) {}

    public function create(array $data): User
    {
        $data['password']    = Hash::make($data['password']);
        $data['permissions'] = $data['permissions'] ?? [];

        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']); // campo vuoto = non toccare l'hash esistente
        }

        // Checkbox non spuntate → null nell'array validated; forziamo array vuoto.
        $data['permissions'] = $data['permissions'] ?? [];

        $oldRole = $user->role;

        $user->update($data);

        $newRole = $user->fresh()->role;

        if ($oldRole !== $newRole) {
            $this->notifications->send(
                $user,
                new RuoloAggiornatoNotification($oldRole, $newRole)
            );
        }

        return $user;
    }
}

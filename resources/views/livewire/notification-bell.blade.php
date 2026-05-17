@auth
    <li class="nav-item dropdown" wire:poll.30s="loadNotifications">
        <a class="nav-link" data-toggle="dropdown" href="#" aria-label="Notifiche">
            <i class="far fa-bell"></i>
            @if ($unreadCount > 0)
                <span class="badge badge-warning navbar-badge">
                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                </span>
            @endif
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
            <span class="dropdown-item dropdown-header">
                @if ($unreadCount > 0)
                    {{ $unreadCount }} {{ $unreadCount === 1 ? 'notifica non letta' : 'notifiche non lette' }}
                @else
                    Nessuna notifica non letta
                @endif
            </span>
            <div class="dropdown-divider"></div>

            @if ($unreadCount > 0)
                <a href="#"
                   wire:click.prevent="markAllAsRead"
                   wire:loading.attr="disabled"
                   class="dropdown-item text-sm text-center text-muted">
                    <span wire:loading.remove wire:target="markAllAsRead">
                        <i class="fas fa-check-double mr-1"></i> Segna tutte come lette
                    </span>
                    <span wire:loading wire:target="markAllAsRead">
                        <i class="fas fa-spinner fa-spin mr-1"></i> Aggiornamento...
                    </span>
                </a>
                <div class="dropdown-divider"></div>
            @endif

            @forelse ($notifications as $notification)
                <a href="{{ $notification->data['url'] ?? '#' }}"
                   wire:click.prevent="markAsRead('{{ $notification->id }}')"
                   class="dropdown-item {{ $notification->read_at ? '' : 'font-weight-bold' }}">
                    <i class="{{ $notification->data['icon'] ?? 'fas fa-info-circle' }} mr-2 text-{{ $notification->data['color'] ?? 'info' }}"></i>
                    {{ $notification->data['title'] ?? 'Notifica' }}
                    <span class="float-right text-muted text-sm">
                        {{ $notification->created_at->diffForHumans() }}
                    </span>
                    @if (!empty($notification->data['body']))
                        <div class="text-sm text-muted text-truncate" style="max-width: 100%;">
                            {{ $notification->data['body'] }}
                        </div>
                    @endif
                </a>
                <div class="dropdown-divider"></div>
            @empty
                <span class="dropdown-item text-muted text-center">Nessuna notifica</span>
                <div class="dropdown-divider"></div>
            @endforelse

            <a href="{{ route('notifications.index') }}" class="dropdown-item dropdown-footer">
                Tutte le notifiche
            </a>
        </div>
    </li>
@endauth

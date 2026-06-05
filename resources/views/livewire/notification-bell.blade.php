@auth
    <li class="nav-item dropdown" wire:poll.30s="loadNotifications">
        <a class="nav-link" data-toggle="dropdown" href="#" aria-label="{{ __('nav.notifications_label') }}">
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
                    {{ $unreadCount === 1 ? __('nav.notifications_header_single', ['count' => $unreadCount]) : __('nav.notifications_header_plural', ['count' => $unreadCount]) }}
                @else
                    {{ __('nav.notifications_none_unread') }}
                @endif
            </span>
            <div class="dropdown-divider"></div>

            @if ($unreadCount > 0)
                <a href="#"
                   wire:click.prevent="markAllAsRead"
                   wire:loading.attr="disabled"
                   class="dropdown-item text-sm text-center text-muted">
                    <span wire:loading.remove wire:target="markAllAsRead">
                        <i class="fas fa-check-double mr-1"></i> {{ __('nav.notifications_mark_all') }}
                    </span>
                    <span wire:loading wire:target="markAllAsRead">
                        <i class="fas fa-spinner fa-spin mr-1"></i> {{ __('nav.notifications_marking') }}
                    </span>
                </a>
                <div class="dropdown-divider"></div>
            @endif

            @forelse ($notifications as $notification)
                <a href="{{ $notification->data['url'] ?? '#' }}"
                   wire:click.prevent="markAsRead('{{ $notification->id }}')"
                   class="dropdown-item {{ $notification->read_at ? '' : 'font-weight-bold' }}">
                    <i class="{{ $notification->data['icon'] ?? 'fas fa-info-circle' }} mr-2 text-{{ $notification->data['color'] ?? 'info' }}"></i>
                    {{ $notification->data['title'] ?? __('nav.notifications_default_title') }}
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
                <span class="dropdown-item text-muted text-center">{{ __('nav.notifications_empty') }}</span>
                <div class="dropdown-divider"></div>
            @endforelse

            <a href="{{ route('notifications.index') }}" class="dropdown-item dropdown-footer">
                {{ __('nav.notifications_all') }}
            </a>
        </div>
    </li>
@endauth

@extends('layouts.admin')

@section('title', 'Notifiche')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">Area personale</p>
            <h1 class="sg-header-title"><i class="far fa-bell mr-2"></i> Notifiche</h1>
        </div>
        @if ($notifications->total() > 0)
            <form method="POST"
                  action="{{ route('notifications.destroyAll') }}"
                  onsubmit="return confirm('Eliminare definitivamente tutte le notifiche?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="sg-btn sg-btn-outline sg-btn-sm">
                    <i class="fas fa-trash"></i> Elimina tutte
                </button>
            </form>
        @endif
    </div>

    <div class="sg-card">
        @if ($notifications->isEmpty())
            <div class="sg-table-empty">Non hai ancora nessuna notifica.</div>
        @else
            <div class="table-responsive">
                <table class="sg-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;"></th>
                            <th>Titolo</th>
                            <th>Messaggio</th>
                            <th style="width: 160px;">Data</th>
                            <th class="text-right" style="width: 100px;">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($notifications as $notification)
                            @php
                                $data  = $notification->data ?? [];
                                $icon  = $data['icon']  ?? 'fas fa-info-circle';
                                $color = $data['color'] ?? 'info';
                                $title = $data['title'] ?? 'Notifica';
                                $body  = $data['body']  ?? '';
                                $url   = $data['url']   ?? null;
                                $unread = is_null($notification->read_at);
                            @endphp
                            <tr class="{{ $unread ? 'font-weight-bold' : '' }}">
                                <td class="text-center">
                                    <i class="{{ $icon }} text-{{ $color }}"></i>
                                </td>
                                <td>
                                    @if ($url)
                                        <a href="{{ $url }}">{{ $title }}</a>
                                    @else
                                        {{ $title }}
                                    @endif
                                </td>
                                <td class="sg-text-muted">{{ $body }}</td>
                                <td class="sg-text-muted">
                                    {{ $notification->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="text-right">
                                    <form method="POST"
                                          action="{{ route('notifications.destroy', $notification->id) }}"
                                          class="d-inline"
                                          onsubmit="return confirm('Eliminare questa notifica?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="sg-btn sg-btn-outline sg-btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="sg-card-section">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

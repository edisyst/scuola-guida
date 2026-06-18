@auth
@if(feature('study_content_enabled'))
<div>
    @if($contents->isEmpty())
        <div class="text-center py-4">
            <i class="fas fa-book-open fa-3x text-muted mb-3 d-block"></i>
            <p class="text-muted">{{ __('study_content.no_content') }}</p>
        </div>
    @else
        @foreach($contents as $content)
            <div class="sg-card mb-3">
                <div class="sg-card-header d-flex justify-content-between align-items-center">
                    <h3 class="sg-card-title mb-0">{{ $content->title }}</h3>
                    @if($readMap[$content->id] ?? false)
                        <span class="badge badge-success">
                            <i class="fas fa-check mr-1"></i>{{ __('study_content.read') }}
                        </span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="sg-study-content-body">
                        {!! $content->body !!}
                    </div>
                    @if(!($readMap[$content->id] ?? false))
                        <div class="mt-3">
                            <button wire:click="markRead({{ $content->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="markRead({{ $content->id }})"
                                    class="sg-btn sg-btn-success sg-btn-sm">
                                <span wire:loading.remove wire:target="markRead({{ $content->id }})">
                                    <i class="fas fa-check mr-1"></i>{{ __('study_content.mark_as_read') }}
                                </span>
                                <span wire:loading wire:target="markRead({{ $content->id }})">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </span>
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    @endif
</div>
@endif
@endauth
